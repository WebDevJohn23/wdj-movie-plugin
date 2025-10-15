<?php
if ( ! defined('ABSPATH') ) exit;

function wdj_mp_table() {
    global $wpdb;
    return $wpdb->prefix . 'wdj_movies_data';
}

function wdj_mp_install_movies_table() {
    global $wpdb;
    $table_name = wdj_mp_table();

    if ( $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $table_name) ) !== $table_name ) {
        $sql = "CREATE TABLE $table_name (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(64) NULL,
            `featureTitle` VARCHAR(255) NOT NULL,
            `posterSrc` VARCHAR(512) NULL,
            `moviePicture` VARCHAR(512) NULL,
            `url` VARCHAR(512) NULL,
            `weight` INT NULL DEFAULT 0,
            `dateStarted` DATETIME NULL,
            `theater` VARCHAR(255) NULL,
            `watched` TINYINT(1) NOT NULL DEFAULT 0,
            `misc` TEXT NULL,
            PRIMARY KEY (`id`),
            KEY `featureTitle_idx` (`featureTitle`),
            KEY `dateStarted_idx` (`dateStarted`),
            KEY `code_idx` (`code`),
            KEY `theater_idx` (`theater`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
