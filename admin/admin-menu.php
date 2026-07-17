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
 * Render the Settings placeholder page.
 */
function mapt_settings_page() {
	echo '<div class="wrap"><h1>Settings</h1><p>Coming soon...</p></div>';
}
