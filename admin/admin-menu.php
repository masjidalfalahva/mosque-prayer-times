<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Prayer Times admin menu.
 */
function mapt_register_admin_menu() {

	add_menu_page(
		'Prayer Times',
		'Prayer Times',
		'manage_options',
		'mapt-dashboard',
		'mapt_dashboard_page',
		'dashicons-calendar-alt',
		25
	);

	add_submenu_page(
		'mapt-dashboard',
		'Dashboard',
		'Dashboard',
		'manage_options',
		'mapt-dashboard',
		'mapt_dashboard_page'
	);

	add_submenu_page(
		'mapt-dashboard',
		'Add Prayer Times',
		'Add Prayer Times',
		'manage_options',
		'mapt-add-prayer',
		'mapt_render_add_prayer_page'
	);

	add_submenu_page(
		'mapt-dashboard',
		'Manage Prayer Times',
		'Manage Prayer Times',
		'manage_options',
		'mapt-manage-prayers',
		'mapt_render_manage_prayers_page'
	);

	add_submenu_page(
		'mapt-dashboard',
		'Import Schedule',
		'Import Schedule',
		'manage_options',
		'mapt-import',
		'mapt_import_page'
	);

	add_submenu_page(
		'mapt-dashboard',
		'Jumu\'ah Schedule',
		'Jumu\'ah Schedule',
		'manage_options',
		'mapt-jummah-schedule',
		'mapt_render_jummah_schedule_page'
	);

	add_submenu_page(
		'mapt-dashboard',
		'Settings',
		'Settings',
		'manage_options',
		'mapt-settings',
		'mapt_settings_page'
	);
}

add_action( 'admin_menu', 'mapt_register_admin_menu' );

/**
 * Render the dashboard page.
 */
function mapt_dashboard_page() {
	?>
	<div class="wrap">
		<h1>🕌 Masjid Al-Falah Prayer Times</h1>

		<p>Welcome to the Prayer Times plugin.</p>

		<h2>Plugin Status</h2>

		<table class="widefat striped">
			<tr>
				<td><strong>Version</strong></td>
				<td><?php echo esc_html( MAPT_VERSION ); ?></td>
			</tr>

			<tr>
				<td><strong>Shortcode</strong></td>
				<td><code>[masjid_prayer_times]</code></td>
			</tr>

			<tr>
				<td><strong>Status</strong></td>
				<td>✅ Active</td>
			</tr>
		</table>
	</div>
	<?php
}

/**
 * Render the Jumu'ah Schedule page.
 */
function mapt_render_jummah_schedule_page() {

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

	$message = '';
	$error   = '';

	$start_date = '';
	$end_date   = '';
	$jummah1    = '';
	$jummah2    = '';
	$jummah3    = '';

	if ( isset( $_POST['mapt_save_jummah_schedule'] ) ) {

		check_admin_referer(
			'mapt_save_jummah_schedule_action',
			'mapt_save_jummah_schedule_nonce'
		);

		$start_date = isset( $_POST['mapt_start_date'] )
			? sanitize_text_field( wp_unslash( $_POST['mapt_start_date'] ) )
			: '';

		$end_date = isset( $_POST['mapt_end_date'] )
			? sanitize_text_field( wp_unslash( $_POST['mapt_end_date'] ) )
			: '';

		$jummah1 = isset( $_POST['mapt_jummah1'] )
			? sanitize_text_field( wp_unslash( $_POST['mapt_jummah1'] ) )
			: '';

		$jummah2 = isset( $_POST['mapt_jummah2'] )
			? sanitize_text_field( wp_unslash( $_POST['mapt_jummah2'] ) )
			: '';

		$jummah3 = isset( $_POST['mapt_jummah3'] )
			? sanitize_text_field( wp_unslash( $_POST['mapt_jummah3'] ) )
			: '';

		if (
			empty( $start_date ) ||
			empty( $end_date ) ||
			empty( $jummah1 ) ||
			empty( $jummah2 ) ||
			empty( $jummah3 )
		) {

			$error = 'Please complete every field before saving.';

		} elseif (
			! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ||
			! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date )
		) {

			$error = 'Please enter valid start and end dates.';

		} elseif ( strtotime( $start_date ) > strtotime( $end_date ) ) {

			$error = 'The start date cannot be later than the end date.';

		} elseif (
			! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $jummah1 ) ||
			! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $jummah2 ) ||
			! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $jummah3 )
		) {

			$error = 'Please enter valid Jumu\'ah times.';

		} else {

			$jummah1_object = DateTime::createFromFormat( 'H:i', $jummah1 );
			$jummah2_object = DateTime::createFromFormat( 'H:i', $jummah2 );
			$jummah3_object = DateTime::createFromFormat( 'H:i', $jummah3 );

			if (
				false === $jummah1_object ||
				false === $jummah2_object ||
				false === $jummah3_object
			) {

				$error = 'One or more Jumu\'ah times could not be processed.';

			} else {

				$jummah1_formatted = $jummah1_object->format( 'g:i A' );
				$jummah2_formatted = $jummah2_object->format( 'g:i A' );
				$jummah3_formatted = $jummah3_object->format( 'g:i A' );

				$update_result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table_name}
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

					$error = 'The Jumu\'ah schedule could not be saved because of a database error.';

				} elseif ( 0 === intval( $update_result ) ) {

					$error =
						'No Friday records were updated. Make sure prayer times have already been imported for the selected date range.';

				} else {

					$message = sprintf(
						'Jumu\'ah schedule saved successfully. %d Friday records were updated.',
						intval( $update_result )
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

	?>

	<div class="wrap">

		<h1>Jumu'ah Schedule</h1>

		<p>
			Choose a date range and enter the three Jumu'ah times.
			The plugin will update every Friday within that range.
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

			<h2>Apply Jumu'ah Times to a Date Range</h2>

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

							<p class="description">
								The first date this Jumu'ah schedule applies.
							</p>
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

							<p class="description">
								The final date this Jumu'ah schedule applies.
							</p>
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

	</div>

	<?php
}

/**
 * Render the Settings placeholder page.
 */
function mapt_settings_page() {
	echo '<div class="wrap"><h1>Settings</h1><p>Coming soon...</p></div>';
}
