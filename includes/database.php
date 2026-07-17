<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create prayer times database table
 */
function mapt_create_database() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'masjid_prayer_times';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (

        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        prayer_date date NOT NULL,

        fajr_adhan varchar(20),
        fajr_iqamah varchar(20),

        sunrise varchar(20),

        dhuhr_adhan varchar(20),
        dhuhr_iqamah varchar(20),

        asr_adhan varchar(20),
        asr_iqamah varchar(20),

        maghrib_adhan varchar(20),
        maghrib_iqamah varchar(20),

        isha_adhan varchar(20),
        isha_iqamah varchar(20),

        ramadan tinyint(1) DEFAULT 0,

        PRIMARY KEY (id),

        UNIQUE KEY prayer_date (prayer_date)

    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);

}
