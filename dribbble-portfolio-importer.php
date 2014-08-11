<?php
/**
 * Plugin Name: Dribbble Portfolio Importer 
 * Plugin URI: http://upthemes.com/
 * Description: Import your Dribbble shots into a portfolio custom post type! 
 * Author: Matthew Simo
 * Version: 1.0.0
 * Author URI: http://upthemes.com 
 * License: GPL2+
 * Text Domain: DP_Importer
 * Domain Path: /languages/
 */


define( 'DPI__VERSION',       '1.0.0' );
define( 'DPI__PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'DPI__PLUGIN_FILE',   __FILE__ );
define( 'DPI__DOMAIN',        'DP_Importer' );


require_once( DPI__PLUGIN_DIR . 'class.DP_Importer.php' );

register_activation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_activation' ) );
register_deactivation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_deactivation' ) );

add_action( 'init', array( 'DP_Importer', 'init' ) );
add_action( 'plugins_loaded', array( 'DP_Importer', 'on_plugins_loaded' ), 100 );


