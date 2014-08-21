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
 * Version:           1.0.0
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

define( 'DPI__VERSION',       '1.0.0' );
define( 'DPI__PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'DPI__PLUGIN_FILE',   __FILE__ );

require_once( DPI__PLUGIN_DIR . 'class.DP_Importer.php' );

register_activation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_activation' ) );
register_deactivation_hook( DPI__PLUGIN_FILE, array( 'DP_Importer', 'on_plugin_deactivation' ) );

add_action( 'init', array( 'DP_Importer', 'init' ) );
add_action( 'plugins_loaded', array( 'DP_Importer', 'on_plugins_loaded' ), 100 );