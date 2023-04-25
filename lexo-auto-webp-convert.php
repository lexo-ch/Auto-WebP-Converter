<?php
/**
 * Plugin Name: LEXO Auto WebP Converter
 * Plugin URI: https://www.lexo.ch
 * Description: Automatically converts JPG and PNG images uploaded to the WordPress media library to the WebP format.
 * Author: LEXO
 * Version: 1.0.0
 * Author URI: https://www.lexo.ch
 */

// Check if Imagick extension is enabled
if (!extension_loaded('imagick') || !class_exists('Imagick')) {
    add_action('admin_notices', 'imagick_not_enabled_notice');
    return;
}

function imagick_not_enabled_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('The Imagick PHP extension is required for the WebP Converter plugin to work.', 'webp-converter'); ?></p>
    </div>
    <?php
}

// Convert uploaded JPG and PNG images to WebP and update metadata
function webp_converter($metadata, $attachment_id) {
    if (!isset($metadata['file'])) {
        return $metadata;
    }

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];

    $file_info = pathinfo($file_path);
    $webp_file_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

    // Only convert JPG and PNG images
    if (!in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png'])) {
        return $metadata;
    }

    try {
        $imagick = new Imagick($file_path);
        $imagick->setImageFormat('webp');

        // Set output quality for JPG images
        if (in_array(strtolower($file_info['extension']), ['jpg', 'jpeg'])) {
            $imagick->setOption('webp:lossless', 'false');
            $imagick->setImageCompressionQuality(70);
        }

        $imagick->writeImage($webp_file_path);
        $imagick->clear();
        $imagick->destroy();

        // Update metadata to point to the WebP image
        $metadata['file'] = str_replace($file_info['basename'], $file_info['filename'] . '.webp', $metadata['file']);
        $metadata['sizes'] = array();

        // Update file size
        $metadata['filesize'] = filesize($webp_file_path);

        // Update MIME type and file URL
        wp_update_attachment_metadata($attachment_id, $metadata);
        update_attached_file($attachment_id, $webp_file_path);

        // Remove the original image from the server
        unlink($file_path);

    } catch (Exception $e) {
        error_log('WebP Converter: Failed to convert image to WebP format - ' . $e->getMessage());
    }

    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'webp_converter', 10, 2);