<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://preventdirectaccess.com
 * @since      1.0.0
 *
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/includes
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class S3_Smart_Upload_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			's3-smart-upload',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
