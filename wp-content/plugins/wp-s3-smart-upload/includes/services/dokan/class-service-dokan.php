<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 9/7/20
 * Time: 14:33
 */

class SSU_Service_Dokan {
	/**
	 * Instance of SSU_Service_Dokan class.
	 *
	 * @var SSU_Service_Woo
	 */
	protected static $instance = null;

	/**
	 * @var string Plugin version.
	 */
	private $version;

	/**
	 * SSU_Service_Dokan constructor.
	 *
	 * @param $version
	 */
	public function __construct( $version ) {
		$this->version = $version;

		add_filter( 'dokan_get_template_part', array(
				$this,
				'dokan_load_downloadable_template',
			),
			10,
			3
		);

		add_action( 'dokan_register_scripts', array(
				$this,
				'dokan_add_script',
			)
		);
	}

	/**
	 * Get instance
	 *
	 * @param $version
	 *
	 * @return SSU_Service_Woo
	 */
	public static function get_instance( $version ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $version );
		}

		return self::$instance;
	}

	/**
	 * Load SSU custom downloadable product template.
	 *
	 * @param $template
	 * @param $slug
	 * @param $name
	 *
	 * @return string
	 */
	public function dokan_load_downloadable_template( $template, $slug, $name ) {
		if ( 'products/downloadable' === $slug ) {
			$template = S3_SMART_UPLOAD_PLUGIN_BASE_DIR . 'includes/services/dokan/templates/downloadable.php';
		}

		return $template;
	}

	/**
	 * Load SSU button script to Dokan's front-end.
	 */
	public function dokan_add_script() {
		wp_enqueue_script( 'ssu-dokan', plugin_dir_url( S3_SMART_UPLOAD_PLUGIN_BASE_FILE ) . 'admin/js/dist/dokan-ssu-btn.bundle.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( 'ssu-dokan', 'ssuData', array(
			'rest_url'           => get_rest_url(),
			'nonce'              => wp_create_nonce( 'wp_rest' ),
			'enable_restriction' => ssu_enable_restriction(),
			'max_file_size'      => SSU_CONSTANTS::MAX_FILE_UPLOAD_SIZE,
			'messages'           => ssu_get_messages(),
		) );
	}
}
