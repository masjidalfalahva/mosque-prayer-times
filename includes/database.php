<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create or update the plugin database tables.
 */
function mapt_create_database() {

	global $wpdb;

	$prayer_table = $wpdb->prefix . 'masjid_prayer_times';
	$jummah_table = $wpdb->prefix . 'masjid_jummah_schedules';

	$charset_collate = $wpdb->get_charset_collate();

	/*
	 * Daily prayer-times table.
	 *
	 * dbDelta() will preserve existing records while adding or updating
	 * the table structure when necessary.
	 */
	$prayer_sql = "CREATE TABLE {$prayer_table} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

		prayer_date date NOT NULL,

		fajr_adhan varchar(20) DEFAULT '',
		fajr_iqamah varchar(20) DEFAULT '',

		sunrise varchar(20) DEFAULT '',

		dhuhr_adhan varchar(20) DEFAULT '',
		dhuhr_iqamah varchar(20) DEFAULT '',

		asr_adhan varchar(20) DEFAULT '',
		asr_iqamah varchar(20) DEFAULT '',

		maghrib_adhan varchar(20) DEFAULT '',
		maghrib_iqamah varchar(20) DEFAULT '',

		isha_adhan varchar(20) DEFAULT '',
		isha_iqamah varchar(20) DEFAULT '',

		jummah1 varchar(20) DEFAULT '',
		jummah2 varchar(20) DEFAULT '',
		jummah3 varchar(20) DEFAULT '',

		ramadan tinyint(1) DEFAULT 0,

		PRIMARY KEY  (id),
		UNIQUE KEY prayer_date (prayer_date)
	) {$charset_collate};";

	/*
	 * Saved Jumu'ah date-range schedules.
	 */
	$jummah_sql = "CREATE TABLE {$jummah_table} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

		start_date date NOT NULL,
		end_date date NOT NULL,

		jummah1 varchar(20) NOT NULL DEFAULT '',
		jummah2 varchar(20) NOT NULL DEFAULT '',
		jummah3 varchar(20) NOT NULL DEFAULT '',

		PRIMARY KEY  (id),
		KEY start_date (start_date),
		KEY end_date (end_date)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( $prayer_sql );
	dbDelta( $jummah_sql );
}
