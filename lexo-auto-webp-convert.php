<?php
/**
 * Plugin Name: Auto WebP Converter
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

function webp_converter($file) {
    $file_info = pathinfo($file['file']);

    // Only convert JPG and PNG images
    if (!in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png'])) {
        return $file;
    }

    try {
        $imagick = new Imagick($file['file']);
        $imagick->setFormat('webp');

        if (in_array(strtolower($file_info['extension']), ['jpg', 'jpeg'])) {
            $imagick->setOption('webp:lossless', 'false');
            $imagick->setImageCompressionQuality(70);
        }

        $webp_file_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

        $imagick->writeImage($webp_file_path);
        $imagick->clear();
        $imagick->destroy();

        unlink($file['file']);

        $file['file'] = $webp_file_path;
        $file['type'] = 'image/webp';
    } catch (Exception $e) {
        error_log('WebP Converter: Failed to convert image to WebP format - ' . $e->getMessage());
    }

    return $file;
}
add_filter('wp_handle_upload', 'webp_converter');
