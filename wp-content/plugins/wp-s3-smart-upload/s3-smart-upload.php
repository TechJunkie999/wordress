<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://preventdirectaccess.com
 * @since             1.4.2
 * @package           S3_Smart_Upload
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Amazon S3 - Wasabi Smart File Uploads
 * Plugin URI:        https://preventdirectaccess.com/docs/upload-wordpress-files-directly-to-amazon-s3-bucket/?utm_source=wp.org&utm_medium=post&utm_campaign=plugin-link
 * Description:       Upload WordPress files directly to your Amazon S3 or Wasabi storage with ease. Protect WooCoomerce product files by automatically generating signed URLs.
 * Version:           1.4.2
 * Author:            BWPS
 * Author URI:        https://preventdirectaccess.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       s3-smart-upload
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
define( 'S3_SMART_UPLOAD_VERSION', '1.4.2' );
define( 'S3_SMART_UPLOAD_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'S3_SMART_UPLOAD_PLUGIN_BASE_FILE', __FILE__ );
define( 'S3_SMART_UPLOAD_PLUGIN_NAME', 'WordPress Amazon S3 - Wasabi Smart File Uploads' );
define( 'S3_SMART_UPLOAD_PLUGIN_BASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'S3_SMART_UPLOAD_PLUGIN_BASE_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-s3-smart-upload-activator.php
 */
function activate_s3_smart_upload() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-s3-smart-upload-activator.php';
	S3_Smart_Upload_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-s3-smart-upload-deactivator.php
 */
function deactivate_s3_smart_upload() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-s3-smart-upload-deactivator.php';
	S3_Smart_Upload_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_s3_smart_upload' );
register_deactivation_hook( __FILE__, 'deactivate_s3_smart_upload' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-s3-smart-upload.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_s3_smart_upload() {
	$plugin = new S3_Smart_Upload();
	$plugin->run();

}

run_s3_smart_upload();
