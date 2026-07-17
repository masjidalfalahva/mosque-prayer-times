<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Load prayer schedule
 */
function mapt_get_prayer_times() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'masjid_prayer_times';

    $results = $wpdb->get_results(
        "SELECT * FROM {$table_name} ORDER BY prayer_date ASC",
        ARRAY_A
    );

    if (empty($results)) {
        return array();
    }

    foreach ($results as &$row) {
        // Keep compatibility with the existing shortcode
        $row['date'] = $row['prayer_date'];
    }

    return $results;
}
