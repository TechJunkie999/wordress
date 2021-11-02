<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/4/18
 * Time: 16:32
 *
 * @package SSU
 */

use Aws\S3\S3Client;

if ( ! class_exists( 'SSU_S3_Service' ) ) {
	/**
	 * Class SSU_S3_Service
	 */
	class SSU_S3_Service {
		/**
		 * S3 class
		 *
		 * @var $s3
		 */
		protected $s3;
		/**
		 * AWS Options
		 *
		 * @var $options
		 */
		protected $options;

		/**
		 * Type service
		 *
		 * @var string
		 */
		public $type;

		/**
		 *
		 * @param mixed $options AWS Options.
		 *
		 * SSU_S3_Service constructor.
		 */
		public function __construct( $options ) {
			$this->options = $options;
			if ( ! class_exists( '\\Aws\\S3\\S3Client' ) ) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';
			}
			$this->config( $options );
		}

		/**
		 * @param array $options S3 Options.
		 *
		 * @return bool
		 */
		private function config( $options ) {
			if ( ! isset( $options['type'] ) ) {
				$this->setup_aws( $options );
				$this->type = SSU_CONSTANTS::TYPE_SERVICE['AWS'];

				return true;
			}
			$type = $options['type'];

			if ( $type === SSU_CONSTANTS::TYPE_SERVICE['WASABI'] ) {
				$this->setup_wasabi( $options );
				$this->type = $type;

				return true;
			}

			$this->setup_aws( $options );
			$this->type = SSU_CONSTANTS::TYPE_SERVICE['AWS'];

			return true;
		}

		/**
		 * Create the presigned URL.
		 *
		 * @param string $file_name    File path.
		 * @param string $content_type Content type.
		 * @param string $time_die     Deadline time.
		 * @param bool   $is_public    Is public read.
		 *
		 * @return mixed empty string if user have never did the aws configuration
		 */
		public function create_presigned_url( $file_name, $content_type, $time_die, $is_public = false ) {
			if ( is_null( $this->s3 ) ) {
				return '';
			}
			$key = $this->get_file_key( $file_name );

			return $this->create_put_presigned_url( $key, $content_type, $time_die, $is_public );
		}

		/**
		 * @param string $key          Key.
		 * @param string $content_type Content type.
		 * @param string $time_die     Deadline time.
		 * @param bool   $is_public    Is public read.
		 *
		 * @return mixed
		 */
		public function create_put_presigned_url( $key, $content_type, $time_die, $is_public ) {
			$params = [
				'Bucket'      => $this->options['bucket'],
				'Key'         => $key,
				'ContentType' => $content_type,
			];

			if ( $is_public ) {
				$params['ACL'] = 'public-read';
			}

			$cmd = $this->s3->getCommand( 'PutObject', $params );

			$request    = $this->s3->createPresignedRequest( $cmd, $time_die );
			$signed_url = strval( $request->getUri() );

			return array(
				'signedUrl' => $signed_url,
			);
		}

		/**
		 * Create the presigned URL.
		 *
		 * @param string $download_url Download URL.
		 * @param string $time_die     Deadline time.
		 *
		 * @return mixed empty string if user have never did the aws configuration
		 */
		public function create_read_presigned_url( $download_url, $time_die ) {
			if ( is_null( $this->s3 ) || ! $download_url ) {
				return false;
			}

			$url_host = parse_url( $download_url, PHP_URL_HOST );
			if ( false === strpos( $url_host, 'amazonaws.com' ) && false === strpos( $url_host, 'wasabisys.com') ) {
				return false;
			}

			if ( false === strpos( $download_url, $this->options['bucket'] ) ) {
				return false;
			}

			// Need to decode to support the special and unicode characters.
			$key = urldecode( ltrim( parse_url( $download_url, PHP_URL_PATH ), '/' ) );

			$cmd = $this->s3->getCommand( 'GetObject', [
				'Bucket' => $this->options['bucket'],
				'Key'    => $key,
			] );

			$request    = $this->s3->createPresignedRequest( $cmd, $time_die );
			$signed_url = strval( $request->getUri() );

			return $signed_url;
		}

		/**
		 * Create the presigned URL.
		 *
		 * @param string  $download_url Download URL.
		 * @param string  $time_die     Deadline time.
		 * @param boolean $is_decode    Is decode.
		 *
		 * @return mixed empty string if user have never did the aws configuration
		 */
		public function create_wasabi_read_presigned_url( $download_url, $time_die, $is_decode = false ) {
			if ( is_null( $this->s3 ) || ! $download_url ) {
				return false;
			}

			$hostname = parse_url( $download_url, PHP_URL_HOST );

			if ( false === strpos( $hostname, 'wasabisys.com' ) ) {
				return false;
			}

			if ( false === strpos( $download_url, $this->options['bucket'] ) ) {
				return false;
			}

			$key = ltrim( parse_url( $download_url, PHP_URL_PATH ), '/' );

			/**
			 * Check with case s3 have bucket in path.
			 * Example:
			 * https://s3.us-east-2.wasabisys.com/linh-demo/4k-money-heist-netflix-2019-9f.jpg
			 * Because linh-demo is bucket, part of URL path.
			 */
			if ( false === strpos( $hostname, $this->options['bucket'] ) ) {
				$paths = explode( '/', $key );
				if ( $paths[0] === $this->options['bucket'] ) {
					array_shift( $paths );
					$key = implode( '/', $paths );
				}
			}

			$cmd = $this->s3->getCommand( 'GetObject', [
				'Bucket' => $this->options['bucket'],
				'Key'    => $is_decode ? urldecode( $key ) : $key,
			] );

			$request    = $this->s3->createPresignedRequest( $cmd, $time_die );
			$signed_url = strval( $request->getUri() );

			return $signed_url;
		}

		/**
		 * Generate s3 hostname.
		 *
		 * @return string S3 Host name.
		 */
		public function getS3Hostname() {
			if ( ! isset( $this->options['bucket'] ) || ! $this->options['region'] ) {
				return false;
			}
			$bucket = $this->options['bucket'];
			$region = $this->options['region'];
			$aws_s3_host_name = "https://${bucket}.s3-${region}.amazonaws.com/";
			$wasabi_s3_host_name = "https://${bucket}.s3.${region}.wasabisys.com/";

			if ( ! isset( $this->options['type'] ) ) {
				return $aws_s3_host_name;
			}

			if ( SSU_CONSTANTS::TYPE_SERVICE['WASABI'] === $this->options['type'] ) {
				return $wasabi_s3_host_name;
			}

			return $aws_s3_host_name;
		}

		/**
		 * Get S3 buckets
		 *
		 * @return mixed Buckects.
		 */
		public function get_s3_buckets() {
			$result = $this->s3->listBuckets();

			return $result['Buckets'];
		}

		/**
		 * Get s3 options
		 *
		 * @param string $key Option key.
		 *
		 * @return string
		 *
		 */
		public function get_options( $key ) {
			return $this->options[ $key ];
		}

		/**
		 * List all objects with prefix key
		 *
		 * @return object
		 */
		public function list_objects( $prefix = '', $delimer = '/' ) {
			return $this->s3->listObjects(
				array(
					'Bucket'    => $this->options['bucket'],
					'Delimiter' => $delimer,
					'Prefix'    => $prefix
				)
			);
		}

		/**
		 * Get object URL.
		 *
		 * @param string $file_name File name.
		 *
		 * @return string
		 */
		public function get_public_url( $file_name ) {
			if ( is_null( $this->s3 ) ) {
				return '';
			}
			$key        = $this->get_file_key( $file_name );
			$public_url = $this->s3->getObjectUrl( $this->options['bucket'], $key );

			return $public_url;
		}

		/**
		 * Create media attachment from s3 link
		 *
		 * @param mixed  $file      File object.
		 * @param string $signed_url S3 Link.
		 *
		 * @return array $metadata Attachment ID
		 */
		public function create_media_attachment_from_s3_url( $file, $signed_url ) {
			$file_name          = sanitize_text_field( $file['name'] );
			// Get public URL from signed URL to fix the special character case.
			$public_url = $this->get_public_url_from_signed_url( $signed_url );
			$wp_filetype        = wp_check_filetype( $file_name );
			$attachment         = array(
				'post_mime_type' => $wp_filetype['type'],
				'guid'           => urldecode( $public_url ),
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $file_name );
			add_post_meta( $attachment_id, 's3_public_url', $public_url );
			$metadata = $this->generate_attachment_metadata_for_s3_link( $attachment_id, $file, $public_url );
			wp_update_attachment_metadata( $attachment_id, $metadata );

			return $metadata;
		}

		/**
		 * Remove all signed param.
		 *
		 * @param $signed_url
		 *
		 * @return string
		 */
		public function get_public_url_from_signed_url( $signed_url ) {
			return strtok( $signed_url, '?' );
		}

		/**
		 * Create media attachment from s3 link
		 *
		 * @param mixed  $file_name File name.
		 * @param string $url       S3 Link.
		 *
		 * @return array $metadata Attachment metadata
		 * @throws Exception
		 * @since 1.2.0
		 */
		public function create_media_attachment( $file_name, $url ) {
			$file_name = sanitize_text_field( $file_name );
			$url       = sanitize_url( $url );
			$mime_type = ssu_get_mime_type( $file_name );
			$attachment = array(
				'post_mime_type' => $mime_type,
				'guid'           => $url,
				'post_title'     => wp_basename( $file_name ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $file_name );
			if ( is_wp_error( $attachment_id ) ) {
				throw new Exception( 'Can not insert attachment' );
			}
			add_post_meta( $attachment_id, 's3_public_url', $url );
			$metadata = $this->generate_attachment_metadata_for_s3_link(
				$attachment_id,
				array(
					'name'   => $file_name,
					'size'   => 0,
					'width'  => 0,
					'height' => 0,
				),
				$url,
				true
			);
			wp_update_attachment_metadata( $attachment_id, $metadata );


			return $metadata;
		}

		/**
		 * Delete attachment file on S3.
		 *
		 * @param string $file_url The file url.
		 */
		public function delete_attachment_file_on_s3_link( $file_url ) {
			$file_name = get_file_name_from_url( $file_url );
			$key       = $this->get_file_key( $file_name );
			$result    = $this->s3->deleteObject( [
				'Bucket' => $this->options['bucket'],
				'Key'    => $key,
			] );
			error_log( 'Result: ' . wp_json_encode( $result ) );
		}

		/**
		 * Delete attachment file on S3.
		 *
		 * @param string $file_url The file url.
		 */
		public function delete_attachment_file_on_s3( $file_name ) {
			$key    = $this->get_file_key( $file_name );
			$result = $this->s3->deleteObject( [
				'Bucket' => $this->options['bucket'],
				'Key'    => $key,
			] );
			error_log( 'Result: ' . wp_json_encode( $result ) );
		}

		/**
		 * Check wether object is existed.
		 *
		 * @param string $file_name File name.
		 *
		 * @return bool Existed
		 */
		public function is_object_existed( $file_name ) {
			$key    = $this->get_file_key( $file_name );
			$params = array(
				'Bucket' => $this->options['bucket'],
				'Key'    => $key,
			);
			try {
				$this->s3->headObject( $params );

				return true;
			} catch ( Aws\S3\Exception\S3Exception $exception ) {
				return false;
			}
		}

		/**
		 * Change object ACL.
		 * @param string $key Object key.
		 * @param string $acl ACL such as "public-read", "private".
		 *
		 * @throws Aws\S3\Exception\S3Exception
		 *
		 * @return array
		 */
		public function put_object_acl( $key, $acl ) {
			$params = array(
				'ACL'    => $acl,
				'Bucket' => $this->options['bucket'],
				'Key'    => $key,
			);

			try {
				return $this->s3->putObjectAcl( $params );
			} catch ( Aws\S3\Exception\S3Exception $exception ) {
				throw $exception;
			}
		}

		/**
		 * Generate attachment metadata for S3 link
		 *
		 * @param int   $attachment_id Attachment's ID.
		 * @param mixed $file          File.
		 * @param int   $public_url    Public's url.
		 * @param bool  $should_get    Should get image from S3 to generate file size.
		 * @since 1.0.0
		 * @since 1.2.0 Allow to get file from s3 to generate width & height.
		 * @return array $metadata
		 */
		private function generate_attachment_metadata_for_s3_link( $attachment_id, $file, $public_url, $should_get = false ) {
			$attachment = get_post( $attachment_id );
			$mime_type  = get_post_mime_type( $attachment );
			$metadata   = array();
			if ( preg_match( '!^image/!', $mime_type ) ) {
				if ( $should_get ) {
					$file = $this->get_file_data( $file, $public_url );
				}
				$metadata = $this->generate_metadata_for_image( $file, $public_url, $mime_type );
			}
			if ( 'application/pdf' === $mime_type ) {
				$this->generate_metadata_for_pdf( $metadata );
			}

			return $metadata;
		}

		/**
		 * Massage file data from server.
		 *
		 * @param array $file File Data.
		 * @param string $url  URL.
		 *
		 * @return array File data.
		 * @since 1.2.0
		 */
		private function get_file_data( $file, $url ) {
			$signed_url = $this->create_read_presigned_url( $url, '+10 minutes' );
			$size = getimagesize( $signed_url );
			if ( ! $size ) {
				return $file;
			}

			$file['width']  = (int) $size[0];
			$file['height'] = (int) $size[1];

			return $file;
		}

		/**
		 * Get file's key.
		 *
		 * @param string $file_name File name.
		 *
		 * @return string
		 */
		public function get_file_key( $file_name ) {
			$prefix_key = $this->options['prefix_key'];

			return ssu_is_folder_empty( $prefix_key )
				? $file_name
				: trailingslashit( $prefix_key ) . $file_name;
		}

		/**
		 * Generate metadata for images type
		 *
		 * @param string $file      File.
		 * @param string $file_url  File url.
		 * @param string $mime_type Mime type.
		 *
		 * @return array Meta data.
		 */
		private function generate_metadata_for_image( $file, $file_url, $mime_type ) {
			$metadata           = array();
			$metadata['width']  = $file['width'];
			$metadata['height'] = $file['height'];
			// Make the file path relative to the upload dir.
			$metadata['file'] = $file['name'];
			// Make thumbnails and other intermediate sizes.
			$_wp_additional_image_sizes = wp_get_additional_image_sizes();
			$sizes                      = array();
			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[ $s ] = array(
					'width'  => '',
					'height' => '',
					'crop'   => false,
				);
				if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
					// For theme-added sizes.
					$sizes[ $s ]['width'] = intval( $_wp_additional_image_sizes[ $s ]['width'] );
				} else {
					// For default sizes set in options.
					$sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
				}
				if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
					// For theme-added sizes.
					$sizes[ $s ]['height'] = intval( $_wp_additional_image_sizes[ $s ]['height'] );
				} else {
					// For default sizes set in options.
					$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
				}
				if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
					// For theme-added sizes.
					$sizes[ $s ]['crop'] = $_wp_additional_image_sizes[ $s ]['crop'];
				} else {
					// For default sizes set in options.
					$sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
				}
			}
			/**
			 * Filters the image sizes automatically generated when uploading an image.
			 *
			 * @param array $sizes    An associative array of image sizes.
			 * @param array $metadata An associative array of image metadata: width, height, file.
			 *
			 * @since 2.9.0
			 * @since 4.4.0 Added the `$metadata` argument.
			 *
			 */
			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes, $metadata );
			if ( $sizes ) {
				// call api to generate image sizes.
				$all_raw_size = array();
				foreach ( $sizes as $size ) {
					$all_raw_size[] = array(
						'width'  => (string) $size['width'],
						'height' => (string) $size['height'],
					);
				}
				// Commented by thinhnp
				// Reason: hide the function to generate image sizes on the fly.
//				$file_key = $this->get_file_key( $file['name'] );
//				$bucket   = $this->options['bucket'];
//				$result   = $this->generate_image_sizes_on_the_fly( $file_key, $all_raw_size, $bucket );
//				if ( false === $result['isError'] ) {
//					$file_name         = $file['name'];
//					$metadata['sizes'] = array_map( function ( $size ) use ( $file_name, $mime_type ) {
//						$file_size         = $this->insert_sizes_to_link( $file_name, $size['width'], $size['height'] );
//						$size['file']      = $file_size;
//						$size['mime-type'] = $mime_type;
//						return $size;
//					}, $sizes );
//				}
			} else {
				$metadata['sizes'] = array();
			}
			// Fetch additional metadata from EXIF/IPTC.
			// TODO: support later for EXIF/IPTC.
//			$image_meta = wp_read_image_metadata( $file );
//			if ( $image_meta ) {
//				$metadata['image_meta'] = $image_meta;
//			}
			return $metadata;
		}

		/**
		 * Generate metadata for PDF file
		 *
		 * @param mixed $metadata Metadata.
		 *
		 * @return mixed $metadata Metadata.
		 */
		private function generate_metadata_for_pdf( $metadata ) {
			$fallback_sizes             = array(
				'thumbnail',
				'medium',
				'large',
			);
			$fallback_sizes             = apply_filters( 'fallback_intermediate_image_sizes', $fallback_sizes, $metadata );
			$sizes                      = array();
			$_wp_additional_image_sizes = wp_get_additional_image_sizes();
			foreach ( $fallback_sizes as $s ) {
				if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
					$sizes[ $s ]['width'] = intval( $_wp_additional_image_sizes[ $s ]['width'] );
				} else {
					$sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
				}
				if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
					$sizes[ $s ]['height'] = intval( $_wp_additional_image_sizes[ $s ]['height'] );
				} else {
					$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
				}
				if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
					$sizes[ $s ]['crop'] = $_wp_additional_image_sizes[ $s ]['crop'];
				} else {
					// Force thumbnails to be soft crops.
					if ( 'thumbnail' !== $s ) {
						$sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
					}
				}
			}
			if ( ! empty( $sizes ) ) {
				//call api to resize multiple images from one resource.
				//TODO: support PDF preview by load the first page as an image.
			}

			return $metadata;
		}

		/**
		 * Insert sizes to link or file name
		 *
		 * @param string $link   Link or file name.
		 * @param int    $width  Width.
		 * @param int    $height Height.
		 *
		 * @return mixed
		 */
		private function insert_sizes_to_link( $link, $width, $height ) {
			$match = array();
			preg_match( '/\.[^.]+$/', $link, $match );
			if ( count( $match ) === 1 ) {
				$file_extensions = end( $match );

				return str_replace( end( $match ), "-$width" . 'x' . "$height$file_extensions", $link );
			}
		}

		/**
		 * Setup for AWS
		 *
		 * @param array $options Options.
		 */
		private function setup_aws( $options ) {
			if ( $options ) {
				$this->s3 = new S3Client( [
					'version'          => 'latest',
					'region'           => $options['region'],
					'credentials'      => [
						'key'    => $options['aws_key'],
						'secret' => $options['aws_secret'],
					],
					'signatureVersion' => 'v4',
				] );
			}
		}

		/**
		 * Setup for WASABI
		 *
		 * @param array $options Options.
		 */
		private function setup_wasabi( $options ) {
			if ( $options ) {
				$this->s3 = new S3Client( [
					'version'          => 'latest',
					'endpoint'         => "https://s3.{$options['region']}.wasabisys.com",
					'region'           => $options['region'],
					'credentials'      => [
						'key'    => $options['aws_key'],
						'secret' => $options['aws_secret'],
					],
					'signatureVersion' => 'v4',
				] );
			}
		}
	}
}
