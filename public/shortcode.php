<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Display prayer times shortcode
 *
 * Usage:
 * [masjid_prayer_times]
 */
function mapt_display_prayer_times() {

    $prayers = mapt_get_prayer_times();

    if ( empty( $prayers ) ) {

        return '<p>No prayer schedule available.</p>';

    }


  // Get today's and tomorrow's dates
$today = current_time('Y-m-d');

$tomorrow = date(
    'Y-m-d',
    strtotime('+1 day', strtotime($today))
);

$today_schedule = null;
$tomorrow_schedule = null;


  foreach ( $prayers as $schedule ) {

    if ( $schedule['date'] == $today ) {
        $today_schedule = $schedule;
    }

    if ( $schedule['date'] == $tomorrow ) {
        $tomorrow_schedule = $schedule;
    }

}


    // If today's date is not found, use first available record
    if ( empty( $today_schedule ) ) {

        $today_schedule = $prayers[0];

    }

    if ( empty( $tomorrow_schedule ) ) {

    $tomorrow_schedule = $today_schedule;

}


    ob_start();

?>

<div class="mapt-prayer-card">

    <h2>🕌 Masjid Al-Falah Prayer Times</h2>

    <p style="text-align:center;font-size:18px;color:#003366;margin-top:-10px;margin-bottom:20px;">
        <?php echo date_i18n('l, F j, Y'); ?>
    </p>

    <h3 style="text-align:center;color:#C8A646;">TODAY</h3>

    <table class="mapt-prayer-table">

        <tr>
            <th>Prayer</th>
            <th>Adhan</th>
            <th>Iqamah</th>
        </tr>

        <tr>
            <td>Fajr</td>
            <td><?php echo esc_html($today_schedule['fajr_adhan']); ?></td>
            <td><?php echo esc_html($today_schedule['fajr_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Sunrise</td>
            <td><?php echo esc_html($today_schedule['sunrise']); ?></td>
            <td>—</td>
        </tr>

        <tr>
            <td>Dhuhr</td>
            <td><?php echo esc_html($today_schedule['dhuhr_adhan']); ?></td>
            <td><?php echo esc_html($today_schedule['dhuhr_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Asr</td>
            <td><?php echo esc_html($today_schedule['asr_adhan']); ?></td>
            <td><?php echo esc_html($today_schedule['asr_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Maghrib</td>
            <td><?php echo esc_html($today_schedule['maghrib_adhan']); ?></td>
            <td><?php echo esc_html($today_schedule['maghrib_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Isha</td>
            <td><?php echo esc_html($today_schedule['isha_adhan']); ?></td>
            <td><?php echo esc_html($today_schedule['isha_iqamah']); ?></td>
        </tr>

    </table>

    <hr style="margin:25px 0;">

    <h3 style="text-align:center;color:#C8A646;">TOMORROW</h3>

    <table class="mapt-prayer-table">

        <tr>
            <th>Prayer</th>
            <th>Adhan</th>
            <th>Iqamah</th>
        </tr>

        <tr>
            <td>Fajr</td>
            <td><?php echo esc_html($tomorrow_schedule['fajr_adhan']); ?></td>
            <td><?php echo esc_html($tomorrow_schedule['fajr_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Sunrise</td>
            <td><?php echo esc_html($tomorrow_schedule['sunrise']); ?></td>
            <td>—</td>
        </tr>

        <tr>
            <td>Dhuhr</td>
            <td><?php echo esc_html($tomorrow_schedule['dhuhr_adhan']); ?></td>
            <td><?php echo esc_html($tomorrow_schedule['dhuhr_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Asr</td>
            <td><?php echo esc_html($tomorrow_schedule['asr_adhan']); ?></td>
            <td><?php echo esc_html($tomorrow_schedule['asr_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Maghrib</td>
            <td><?php echo esc_html($tomorrow_schedule['maghrib_adhan']); ?></td>
            <td><?php echo esc_html($tomorrow_schedule['maghrib_iqamah']); ?></td>
        </tr>

        <tr>
            <td>Isha</td>
            <td><?php echo esc_html($tomorrow_schedule['isha_adhan']); ?></td>
            <td><?php echo esc_html($tomorrow_schedule['isha_iqamah']); ?></td>
        </tr>

    </table>

    <?php if ( date('N', strtotime($today_schedule['date'])) == 5 ) : ?>

        <hr style="margin:25px 0;">

        <h3 style="text-align:center;color:#C8A646;">JUMU'AH</h3>

        <p style="text-align:center;font-size:18px;font-weight:bold;">

            <?php echo esc_html($today_schedule['jummah1']); ?>

            <?php if (!empty($today_schedule['jummah2'])) : ?>
                • <?php echo esc_html($today_schedule['jummah2']); ?>
            <?php endif; ?>

            <?php if (!empty($today_schedule['jummah3'])) : ?>
                • <?php echo esc_html($today_schedule['jummah3']); ?>
            <?php endif; ?>

        </p>

    <?php endif; ?>

</div>


<?php

return ob_get_clean();

}


add_shortcode(
    'masjid_prayer_times',
    'mapt_display_prayer_times'
);
