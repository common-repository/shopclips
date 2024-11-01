<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @package           Shopclips
 *
 * @wordpress-plugin
 * Plugin Name:       shopclips
 * Plugin URI:        https://verbo.ai
 * Description:       Adds utilities that helps shopclips work better with WooCommerce stores.
 * Version:           1.0.7
 * Author:            Verbo
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shopclips
 * Domain Path:       /languages
 *
 * WC requires at least: 4.5.1
 * WC tested up to: 5.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'SHOPCLIPS_VERSION', '1.0.7' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-shopclips.php';

$plugin = new Shopclips();
$plugin->run();
