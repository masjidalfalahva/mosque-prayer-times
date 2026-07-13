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
require_once MAPT_PLUGIN_DIR . 'public/shortcode.php';

/**
 * Plugin activation
 */
function mapt_activate_plugin() {

    // Future database setup will go here.

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
