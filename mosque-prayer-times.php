<?php
/*
Plugin Name: Masjid Al-Falah Prayer Times
Plugin URI: https://github.com/masjidalfalahva/mosque-prayer-times
Description: Displays daily prayer times, iqamah times, and Ramadan schedule for Masjid Al-Falah.
Version: 1.0.0
Author: Masjid Al-Falah
Author URI: https://github.com/masjidalfalahva
License: GPL2
Text Domain: masjid-prayer-times
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin version
define( 'MAPT_VERSION', '1.0.0' );

// Plugin path
define( 'MAPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Plugin URL
define( 'MAPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin files
require_once MAPT_PLUGIN_DIR . 'includes/database.php';
require_once MAPT_PLUGIN_DIR . 'includes/prayer-loader.php';
require_once MAPT_PLUGIN_DIR . 'public/shortcode.php';
require_once MAPT_PLUGIN_DIR . 'admin/admin-menu.php';
require_once MAPT_PLUGIN_DIR . 'admin/add-prayer.php';
require_once MAPT_PLUGIN_DIR . 'admin/manage-prayers.php';
require_once MAPT_PLUGIN_DIR . 'admin/import-prayers.php';

/**
 * Plugin activation
 */
function mapt_activate_plugin() {

     mapt_create_database();

}

register_activation_hook(
    __FILE__,
    'mapt_activate_plugin'
);


/**
 * Plugin loaded message (temporary test)
 */
function mapt_loaded_message() {

    // This confirms the plugin loads correctly.

}

add_action(
    'plugins_loaded',
    'mapt_loaded_message'
);

/**
 * Load plugin styles
 */
function mapt_load_styles() {

    wp_enqueue_style(
        'mapt-prayer-style',
        MAPT_PLUGIN_URL . 'public/css/prayer-times.css',
        array(),
        MAPT_VERSION
    );

}

add_action(
    'wp_enqueue_scripts',
    'mapt_load_styles'
);


