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

		/*
		 * A valid daily row should contain at least:
		 * Day, Islamic month, Islamic day, Gregorian date,
		 * Fajr, Fajr iqamah, Sunrise, Dhuhr, Dhuhr iqamah,
		 * Asr, Asr iqamah, Maghrib, Isha, Isha iqamah.
		 */
		if ( count( $row ) < 14 ) {
			continue;
		}

		$weekday       = trim( $row[0] );
		$islamic_month = strtoupper( trim( $row[1] ) );
		$islamic_day   = trim( $row[2] );
		$date_text     = trim( $row[3] );

		/*
		 * Skip headings and column-header rows.
		 */
		if ( ! preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)$/i', $weekday ) ) {
			continue;
		}

		if ( ! preg_match( '/^(\d{1,2})\s*([A-Za-z]{3})$/', $date_text, $date_matches ) ) {
			continue;
		}

		$day_number  = intval( $date_matches[1] );
		$month_text = ucfirst( strtolower( $date_matches[2] ) );

		$month_number = date(
			'n',
			strtotime( '1 ' . $month_text . ' 2026' )
		);

		if ( empty( $month_number ) ) {
			continue;
		}

		$prayer_date = sprintf(
			'2026-%02d-%02d',
			$month_number,
			$day_number
		);

		$fajr_adhan     = mapt_normalize_prayer_time( $row[4] );
		$fajr_iqamah    = mapt_normalize_prayer_time( $row[5] );
		$sunrise        = mapt_normalize_prayer_time( $row[6] );
		$dhuhr_adhan    = mapt_normalize_prayer_time( $row[7] );
		$dhuhr_iqamah   = mapt_normalize_prayer_time( $row[8] );
		$asr_adhan      = mapt_normalize_prayer_time( $row[9] );
		$asr_iqamah     = mapt_normalize_prayer_time( $row[10] );
		$maghrib_adhan  = mapt_normalize_prayer_time( $row[11] );
		$isha_adhan     = mapt_normalize_prayer_time( $row[12] );
		$isha_iqamah    = mapt_normalize_prayer_time( $row[13] );

		/*
		 * Reject rows that do not contain the expected prayer times.
		 */
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
 * Normalize a prayer time into a consistent display format.
 *
 * Examples:
 * 5:53a becomes 5:53 AM
 * 12:18p becomes 12:18 PM
 * 6:15 becomes 6:15
 *
 * @param string $time Raw time value.
 *
 * @return string
 */
function mapt_normalize_prayer_time( $time ) {

	$time = strtolower( trim( $time ) );

	$time = preg_replace( '/\s+/', '', $time );

	if ( empty( $time ) ) {
		return '';
	}

	if ( preg_match( '/^(\d{1,2}):(\d{2})(a|p)$/', $time, $matches ) ) {

		$suffix = ( 'a' === $matches[3] ) ? 'AM' : 'PM';

		return intval( $matches[1] ) . ':' . $matches[2] . ' ' . $suffix;
	}

	if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $time, $matches ) ) {
		return intval( $matches[1] ) . ':' . $matches[2];
	}

	return '';
}
