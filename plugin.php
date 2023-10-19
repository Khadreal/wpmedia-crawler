<?php
/**
 * Plugin Template
 *
 * @package     WPCrawler
 * @author      Opeyemi Ibrahim
 * @copyright   opeyemi
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: WP Media Web Crawler
 * Version:     1.0
 * Description: A website crawler to help site admin make better SEO decision.
 * Author:      Opeyemi Ibrahim
 */

namespace WPCrawler;

define( 'ROCKET_CRWL_PLUGIN_FILENAME', plugin_dir_path( __FILE__ ) ); // Filename of the plugin, including the file.

if ( !defined( 'ABSPATH' ) ) { // If WordPress is not loaded.
	exit( 'WordPress not loaded. Can not load the plugin' );
}

// Load the dependencies installed through composer.
require_once ROCKET_CRWL_PLUGIN_FILENAME . 'vendor/autoload.php';
require_once __DIR__ . '/src/support/exceptions.php';

// Plugin initialization.
/**
 * Creates the plugin object on plugins_loaded hook
 *
 * @return void
 */
function wpc_crawler_plugin_init() {
	new Component();
}
add_action( 'plugins_loaded',  __NAMESPACE__ . '\wpc_crawler_plugin_init' );

register_activation_hook( __FILE__, __NAMESPACE__ . '\Component::wpc_activate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\Component::wpc_uninstall' );
