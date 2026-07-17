<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Load prayer schedule
 */
function mapt_get_prayer_times() {

    $file = MAPT_PLUGIN_DIR . 'data/prayer-schedule.csv';

    if ( ! file_exists( $file ) ) {
        return array();
    }

    $rows = array();

    if (($handle = fopen($file, "r")) !== FALSE) {

        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {

            $rows[] = array_combine(
                $headers,
                $data
            );

        }

        fclose($handle);
    }

    return $rows;

}
