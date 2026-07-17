<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert Word table rows into normalized prayer-time records.
 *
 * @param array $rows DOCX table rows.
 *
 * @return array
 */
function mapt_parse_prayer_rows( $rows ) {

	$records = array();

	foreach ( $rows as $row ) {

		if ( count( $row ) < 13 ) {
			continue;
		}

		$weekday = preg_replace(
			'/\s+/',
			'',
			trim( $row[0] )
		);

		if ( ! preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)$/i', $weekday ) ) {
			continue;
		}

		$islamic_text = strtoupper(
			preg_replace(
				'/\s+/',
				'',
				trim( $row[1] )
			)
		);

		$date_text = preg_replace(
			'/\s+/',
			'',
			trim( $row[2] )
		);

		if ( preg_match( '/^([A-Z]+)\d+$/', $islamic_text, $islamic_matches ) ) {
			$islamic_month = $islamic_matches[1];
		} else {
			$islamic_month = '';
		}

		if ( ! preg_match( '/^(\d{1,2})([A-Za-z]{3})$/', $date_text, $date_matches ) ) {
			continue;
		}

		$day_number = intval( $date_matches[1] );
		$month_text = strtolower( $date_matches[2] );

		$months = array(
			'jan' => 1,
			'feb' => 2,
			'mar' => 3,
			'apr' => 4,
			'may' => 5,
			'jun' => 6,
			'jul' => 7,
			'aug' => 8,
			'sep' => 9,
			'oct' => 10,
			'nov' => 11,
			'dec' => 12,
		);

		if ( ! isset( $months[ $month_text ] ) ) {
			continue;
		}

		$month_number = $months[ $month_text ];

		if ( ! checkdate( $month_number, $day_number, 2026 ) ) {
			continue;
		}

		$prayer_date = sprintf(
			'2026-%02d-%02d',
			$month_number,
			$day_number
		);

		$fajr_adhan    = mapt_normalize_prayer_time( $row[3] );
		$fajr_iqamah   = mapt_normalize_prayer_time( $row[4] );
		$sunrise       = mapt_normalize_prayer_time( $row[5] );
		$dhuhr_adhan   = mapt_normalize_prayer_time( $row[6] );
		$dhuhr_iqamah  = mapt_normalize_prayer_time( $row[7] );
		$asr_adhan     = mapt_normalize_prayer_time( $row[8] );
		$asr_iqamah    = mapt_normalize_prayer_time( $row[9] );
		$maghrib_adhan = mapt_normalize_prayer_time( $row[10] );
		$isha_adhan    = mapt_normalize_prayer_time( $row[11] );
		$isha_iqamah   = mapt_normalize_prayer_time( $row[12] );

		if (
			empty( $fajr_adhan ) ||
			empty( $fajr_iqamah ) ||
			empty( $sunrise ) ||
			empty( $dhuhr_adhan ) ||
			empty( $dhuhr_iqamah ) ||
			empty( $asr_adhan ) ||
			empty( $asr_iqamah ) ||
			empty( $maghrib_adhan ) ||
			empty( $isha_adhan ) ||
			empty( $isha_iqamah )
		) {
			continue;
		}

		$records[] = array(
			'prayer_date'    => $prayer_date,
			'fajr_adhan'     => $fajr_adhan,
			'fajr_iqamah'    => $fajr_iqamah,
			'sunrise'        => $sunrise,
			'dhuhr_adhan'    => $dhuhr_adhan,
			'dhuhr_iqamah'   => $dhuhr_iqamah,
			'asr_adhan'      => $asr_adhan,
			'asr_iqamah'     => $asr_iqamah,
			'maghrib_adhan'  => $maghrib_adhan,
			'maghrib_iqamah' => $maghrib_adhan,
			'isha_adhan'     => $isha_adhan,
			'isha_iqamah'    => $isha_iqamah,
			'jummah1'        => '',
			'jummah2'        => '',
			'jummah3'        => '',
			'ramadan'        => ( 'RMD' === $islamic_month ) ? 1 : 0,
		);
	}

	return $records;
}

/**
 * Normalize a prayer time.
 *
 * @param string $time Raw Word-table value.
 *
 * @return string
 */
function mapt_normalize_prayer_time( $time ) {

	$time = strtolower( trim( $time ) );

	$time = preg_replace( '/\s+/', '', $time );

	if ( empty( $time ) ) {
		return '';
	}

	$time = preg_replace(
		'/^(\d{1,2}:\d{2})[^apn\d]$/',
		'$1',
		$time
	);

	if ( preg_match( '/^(\d{1,2}):(\d{2})(a|p)$/', $time, $matches ) ) {

		$suffix = ( 'a' === $matches[3] ) ? 'AM' : 'PM';

		return intval( $matches[1] ) . ':' . $matches[2] . ' ' . $suffix;
	}

	if ( preg_match( '/^(\d{1,2}):(\d{2})n$/', $time, $matches ) ) {
		return intval( $matches[1] ) . ':' . $matches[2] . ' PM';
	}

	if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $time, $matches ) ) {
		return intval( $matches[1] ) . ':' . $matches[2];
	}

	return '';
}

/**
 * Insert or update parsed prayer records in the database.
 *
 * @param array $records Parsed prayer records.
 *
 * @return array
 */
function mapt_save_prayer_records( $records ) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'masjid_prayer_times';

	$results = array(
		'added'   => 0,
		'updated' => 0,
		'errors'  => 0,
	);

	if ( empty( $records ) || ! is_array( $records ) ) {
		return $results;
	}

	foreach ( $records as $record ) {

		if ( empty( $record['prayer_date'] ) ) {
			$results['errors']++;
			continue;
		}

		$prayer_date = sanitize_text_field( $record['prayer_date'] );

		$data = array(
			'prayer_date'    => $prayer_date,
			'fajr_adhan'     => sanitize_text_field( $record['fajr_adhan'] ),
			'fajr_iqamah'    => sanitize_text_field( $record['fajr_iqamah'] ),
			'sunrise'        => sanitize_text_field( $record['sunrise'] ),
			'dhuhr_adhan'    => sanitize_text_field( $record['dhuhr_adhan'] ),
			'dhuhr_iqamah'   => sanitize_text_field( $record['dhuhr_iqamah'] ),
			'asr_adhan'      => sanitize_text_field( $record['asr_adhan'] ),
			'asr_iqamah'     => sanitize_text_field( $record['asr_iqamah'] ),
			'maghrib_adhan'  => sanitize_text_field( $record['maghrib_adhan'] ),
			'maghrib_iqamah' => sanitize_text_field( $record['maghrib_iqamah'] ),
			'isha_adhan'     => sanitize_text_field( $record['isha_adhan'] ),
			'isha_iqamah'    => sanitize_text_field( $record['isha_iqamah'] ),
			'ramadan'        => ! empty( $record['ramadan'] ) ? 1 : 0,
		);

		$formats = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
		);

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id
				FROM {$table_name}
				WHERE prayer_date = %s
				LIMIT 1",
				$prayer_date
			)
		);

		if ( $existing_id ) {

			$update_result = $wpdb->update(
				$table_name,
				$data,
				array(
					'id' => intval( $existing_id ),
				),
				$formats,
				array(
					'%d',
				)
			);

			if ( false === $update_result ) {
				$results['errors']++;
			} else {
				$results['updated']++;
			}

		} else {

			$insert_result = $wpdb->insert(
				$table_name,
				$data,
				$formats
			);

			if ( false === $insert_result ) {
				$results['errors']++;
			} else {
				$results['added']++;
			}
		}
	}

	return $results;
}

/**
 * Extract monthly Jumu'ah schedules from the Word-document rows.
 *
 * @param array $rows DOCX table rows.
 *
 * @return array
 */
function mapt_parse_jummah_schedules( $rows ) {

	$lines = array();

	foreach ( $rows as $row ) {

		if ( ! is_array( $row ) ) {
			continue;
		}

		$line = implode( ' ', $row );
		$line = html_entity_decode( $line, ENT_QUOTES, 'UTF-8' );
		$line = preg_replace( '/\s+/', ' ', $line );
		$line = trim( $line );

		if ( '' !== $line ) {
			$lines[] = $line;
		}
	}

	$document_text = implode( ' ', $lines );

	$document_text = str_replace(
		array( '’', '‘', '´', '`' ),
		"'",
		$document_text
	);

	$months = array(
		'January'   => 1,
		'February'  => 2,
		'March'     => 3,
		'April'     => 4,
		'May'       => 5,
		'June'      => 6,
		'July'      => 7,
		'August'    => 8,
		'September' => 9,
		'October'   => 10,
		'November'  => 11,
		'December'  => 12,
	);

	$schedules = array();

	foreach ( $months as $month_name => $month_number ) {

		$pattern =
			'/Prayer\s*Schedule[\s,]*' .
			preg_quote( $month_name, '/' ) .
			'[\s,]*2026(.*?)(?=DAY\s+)/i';

		if ( ! preg_match( $pattern, $document_text, $heading_match ) ) {
			continue;
		}

		$heading = trim( $heading_match[1] );

		/*
		 * March contains two different Jumu'ah schedules.
		 */
		if ( 3 === $month_number ) {

			$march_pattern =
				'/Friday\s+Jama[\'’]?ts?\s*' .
				'\(\s*(\d{1,2})\s*Mar\s*\)\s*:\s*' .
				'(\d{1,2}:\d{2})\s*,\s*' .
				'(\d{1,2}:\d{2})\s*,\s*' .
				'(\d{1,2}:\d{2})' .
				'.*?' .
				'Friday\s+Jama[\'’]?ts?\s*' .
				'\(\s*From\s+(\d{1,2})\s*Mar\s*\)\s*:\s*' .
				'(\d{1,2}:\d{2})\s*,\s*' .
				'(\d{1,2}:\d{2})\s*,\s*' .
				'(\d{1,2}:\d{2})/i';

			if ( preg_match( $march_pattern, $heading, $march_match ) ) {

				$schedules[] = array(
					'month'     => 3,
					'start_day' => intval( $march_match[1] ),
					'jummah1'   => mapt_normalize_jummah_time( $march_match[2] ),
					'jummah2'   => mapt_normalize_jummah_time( $march_match[3] ),
					'jummah3'   => mapt_normalize_jummah_time( $march_match[4] ),
				);

				$schedules[] = array(
					'month'     => 3,
					'start_day' => intval( $march_match[5] ),
					'jummah1'   => mapt_normalize_jummah_time( $march_match[6] ),
					'jummah2'   => mapt_normalize_jummah_time( $march_match[7] ),
					'jummah3'   => mapt_normalize_jummah_time( $march_match[8] ),
				);

				continue;
			}
		}

		$normal_pattern =
			'/Friday\s+Jama[\'’]?ts?\s*:\s*' .
			'(\d{1,2}:\d{2})\s*,\s*' .
			'(\d{1,2}:\d{2})\s*,\s*' .
			'(\d{1,2}:\d{2})/i';

		if ( preg_match( $normal_pattern, $heading, $time_match ) ) {

			$schedules[] = array(
				'month'     => $month_number,
				'start_day' => 1,
				'jummah1'   => mapt_normalize_jummah_time( $time_match[1] ),
				'jummah2'   => mapt_normalize_jummah_time( $time_match[2] ),
				'jummah3'   => mapt_normalize_jummah_time( $time_match[3] ),
			);
		}
	}

	return $schedules;
}

/**
 * Normalize a Jumu'ah time as an afternoon time.
 *
 * @param string $time Raw Jumu'ah time.
 *
 * @return string
 */
function mapt_normalize_jummah_time( $time ) {

	$time = preg_replace( '/\s+/', '', trim( $time ) );

	if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $time, $matches ) ) {
		return '';
	}

	return intval( $matches[1] ) . ':' . $matches[2] . ' PM';
}

/**
 * Save Jumu'ah times to each Friday in the database.
 *
 * @param array $schedules Parsed monthly Jumu'ah schedules.
 *
 * @return array
 */
function mapt_save_jummah_schedules( $schedules ) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'masjid_prayer_times';

	$results = array(
		'schedules'       => count( $schedules ),
		'fridays_updated' => 0,
		'errors'          => 0,
	);

	if ( empty( $schedules ) || ! is_array( $schedules ) ) {
		return $results;
	}

	usort(
		$schedules,
		function ( $first, $second ) {

			if ( $first['month'] === $second['month'] ) {
				return $first['start_day'] - $second['start_day'];
			}

			return $first['month'] - $second['month'];
		}
	);

	foreach ( $schedules as $index => $schedule ) {

		$month     = intval( $schedule['month'] );
		$start_day = intval( $schedule['start_day'] );

		$start_date = sprintf(
			'2026-%02d-%02d',
			$month,
			$start_day
		);

		$end_day = cal_days_in_month(
			CAL_GREGORIAN,
			$month,
			2026
		);

		if (
			isset( $schedules[ $index + 1 ] ) &&
			intval( $schedules[ $index + 1 ]['month'] ) === $month
		) {
			$end_day = intval(
				$schedules[ $index + 1 ]['start_day']
			) - 1;
		}

		$end_date = sprintf(
			'2026-%02d-%02d',
			$month,
			$end_day
		);

		$update_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name}
				SET jummah1 = %s,
					jummah2 = %s,
					jummah3 = %s
				WHERE prayer_date BETWEEN %s AND %s
				AND DAYOFWEEK(prayer_date) = 6",
				sanitize_text_field( $schedule['jummah1'] ),
				sanitize_text_field( $schedule['jummah2'] ),
				sanitize_text_field( $schedule['jummah3'] ),
				$start_date,
				$end_date
			)
		);

		if ( false === $update_result ) {
			$results['errors']++;
		} else {
			$results['fridays_updated'] += intval( $update_result );
		}
	}

	return $results;
}
