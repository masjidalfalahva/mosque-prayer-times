<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert a stored Jumu'ah time such as "1:40 PM" to an HTML time value.
 */
function mapt_jummah_time_for_input( $time ) {

	if ( empty( $time ) ) {
		return '';
	}

	$timestamp = strtotime( $time );

	if ( false === $timestamp ) {
		return '';
	}

	return wp_date( 'H:i', $timestamp );
}

/**
 * Check whether a Jumu'ah date range overlaps another saved schedule.
 */
function mapt_jummah_range_overlaps( $start_date, $end_date, $exclude_id = 0 ) {

	global $wpdb;

	$jummah_table = $wpdb->prefix . 'masjid_jummah_schedules';

	$sql = "SELECT id, start_date, end_date FROM {$jummah_table}";

	if ( $exclude_id > 0 ) {
		$sql .= $wpdb->prepare( ' WHERE id != %d', $exclude_id );
	}

	$existing_schedules = $wpdb->get_results( $sql );

	foreach ( $existing_schedules as $schedule ) {

		if (
			$start_date <= $schedule->end_date &&
			$end_date >= $schedule->start_date
		) {
			return true;
		}
	}

	return false;
}

/**
 * Clear Jumu'ah times from Friday prayer records in a date range.
 */
function mapt_clear_jummah_range( $start_date, $end_date ) {

	global $wpdb;

	$prayer_table = $wpdb->prefix . 'masjid_prayer_times';

	return $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$prayer_table}
			SET jummah1 = '',
				jummah2 = '',
				jummah3 = ''
			WHERE prayer_date BETWEEN %s AND %s
			AND DAYOFWEEK(prayer_date) = 6",
			$start_date,
			$end_date
		)
	);
}

/**
 * Apply Jumu'ah times to Friday prayer records in a date range.
 */
function mapt_apply_jummah_range(
	$start_date,
	$end_date,
	$jummah1,
	$jummah2,
	$jummah3
) {

	global $wpdb;

	$prayer_table = $wpdb->prefix . 'masjid_prayer_times';

	return $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$prayer_table}
			SET jummah1 = %s,
				jummah2 = %s,
				jummah3 = %s
			WHERE prayer_date BETWEEN %s AND %s
			AND DAYOFWEEK(prayer_date) = 6",
			$jummah1,
			$jummah2,
			$jummah3,
			$start_date,
			$end_date
		)
	);
}

/**
 * Render the Jumu'ah Schedule manager page.
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

	$editing_id = 0;
	$start_date = '';
	$end_date   = '';
	$jummah1    = '';
	$jummah2    = '';
	$jummah3    = '';

	/*
	 * Delete a saved schedule.
	 */
	if ( isset( $_POST['mapt_delete_jummah_schedule'] ) ) {

		check_admin_referer(
			'mapt_delete_jummah_schedule_action',
			'mapt_delete_jummah_schedule_nonce'
		);

		$delete_id = isset( $_POST['mapt_schedule_id'] )
			? absint( $_POST['mapt_schedule_id'] )
			: 0;

		$schedule_to_delete = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$jummah_table} WHERE id = %d",
				$delete_id
			)
		);

		if ( ! $schedule_to_delete ) {

			$error = 'The Jumu\'ah schedule could not be found.';

		} else {

			$wpdb->query( 'START TRANSACTION' );

			$clear_result = mapt_clear_jummah_range(
				$schedule_to_delete->start_date,
				$schedule_to_delete->end_date
			);

			$delete_result = $wpdb->delete(
				$jummah_table,
				array( 'id' => $delete_id ),
				array( '%d' )
			);

			if ( false === $clear_result || false === $delete_result ) {

				$wpdb->query( 'ROLLBACK' );

				$error =
					'The Jumu\'ah schedule could not be deleted because of a database error.';

			} else {

				$wpdb->query( 'COMMIT' );

				$message =
					'Jumu\'ah schedule deleted successfully.';
			}
		}
	}

	/*
	 * Load a schedule into the form for editing.
	 */
	if (
		isset( $_GET['mapt_action'], $_GET['schedule_id'] ) &&
		'edit' === sanitize_key(
			wp_unslash( $_GET['mapt_action'] )
		)
	) {

		$editing_id = absint( $_GET['schedule_id'] );

		$editing_schedule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$jummah_table} WHERE id = %d",
				$editing_id
			)
		);

		if ( $editing_schedule ) {

			$start_date = $editing_schedule->start_date;
			$end_date   = $editing_schedule->end_date;

			$jummah1 =
				mapt_jummah_time_for_input(
					$editing_schedule->jummah1
				);

			$jummah2 =
				mapt_jummah_time_for_input(
					$editing_schedule->jummah2
				);

			$jummah3 =
				mapt_jummah_time_for_input(
					$editing_schedule->jummah3
				);

		} else {

			$editing_id = 0;

			$error =
				'The Jumu\'ah schedule you tried to edit could not be found.';
		}
	}

	/*
	 * Save a new schedule or update an existing schedule.
	 */
	if ( isset( $_POST['mapt_save_jummah_schedule'] ) ) {

		check_admin_referer(
			'mapt_save_jummah_schedule_action',
			'mapt_save_jummah_schedule_nonce'
		);

		$editing_id = isset( $_POST['mapt_schedule_id'] )
			? absint( $_POST['mapt_schedule_id'] )
			: 0;

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

			$error =
				'Please complete every field before saving.';

		} else {

			$start_date_object =
				DateTime::createFromFormat(
					'!Y-m-d',
					$start_date
				);

			$end_date_object =
				DateTime::createFromFormat(
					'!Y-m-d',
					$end_date
				);

			$jummah1_object =
				DateTime::createFromFormat(
					'!H:i',
					$jummah1
				);

			$jummah2_object =
				DateTime::createFromFormat(
					'!H:i',
					$jummah2
				);

			$jummah3_object =
				DateTime::createFromFormat(
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

				$error =
					'Please enter valid start and end dates.';

			} elseif ( $start_date_object > $end_date_object ) {

				$error =
					'The start date cannot be later than the end date.';

			} elseif ( ! $times_are_valid ) {

				$error =
					'Please enter valid Jumu\'ah times.';

			} elseif (
				mapt_jummah_range_overlaps(
					$start_date,
					$end_date,
					$editing_id
				)
			) {

				$error =
					'This date range overlaps an existing Jumu\'ah schedule. Back-to-back ranges are allowed, but the same date cannot belong to two schedules.';

			} else {

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

					$old_schedule = null;

					if ( $editing_id > 0 ) {

						$old_schedule = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT *
								FROM {$jummah_table}
								WHERE id = %d",
								$editing_id
							)
						);

						if ( ! $old_schedule ) {

							$error =
								'The Jumu\'ah schedule you tried to update could not be found.';
						}
					}

					if ( empty( $error ) ) {

						$wpdb->query(
							'START TRANSACTION'
						);

						if ( $editing_id > 0 ) {

							$clear_result =
								mapt_clear_jummah_range(
									$old_schedule->start_date,
									$old_schedule->end_date
								);

							$save_result =
								$wpdb->update(
									$jummah_table,
									array(
										'start_date' => $start_date,
										'end_date'   => $end_date,
										'jummah1'    => $jummah1_formatted,
										'jummah2'    => $jummah2_formatted,
										'jummah3'    => $jummah3_formatted,
									),
									array(
										'id' => $editing_id,
									),
									array(
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
									),
									array(
										'%d',
									)
								);

						} else {

							$clear_result = true;

							$save_result =
								$wpdb->insert(
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
						}

						$apply_result =
							mapt_apply_jummah_range(
								$start_date,
								$end_date,
								$jummah1_formatted,
								$jummah2_formatted,
								$jummah3_formatted
							);

						if (
							false === $clear_result ||
							false === $save_result ||
							false === $apply_result
						) {

							$wpdb->query(
								'ROLLBACK'
							);

							$error =
								'The Jumu\'ah schedule could not be saved because of a database error.';

						} else {

							$wpdb->query(
								'COMMIT'
							);

							if ( $editing_id > 0 ) {

								$message = sprintf(
									'Jumu\'ah schedule updated successfully. It applies to %d Friday records.',
									$friday_count
								);

							} else {

								$message = sprintf(
									'Jumu\'ah schedule saved successfully. It applies to %d Friday records.',
									$friday_count
								);
							}

							$editing_id = 0;
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

	/*
	 * Load all saved schedules.
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
			Create date-range schedules for Jumu'ah.
			Each saved schedule is automatically applied
			to every Friday in its range.
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

			<h2>
				<?php
				echo $editing_id > 0
					? 'Edit Jumu\'ah Schedule'
					: 'Add New Jumu\'ah Schedule';
				?>
			</h2>

			<form method="post">

				<?php
				wp_nonce_field(
					'mapt_save_jummah_schedule_action',
					'mapt_save_jummah_schedule_nonce'
				);
				?>

				<input
					type="hidden"
					name="mapt_schedule_id"
					value="<?php echo esc_attr( $editing_id ); ?>"
				>

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
					$editing_id > 0
						? 'Update Jumu\'ah Schedule'
						: 'Save Jumu\'ah Schedule',
					'primary',
					'mapt_save_jummah_schedule'
				);
				?>

				<?php if ( $editing_id > 0 ) : ?>

					<a
						class="button"
						href="<?php echo esc_url(
							admin_url(
								'admin.php?page=mapt-jummah-schedule'
							)
						); ?>"
					>
						Cancel Edit
					</a>

				<?php endif; ?>

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
				<p>
					No saved Jumu'ah schedules were found.
				</p>
			</div>

		<?php else : ?>

			<table
				class="widefat striped"
				style="max-width:1050px;"
			>

				<thead>
					<tr>
						<th>Start Date</th>
						<th>End Date</th>
						<th>Jumu'ah 1</th>
						<th>Jumu'ah 2</th>
						<th>Jumu'ah 3</th>
						<th>Actions</th>
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
										strtotime(
											$schedule->start_date
										)
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									wp_date(
										'F j, Y',
										strtotime(
											$schedule->end_date
										)
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$schedule->jummah1
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$schedule->jummah2
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$schedule->jummah3
								);
								?>
							</td>

							<td>

								<a
									class="button button-small"
									href="<?php
									echo esc_url(
										add_query_arg(
											array(
												'page' =>
													'mapt-jummah-schedule',
												'mapt_action' =>
													'edit',
												'schedule_id' =>
													absint(
														$schedule->id
													),
											),
											admin_url(
												'admin.php'
											)
										)
									);
									?>"
								>
									Edit
								</a>

								<form
									method="post"
									style="display:inline-block;margin-left:6px;"
									onsubmit="return confirm('Delete this Jumu\'ah schedule and clear its Friday times?');"
								>

									<?php
									wp_nonce_field(
										'mapt_delete_jummah_schedule_action',
										'mapt_delete_jummah_schedule_nonce'
									);
									?>

									<input
										type="hidden"
										name="mapt_schedule_id"
										value="<?php
										echo esc_attr(
											$schedule->id
										);
										?>"
									>

									<button
										type="submit"
										name="mapt_delete_jummah_schedule"
										class="button button-small button-link-delete"
									>
										Delete
									</button>

								</form>

							</td>

						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

		<?php endif; ?>

	</div>

	<?php
}
