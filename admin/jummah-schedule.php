<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the saved Jumu'ah Schedule manager page.
 *
 * This uses a unique function name so it will not conflict with
 * the current working function inside admin-menu.php.
 */
function mapt_render_jummah_schedule_manager_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die(
			esc_html__(
				'You do not have permission to access this page.',
				'masjid-prayer-times'
			)
		);
	}

	global $wpdb;

	$prayer_table = $wpdb->prefix . 'masjid_prayer_times';
	$jummah_table = $wpdb->prefix . 'masjid_jummah_schedules';

	$message = '';
	$error   = '';

	$start_date = '';
	$end_date   = '';
	$jummah1    = '';
	$jummah2    = '';
	$jummah3    = '';

	/*
	 * Save a new schedule.
	 */
	if ( isset( $_POST['mapt_save_jummah_schedule'] ) ) {

		check_admin_referer(
			'mapt_save_jummah_schedule_action',
			'mapt_save_jummah_schedule_nonce'
		);

		$start_date = isset( $_POST['mapt_start_date'] )
			? sanitize_text_field(
				wp_unslash( $_POST['mapt_start_date'] )
			)
			: '';

		$end_date = isset( $_POST['mapt_end_date'] )
			? sanitize_text_field(
				wp_unslash( $_POST['mapt_end_date'] )
			)
			: '';

		$jummah1 = isset( $_POST['mapt_jummah1'] )
			? sanitize_text_field(
				wp_unslash( $_POST['mapt_jummah1'] )
			)
			: '';

		$jummah2 = isset( $_POST['mapt_jummah2'] )
			? sanitize_text_field(
				wp_unslash( $_POST['mapt_jummah2'] )
			)
			: '';

		$jummah3 = isset( $_POST['mapt_jummah3'] )
			? sanitize_text_field(
				wp_unslash( $_POST['mapt_jummah3'] )
			)
			: '';

		if (
			empty( $start_date ) ||
			empty( $end_date ) ||
			empty( $jummah1 ) ||
			empty( $jummah2 ) ||
			empty( $jummah3 )
		) {

			$error = 'Please complete every field before saving.';

		} else {

			$start_date_object = DateTime::createFromFormat(
				'!Y-m-d',
				$start_date
			);

			$end_date_object = DateTime::createFromFormat(
				'!Y-m-d',
				$end_date
			);

			$jummah1_object = DateTime::createFromFormat(
				'!H:i',
				$jummah1
			);

			$jummah2_object = DateTime::createFromFormat(
				'!H:i',
				$jummah2
			);

			$jummah3_object = DateTime::createFromFormat(
				'!H:i',
				$jummah3
			);

			$dates_are_valid =
				false !== $start_date_object &&
				false !== $end_date_object &&
				$start_date_object->format( 'Y-m-d' ) === $start_date &&
				$end_date_object->format( 'Y-m-d' ) === $end_date;

			$times_are_valid =
				false !== $jummah1_object &&
				false !== $jummah2_object &&
				false !== $jummah3_object &&
				$jummah1_object->format( 'H:i' ) === $jummah1 &&
				$jummah2_object->format( 'H:i' ) === $jummah2 &&
				$jummah3_object->format( 'H:i' ) === $jummah3;

			if ( ! $dates_are_valid ) {

				$error = 'Please enter valid start and end dates.';

			} elseif ( $start_date_object > $end_date_object ) {

				$error = 'The start date cannot be later than the end date.';

			} elseif ( ! $times_are_valid ) {

				$error = 'Please enter valid Jumu\'ah times.';

			} else {

				/*
				 * Prevent overlapping date ranges.
				 */
				$overlapping_schedule = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id
						FROM {$jummah_table}
						WHERE start_date <= %s
						AND end_date >= %s
						LIMIT 1",
						$end_date,
						$start_date
					)
				);

				if ( $overlapping_schedule ) {

					$error =
						'This date range overlaps an existing Jumu\'ah schedule. Please choose different dates.';

				} else {

					/*
					 * Count Friday prayer records before saving.
					 */
					$friday_count = intval(
						$wpdb->get_var(
							$wpdb->prepare(
								"SELECT COUNT(*)
								FROM {$prayer_table}
								WHERE prayer_date BETWEEN %s AND %s
								AND DAYOFWEEK(prayer_date) = 6",
								$start_date,
								$end_date
							)
						)
					);

					if ( 0 === $friday_count ) {

						$error =
							'No Friday prayer records were found. Import the daily prayer schedule for this date range first.';

					} else {

						$jummah1_formatted =
							$jummah1_object->format( 'g:i A' );

						$jummah2_formatted =
							$jummah2_object->format( 'g:i A' );

						$jummah3_formatted =
							$jummah3_object->format( 'g:i A' );

						$wpdb->query( 'START TRANSACTION' );

						$insert_result = $wpdb->insert(
							$jummah_table,
							array(
								'start_date' => $start_date,
								'end_date'   => $end_date,
								'jummah1'    => $jummah1_formatted,
								'jummah2'    => $jummah2_formatted,
								'jummah3'    => $jummah3_formatted,
							),
							array(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
							)
						);

						if ( false === $insert_result ) {

							$wpdb->query( 'ROLLBACK' );

							$error =
								'The Jumu\'ah schedule could not be saved because of a database error.';

						} else {

							$update_result = $wpdb->query(
								$wpdb->prepare(
									"UPDATE {$prayer_table}
									SET jummah1 = %s,
										jummah2 = %s,
										jummah3 = %s
									WHERE prayer_date BETWEEN %s AND %s
									AND DAYOFWEEK(prayer_date) = 6",
									$jummah1_formatted,
									$jummah2_formatted,
									$jummah3_formatted,
									$start_date,
									$end_date
								)
							);

							if ( false === $update_result ) {

								$wpdb->query( 'ROLLBACK' );

								$error =
									'The Friday prayer records could not be updated because of a database error.';

							} else {

								$wpdb->query( 'COMMIT' );

								$message = sprintf(
									'Jumu\'ah schedule saved successfully. It applies to %d Friday records.',
									$friday_count
								);

								$start_date = '';
								$end_date   = '';
								$jummah1    = '';
								$jummah2    = '';
								$jummah3    = '';
							}
						}
					}
				}
			}
		}
	}

	/*
	 * Retrieve saved schedules.
	 */
	$saved_schedules = $wpdb->get_results(
		"SELECT
			id,
			start_date,
			end_date,
			jummah1,
			jummah2,
			jummah3
		FROM {$jummah_table}
		ORDER BY start_date ASC"
	);

	?>

	<div class="wrap">

		<h1>Jumu'ah Schedule</h1>

		<p>
			Create a schedule for a date range. The selected times will
			automatically be applied to every Friday in that range.
		</p>

		<?php if ( ! empty( $message ) ) : ?>

			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>

		<?php endif; ?>

		<?php if ( ! empty( $error ) ) : ?>

			<div class="notice notice-error">
				<p><?php echo esc_html( $error ); ?></p>
			</div>

		<?php endif; ?>

		<div
			class="card"
			style="max-width:750px;padding:24px;margin-top:20px;"
		>

			<h2>Add New Jumu'ah Schedule</h2>

			<form method="post">

				<?php
				wp_nonce_field(
					'mapt_save_jummah_schedule_action',
					'mapt_save_jummah_schedule_nonce'
				);
				?>

				<table class="form-table">

					<tr>
						<th scope="row">
							<label for="mapt_start_date">
								Start Date
							</label>
						</th>

						<td>
							<input
								type="date"
								id="mapt_start_date"
								name="mapt_start_date"
								value="<?php echo esc_attr( $start_date ); ?>"
								required
							>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="mapt_end_date">
								End Date
							</label>
						</th>

						<td>
							<input
								type="date"
								id="mapt_end_date"
								name="mapt_end_date"
								value="<?php echo esc_attr( $end_date ); ?>"
								required
							>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="mapt_jummah1">
								Jumu'ah 1
							</label>
						</th>

						<td>
							<input
								type="time"
								id="mapt_jummah1"
								name="mapt_jummah1"
								value="<?php echo esc_attr( $jummah1 ); ?>"
								required
							>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="mapt_jummah2">
								Jumu'ah 2
							</label>
						</th>

						<td>
							<input
								type="time"
								id="mapt_jummah2"
								name="mapt_jummah2"
								value="<?php echo esc_attr( $jummah2 ); ?>"
								required
							>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="mapt_jummah3">
								Jumu'ah 3
							</label>
						</th>

						<td>
							<input
								type="time"
								id="mapt_jummah3"
								name="mapt_jummah3"
								value="<?php echo esc_attr( $jummah3 ); ?>"
								required
							>
						</td>
					</tr>

				</table>

				<?php
				submit_button(
					'Save Jumu\'ah Schedule',
					'primary',
					'mapt_save_jummah_schedule'
				);
				?>

			</form>

		</div>

		<h2 style="margin-top:35px;">
			Saved Jumu'ah Schedules
		</h2>

		<?php if ( empty( $saved_schedules ) ) : ?>

			<div
				class="notice notice-info inline"
				style="max-width:750px;"
			>
				<p>No saved Jumu'ah schedules were found.</p>
			</div>

		<?php else : ?>

			<table
				class="widefat striped"
				style="max-width:950px;"
			>

				<thead>
					<tr>
						<th>Start Date</th>
						<th>End Date</th>
						<th>Jumu'ah 1</th>
						<th>Jumu'ah 2</th>
						<th>Jumu'ah 3</th>
					</tr>
				</thead>

				<tbody>

					<?php foreach ( $saved_schedules as $schedule ) : ?>

						<tr>
							<td>
								<?php
								echo esc_html(
									wp_date(
										'F j, Y',
										strtotime( $schedule->start_date )
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									wp_date(
										'F j, Y',
										strtotime( $schedule->end_date )
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html( $schedule->jummah1 );
								?>
							</td>

							<td>
								<?php
								echo esc_html( $schedule->jummah2 );
								?>
							</td>

							<td>
								<?php
								echo esc_html( $schedule->jummah3 );
								?>
							</td>
						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

		<?php endif; ?>

	</div>

	<?php
}
