<?php
/**
 * Freefolio
 *
 * Adds a portfolio post type, custom widget and automatically imports Dribbble shots.
 *
 * @package   Freefolio
 * @author    Matthew Simo <matthew@upthemes.com>
 * @license   GPL-2.0+
 * @link      https://upthemes.com
 * @copyright 2014 UpThemes
 *
 * @wordpress-plugin
 * Plugin Name:       Freefolio
 * Plugin URI:        http://wordpress.org/plugins/freefolio-by-upthemes/
 * Description:       Adds a portfolio post type, custom widget and automatically imports Dribbble shots.
 * Version:           1.1.1
 * Author:            Matthew Simo and Chris Wallace
 * Author URI:        https://upthemes.com
 * Text Domain:       freefolio
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/UpThemes/freefolio
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Set up plugin constants
 */
define( 'DPI__PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'DPI__PLUGIN_FILE',   __FILE__ );

/**
 * Add localization to the FreeFolio plugin.
 */
function freefolio_localize(){

	load_plugin_textdomain( 'freefolio', false, basename( dirname( __FILE__ ) ) . '/languages' );

}
add_action( 'plugins_loaded', 'freefolio_localize' );

/**
 * Dribbble Importer setup
 */

require_once( DPI__PLUGIN_DIR . 'class.DP_Importer.php' );

register_activation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_activation' ) );
register_deactivation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_deactivation' ) );

add_action( 'init', array( 'DP_Importer', 'init' ) );
add_action( 'plugins_loaded', array( 'DP_Importer', 'on_plugins_loaded' ), 100 );
