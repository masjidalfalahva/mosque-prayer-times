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
		 * The Masjid Al-Falah document contains 13 columns:
		 *
		 * 0  Weekday
		 * 1  Islamic month and day
		 * 2  Gregorian day and month
		 * 3  Fajr Adhan
		 * 4  Fajr Iqamah
		 * 5  Sunrise
		 * 6  Dhuhr Adhan
		 * 7  Dhuhr Iqamah
		 * 8  Asr Adhan
		 * 9  Asr Iqamah
		 * 10 Maghrib Adhan
		 * 11 Isha Adhan
		 * 12 Isha Iqamah
		 */
		if ( count( $row ) < 13 ) {
			continue;
		}

		$weekday = preg_replace(
			'/\s+/',
			'',
			trim( $row[0] )
		);

		/*
		 * Skip headings and non-prayer rows.
		 */
		if ( ! preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)$/i', $weekday ) ) {
			continue;
		}

		/*
		 * Word sometimes inserts spaces between individual letters.
		 */
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

		/*
		 * Extract the Islamic month abbreviation.
		 *
		 * Examples:
		 * RJB12
		 * RMD1
		 * SHW13
		 */
		if ( preg_match( '/^([A-Z]+)\d+$/', $islamic_text, $islamic_matches ) ) {
			$islamic_month = $islamic_matches[1];
		} else {
			$islamic_month = '';
		}

		/*
		 * Extract Gregorian date.
		 *
		 * Examples:
		 * 1Jan
		 * 18Feb
		 * 31Mar
		 */
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

		/*
		 * Skip any row that does not contain all expected prayer times.
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

			/*
			 * The schedule states that Maghrib jama'ah is directly
			 * after the Adhan, so both fields use the same value.
			 */
			'maghrib_iqamah' => $maghrib_adhan,

			'isha_adhan'     => $isha_adhan,
			'isha_iqamah'    => $isha_iqamah,

			/*
			 * Jumu'ah values will be populated in a later step.
			 */
			'jummah1'        => '',
			'jummah2'        => '',
			'jummah3'        => '',

			/*
			 * Rows with the Islamic month code RMD are Ramadan rows.
			 */
			'ramadan'        => ( 'RMD' === $islamic_month ) ? 1 : 0,
		);
	}

	return $records;
}

/**
 * Normalize a prayer time.
 *
 * Examples:
 * 5:5 3 a  becomes 5:53 AM
 * 12:18p   becomes 12:18 PM
 * 12:00n   becomes 12:00 PM
 * 6:15     becomes 6:15
 *
 * @param string $time Raw Word-table value.
 *
 * @return string
 */
function mapt_normalize_prayer_time( $time ) {

	$time = strtolower( trim( $time ) );

	/*
	 * Remove spaces Word placed between characters.
	 */
	$time = preg_replace( '/\s+/', '', $time );

	if ( empty( $time ) ) {
		return '';
	}

	/*
	 * Some cells can contain an accidental trailing character
	 * introduced by the Word document.
	 *
	 * Example: 1:45Z
	 */
	$time = preg_replace(
		'/^(\d{1,2}:\d{2})[^apn\d]$/',
		'$1',
		$time
	);

	/*
	 * Time with AM or PM abbreviation.
	 */
	if ( preg_match( '/^(\d{1,2}):(\d{2})(a|p)$/', $time, $matches ) ) {

		$suffix = ( 'a' === $matches[3] ) ? 'AM' : 'PM';

		return intval( $matches[1] ) . ':' . $matches[2] . ' ' . $suffix;
	}

	/*
	 * The letter "n" means noon in the document.
	 */
	if ( preg_match( '/^(\d{1,2}):(\d{2})n$/', $time, $matches ) ) {
		return intval( $matches[1] ) . ':' . $matches[2] . ' PM';
	}

	/*
	 * Iqamah times often have no AM or PM suffix.
	 */
	if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $time, $matches ) ) {
		return intval( $matches[1] ) . ':' . $matches[2];
	}

	return '';
}
