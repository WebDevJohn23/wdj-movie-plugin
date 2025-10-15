<?php

/*
Plugin Name: WDJ Movie Plugin
Plugin URI: https://github.com/WebDevJohn23/wdj-movie-plugin
Description: Scrapes Regal theater pages, stores movies in a custom table, and displays results in the admin panel.
Version: 1.0.0
Author: Johnathan Julig
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wdj-movie-plugin
Update URI: https://github.com/WebDevJohn23/wdj-movie-plugin
*/

if ( ! defined('ABSPATH') ) exit;

// --- Load core includes ---
require_once plugin_dir_path(__FILE__) . 'includes/db.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

// --- Create database table on activation ---
register_activation_hook(__FILE__, 'wdj_mp_install_movies_table');


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