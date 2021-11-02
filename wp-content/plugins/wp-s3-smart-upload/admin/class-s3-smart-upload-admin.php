<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://preventdirectaccess.com
 * @since      1.0.0
 *
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/admin
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class S3_Smart_Upload_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in S3_Smart_Upload_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The S3_Smart_Upload_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$screen = get_current_screen();
		if ( 'media_page_s3-smart-upload-settings' === $screen->id ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( S3_SMART_UPLOAD_PLUGIN_BASE_FILE ) . 'public/dist/styles/style.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in S3_Smart_Upload_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The S3_Smart_Upload_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$screen = get_current_screen();
		if ( 'media_page_s3-smart-upload-settings' === $screen->id ) {
			$display_folder = defined( 'SSU_FOLDER' ) ? ssu_get_upload_folder( SSU_FOLDER ) : '';
			$display_folder = trailingslashit( $display_folder );

			wp_enqueue_script( $this->plugin_name . '-vendor', plugin_dir_url( S3_SMART_UPLOAD_PLUGIN_BASE_FILE ) . 'public/dist/vendors.ssu.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( S3_SMART_UPLOAD_PLUGIN_BASE_FILE ) . 'public/dist/main.ssu.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name, 'ssuData', array(
				'home_url'           => get_rest_url(),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'provider'           => defined( 'SSU_PROVIDER' ) ? SSU_PROVIDER : 'aws',
				'display_folder'     => $display_folder,
				'plugin_name'        => S3_SMART_UPLOAD_PLUGIN_NAME,
				'max_file_size'      => SSU_CONSTANTS::MAX_FILE_UPLOAD_SIZE,
				'enable_restriction' => ssu_enable_restriction(),
				'messages'           => ssu_get_messages(),
			) );
		}

		if ( 'product' === $screen->id ) {
			wp_enqueue_script( 'ssu-woo', plugin_dir_url( S3_SMART_UPLOAD_PLUGIN_BASE_FILE ) . 'admin/js/dist/ssu-btn.bundle.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( 'ssu-woo', 'ssuData', array(
				'rest_url'           => get_rest_url(),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'enable_restriction' => ssu_enable_restriction(),
				'max_file_size'      => SSU_CONSTANTS::MAX_FILE_UPLOAD_SIZE,
				'messages'           => ssu_get_messages(),
			) );
		}
	}

	/**
	 * Create plugin submenu
	 */
	public function s3_smart_upload_create_plugin_submenu() {
		$capability     = ssu_get_capability();
		$upload_page    = new S3_Smart_Upload_Page();
		$menu_name      = __( 'S3 Smart Upload', 's3-smart-upload' );
		$menu_page_name = add_submenu_page( 'upload.php', $menu_name, $menu_name, $capability, SSU_CONSTANTS::SUB_MENU, array(
			$upload_page,
			'render_ui',
		) );
		add_action( 'admin_print_styles-' . $menu_page_name, array(
			$upload_page,
			's3_smart_upload_setting_assets',
		) );
	}

	/**
	 * Setup RESTful API.
	 */
	public function ssu_rest_api_init_cb() {
		$api = new SSU_API();
		$api->register_rest_routes();
		if ( check_plugin_type( SSU_CONSTANTS::GOLD_TYPE_PLUGIN ) ) {
			$api = new SSU_API_Gold();
			$api->register_rest_routes();
		}
	}

	/**
	 * Handle file URL in media.
	 *
	 * @param string $url The attachment's url.
	 * @param int $attachment_id The attachment's ID.
	 *
	 * @return string Attachment's url
	 */
	public function change_file_url_in_media( $url, $attachment_id ) {
		if ( ! $this->is_upload_to_s3( $attachment_id ) ) {
			return $url;
		}

		return get_post_meta( $attachment_id, 's3_public_url', true );
	}

	/**
	 * Handle image downsize
	 *
	 * @param bool $downsize Downsize.
	 * @param int $attachment_id Attachment ID.
	 * @param int $size Size.
	 *
	 * @return array|bool
	 */
	public function ssu_image_downsize( $downsize, $attachment_id, $size ) {
		return false;
	}

	/**
	 * Handle image srcset.
	 *
	 * @param mixed $image_meta Image's meta.
	 * @param array $size_array Size's array.
	 * @param string $image_src Image's src.
	 * @param int $attachment_id Attachment's ID.
	 *
	 * @return mixed
	 */
	public function ssu_image_srcset_meta( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		$s3_public_url = get_post_meta( $attachment_id, 's3_public_url', true );
		if ( empty( $s3_public_url ) ) {
			return $sources;
		}
		$image_baseurl = trailingslashit( get_base_upload_url( $image_meta ) );
		/*
		 * If currently on HTTPS, prefer HTTPS URLs when we know they're supported by the domain
		 * (which is to say, when they share the domain name of the current request).
		 */
		$s3_url = get_s3_baseurl( $s3_public_url );
		return array_map( function ( $source ) use ( $s3_url, $image_baseurl ) {
			$source['url'] = str_replace( $image_baseurl, $s3_url, $source['url'] );
			return $source;
		}, $sources );
	}

	/**
	 * Handle delete post meta when delete attachment file.
	 *
	 * @param int $post_id Post Id.
	 */
	public function handle_delete_post_meta( $post_id ) {
		if ( ! defined( SSU_CONSTANTS::SSU_WP_REMOVE_VAR ) || true !== SSU_WP_REMOVE ) {
			return;
		}

		$file_url = get_post_meta( $post_id, 's3_public_url', true );
		if ( $this->is_upload_to_s3( $post_id ) ) {
			$aws_options = ssu_get_s3_options();
			$s3_service  = new SSU_S3_Service( $aws_options );
			$s3_service->delete_attachment_file_on_s3_link( $file_url );
			$sizes = get_attachment_sizes_file_name( $post_id );
			delete_post_meta( $post_id, 's3_public_url' );
			foreach ( $sizes as $size ) {
				$s3_service->delete_attachment_file_on_s3( $size );
			}
		}
	}

	/**
	 * Helper function to check the file is uploaded to S3.
	 *
	 * @param int $post_id Post's ID.
	 *
	 * @return bool is uploaded to S3.
	 */
	private function is_upload_to_s3( $post_id ) {
		$s3_public_url = get_post_meta( $post_id, 's3_public_url', true );
		if ( empty( $s3_public_url ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add link to setting.
	 *
	 * @param array $links Links
	 *
	 * @return array
	 */
	public function handle_plugin_links( $links ) {
		$setting_url = esc_url( admin_url( 'upload.php?page=' . SSU_CONSTANTS::SUB_MENU ) );
		$plugin_link = '<a href="' . $setting_url . '">' . __( 'Upload Files', 's3-smart-upload' ) . '</a>';
		array_unshift( $links, $plugin_link );

		return $links;
	}
}
