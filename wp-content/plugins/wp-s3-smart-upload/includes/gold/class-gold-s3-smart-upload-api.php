<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/4/18
 * Time: 16:22
 *
 * @package SSU
 */

if ( ! class_exists( 'SSU_API_Gold' ) ) {
	/**
	 * The class which manage API
	 * Class SSU_API
	 */
	class SSU_API_Gold {
		/**
		 * AWS Service
		 *
		 * @var object $service AWS service.
		 */
		private $service;

		/**
		 * SSU_API constructor.
		 */
		public function __construct() {
			$options = ssu_get_s3_options();
			if ( $options ) {
				$this->service = new SSU_S3_Service( $options );
			}
		}

		/**
		 * Function to declare the API routes.
		 */
		public function register_rest_routes() {
//			register_rest_route( 'ssu/v1', '/api-stack', array(
//				'methods'             => 'GET',
//				'callback'            => array( $this, 'get_build_status' ),
//				'permission_callback' => function () {
//					return current_user_can( 'manage_options' );
//				},
//			) );
			if ( isset( $this->service ) ) {
				register_rest_route( 'ssu/v1', '/s3-buckets', array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'fetch_s3_buckets' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				) );
//				register_rest_route( 'ssu/v1', '/api-stack', array(
//					'methods'             => 'POST',
//					'callback'            => array( $this, 'build_api_stack_to_aws' ),
//					'permission_callback' => function () {
//						return current_user_can( 'manage_options' );
//					},
//				) );
			}
			register_rest_route( 'ssu/v1', '/aws-options', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_aws_options' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
//			register_rest_route( 'ssu/v1', '/aws-options', array(
//				'methods'             => 'POST',
//				'callback'            => array( $this, 'create_aws_options' ),
//				'permission_callback' => function () {
//					return current_user_can( 'manage_options' );
//				},
//			) );
//			register_rest_route( 'ssu/v1', '/s3-api', array(
//				'methods'             => 'POST',
//				'callback'            => array( $this, 'update_s3_api_settings' ),
//				'permission_callback' => function () {
//					return current_user_can( 'manage_options' );
//				},
//			) );
			register_rest_route( 'ssu/v1', '/build-options', array(
				'methods'  => 'GET',
				'callback' => array( $this, 'delete_aws_options' ),
				'permission_callback' => '__return_true',
//				'permission_callback' => function () {
//					return current_user_can( 'manage_options' );
//				},
			) );
		}

		/**
		 * Function to create new WP media.
		 *
		 * @param mixed $data query string object.
		 *
		 * @return mixed
		 */
		public function create_wp_media( $data ) {
			return $this->service->create_media_attachment_from_s3_url( $data['file'], $data['signedUrl'] );
		}

		/**
		 * Get AWS options
		 */
		public function get_aws_options() {
			$options = ssu_get_s3_options();
			if ( isset( $options['is_default'] ) ) {
				return array(
					'is_default' => true,
					'aws_key'    => '',
					'aws_secret' => '',
					'region'     => 'us-west-1',
				);
			}

			return $options;
		}

		/**
		 * Create AWS options
		 *
		 * @param mixed $data includes aws-key and aws-secret.
		 *
		 * @return mixed
		 */
//		public function create_aws_options( $data ) {
//			$option      = array(
//				'aws_key'    => $data['aws_key'],
//				'aws_secret' => $data['aws_secret'],
//				'bucket'     => $data['bucket'],
//				'prefix_key' => $data['prefix_key'],
//				'region'     => $data['region'],
//			);
//			$option_json = wp_json_encode( $option );
//			$result      = update_option( SSU_CONSTANTS::AWS_OPTION_NAME, $option_json );
//			delete_build_options();
//			return $result;
//		}

		/**
		 * Fetch S3 buckets based on AWS Key and Secret
		 *
		 * @param mixed $data Request object.
		 *
		 * @return mixed
		 */
		public function fetch_s3_buckets() {
			return $this->service->get_s3_buckets();
		}

		/**
		 * Remote build image API stack to AWS
		 *
		 * @return array Build metadata
		 */
//		public function build_api_stack_to_aws( $data ) {
//			return SSU_Build_Service::build_image_api_stack( $data );
//		}

		/**
		 * Get build information
		 *
		 * @return array
		 */
		public function get_build_status() {
			return SSU_Build_Service::get_build_status();
		}

		/**
		 * Delete build options
		 *
		 * @return mixed
		 */
		public function delete_aws_options() {
			delete_build_options();
			delete_aws_options();
		}

	}
}
