<?php
// variables
$menuSlug = 'wdj-movie-plugin';


function wdj_movie_plugin_adminmenu() {
    add_options_page('WDJ Movie Plugin', 'WDJ Movie Plugin', 'manage_options', 'wdj-movie-plugin', 'wdj_movie_plugin_adminpage');
}

// adding above function to wordpress
add_action('admin_menu', 'wdj_movie_plugin_adminmenu');


// admin page
function wdj_movie_plugin_adminpage() {
    echo "<h3>Coming Soon...</h3>";

}
