<?php
/**
 * Plugin Name: Auto WebP Converter
 * Plugin URI: https://www.lexo.ch
 * Description: Automatically converts JPG and PNG images uploaded to the WordPress media library to the WebP format.
 * Author: LEXO
 * Version: 2.0.1
 * Author URI: https://www.lexo.ch
 */

 // Check if GD extension is enabled
if (!extension_loaded('gd') || !function_exists('gd_info')) {
    add_action('admin_notices', 'gd_not_enabled_notice');
    return;
}

function gd_not_enabled_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('The GD PHP extension is required for the WebP Converter plugin to work.', 'webp-converter'); ?></p>
    </div>
    <?php
}

function webp_converter($file) {
    $file_info = pathinfo($file['file']);

    // Only convert JPG and PNG images
    if (!in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png'])) {
        return $file;
    }

    try {
        $image = null;

        $file_info['filename'] = wp_unique_filename($file_info['dirname'], $file_info['filename'] . '.webp');

        $webp_file_path = $file_info['dirname'] . '/' . $file_info['filename'];

        switch(strtolower($file_info['extension'])) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file['file']);
                imagewebp($image, $webp_file_path, 85);
                break;
            case 'png':
                $image = imagecreatefrompng($file['file']);
                imagewebp($image, $webp_file_path, 100);
                break;
        }

        if (!$image) {
            throw new Exception('Failed to create image resource');
        }

        imagedestroy($image);

        unlink($file['file']);

        $file['file'] = $webp_file_path;
        $file['type'] = 'image/webp';
    } catch (Exception $e) {
        error_log('WebP Converter: Failed to convert image to WebP format - ' . $e->getMessage());
    }

    return $file;
}
add_filter('wp_handle_upload', 'webp_converter');
