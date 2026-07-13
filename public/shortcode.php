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

    ob_start();

    ?>

    <div class="mapt-prayer-card">

        <h2>Masjid Al-Falah Prayer Times</h2>

        <table class="mapt-prayer-table">

            <tr>
                <th>Prayer</th>
                <th>Adhan</th>
                <th>Iqamah</th>
            </tr>

            <tr>
                <td>Fajr</td>
                <td>5:15 AM</td>
                <td>5:45 AM</td>
            </tr>

            <tr>
                <td>Dhuhr</td>
                <td>1:15 PM</td>
                <td>1:30 PM</td>
            </tr>

            <tr>
                <td>Asr</td>
                <td>5:45 PM</td>
                <td>6:00 PM</td>
            </tr>

            <tr>
                <td>Maghrib</td>
                <td>Sunset</td>
                <td>10 min after Adhan</td>
            </tr>

            <tr>
                <td>Isha</td>
                <td>8:45 PM</td>
                <td>9:00 PM</td>
            </tr>

        </table>

    </div>

    <?php

    return ob_get_clean();

}


add_shortcode(
    'masjid_prayer_times',
    'mapt_display_prayer_times'
);
