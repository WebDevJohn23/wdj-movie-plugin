<?php
if ( ! defined('ABSPATH') ) exit;

add_action('admin_menu', function(){
    add_options_page('WDJ Movie Plugin','WDJ Movie Plugin','manage_options','wdj-movie-plugin','wdj_mp_admin_page');
});

function wdj_mp_admin_page() {
    if ( isset($_POST['wdj_mp_refresh']) && check_admin_referer('wdj_mp_refresh') ) {
        $theaters = array(
            'https://www.regmovies.com/theatres/regal-cielo-vista-0765',
            'https://www.regmovies.com/theatres/regal-huebner-oaks-0581',
            'https://www.regmovies.com/theatres/regal-fiesta-san-antonio-0938',
            'https://www.regmovies.com/theatres/regal-alamo-quarry-0939',
            'https://www.regmovies.com/theatres/regal-northwoods-0940',
            'https://www.regmovies.com/theatres/regal-live-oak-0795',
        );
        foreach ($theaters as $url) wdj_mp_refresh_from_theater_page($url);
        echo '<div class="notice notice-success"><p>Refresh complete.</p></div>';
    }

    echo '<div class="wrap"><h1>WDJ Movie Plugin</h1>';
    echo '<form method="post">';
    wp_nonce_field('wdj_mp_refresh');
    submit_button('Refresh Theater Data','primary','wdj_mp_refresh');
    echo '</form>';
    echo '<p>Shortcode: <code>[wdj_movies]</code> or <code>[wdj_movies status="0|1|2"]</code></p>';
    echo '</div>';

    echo '<h2>Currently Stored Movies</h2>';
    $movies = wdj_mp_get_movies_by_status(0);
    if ( $movies ) {
        echo '<table class="widefat striped"><thead><tr><th>Poster</th><th>Title</th><th>Date</th><th>Theaters</th></tr></thead><tbody>';
        foreach ($movies as $m) {
            $date = $m->dateStarted ? date_i18n('m/d/Y', strtotime($m->dateStarted)) : '';
            echo '<tr>';
            echo '<td><img src="' . esc_url($m->posterSrc) . '" style="height:100px" /></td>';
            echo '<td>' . esc_html($m->featureTitle) . '</td>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td>' . esc_html($m->theaters) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No movies found.</p>';
    }
    echo '</div>';


    // Danger zone: toggle purge-on-uninstall
    if ( isset($_POST['wdj_mp_toggle_purge']) && check_admin_referer('wdj_mp_toggle_purge') ) {
        update_option('wdj_mp_purge_on_uninstall', isset($_POST['purge_confirm']) ? '1' : '0');
        echo '<div class="notice notice-success"><p>Purge on uninstall '
            . (get_option('wdj_mp_purge_on_uninstall','0')==='1' ? 'ENABLED' : 'DISABLED')
            . '.</p></div>';
    }

    echo '<hr><h2 style="color:#b32d2e">Danger zone</h2>';
    echo '<form method="post" onsubmit="return confirm(\'This controls table deletion on uninstall. Continue?\');">';
    wp_nonce_field('wdj_mp_toggle_purge');
    $checked = get_option('wdj_mp_purge_on_uninstall','0')==='1' ? 'checked' : '';
    echo '<label><input type="checkbox" name="purge_confirm" value="1" ' . $checked . '> ';
    echo 'Delete the <code>' . esc_html( wdj_mp_table() ) . '</code> table when this plugin is uninstalled.</label><br><br>';
    submit_button('Save setting', 'secondary', 'wdj_mp_toggle_purge', false);
    echo '</form>';



}
