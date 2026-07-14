<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Prayer Times admin menu.
 */
function mapt_admin_menu() {

    add_menu_page(
        'Prayer Times',
        'Prayer Times',
        'manage_options',
        'mapt-prayer-times',
        'mapt_dashboard_page',
        'dashicons-calendar-alt',
        25
    );

}

add_action( 'admin_menu', 'mapt_admin_menu' );

/**
 * Dashboard page.
 */
function mapt_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>Masjid Al-Falah Prayer Times</h1>

        <p>Welcome to the Prayer Times plugin.</p>

        <p>The prayer management tools will appear here.</p>
    </div>
    <?php
}
