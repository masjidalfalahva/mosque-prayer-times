<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Manage Prayer Times page.
 */
function mapt_render_manage_prayers_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die(
			esc_html__(
				'You do not have permission to access this page.',
				'masjid-prayer-times'
			)
		);
	}

	global $wpdb;

	$table_name = $wpdb->prefix . 'masjid_prayer_times';

	$message      = '';
	$message_type = 'success';

	/*
	 * Delete a prayer-times record.
	 */
	if (
		isset( $_GET['mapt_action'], $_GET['prayer_id'] ) &&
		'delete' === sanitize_key( wp_unslash( $_GET['mapt_action'] ) )
	) {

		$prayer_id = absint( $_GET['prayer_id'] );

		check_admin_referer(
			'mapt_delete_prayer_' . $prayer_id
		);

		$deleted = $wpdb->delete(
			$table_name,
			array(
				'id' => $prayer_id,
			),
			array(
				'%d',
			)
		);

		if ( false === $deleted ) {
			$message      = 'The prayer-times record could not be deleted.';
			$message_type = 'error';
		} elseif ( 0 === $deleted ) {
			$message      = 'The prayer-times record was not found.';
			$message_type = 'warning';
		} else {
			$message = 'Prayer times deleted successfully.';
		}
	}

	/*
	 * Get all saved prayer times.
	 */
	$prayer_records = $wpdb->get_results(
		"SELECT *
		FROM {$table_name}
		ORDER BY prayer_date DESC"
	);

	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Manage Prayer Times</h1>

		<a
			href="<?php echo esc_url(
				admin_url( 'admin.php?page=mapt-add-prayer' )
			); ?>"
			class="page-title-action"
		>
			Add Prayer Times
		</a>

		<hr class="wp-header-end">

		<?php if ( ! empty( $message ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( empty( $prayer_records ) ) : ?>

			<div class="notice notice-info">
				<p>
					No prayer times have been saved yet.
					<a href="<?php echo esc_url(
						admin_url( 'admin.php?page=mapt-add-prayer' )
					); ?>">
						Add your first prayer-times record.
					</a>
				</p>
			</div>

		<?php else : ?>

			<p>
				<strong>
					<?php echo esc_html( count( $prayer_records ) ); ?>
				</strong>
				saved prayer-time records.
			</p>

			<div style="overflow-x: auto;">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Date</th>
							<th>Fajr</th>
							<th>Sunrise</th>
							<th>Dhuhr</th>
							<th>Asr</th>
							<th>Maghrib</th>
							<th>Isha</th>
							<th>Jumu'ah</th>
							<th>Ramadan</th>
							<th>Actions</th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ( $prayer_records as $record ) : ?>

							<?php
							$edit_url = add_query_arg(
								array(
									'page'           => 'mapt-add-prayer',
									'mapt_edit_date' => $record->prayer_date,
								),
								admin_url( 'admin.php' )
							);

							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'page'        => 'mapt-manage-prayers',
										'mapt_action' => 'delete',
										'prayer_id'   => absint( $record->id ),
									),
									admin_url( 'admin.php' )
								),
								'mapt_delete_prayer_' . absint( $record->id )
							);
							?>

							<tr>
								<td>
									<strong>
										<?php echo esc_html( $record->prayer_date ); ?>
									</strong>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->fajr_adhan )
									);
									?>
									<br>
									<small>
										Iqamah:
										<?php
										echo esc_html(
											mapt_admin_display_time( $record->fajr_iqamah )
										);
										?>
									</small>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->sunrise )
									);
									?>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->dhuhr_adhan )
									);
									?>
									<br>
									<small>
										Iqamah:
										<?php
										echo esc_html(
											mapt_admin_display_time( $record->dhuhr_iqamah )
										);
										?>
									</small>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->asr_adhan )
									);
									?>
									<br>
									<small>
										Iqamah:
										<?php
										echo esc_html(
											mapt_admin_display_time( $record->asr_iqamah )
										);
										?>
									</small>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->maghrib_adhan )
									);
									?>
									<br>
									<small>
										Iqamah:
										<?php
										echo esc_html(
											mapt_admin_display_time( $record->maghrib_iqamah )
										);
										?>
									</small>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->isha_adhan )
									);
									?>
									<br>
									<small>
										Iqamah:
										<?php
										echo esc_html(
											mapt_admin_display_time( $record->isha_iqamah )
										);
										?>
									</small>
								</td>

								<td>
									<?php
									echo esc_html(
										mapt_admin_display_time( $record->jummah1 )
									);
									?>
									<br>

									<?php
									echo esc_html(
										mapt_admin_display_time( $record->jummah2 )
									);
									?>
									<br>

									<?php
									echo esc_html(
										mapt_admin_display_time( $record->jummah3 )
									);
									?>
								</td>

								<td>
									<?php if ( ! empty( $record->ramadan ) ) : ?>
										<span aria-label="Ramadan">🌙 Yes</span>
									<?php else : ?>
										No
									<?php endif; ?>
								</td>

								<td>
									<a
										href="<?php echo esc_url( $edit_url ); ?>"
										class="button button-small"
									>
										Edit
									</a>

									<a
										href="<?php echo esc_url( $delete_url ); ?>"
										class="button button-small"
										onclick="return confirm('Are you sure you want to delete these prayer times?');"
									>
										Delete
									</a>
								</td>
							</tr>

						<?php endforeach; ?>
					</tbody>

					<tfoot>
						<tr>
							<th>Date</th>
							<th>Fajr</th>
							<th>Sunrise</th>
							<th>Dhuhr</th>
							<th>Asr</th>
							<th>Maghrib</th>
							<th>Isha</th>
							<th>Jumu'ah</th>
							<th>Ramadan</th>
							<th>Actions</th>
						</tr>
					</tfoot>
				</table>
			</div>

		<?php endif; ?>
	</div>
	<?php
}

/**
 * Convert a stored 24-hour time into a readable 12-hour time.
 *
 * Example: 13:30 becomes 1:30 PM.
 */
function mapt_admin_display_time( $time ) {

	if ( empty( $time ) ) {
		return '—';
	}

	$time_object = DateTimeImmutable::createFromFormat(
		'!H:i',
		substr( $time, 0, 5 )
	);

	if ( ! $time_object ) {
		return $time;
	}

	return $time_object->format( 'g:i A' );
}
