<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://github.com/MakiOmar
 * @since             1.0.0
 * @package           Mokhlef
 *
 * @wordpress-plugin
 * Plugin Name:       Support team edits
 * Plugin URI:        https://makiomar.com
 * Description:       A plugin built specially for Support team edits.
 * Version:           1.0.09
 * Author:            Mohammad Omar
 * Author URI:        https://https://github.com/MakiOmar
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mokhlef
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MOKHLEF_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mokhlef-activator.php
 */
function activate_mokhlef() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mokhlef-activator.php';
	Mokhlef_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mokhlef-deactivator.php
 */
function deactivate_mokhlef() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mokhlef-deactivator.php';
	Mokhlef_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mokhlef' );
register_deactivation_hook( __FILE__, 'deactivate_mokhlef' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mokhlef.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

 require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

 $mokhlef_update_checker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/MakiOmar/Al-Mokhlef',
    __FILE__,
    plugin_basename(__FILE__)
);
//Set the branch that contains the stable release..
$mokhlef_update_checker->setBranch('master');

function run_mokhlef() {

	$plugin = new Mokhlef();
	$plugin->run();

}
run_mokhlef();
