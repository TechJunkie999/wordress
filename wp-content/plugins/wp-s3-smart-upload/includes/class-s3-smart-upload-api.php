<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/4/18
 * Time: 16:22
 *
 * @package SSU
 */

if ( ! class_exists( 'SSU_API' ) ) {
	/**
	 * The class which manage API
	 * Class SSU_API
	 */
	class SSU_API {
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
			$capability = ssu_get_capability();
			if ( isset( $this->service ) ) {

				// Create s3 signed-url.
				register_rest_route( 'ssu/v1', '/s3/sign', array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_signed_url' ),
					'permission_callback' => '__return_true',
				) );

				// Show file under media library after upload.
				register_rest_route( 'ssu/v1', '/wp-media', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_wp_media' ),
					'permission_callback' => function () use ( $capability ) {
						return current_user_can( $capability );
					},
				) );

				// Show S3 existing file under media library.
				register_rest_route( 'ssu/v1', '/add-file-to-media', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'add_file_to_media' ),
					'permission_callback' => function () use ( $capability ) {
						return current_user_can( $capability );
					},
				) );

				register_rest_route( 'ssu/v1', '/s3', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'is_s3_object_existed' ),
					'permission_callback' => function () use ( $capability ) {
						return current_user_can( $capability );
					},
				) );

				// Load S3 folder structure.
				register_rest_route( 'ssu/v1', '/s3/objects', array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_objects' ),
					'permission_callback' => function () use ( $capability ) {
						return current_user_can( $capability );
					},
				) );

				// Set OBJECT ACL.
				register_rest_route( 'ssu/v1', '/s3/object/acl', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_object_acl' ),
					'permission_callback' => function () use ( $capability ) {
						return current_user_can( $capability );
					},
				) );

			}

			register_rest_route( 'ssu/v1', '/plugin', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_plugin_info' ),
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			) );

			// Create s3 signed-url.
			register_rest_route( 'ssu/v1', '/s3/pre-signed', array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'get_put_presigned_url' ),
				'permission_callback' => '__return_true',
			) );

		}

		/**
		 * Get signed URL for uploader.
		 *
		 * @param mixed $data Request's data.
		 *
		 * @return WP_REST_Response
		 * @since 1.0.0
		 * @since 1.2.0 Catch exception.
		 */
		public function get_signed_url( $data ) {
			if ( ! isset( $data['objectName'] ) || ! isset( $data['contentType'] ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}

			$is_public_acl = 'true' === $data->get_header( 'ssu-public-acl' );
			$file_name     = $data['objectName'];
			$content_type  = $data['contentType'];

			if ( ! isset( $data['uploadFolder'] ) ) {
				$key = $this->service->get_file_key( $file_name );
			} else {
				$key = ! ssu_is_folder_empty( $data['uploadFolder'] ) ? trailingslashit( $data['uploadFolder'] ) . $file_name : $file_name;
			}

			try {
				$result = $this->service->create_put_presigned_url( $key, $content_type, '1 hour', $is_public_acl );
				$result['key'] = $key;

				return $result;
			} catch ( Exception $exception ) {
				ssu_log_message( $exception->getMessage() );

				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}
		}

		/**
		 * Get signed URL for uploader.
		 *
		 * @param mixed $data Request's data.
		 *
		 * @return WP_REST_Response
		 * @since 1.0.0
		 * @since 1.2.0 Catch exception.
		 */
		public function get_put_presigned_url( $data ) {
			if ( ! check_ajax_referer( 'wp_rest', '_wpnonce', false ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}

			if ( ! isset( $data['objectName'], $data['contentType'] ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}

			$object_name  = $data['objectName'];
			$content_type = $data['contentType'];
			$post_id      = isset( $data['post_id'] ) ? $data['post_id'] : false;

			if ( apply_filters( 'ssu_shortcode_should_block', false, $object_name, $content_type ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}

			$is_public_acl = apply_filters( 'ssu_shortcode_public_acl', false, $post_id, $object_name, $content_type );
			$folder_name   = apply_filters( 'ssu_shortcode_upload_folder', false, $post_id, $object_name, $content_type );
			$expiry        = apply_filters( 'ssu_shortcode_signed_url_expiry', '1 hour', $post_id, $object_name, $content_type );

			$file_name = ! ssu_is_folder_empty( $folder_name ) ? trailingslashit( $folder_name ) . $object_name : $object_name;

			try {
				return $this->service->create_put_presigned_url( $file_name, $content_type, $expiry, $is_public_acl );
			} catch ( Exception $exception ) {
				ssu_log_message( $exception->getMessage() );

				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}
		}

		/**
		 * List objects on bucket.
		 *
		 * @param mixed $request Request's data.
		 *
		 * @return WP_REST_Response
		 * @since 1.1.0
		 * @since 1.2.0 Catch exception.
		 */
		public function list_objects( $request ) {
			$prefix = $request->get_param( 'prefix' );

			try {
				$objects = $this->service->list_objects( $prefix );
			} catch ( Exception $exception ) {
				ssu_log_message( $exception->getMessage() );

				return $this->send_json( array(), 400, $exception->getMessage(), false );
			}

			return $this->send_json(
				array(
					'CommonPrefixes' => $objects->getPath( 'CommonPrefixes' ),
					'Contents'       => $objects->getPath( 'Contents' ),
				)
			);
		}

		/**
		 * Function to create new WP media.
		 *
		 * @param mixed $data query string object.
		 *
		 * @return WP_REST_Response
		 * @since 1.0.0
		 * @since 1.2.0 Catch exception.
		 */
		public function create_wp_media( $data ) {
			if ( ! isset( $data['file'] ) || ! isset( $data['signedUrl'] ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}

			try {
				return $this->service->create_media_attachment_from_s3_url( $data['file'], $data['signedUrl'] );
			} catch ( Exception $exception ) {
				ssu_log_message( $exception->getMessage() );

				return $this->send_json( array(), 400, $exception->getMessage(), false );
			}
		}

		/**
		 * Function to create new WP media.
		 *
		 * @param array $data query string object.
		 *
		 * @return WP_REST_Response
		 * @since 1.2.0
		 */
		public function add_file_to_media( $data ) {
			if ( ! isset( $data['file'] ) || ! isset( $data['url'] ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}
			try {
				$result = $this->service->create_media_attachment( $data['file'], $data['url'] );

				return $this->send_json( $result, 200 );
			} catch ( Exception $exception ) {
				ssu_log_message( $exception->getMessage() );

				return $this->send_json( $exception->getMessage(), 400 );
			}
		}


		/**
		 * Get AWS options
		 */
		public function get_aws_options() {
			$raw_options = get_option( SSU_CONSTANTS::AWS_OPTION_NAME );
			$options     = array();
			if ( $raw_options ) {
				$options = ssu_get_s3_options();
			}

			return $options;
		}

		/**
		 * Fetch S3 buckets based on AWS Key and Secret
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
		public function build_api_stack_to_aws() {
			return SSU_Build_Service::build_image_api_stack();
		}

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

		/**
		 * Function to return plugin info including plugin type.
		 */
		public function get_plugin_info() {
			$configs = require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-configs.php';

			$aws_configured = isset( $this->service );

			if ( $aws_configured ) {
				$prefix_key = $this->service->get_options( 'prefix_key' );
			} else {
				$prefix_key = '';
			}

			return array(
				'type'          => $configs->type,
				'awsConfigured' => $aws_configured,
				's3Hostname'    => $aws_configured ? $this->service->getS3Hostname() : '',
				'prefixKey'     => ! ssu_is_folder_empty( $prefix_key ) ? trailingslashit( $prefix_key ) : '',
			);
		}

		/**
		 * Check whether s3 object is existed.
		 *
		 * @param mixed $data Post request's data.
		 *
		 * @return bool
		 */
		public function is_s3_object_existed( $data ) {
			return $this->service->is_object_existed( $data['file_name'] );
		}

		/**
		 * Set object ACL.
		 *
		 * @param $data
		 *
		 * @return array|WP_REST_Response
		 */
		public function set_object_acl( $data ) {
			if ( ! isset( $data['key'] ) || ! isset( $data['acl'] ) ) {
				return $this->send_json( false, 400, SSU_CONSTANTS::BAD_REQUEST_MESSAGE, false );
			}

			try {
				return $this->service->put_object_acl( $data['key'], $data['acl'] );
			} catch ( Aws\S3\Exception\S3Exception $exception ) {
				ssu_log_message( $exception->getMessage() );

				return $this->send_json( $exception->getMessage(), 400 );
			}
		}


		/**
		 * Response for request.
		 *
		 * @param array|object $result  Response data.
		 * @param int          $status  Response status.
		 * @param string       $message Message for API.
		 * @param bool         $success Success status.
		 *
		 * @return WP_REST_Response
		 */
		protected function send_json( $result = null, $status = 200, $message = '', $success = true ) {
			return new WP_REST_Response(
				array(
					'success' => $success,
					'result'  => $result,
					'message' => $message,
				),
				$status
			);
		}
	}
}
