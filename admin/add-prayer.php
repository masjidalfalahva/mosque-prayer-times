<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Add Prayer Times admin page.
 */
function mapt_render_add_prayer_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'masjid-prayer-times' ) );
	}

	global $wpdb;

	$table_name = $wpdb->prefix . 'masjid_prayer_times';

	$message      = '';
	$message_type = 'success';

	$prayer_date = wp_date( 'Y-m-d' );

	$fields = array(
		'fajr_adhan'     => '',
		'fajr_iqamah'    => '',
		'sunrise'        => '',
		'dhuhr_adhan'    => '',
		'dhuhr_iqamah'   => '',
		'asr_adhan'      => '',
		'asr_iqamah'     => '',
		'maghrib_adhan'  => '',
		'maghrib_iqamah' => '',
		'isha_adhan'     => '',
		'isha_iqamah'    => '',
		'jummah1'        => '',
		'jummah2'        => '',
		'jummah3'        => '',
	);

	$ramadan = 0;

	/*
	 * Process the form.
	 */
	if (
		isset( $_SERVER['REQUEST_METHOD'] ) &&
		'POST' === $_SERVER['REQUEST_METHOD'] &&
		isset( $_POST['mapt_save_prayer_times'] )
	) {

		check_admin_referer(
			'mapt_save_prayer_times_action',
			'mapt_prayer_times_nonce'
		);

		$prayer_date = isset( $_POST['prayer_date'] )
			? sanitize_text_field( wp_unslash( $_POST['prayer_date'] ) )
			: '';

		foreach ( $fields as $field_name => $field_value ) {
			$fields[ $field_name ] = isset( $_POST[ $field_name ] )
				? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) )
				: '';
		}

		$ramadan = isset( $_POST['ramadan'] ) ? 1 : 0;

		/*
		 * Validate the date.
		 */
		$date_object = DateTime::createFromFormat( 'Y-m-d', $prayer_date );

		$date_is_valid =
			$date_object &&
			$date_object->format( 'Y-m-d' ) === $prayer_date;

		if ( ! $date_is_valid ) {

			$message      = 'Please select a valid date.';
			$message_type = 'error';

		} else {

			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} WHERE prayer_date = %s",
					$prayer_date
				)
			);

			$data = array(
				'prayer_date'    => $prayer_date,
				'fajr_adhan'     => $fields['fajr_adhan'],
				'fajr_iqamah'    => $fields['fajr_iqamah'],
				'sunrise'        => $fields['sunrise'],
				'dhuhr_adhan'    => $fields['dhuhr_adhan'],
				'dhuhr_iqamah'   => $fields['dhuhr_iqamah'],
				'asr_adhan'      => $fields['asr_adhan'],
				'asr_iqamah'     => $fields['asr_iqamah'],
				'maghrib_adhan'  => $fields['maghrib_adhan'],
				'maghrib_iqamah' => $fields['maghrib_iqamah'],
				'isha_adhan'     => $fields['isha_adhan'],
				'isha_iqamah'    => $fields['isha_iqamah'],
				'jummah1'        => $fields['jummah1'],
				'jummah2'        => $fields['jummah2'],
				'jummah3'        => $fields['jummah3'],
				'ramadan'        => $ramadan,
				'updated_at'     => current_time( 'mysql' ),
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
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			);

			if ( $existing_id ) {

				$result = $wpdb->update(
					$table_name,
					$data,
					array(
						'id' => absint( $existing_id ),
					),
					$formats,
					array( '%d' )
				);

				if ( false === $result ) {
					$message      = 'The prayer times could not be updated. Please try again.';
					$message_type = 'error';
				} else {
					$message = 'Prayer times updated successfully for ' .
						wp_date( 'F j, Y', strtotime( $prayer_date ) ) .
						'.';
				}
			} else {

				$data['created_at'] = current_time( 'mysql' );
				$formats[]          = '%s';

				$result = $wpdb->insert(
					$table_name,
					$data,
					$formats
				);

				if ( false === $result ) {
					$message      = 'The prayer times could not be saved. Please try again.';
					$message_type = 'error';
				} else {
					$message = 'Prayer times saved successfully for ' .
						wp_date( 'F j, Y', strtotime( $prayer_date ) ) .
						'.';
				}
			}
		}
	}

	?>
	<div class="wrap">
		<h1>Add Prayer Times</h1>

		<p>
			Enter the Adhan and Iqamah times for one date. Saving an existing
			date will update that date instead of creating a duplicate.
		</p>

		<?php if ( ! empty( $message ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php
			wp_nonce_field(
				'mapt_save_prayer_times_action',
				'mapt_prayer_times_nonce'
			);
			?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="prayer_date">Date</label>
						</th>
						<td>
							<input
								type="date"
								id="prayer_date"
								name="prayer_date"
								value="<?php echo esc_attr( $prayer_date ); ?>"
								required
							>
						</td>
					</tr>
				</tbody>
			</table>

			<hr>

			<h2>Daily Prayer Times</h2>

			<table class="widefat striped" style="max-width: 850px;">
				<thead>
					<tr>
						<th>Prayer</th>
						<th>Adhan</th>
						<th>Iqamah</th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td><strong>Fajr</strong></td>
						<td>
							<input
								type="time"
								name="fajr_adhan"
								value="<?php echo esc_attr( $fields['fajr_adhan'] ); ?>"
							>
						</td>
						<td>
							<input
								type="time"
								name="fajr_iqamah"
								value="<?php echo esc_attr( $fields['fajr_iqamah'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<td><strong>Sunrise</strong></td>
						<td colspan="2">
							<input
								type="time"
								name="sunrise"
								value="<?php echo esc_attr( $fields['sunrise'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<td><strong>Dhuhr</strong></td>
						<td>
							<input
								type="time"
								name="dhuhr_adhan"
								value="<?php echo esc_attr( $fields['dhuhr_adhan'] ); ?>"
							>
						</td>
						<td>
							<input
								type="time"
								name="dhuhr_iqamah"
								value="<?php echo esc_attr( $fields['dhuhr_iqamah'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<td><strong>Asr</strong></td>
						<td>
							<input
								type="time"
								name="asr_adhan"
								value="<?php echo esc_attr( $fields['asr_adhan'] ); ?>"
							>
						</td>
						<td>
							<input
								type="time"
								name="asr_iqamah"
								value="<?php echo esc_attr( $fields['asr_iqamah'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<td><strong>Maghrib</strong></td>
						<td>
							<input
								type="time"
								name="maghrib_adhan"
								value="<?php echo esc_attr( $fields['maghrib_adhan'] ); ?>"
							>
						</td>
						<td>
							<input
								type="time"
								name="maghrib_iqamah"
								value="<?php echo esc_attr( $fields['maghrib_iqamah'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<td><strong>Isha</strong></td>
						<td>
							<input
								type="time"
								name="isha_adhan"
								value="<?php echo esc_attr( $fields['isha_adhan'] ); ?>"
							>
						</td>
						<td>
							<input
								type="time"
								name="isha_iqamah"
								value="<?php echo esc_attr( $fields['isha_iqamah'] ); ?>"
							>
						</td>
					</tr>
				</tbody>
			</table>

			<br>

			<h2>Friday Jumu'ah Times</h2>

			<p>
				These fields may be left empty on days other than Friday.
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="jummah1">Jumu'ah 1</label>
						</th>
						<td>
							<input
								type="time"
								id="jummah1"
								name="jummah1"
								value="<?php echo esc_attr( $fields['jummah1'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="jummah2">Jumu'ah 2</label>
						</th>
						<td>
							<input
								type="time"
								id="jummah2"
								name="jummah2"
								value="<?php echo esc_attr( $fields['jummah2'] ); ?>"
							>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="jummah3">Jumu'ah 3</label>
						</th>
						<td>
							<input
								type="time"
								id="jummah3"
								name="jummah3"
								value="<?php echo esc_attr( $fields['jummah3'] ); ?>"
							>
						</td>
					</tr>
				</tbody>
			</table>

			<hr>

			<h2>Ramadan</h2>

			<label for="ramadan">
				<input
					type="checkbox"
					id="ramadan"
					name="ramadan"
					value="1"
					<?php checked( $ramadan, 1 ); ?>
				>
				This date is during Ramadan
			</label>

			<?php
			submit_button(
				'Save Prayer Times',
				'primary',
				'mapt_save_prayer_times'
			);
			?>
		</form>
	</div>
	<?php
}
