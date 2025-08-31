<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function htwpi_sideload_image_get_attachment_id($image_url, $post_id = 0) {
    if (empty($image_url)) {
        return false;
    }
    
    // Check if the image is already in the media library
    $attachment_id = attachment_url_to_postid($image_url);
    if ($attachment_id) {
        return $attachment_id;
    }
    
    // Download the image
    $upload_dir = wp_upload_dir();
    $image_data = @file_get_contents($image_url);
    
    if (!$image_data) {
        return false;
    }
    
    // Get filename from URL
    $filename = basename($image_url);
    $filename = sanitize_file_name($filename);
    
    // Create unique filename if it already exists
    $filepath = $upload_dir['path'] . '/' . $filename;
    $counter = 1;
    while (file_exists($filepath)) {
        $path_parts = pathinfo($filename);
        $filename = $path_parts['filename'] . '-' . $counter . '.' . $path_parts['extension'];
        $filepath = $upload_dir['path'] . '/' . $filename;
        $counter++;
    }
    
    // Save the image
    file_put_contents($filepath, $image_data);
    
    // Check file type
    $filetype = wp_check_filetype_and_ext($filepath, $filename);
    if (!wp_match_mime_types('image', $filetype['type'])) {
        unlink($filepath);
        return false;
    }
    
    // Prepare attachment data
    $attachment = [
        'post_mime_type' => $filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content' => '',
        'post_status' => 'inherit'
    ];
    
    // Insert attachment
    $attachment_id = wp_insert_attachment($attachment, $filepath, $post_id);
    
    if (is_wp_error($attachment_id)) {
        unlink($filepath);
        return false;
    }
    
    // Generate attachment metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $filepath);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    return $attachment_id;
}

function htwpi_replace_images_in_elementor_data($data, $post_id) {
    if (!is_array($data)) {
        return $data;
    }
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = htwpi_replace_images_in_elementor_data($value, $post_id);
        } elseif (is_string($value)) {
            // Check if this is an image URL
            if (htwpi_is_image_url($value)) {
                $attachment_id = htwpi_sideload_image_get_attachment_id($value, $post_id);
                if ($attachment_id) {
                    $attachment_url = wp_get_attachment_url($attachment_id);
                    if ($attachment_url) {
                        $data[$key] = $attachment_url;
                    }
                }
            }
        }
    }
    
    // Special handling for Elementor image widgets
    if (isset($data['widgetType']) && $data['widgetType'] === 'image') {
        if (isset($data['settings']['image']['url'])) {
            $image_url = $data['settings']['image']['url'];
            $attachment_id = htwpi_sideload_image_get_attachment_id($image_url, $post_id);
            if ($attachment_id) {
                $attachment_url = wp_get_attachment_url($attachment_id);
                if ($attachment_url) {
                    $data['settings']['image']['url'] = $attachment_url;
                    $data['settings']['image']['id'] = $attachment_id;
                }
            }
        }
    }
    
    // Handle background images
    if (isset($data['settings']['background_image']['url'])) {
        $bg_image_url = $data['settings']['background_image']['url'];
        $attachment_id = htwpi_sideload_image_get_attachment_id($bg_image_url, $post_id);
        if ($attachment_id) {
            $attachment_url = wp_get_attachment_url($attachment_id);
            if ($attachment_url) {
                $data['settings']['background_image']['url'] = $attachment_url;
                $data['settings']['background_image']['id'] = $attachment_id;
            }
        }
    }
    
    return $data;
}

function htwpi_is_image_url($url) {
    if (!is_string($url) || empty($url)) {
        return false;
    }
    
    // Skip if it's already a WordPress URL
    if (strpos($url, 'wp-content') !== false) {
        return false;
    }
    
    // Skip if it's a data URL
    if (strpos($url, 'data:image') === 0) {
        return false;
    }
    
    // Check file extension
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    $path_parts = pathinfo($url);
    
    if (!isset($path_parts['extension'])) {
        return false;
    }
    
    $extension = strtolower($path_parts['extension']);
    return in_array($extension, $image_extensions);
}

function htwpi_process_content_images($content, $post_id) {
    // Find all image tags in content
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
    
    if (isset($matches[1])) {
        foreach ($matches[1] as $image_url) {
            $attachment_id = htwpi_sideload_image_get_attachment_id($image_url, $post_id);
            if ($attachment_id) {
                $attachment_url = wp_get_attachment_url($attachment_id);
                if ($attachment_url) {
                    $content = str_replace($image_url, $attachment_url, $content);
                }
            }
        }
    }
    
    return $content;
}

function htwpi_get_image_dimensions($image_url) {
    if (!function_exists('getimagesize')) {
        return false;
    }
    
    // Try to get dimensions from local file
    $upload_dir = wp_upload_dir();
    $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);
    
    if (file_exists($image_path)) {
        $dimensions = @getimagesize($image_path);
        if ($dimensions) {
            return [
                'width' => $dimensions[0],
                'height' => $dimensions[1]
            ];
        }
    }
    
    // Try to get dimensions from remote URL
    $dimensions = @getimagesize($image_url);
    if ($dimensions) {
        return [
            'width' => $dimensions[0],
            'height' => $dimensions[1]
        ];
    }
    
    return false;
}

function htwpi_optimize_image($attachment_id) {
    if (!function_exists('wp_get_image_editor')) {
        return false;
    }
    
    $attachment_path = get_attached_file($attachment_id);
    if (!$attachment_path) {
        return false;
    }
    
    $editor = wp_get_image_editor($attachment_path);
    if (is_wp_error($editor)) {
        return false;
    }
    
    // Get original dimensions
    $original_size = $editor->get_size();
    
    // Resize if too large (max 1920px width)
    $max_width = 1920;
    if ($original_size['width'] > $max_width) {
        $editor->resize($max_width, null, false);
        $editor->save($attachment_path);
        
        // Update attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $attachment_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return true;
    }
    
    return false;
}