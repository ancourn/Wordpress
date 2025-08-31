<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Queue {
    
    public static function init() {
        // Register custom action if Action Scheduler is available
        if (class_exists('ActionScheduler')) {
            add_action('htwpi_import_job', array(__CLASS__, 'process_import_job'), 10, 2);
        }
    }
    
    public static function enqueue_import($zip_path, $user_id = null) {
        if (class_exists('ActionScheduler')) {
            // Use Action Scheduler for background processing
            $args = array(
                'zip_path' => $zip_path,
                'user_id' => $user_id ?: get_current_user_id()
            );
            
            return ActionScheduler::enqueue_async_action('htwpi_import_job', $args);
        } else {
            // Fallback to direct processing
            return self::process_import_directly($zip_path, $user_id);
        }
    }
    
    public static function process_import_job($zip_path, $user_id) {
        try {
            // Set user context
            if ($user_id) {
                wp_set_current_user($user_id);
            }
            
            // Process the import
            $result = self::process_import_directly($zip_path, $user_id);
            
            // Log the result
            if (is_wp_error($result)) {
                error_log('HTML Import Job Failed: ' . $result->get_error_message());
            } else {
                error_log('HTML Import Job Completed Successfully');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('HTML Import Job Error: ' . $e->getMessage());
            return new WP_Error('import_error', $e->getMessage());
        }
    }
    
    public static function process_import_directly($zip_path, $user_id = null) {
        // Validate file path
        if (!file_exists($zip_path)) {
            return new WP_Error('file_not_found', 'ZIP file not found');
        }
        
        // Set user context
        if ($user_id) {
            wp_set_current_user($user_id);
        }
        
        // Process the ZIP import
        require_once __DIR__ . '/zip-import.php';
        $result = HTML_WP_Zip_Import::import_zip($zip_path);
        
        // Clean up temporary file
        if (file_exists($zip_path)) {
            unlink($zip_path);
        }
        
        return $result;
    }
    
    public static function get_scheduled_jobs() {
        if (!class_exists('ActionScheduler')) {
            return array();
        }
        
        $args = array(
            'hook' => 'htwpi_import_job',
            'status' => ActionScheduler_Store::STATUS_PENDING,
            'per_page' => 20
        );
        
        return ActionScheduler::store()->query_actions($args);
    }
    
    public static function get_completed_jobs($limit = 10) {
        if (!class_exists('ActionScheduler')) {
            return array();
        }
        
        $args = array(
            'hook' => 'htwpi_import_job',
            'status' => ActionScheduler_Store::STATUS_COMPLETE,
            'per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return ActionScheduler::store()->query_actions($args);
    }
    
    public static function get_failed_jobs($limit = 10) {
        if (!class_exists('ActionScheduler')) {
            return array();
        }
        
        $args = array(
            'hook' => 'htwpi_import_job',
            'status' => ActionScheduler_Store::STATUS_FAILED,
            'per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return ActionScheduler::store()->query_actions($args);
    }
    
    public static function cancel_job($action_id) {
        if (!class_exists('ActionScheduler')) {
            return false;
        }
        
        try {
            ActionScheduler::store()->cancel_action($action_id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function retry_job($action_id) {
        if (!class_exists('ActionScheduler')) {
            return false;
        }
        
        try {
            $action = ActionScheduler::store()->fetch_action($action_id);
            if ($action) {
                $args = $action->get_args();
                return ActionScheduler::enqueue_async_action('htwpi_import_job', $args);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function get_job_status($action_id) {
        if (!class_exists('ActionScheduler')) {
            return 'unknown';
        }
        
        try {
            $status = ActionScheduler::store()->get_status($action_id);
            return $status;
        } catch (Exception $e) {
            return 'unknown';
        }
    }
    
    public static function cleanup_old_jobs($days = 30) {
        if (!class_exists('ActionScheduler')) {
            return false;
        }
        
        try {
            $args = array(
                'hook' => 'htwpi_import_job',
                'status' => ActionScheduler_Store::STATUS_COMPLETE,
                'date' => as_get_datetime_object("-{$days} days")
            );
            
            $actions = ActionScheduler::store()->query_actions($args);
            $deleted = 0;
            
            foreach ($actions as $action_id) {
                ActionScheduler::store()->delete_action($action_id);
                $deleted++;
            }
            
            return $deleted;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function get_job_logs($action_id) {
        if (!class_exists('ActionScheduler')) {
            return array();
        }
        
        try {
            $logs = ActionScheduler_Logger::instance()->get_logs($action_id);
            return $logs;
        } catch (Exception $e) {
            return array();
        }
    }
    
    public static function is_available() {
        return class_exists('ActionScheduler');
    }
    
    public static function get_queue_stats() {
        if (!class_exists('ActionScheduler')) {
            return array(
                'available' => false,
                'pending' => 0,
                'completed' => 0,
                'failed' => 0
            );
        }
        
        $pending = count(self::get_scheduled_jobs());
        $completed = count(self::get_completed_jobs(1000));
        $failed = count(self::get_failed_jobs(1000));
        
        return array(
            'available' => true,
            'pending' => $pending,
            'completed' => $completed,
            'failed' => $failed
        );
    }
}

// Initialize the queue system
add_action('plugins_loaded', array('HTML_WP_Queue', 'init'));