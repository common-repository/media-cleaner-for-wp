<?php
/**
 * Plugin Name: Media Cleaner for WP
 * Description: Media Cleaner for WordPress is an essential tool designed to streamline your WordPress media library. It efficiently removes unused media files and repairs broken links, ensuring your library remains clutter-free. The built-in trash functionality allows you to review and verify changes before final deletion. Additionally, Media Cleaner leverages intelligent analysis to maintain compatibility with various plugins.
 * Version: 1.0.0
 * Author: abovebits.com
 * Author URI: https://abovebits.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define('MCFWP_ADMIN_SETTINGS_LINK', 'delete-unused-images-settings');
define('MCFWP_PLUGIN_VERSION', '1.0.0' );

include_once ('functions.php');

add_action('admin_menu', 'mcfwp_unused_images_settings_page');

function mcfwp_unused_images_settings_page() {
    add_options_page(
        'Media Cleaner Settings Page',
        'Media Cleaner Settings',
        'manage_options',
        MCFWP_ADMIN_SETTINGS_LINK,
        'mcfwp_unused_images_settings_page_content'
    );
}

add_action( 'admin_enqueue_scripts', 'mcfwp_media_cleaner_scripts' );
function mcfwp_media_cleaner_scripts(){
	wp_register_script( 'media-cleaner-script', plugin_dir_url( __FILE__ ).'assets/js/script.js', [ 'jquery' ], MCFWP_PLUGIN_VERSION, true );
	wp_enqueue_script( 'media-cleaner-script' );
    
    wp_register_style( 'media-cleaner-style', plugin_dir_url( __FILE__ ).'assets/css/style.css', [], MCFWP_PLUGIN_VERSION );
	wp_enqueue_style( 'media-cleaner-style' );

    $url = admin_url('admin-ajax.php');
    $url = wp_nonce_url($url, 'media_cleaner', 'media_cleaner_nonce');
    
    wp_localize_script('media-cleaner-script', 'mcfwp_ajax_var', array(
        'url' => $url,
    ));
}


function mcfwp_add_setting_link($links) {
    $settings_link = menu_page_url(MCFWP_ADMIN_SETTINGS_LINK, false);
    $settings_link = '<a href="'.$settings_link.'">Settings</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'mcfwp_add_setting_link');

function mcfwp_unused_images_settings_page_content() {
    include_once (plugin_dir_path(__FILE__) . '/templates/admin-views.php');
}

add_action('wp_ajax_mcfwp_scan', 'mcfwp_scan_callback');
function mcfwp_scan_callback(){
    $all_unused_images = mcfwp_scan_update();

    wp_send_json(
        array(
            'unused_images' => count($all_unused_images),
        )
    );
}

add_action('wp_ajax_mcfwp_check_unused_images', 'mcfwp_check_unused_images_callback');
function mcfwp_check_unused_images_callback() {
    $all_unused_images = get_option('mcfwp_get_all_unused_images', false);
    $unused_images = false;

    if(isset($all_unused_images) && is_array($all_unused_images) && count($all_unused_images)>0){
        $unused_images = true;
    }

    wp_send_json(
        array(
            'unused_images' => $unused_images,
        )
    );
}

add_action('wp_ajax_mcfwp_unused_images', 'mcfwp_unused_images_callback');
function mcfwp_unused_images_callback(){
    $all_unused_images = get_option('mcfwp_get_all_unused_images', false);

    if(!$all_unused_images){
        wp_send_json(
            array()
        );
    }

    foreach ($all_unused_images AS $i=>$image_path) {
        if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
            $all_unused_images[$i] = str_replace( sanitize_text_field( wp_unslash($_SERVER['DOCUMENT_ROOT'] ) ), get_site_url(), $image_path);    
        }
    }

    $all_unused_images = array_values($all_unused_images);

    wp_send_json(
        array(
            'images' => (array)$all_unused_images,
            'count' => count($all_unused_images),
        )
    );

}

add_action('wp_ajax_mcfwp_delete', 'mcfwp_delete_callback');
function mcfwp_delete_callback(){
    if (isset( $_REQUEST['media_cleaner_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['media_cleaner_nonce'])), 'media_cleaner' ) && isset($_POST['data']) && !empty($_POST['data'])) {
        $needs_delete_images = sanitize_text_field(wp_unslash($_POST['data']));
        $all_images_for_uploads = mcfwp_get_all_images_for_uploads();
        $needs_delete_images = array_values($needs_delete_images);

        foreach ($needs_delete_images AS $i => $needs_delete_image){
            if($i < 100){
                mcfwp_delete_image($needs_delete_image, $all_images_for_uploads);
                unset($needs_delete_images[$i]);
            }
        }
        $needs_delete_images = array_values($needs_delete_images);
        $all_unused_images = mcfwp_scan_update();

        wp_send_json(
            array(
                'mcfwp_get_all_unused_images' => count($all_unused_images),
                'needs_delete_images' => count($needs_delete_images),
                'data' => $needs_delete_images,
            )
        );

    }
    wp_send_json(
        array(
            'end' => true
        )
    );
}

add_action('wp_ajax_mcfwp_trash', 'mcfwp_trash_callback');
function mcfwp_trash_callback(){
    if (isset( $_REQUEST['media_cleaner_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['media_cleaner_nonce'])), 'media_cleaner' ) && isset($_POST['data']) && !empty($_POST['data'])) {
        $needs_delete_images = sanitize_text_field(wp_unslash($_POST['data']));
        $all_images_for_uploads = mcfwp_get_all_images_for_uploads();
        $all_unused_images = get_option('mcfwp_get_all_unused_images', false);
        foreach ($needs_delete_images AS $needs_delete_image){
            $all_unused_images = mcfwp_trash_image($needs_delete_image, $all_images_for_uploads, $all_unused_images);
        }
        update_option('mcfwp_get_all_unused_images', $all_unused_images);

        wp_send_json(
            array(
                'mcfwp_get_all_unused_images' => count($all_unused_images)
            )
        );

    }
    wp_send_json(
        array(
            'end' => true
        )
    );
}

add_action('wp_ajax_mcfwp_restore', 'mcfwp_restore_callback');
function mcfwp_restore_callback(){
    if (isset( $_REQUEST['media_cleaner_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['media_cleaner_nonce'])), 'media_cleaner' ) && isset($_POST['data']) && !empty($_POST['data'])) {
        if(sanitize_text_field(wp_unslash($_POST['data'])) == 'all'){
            mcfwp_restore_all();
        }
        $needs_delete_images = sanitize_text_field(wp_unslash($_POST['data']));
        $all_images_for_uploads = mcfwp_get_all_images_for_uploads();
        $all_unused_images = get_option('mcfwp_get_all_unused_images', false);
        foreach ($needs_delete_images AS $needs_delete_image){
            $all_unused_images = mcfwp_restore_image($needs_delete_image, $all_images_for_uploads, $all_unused_images);
        }
        update_option('mcfwp_get_all_unused_images', $all_unused_images);

        wp_send_json(
            array(
                'mcfwp_get_all_unused_images' => count($all_unused_images)
            )
        );

    }
    wp_send_json(
        array(
            'end' => true
        )
    );
}
