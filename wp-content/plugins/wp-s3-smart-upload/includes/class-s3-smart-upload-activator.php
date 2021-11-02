<?php

/**
 * Fired during plugin activation
 *
 * @link       https://preventdirectaccess.com
 * @since      1.0.0
 *
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/includes
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class S3_Smart_Upload_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ssu_check_conflict_with_pda_s3() ) {
			wp_die(
				wp_kses(
					sprintf(
						// translators: %s: Plugin name.
						__( 'Please update <a target="_blank" href="https://preventdirectaccess.com/extensions/amazon-s3-wordpress-uploads/">PDA S3 Integration</a> to the latest version for %s plugin to work properly.', 's3-smart-upload' ),
						S3_SMART_UPLOAD_PLUGIN_NAME
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				)
			);
		}
	}

}
