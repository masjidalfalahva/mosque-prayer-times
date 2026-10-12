<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display full-screen Masjid TV prayer display.
 *
 * Usage:
 * [masjid_prayer_tv]
 */
function mapt_display_prayer_tv() {

	$prayers = mapt_get_prayer_times();

	if ( empty( $prayers ) ) {
		return '<p>No prayer schedule available.</p>';
	}

	$today = current_time( 'Y-m-d' );

	$today_schedule = null;

	foreach ( $prayers as $schedule ) {

		if ( $schedule['date'] === $today ) {
			$today_schedule = $schedule;
			break;
		}
	}

	if ( empty( $today_schedule ) ) {
		return '<p>No prayer schedule is available for today.</p>';
	}

	/*
	 * Find the next Friday with Jumu'ah times.
	 */
	$jummah_schedule = null;

	foreach ( $prayers as $schedule ) {

		if (
			$schedule['date'] >= $today &&
			date( 'N', strtotime( $schedule['date'] ) ) == 5 &&
			! empty( $schedule['jummah1'] )
		) {
			$jummah_schedule = $schedule;
			break;
		}
	}

	ob_start();
	?>

	<div class="mapt-tv-display">

		<header class="mapt-tv-header">

			<div>
				<h1>Masjid Al-Falah</h1>
				<p>Prayer Times</p>
			</div>

			<div class="mapt-tv-clock-area">
				<div id="mapt-tv-clock">--:--</div>

				<div class="mapt-tv-date">
					<?php
					echo esc_html(
						wp_date(
							'l, F j, Y',
							current_time( 'timestamp' )
						)
					);
					?>
				</div>
			</div>

		</header>

		<div class="mapt-tv-main">

			<section class="mapt-tv-prayers">

				<div class="mapt-tv-prayer-row">
					<div class="mapt-tv-prayer-name">Fajr</div>
					<div>
						<span>Adhan</span>
						<strong>
							<?php echo esc_html( $today_schedule['fajr_adhan'] ); ?>
						</strong>
					</div>
					<div>
						<span>Iqamah</span>
						<strong>
							<?php echo esc_html( $today_schedule['fajr_iqamah'] ); ?>
						</strong>
					</div>
				</div>

				<div class="mapt-tv-prayer-row">
					<div class="mapt-tv-prayer-name">Sunrise</div>
					<div>
						<span>Time</span>
						<strong>
							<?php echo esc_html( $today_schedule['sunrise'] ); ?>
						</strong>
					</div>
					<div>
						<span>&nbsp;</span>
						<strong>—</strong>
					</div>
				</div>

				<div class="mapt-tv-prayer-row">
					<div class="mapt-tv-prayer-name">Dhuhr</div>
					<div>
						<span>Adhan</span>
						<strong>
							<?php echo esc_html( $today_schedule['dhuhr_adhan'] ); ?>
						</strong>
					</div>
					<div>
						<span>Iqamah</span>
						<strong>
							<?php echo esc_html( $today_schedule['dhuhr_iqamah'] ); ?>
						</strong>
					</div>
				</div>

				<div class="mapt-tv-prayer-row">
					<div class="mapt-tv-prayer-name">Asr</div>
					<div>
						<span>Adhan</span>
						<strong>
							<?php echo esc_html( $today_schedule['asr_adhan'] ); ?>
						</strong>
					</div>
					<div>
						<span>Iqamah</span>
						<strong>
							<?php echo esc_html( $today_schedule['asr_iqamah'] ); ?>
						</strong>
					</div>
				</div>

				<div class="mapt-tv-prayer-row">
					<div class="mapt-tv-prayer-name">Maghrib</div>
					<div>
						<span>Adhan</span>
						<strong>
							<?php echo esc_html( $today_schedule['maghrib_adhan'] ); ?>
						</strong>
					</div>
					<div>
						<span>Iqamah</span>
						<strong>
							<?php echo esc_html( $today_schedule['maghrib_iqamah'] ); ?>
						</strong>
					</div>
				</div>

				<div class="mapt-tv-prayer-row">
					<div class="mapt-tv-prayer-name">Isha</div>
					<div>
						<span>Adhan</span>
						<strong>
							<?php echo esc_html( $today_schedule['isha_adhan'] ); ?>
						</strong>
					</div>
					<div>
						<span>Iqamah</span>
						<strong>
							<?php echo esc_html( $today_schedule['isha_iqamah'] ); ?>
						</strong>
					</div>
				</div>

			</section>

			<aside class="mapt-tv-side-panel">

				<div class="mapt-tv-next-prayer">
					<h2>Next Prayer</h2>
					<div id="mapt-tv-next-name">Calculating...</div>
					<div id="mapt-tv-next-time"></div>
					<div id="mapt-tv-countdown"></div>
				</div>

				<?php if ( ! empty( $jummah_schedule ) ) : ?>

					<div class="mapt-tv-jummah">

						<h2>Jumu'ah</h2>

						<p>
							<?php echo esc_html( $jummah_schedule['jummah1'] ); ?>
						</p>

						<?php if ( ! empty( $jummah_schedule['jummah2'] ) ) : ?>
							<p>
								<?php echo esc_html( $jummah_schedule['jummah2'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( ! empty( $jummah_schedule['jummah3'] ) ) : ?>
							<p>
								<?php echo esc_html( $jummah_schedule['jummah3'] ); ?>
							</p>
						<?php endif; ?>

					</div>

				<?php endif; ?>

			</aside>

		</div>

	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function () {

		const clock = document.getElementById('mapt-tv-clock');
		const nextName = document.getElementById('mapt-tv-next-name');
		const nextTime = document.getElementById('mapt-tv-next-time');
		const countdown = document.getElementById('mapt-tv-countdown');

		const prayerTimes = [
			{
				name: 'Fajr',
				time: '<?php echo esc_js( $today_schedule['fajr_adhan'] ); ?>'
			},
			{
				name: 'Dhuhr',
				time: '<?php echo esc_js( $today_schedule['dhuhr_adhan'] ); ?>'
			},
			{
				name: 'Asr',
				time: '<?php echo esc_js( $today_schedule['asr_adhan'] ); ?>'
			},
			{
				name: 'Maghrib',
				time: '<?php echo esc_js( $today_schedule['maghrib_adhan'] ); ?>'
			},
			{
				name: 'Isha',
				time: '<?php echo esc_js( $today_schedule['isha_adhan'] ); ?>'
			}
		];

		function parseTime(timeString) {

			const match = timeString.match(
				/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i
			);

			if (!match) {
				return null;
			}

			let hour = parseInt(match[1], 10);
			const minute = parseInt(match[2], 10);
			const period = match[3].toUpperCase();

			if (period === 'PM' && hour !== 12) {
				hour += 12;
			}

			if (period === 'AM' && hour === 12) {
				hour = 0;
			}

			const now = new Date();

			const result = new Date(
				now.getFullYear(),
				now.getMonth(),
				now.getDate(),
				hour,
				minute,
				0
			);

			return result;
		}

		function updateDisplay() {

			const now = new Date();

			clock.textContent = now.toLocaleTimeString(
				[],
				{
					hour: 'numeric',
					minute: '2-digit',
					second: '2-digit'
				}
			);

			let nextPrayer = null;

			for (const prayer of prayerTimes) {

				const prayerDate = parseTime(prayer.time);

				if (prayerDate && prayerDate > now) {
					nextPrayer = {
						name: prayer.name,
						time: prayer.time,
						date: prayerDate
					};

					break;
				}
			}

			if (!nextPrayer) {

				nextName.textContent = 'Fajr Tomorrow';
				nextTime.textContent = prayerTimes[0].time;
				countdown.textContent = '';

				return;
			}

			nextName.textContent = nextPrayer.name;
			nextTime.textContent = nextPrayer.time;

			const difference =
				nextPrayer.date.getTime() - now.getTime();

			const totalMinutes =
				Math.floor(difference / 60000);

			const hours =
				Math.floor(totalMinutes / 60);

			const minutes =
				totalMinutes % 60;

			if (hours > 0) {
				countdown.textContent =
					hours + ' hr ' + minutes + ' min';
			} else {
				countdown.textContent =
					minutes + ' min';
			}
		}

		updateDisplay();

		setInterval(
			updateDisplay,
			1000
		);

	});
	</script>

	<?php

	return ob_get_clean();
}

add_shortcode(
	'masjid_prayer_tv',
	'mapt_display_prayer_tv'
);
