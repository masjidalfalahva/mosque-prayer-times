<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Import Schedule admin page.
 */
function mapt_import_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die(
			esc_html__(
				'You do not have permission to access this page.',
				'masjid-prayer-times'
			)
		);
	}

	$message = '';
	$error   = '';

	if ( isset( $_POST['mapt_import_submit'] ) ) {

		check_admin_referer(
			'mapt_import_schedule_action',
			'mapt_import_schedule_nonce'
		);

		if (
			empty( $_FILES['mapt_schedule_file'] ) ||
			empty( $_FILES['mapt_schedule_file']['name'] )
		) {

			$error = 'Please choose a Word document before clicking Import Schedule.';

		} else {

			$file = $_FILES['mapt_schedule_file'];

			if ( ! empty( $file['error'] ) ) {

				$error =
					'The file could not be uploaded. Upload error code: ' .
					intval( $file['error'] );

			} else {

				$extension = strtolower(
					pathinfo(
						sanitize_file_name( $file['name'] ),
						PATHINFO_EXTENSION
					)
				);

				if ( 'docx' !== $extension ) {

					$error = 'Please upload a .docx Word document.';

				} else {

					require_once MAPT_PLUGIN_DIR . 'includes/docx-importer.php';

					$rows = mapt_read_docx_table_rows(
						$file['tmp_name']
					);

					if ( is_wp_error( $rows ) ) {

						$error = $rows->get_error_message();

					} else {

						$records = mapt_parse_prayer_rows( $rows );

						if ( 365 !== count( $records ) ) {

							$error = sprintf(
								'The document contained %d table rows, but only %d daily prayer records were recognized. Nothing was saved.',
								count( $rows ),
								count( $records )
							);

						} else {

							$import_results =
								mapt_save_prayer_records( $records );

							if ( $import_results['errors'] > 0 ) {

								$error = sprintf(
									'The document was read, but the daily prayer import had problems. Added: %d. Updated: %d. Errors: %d.',
									$import_results['added'],
									$import_results['updated'],
									$import_results['errors']
								);

							} else {

								$jummah_schedules =
									mapt_parse_jummah_schedules( $rows );

								$jummah_results =
									mapt_save_jummah_schedules(
										$jummah_schedules
									);

								if ( $jummah_results['errors'] > 0 ) {

									$error = sprintf(
										'Daily prayer times were imported, but the Jumu\'ah update had problems. Daily records added: %d. Daily records updated: %d. Jumu\'ah schedules recognized: %d. Fridays updated: %d. Jumu\'ah errors: %d.',
										$import_results['added'],
										$import_results['updated'],
										$jummah_results['schedules'],
										$jummah_results['fridays_updated'],
										$jummah_results['errors']
									);

								} elseif (
									empty( $jummah_schedules )
								) {

									$error =
										'Daily prayer times were imported successfully, but no Jumu\'ah schedules were recognized in the document.';

								} else {

									$message = sprintf(
										'Import complete! 365 prayer records were recognized. Added: %d. Updated: %d. Jumu\'ah schedules recognized: %d. Fridays updated: %d. Errors: 0.',
										$import_results['added'],
										$import_results['updated'],
										$jummah_results['schedules'],
										$jummah_results['fridays_updated']
									);
								}
							}
						}
					}
				}
			}
		}
	}

	?>

	<div class="wrap">

		<h1>Import Prayer Schedule</h1>

		<p>
			Upload the annual Masjid Al-Falah prayer schedule in
			<strong>.docx</strong> format.
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
			style="max-width:700px;padding:24px;margin-top:20px;"
		>

			<h2>Upload Word Schedule</h2>

			<p>
				Choose your yearly prayer schedule, such as
				<code>2026 Prayer Schedule.docx</code>.
			</p>

			<form
				method="post"
				enctype="multipart/form-data"
			>

				<?php
				wp_nonce_field(
					'mapt_import_schedule_action',
					'mapt_import_schedule_nonce'
				);
				?>

				<table class="form-table">

					<tr>
						<th scope="row">
							<label for="mapt_schedule_file">
								Word document
							</label>
						</th>

						<td>
							<input
								type="file"
								id="mapt_schedule_file"
								name="mapt_schedule_file"
								accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
								required
							>

							<p class="description">
								Accepted format: .docx
							</p>
						</td>
					</tr>

				</table>

				<?php
				submit_button(
					'Import Schedule',
					'primary',
					'mapt_import_submit'
				);
				?>

			</form>

		</div>

	</div>

	<?php
}
