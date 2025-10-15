<?php

/*
Plugin Name: WDJ Movie Plugin
Plugin URI: https://github.com/WebDevJohn23/wdj-movie-plugin
Description: Display movie showtimes and posters.
Version: 1.0.0
Author: Johnathan Julig
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wdj-movie-plugin
Update URI: https://github.com/WebDevJohn23/wdj-movie-plugin
*/

// Uncomment below to debug

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// add required files here
require_once('includes/settings.php');



// sets the plugin settings link
function wdj_movie_plugin_settings_link($links)
{
    // create menu slug .. example: custom-plugin
    $menuSlug = 'wdj-movie-plugin';

    $settings_link = '<a href="options-general.php?page=' . $menuSlug . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// changes plugin link settings
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'wdj_movie_plugin_settings_link');
