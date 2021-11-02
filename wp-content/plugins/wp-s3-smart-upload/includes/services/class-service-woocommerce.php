<?php

/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/11/18
 * Time: 16:02
 */
class SSU_Service_Woo {
	/**
	 * Instance of SSU_Service_Woo class.
	 *
	 * @var SSU_Service_Woo
	 */
	protected static $instance = null;

	const OPTIONS = array(
		'ALLOW_SIGNED_URL' => '_ssu_allow_signed_url',
	);

	/**
	 * SSU_Service_Woo constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_file_download_path', array(
				$this,
				'create_wasabi_signed_file_download_path'
			)
		);
	}

	/**
	 * Get instance
	 *
	 * @return SSU_Service_Woo
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Filter downloadable items of Woo.
	 *
	 * @param string $download_url Download URL.
	 *
	 * @return string
	 */
	public function create_wasabi_signed_file_download_path( $download_url ) {
		/**
		 * Version compare with PDA S3
		 */
		if ( ! defined( 'pda_s3_VERSION' ) || version_compare(pda_s3_VERSION, '1.1.5.2', '<=') ) {
			return $download_url;
		}

		$aws_options = ssu_get_s3_options();
		if ( false === $aws_options ) {
			return $download_url;
		}
		$ssu_s3_service = new SSU_S3_Service( $aws_options );

		// Only handle for wasabi.
		if ( $ssu_s3_service->type !== SSU_CONSTANTS::TYPE_SERVICE['WASABI'] ) {
			return $download_url;
		}

		$download_expired = defined( 'SSU_SURL_EXPIRY' ) ? (int) SSU_SURL_EXPIRY : 60;

		// Create WASABI singed URL.
		$signed_url = $ssu_s3_service->create_wasabi_read_presigned_url( $download_url, "+{$download_expired} seconds", true );
		if ( ! empty( $signed_url ) ) {
			return $signed_url;
		}

		return $download_url;
	}
}
