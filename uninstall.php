<?php

// Runs only on plugin uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

require_once __DIR__ . '/includes/db.php'; // for wdj_mp_table()

$purge = get_option('wdj_mp_purge_on_uninstall', '0');
if ($purge === '1') {
    global $wpdb;
    $table = wdj_mp_table(); // your wp_{prefix}movies table
    $wpdb->query("DROP TABLE IF EXISTS $table");
    delete_option('wdj_mp_purge_on_uninstall');
}
