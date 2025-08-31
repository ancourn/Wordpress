<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Background processing functionality for HTML to WordPress Importer
 * Handles large imports using Action Scheduler or WP Cron fallback
 */

class HTML_WP_Background_Processor {
    
    /**
     * Check if Action Scheduler is available
     * 
     * @return bool True if Action Scheduler is available
     */
    public static function is_action_scheduler_available() {
        return class_exists( 'ActionScheduler' ) || class_exists( 'ActionScheduler_Versions' );
    }
    
    /**
     * Queue an import job for background processing
     * 
     * @param array $job_data Job data array
     * @param string $priority Priority (high, medium, low)
     * @return bool|WP_Error Job ID or error
     */
    public static function queue_import_job( $job_data, $priority = 'medium' ) {
        // Validate required job data
        $required_fields = ['zip_path', 'user_id', 'job_id'];
        foreach ( $required_fields as $field ) {
            if ( empty( $job_data[$field] ) ) {
                return new WP_Error( 'missing_job_data', "Missing required job data: {$field}" );
            }
        }
        
        // Set default job data
        $default_data = [
            'status' => 'queued',
            'created_at' => current_time( 'mysql' ),
            'priority' => $priority,
            'progress' => 0,
            'total_files' => 0,
            'processed_files' => 0,
            'errors' => [],
            'results' => [],
        ];
        
        $job_data = wp_parse_args( $job_data, $default_data );
        
        // Store job data
        $job_id = self::store_job_data( $job_data );
        
        if ( is_wp_error( $job_id ) ) {
            return $job_id;
        }
        
        // Queue the job using Action Scheduler if available
        if ( self::is_action_scheduler_available() ) {
            return self::queue_with_action_scheduler( $job_id, $priority );
        }
        
        // Fallback to WP Cron
        return self::queue_with_wp_cron( $job_id, $priority );
    }
    
    /**
     * Store job data in database
     * 
     * @param array $job_data Job data
     * @return string|WP_Error Job ID or error
     */
    private static function store_job_data( $job_data ) {
        $job_id = sanitize_key( $job_data['job_id'] );
        
        // Store job data in options table
        $option_key = 'htwpi_job_' . $job_id;
        $stored = update_option( $option_key, $job_data, false );
        
        if ( ! $stored ) {
            return new WP_Error( 'job_storage_failed', 'Failed to store job data' );
        }
        
        // Add job to jobs list
        $jobs = get_option( 'htwpi_jobs', [] );
        $jobs[$job_id] = [
            'job_id' => $job_id,
            'status' => $job_data['status'],
            'created_at' => $job_data['created_at'],
            'user_id' => $job_data['user_id'],
            'priority' => $job_data['priority'],
        ];
        
        update_option( 'htwpi_jobs', $jobs, false );
        
        return $job_id;
    }
    
    /**
     * Queue job with Action Scheduler
     * 
     * @param string $job_id Job ID
     * @param string $priority Priority
     * @return bool True if queued successfully
     */
    private static function queue_with_action_scheduler( $job_id, $priority ) {
        $hook = 'htwpi_process_import_job';
        $args = ['job_id' => $job_id];
        
        // Set delay based on priority
        $delay = 0;
        switch ( $priority ) {
            case 'high':
                $delay = 0;
                break;
            case 'medium':
                $delay = 30; // 30 seconds
                break;
            case 'low':
                $delay = 300; // 5 minutes
                break;
        }
        
        try {
            if ( class_exists( 'ActionScheduler' ) ) {
                ActionScheduler::enqueue_async_action( $hook, $args, 'htwpi-import' );
                return true;
            } elseif ( function_exists( 'as_enqueue_async_action' ) ) {
                as_enqueue_async_action( $hook, $args, 'htwpi-import' );
                return true;
            }
        } catch ( Exception $e ) {
            // Fall back to WP Cron
            return self::queue_with_wp_cron( $job_id, $priority );
        }
        
        return false;
    }
    
    /**
     * Queue job with WP Cron fallback
     * 
     * @param string $job_id Job ID
     * @param string $priority Priority
     * @return bool True if queued successfully
     */
    private static function queue_with_wp_cron( $job_id, $priority ) {
        $hook = 'htwpi_process_import_job_cron';
        $args = ['job_id' => $job_id];
        
        // Set delay based on priority
        $timestamp = time();
        switch ( $priority ) {
            case 'high':
                $timestamp += 10; // 10 seconds
                break;
            case 'medium':
                $timestamp += 60; // 1 minute
                break;
            case 'low':
                $timestamp += 600; // 10 minutes
                break;
        }
        
        return wp_schedule_single_event( $timestamp, $hook, $args );
    }
    
    /**
     * Process import job
     * 
     * @param string $job_id Job ID
     * @return bool|WP_Error True on success, error on failure
     */
    public static function process_job( $job_id ) {
        $job_data = self::get_job_data( $job_id );
        
        if ( is_wp_error( $job_data ) ) {
            return $job_data;
        }
        
        // Update job status
        self::update_job_status( $job_id, 'processing' );
        
        try {
            // Process the import
            $results = self::execute_import( $job_data );
            
            // Update job with results
            $job_data['status'] = 'completed';
            $job_data['completed_at'] = current_time( 'mysql' );
            $job_data['results'] = $results;
            $job_data['progress'] = 100;
            
            self::update_job_data( $job_id, $job_data );
            
            // Send notification to user
            self::send_completion_notification( $job_id, $job_data );
            
            return true;
            
        } catch ( Exception $e ) {
            // Update job with error
            $job_data['status'] = 'failed';
            $job_data['completed_at'] = current_time( 'mysql' );
            $job_data['errors'][] = $e->getMessage();
            
            self::update_job_data( $job_id, $job_data );
            
            // Send error notification
            self::send_error_notification( $job_id, $job_data );
            
            return new WP_Error( 'job_failed', $e->getMessage() );
        }
    }
    
    /**
     * Execute the actual import
     * 
     * @param array $job_data Job data
     * @return array Import results
     */
    private static function execute_import( $job_data ) {
        $zip_path = $job_data['zip_path'];
        $use_elementor = isset( $job_data['use_elementor'] ) ? $job_data['use_elementor'] : false;
        $parent_page = isset( $job_data['parent_page'] ) ? $job_data['parent_page'] : 0;
        $create_menu = isset( $job_data['create_menu'] ) ? $job_data['create_menu'] : true;
        
        // Validate ZIP file exists
        if ( ! file_exists( $zip_path ) ) {
            throw new Exception( 'ZIP file not found: ' . $zip_path );
        }
        
        // Process the ZIP import
        require_once plugin_dir_path( __FILE__ ) . 'zip-import.php';
        $results = HTML_WP_Zip_Import::import_zip( $zip_path, $use_elementor, $parent_page, $create_menu );
        
        if ( is_wp_error( $results ) ) {
            throw new Exception( $results->get_error_message() );
        }
        
        // Clean up temporary ZIP file
        if ( file_exists( $zip_path ) ) {
            @unlink( $zip_path );
        }
        
        return $results;
    }
    
    /**
     * Get job data
     * 
     * @param string $job_id Job ID
     * @return array|WP_Error Job data or error
     */
    public static function get_job_data( $job_id ) {
        $option_key = 'htwpi_job_' . $job_id;
        $job_data = get_option( $option_key );
        
        if ( ! $job_data ) {
            return new WP_Error( 'job_not_found', 'Job not found: ' . $job_id );
        }
        
        return $job_data;
    }
    
    /**
     * Update job data
     * 
     * @param string $job_id Job ID
     * @param array $job_data Job data
     * @return bool True if updated successfully
     */
    public static function update_job_data( $job_id, $job_data ) {
        $option_key = 'htwpi_job_' . $job_id;
        return update_option( $option_key, $job_data, false );
    }
    
    /**
     * Update job status
     * 
     * @param string $job_id Job ID
     * @param string $status Job status
     * @return bool True if updated successfully
     */
    public static function update_job_status( $job_id, $status ) {
        $job_data = self::get_job_data( $job_id );
        
        if ( is_wp_error( $job_data ) ) {
            return false;
        }
        
        $job_data['status'] = $status;
        
        if ( $status === 'processing' ) {
            $job_data['started_at'] = current_time( 'mysql' );
        }
        
        return self::update_job_data( $job_id, $job_data );
    }
    
    /**
     * Get all jobs
     * 
     * @param array $args Query arguments
     * @return array Jobs data
     */
    public static function get_jobs( $args = [] ) {
        $defaults = [
            'status' => 'all',
            'user_id' => 0,
            'limit' => 20,
            'offset' => 0,
        ];
        
        $args = wp_parse_args( $args, $defaults );
        
        $jobs = get_option( 'htwpi_jobs', [] );
        $filtered_jobs = [];
        
        foreach ( $jobs as $job_id => $job ) {
            // Filter by status
            if ( $args['status'] !== 'all' && $job['status'] !== $args['status'] ) {
                continue;
            }
            
            // Filter by user ID
            if ( $args['user_id'] > 0 && $job['user_id'] !== $args['user_id'] ) {
                continue;
            }
            
            // Get full job data
            $full_job_data = self::get_job_data( $job_id );
            if ( ! is_wp_error( $full_job_data ) ) {
                $filtered_jobs[] = $full_job_data;
            }
        }
        
        // Sort by created date (newest first)
        usort( $filtered_jobs, function( $a, $b ) {
            return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
        } );
        
        // Apply limit and offset
        if ( $args['limit'] > 0 ) {
            $filtered_jobs = array_slice( $filtered_jobs, $args['offset'], $args['limit'] );
        }
        
        return $filtered_jobs;
    }
    
    /**
     * Delete job
     * 
     * @param string $job_id Job ID
     * @return bool True if deleted successfully
     */
    public static function delete_job( $job_id ) {
        // Remove job data
        $option_key = 'htwpi_job_' . $job_id;
        delete_option( $option_key );
        
        // Remove from jobs list
        $jobs = get_option( 'htwpi_jobs', [] );
        if ( isset( $jobs[$job_id] ) ) {
            unset( $jobs[$job_id] );
            update_option( 'htwpi_jobs', $jobs, false );
        }
        
        return true;
    }
    
    /**
     * Clean up old jobs
     * 
     * @param int $days_old Delete jobs older than this many days
     * @return int Number of jobs cleaned up
     */
    public static function cleanup_old_jobs( $days_old = 7 ) {
        $jobs = get_option( 'htwpi_jobs', [] );
        $cleaned_count = 0;
        $cutoff_time = strtotime( "-{$days_old} days" );
        
        foreach ( $jobs as $job_id => $job ) {
            $job_data = self::get_job_data( $job_id );
            
            if ( ! is_wp_error( $job_data ) ) {
                $created_time = strtotime( $job_data['created_at'] );
                
                if ( $created_time < $cutoff_time ) {
                    self::delete_job( $job_id );
                    $cleaned_count++;
                }
            }
        }
        
        return $cleaned_count;
    }
    
    /**
     * Send completion notification to user
     * 
     * @param string $job_id Job ID
     * @param array $job_data Job data
     */
    private static function send_completion_notification( $job_id, $job_data ) {
        $user_id = $job_data['user_id'];
        $user = get_user_by( 'ID', $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        $subject = 'HTML Import Completed Successfully';
        $message = "Your HTML import job has been completed successfully.\n\n";
        $message .= "Job ID: {$job_id}\n";
        $message .= "Files processed: " . count( $job_data['results'] ) . "\n";
        $message .= "Completed at: " . $job_data['completed_at'] . "\n\n";
        $message .= "You can view the imported pages in your WordPress admin area.\n";
        
        wp_mail( $user->user_email, $subject, $message );
    }
    
    /**
     * Send error notification to user
     * 
     * @param string $job_id Job ID
     * @param array $job_data Job data
     */
    private static function send_error_notification( $job_id, $job_data ) {
        $user_id = $job_data['user_id'];
        $user = get_user_by( 'ID', $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        $subject = 'HTML Import Failed';
        $message = "Your HTML import job has encountered an error.\n\n";
        $message .= "Job ID: {$job_id}\n";
        $message .= "Error: " . implode( "\n", $job_data['errors'] ) . "\n";
        $message .= "Failed at: " . $job_data['completed_at'] . "\n\n";
        $message .= "Please check the error and try again.\n";
        
        wp_mail( $user->user_email, $subject, $message );
    }
    
    /**
     * Register action hooks
     */
    public static function register_hooks() {
        // Action Scheduler hook
        add_action( 'htwpi_process_import_job', [ __CLASS__, 'process_job' ] );
        
        // WP Cron fallback hook
        add_action( 'htwpi_process_import_job_cron', [ __CLASS__, 'process_job' ] );
        
        // Schedule cleanup
        if ( ! wp_next_scheduled( 'htwpi_cleanup_jobs' ) ) {
            wp_schedule_event( time(), 'daily', 'htwpi_cleanup_jobs' );
        }
        
        add_action( 'htwpi_cleanup_jobs', [ __CLASS__, 'cleanup_old_jobs' ] );
    }
    
    /**
     * Get job status text
     * 
     * @param string $status Job status
     * @return string Human-readable status
     */
    public static function get_status_text( $status ) {
        $status_texts = [
            'queued' => 'Queued',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];
        
        return isset( $status_texts[$status] ) ? $status_texts[$status] : $status;
    }
    
    /**
     * Get job progress percentage
     * 
     * @param string $job_id Job ID
     * @return int Progress percentage
     */
    public static function get_job_progress( $job_id ) {
        $job_data = self::get_job_data( $job_id );
        
        if ( is_wp_error( $job_data ) ) {
            return 0;
        }
        
        return isset( $job_data['progress'] ) ? intval( $job_data['progress'] ) : 0;
    }
}