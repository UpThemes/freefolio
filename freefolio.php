<?php
/**
 * Plugin Name: Freefolio
 * Plugin URI: http://wordpress.org/plugins/freefolio/
 * Description: Adds a portfolio post type, custom widget and automatically imports Dribbble shots.
 * Author: Matthew Simo
 * Version: 1.0.0
 * Author URI: https://upthemes.com/
 * License: GPL2+
 * Text Domain: freefolio
 * Domain Path: /languages/
 */


define( 'DPI__VERSION',       '1.0.0' );
define( 'DPI__PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'DPI__PLUGIN_FILE',   __FILE__ );

require_once( DPI__PLUGIN_DIR . 'class.DP_Importer.php' );

register_activation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_activation' ) );
register_deactivation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_deactivation' ) );

add_action( 'init', array( 'DP_Importer', 'init' ) );
add_action( 'plugins_loaded', array( 'DP_Importer', 'on_plugins_loaded' ), 100 );