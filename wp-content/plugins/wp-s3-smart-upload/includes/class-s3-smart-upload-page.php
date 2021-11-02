<?php
if ( ! class_exists( "S3_Smart_Upload_Page" ) ) {
	/**
	 * Class to render UI fro S3 smart upload page.
	 *
	 * Class S3_Smart_Upload_Page
	 */
	class S3_Smart_Upload_Page {
		/**
		 * Render page
		 */
		public function render_ui() {
			?>
            <div class="wrap-select-file-upload wrap" id="s3-smart-upload-app">
            </div>
			<?php
		}
		/**
		 * Register assets file
		 */
		public function s3_smart_upload_setting_assets() {
			wp_register_style( 's3-smart-upload-settings-css', plugin_dir_url( S3_SMART_UPLOAD_PLUGIN_BASE_FILE ) . 'admin/css/s3-smart-upload-setting.css', array(), S3_SMART_UPLOAD_VERSION, 'all' );
			wp_enqueue_style( 's3-smart-upload-settings-css' );
		}
	}
}
