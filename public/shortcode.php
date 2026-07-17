<?php
/**
 * Public prayer-times shortcode.
 *
 * Shortcode:
 * [masjid_prayer_times]
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert a stored prayer time into a visitor-friendly format.
 *
 * Supports values such as:
 * 05:30
 * 05:30:00
 * 5:30 AM
 *
 * @param string|null $time Stored prayer time.
 * @return string
 */
function mapt_format_public_time($time)
{
    if (empty($time)) {
        return '—';
    }

    $time = trim((string) $time);

    $formats = [
        'H:i:s',
        'H:i',
        'h:i A',
        'g:i A',
        'h:i a',
        'g:i a',
    ];

    foreach ($formats as $format) {
        $date_time = DateTimeImmutable::createFromFormat(
            '!' . $format,
            $time,
            wp_timezone()
        );

        if ($date_time instanceof DateTimeImmutable) {
            return $date_time->format('g:i A');
        }
    }

    return esc_html($time);
}

/**
 * Get one prayer-times record by date.
 *
 * @param string $date Date in Y-m-d format.
 * @return object|null
 */
function mapt_get_public_prayer_record($date)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'masjid_prayer_times';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE prayer_date = %s LIMIT 1",
            $date
        )
    );
}

/**
 * Render one prayer row.
 *
 * @param string $name        Prayer name.
 * @param string $adhan       Adhan time.
 * @param string $iqamah      Iqamah time.
 * @param bool   $show_iqamah Whether to show the Iqamah column.
 * @return string
 */
function mapt_render_prayer_row($name, $adhan, $iqamah = '', $show_iqamah = true)
{
    ob_start();
    ?>
    <div class="mapt-prayer-row">
        <div class="mapt-prayer-name">
            <?php echo esc_html($name); ?>
        </div>

        <div class="mapt-prayer-time">
            <span class="mapt-mobile-label">Adhan</span>
            <?php echo esc_html(mapt_format_public_time($adhan)); ?>
        </div>

        <?php if ($show_iqamah) : ?>
            <div class="mapt-prayer-time">
                <span class="mapt-mobile-label">Iqamah</span>
                <?php echo esc_html(mapt_format_public_time($iqamah)); ?>
            </div>
        <?php else : ?>
            <div class="mapt-prayer-time mapt-empty-time">
                <span class="mapt-mobile-label">Iqamah</span>
                —
            </div>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * Render one day's prayer schedule.
 *
 * @param object|null       $record Prayer-times database record.
 * @param DateTimeImmutable $date   Date being displayed.
 * @param string            $label  Card label, such as Today or Tomorrow.
 * @return string
 */
function mapt_render_day_card($record, DateTimeImmutable $date, $label)
{
    ob_start();
    ?>
    <section class="mapt-day-card">
        <div class="mapt-day-heading">
            <div>
                <span class="mapt-day-label">
                    <?php echo esc_html($label); ?>
                </span>

                <h3>
                    <?php echo esc_html($date->format('l, F j, Y')); ?>
                </h3>
            </div>

            <?php if (!empty($record->ramadan)) : ?>
                <span class="mapt-ramadan-badge">
                    Ramadan
                </span>
            <?php endif; ?>
        </div>

        <?php if (!$record) : ?>
            <div class="mapt-no-data">
                Prayer times have not been entered for this date.
            </div>
        <?php else : ?>
            <div class="mapt-prayer-table">
                <div class="mapt-table-header">
                    <div>Prayer</div>
                    <div>Adhan</div>
                    <div>Iqamah</div>
                </div>

                <?php
                echo mapt_render_prayer_row(
                    'Fajr',
                    $record->fajr_adhan ?? '',
                    $record->fajr_iqamah ?? ''
                );

                echo mapt_render_prayer_row(
                    'Sunrise',
                    $record->sunrise ?? '',
                    '',
                    false
                );

                echo mapt_render_prayer_row(
                    'Dhuhr',
                    $record->dhuhr_adhan ?? '',
                    $record->dhuhr_iqamah ?? ''
                );

                echo mapt_render_prayer_row(
                    'Asr',
                    $record->asr_adhan ?? '',
                    $record->asr_iqamah ?? ''
                );

                echo mapt_render_prayer_row(
                    'Maghrib',
                    $record->maghrib_adhan ?? '',
                    $record->maghrib_iqamah ?? ''
                );

                echo mapt_render_prayer_row(
                    'Isha',
                    $record->isha_adhan ?? '',
                    $record->isha_iqamah ?? ''
                );
                ?>
            </div>

            <?php if ($date->format('N') === '5') : ?>
                <div class="mapt-jummah-section">
                    <h4>Jumu’ah Prayer Times</h4>

                    <div class="mapt-jummah-grid">
                        <div class="mapt-jummah-time">
                            <span>Jumu’ah 1</span>
                            <strong>
                                <?php
                                echo esc_html(
                                    mapt_format_public_time(
                                        $record->jummah1 ?? ''
                                    )
                                );
                                ?>
                            </strong>
                        </div>

                        <div class="mapt-jummah-time">
                            <span>Jumu’ah 2</span>
                            <strong>
                                <?php
                                echo esc_html(
                                    mapt_format_public_time(
                                        $record->jummah2 ?? ''
                                    )
                                );
                                ?>
                            </strong>
                        </div>

                        <div class="mapt-jummah-time">
                            <span>Jumu’ah 3</span>
                            <strong>
                                <?php
                                echo esc_html(
                                    mapt_format_public_time(
                                        $record->jummah3 ?? ''
                                    )
                                );
                                ?>
                            </strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php

    return ob_get_clean();
}

/**
 * Register the public prayer-times stylesheet.
 */
function mapt_enqueue_public_styles()
{
    if (!defined('MAPT_PLUGIN_URL')) {
        return;
    }

    wp_enqueue_style(
        'mapt-prayer-times',
        MAPT_PLUGIN_URL . 'public/css/prayer-times.css',
        [],
        defined('MAPT_VERSION') ? MAPT_VERSION : '1.0.0'
    );
}

/**
 * Render the prayer-times shortcode.
 *
 * @return string
 */
function mapt_prayer_times_shortcode()
{
    mapt_enqueue_public_styles();

    $timezone = wp_timezone();

    $today = new DateTimeImmutable('today', $timezone);
    $tomorrow = $today->modify('+1 day');

    $today_record = mapt_get_public_prayer_record(
        $today->format('Y-m-d')
    );

    $tomorrow_record = mapt_get_public_prayer_record(
        $tomorrow->format('Y-m-d')
    );

    ob_start();
    ?>
    <div class="mapt-public-wrapper">
        <div class="mapt-public-header">
            <div class="mapt-mosque-icon" aria-hidden="true">
                ☾
            </div>

            <div>
                <h2>Masjid Al-Falah</h2>
                <p>Daily Prayer Times</p>
            </div>
        </div>

        <div class="mapt-day-grid">
            <?php
            echo mapt_render_day_card(
                $today_record,
                $today,
                'Today'
            );

            echo mapt_render_day_card(
                $tomorrow_record,
                $tomorrow,
                'Tomorrow'
            );
            ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode(
    'masjid_prayer_times',
    'mapt_prayer_times_shortcode'
);
