<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/14/18
 * Time: 06:59
 */

if ( ! class_exists( 'SSU_Build_Service' ) ) {
	/**
	 * Class for defining the build services
	 */
	class SSU_Build_Service {
		/**
		 * Build image API stack to the AWS Account.
		 *
		 * @param mixed $data AWS key and secret.
		 *
		 * @return array Build metadata's result.
		 */
		public static function build_image_api_stack( $data ) {
			$aws_options = ssu_get_s3_options();
			$body        = array(
				'build_parameters' => array(
					'AWS_ACCESS_KEY_ID'     => $data['aws_key'],
					'AWS_SECRET_ACCESS_KEY' => $data['aws_secret'],
					'BUCKET_NAME'           => $aws_options['bucket'],
					'REGION'                => $aws_options['region'],
				),
			);
			$args        = array(
				'body'        => wp_json_encode( $body ),
				'timeout'     => '100',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
			);
			$result      = array(
				'isError' => true,
				'data'    => '',
			);
			$response    = wp_remote_post( 'https://circleci.com/api/v1.1/project/bitbucket/ymese_dev/lambda_func/tree/develop?circle-token=eae4a32e00de640bce426a62e9911b6f0e6ceb2a', $args );
			if ( is_wp_error( $response ) ) {
				$result['message'] = $response->get_error_message();
			} else {
				$body           = wp_remote_retrieve_body( $response );
				$data           = json_decode( $body );
				$result['data'] = $data;
				update_build_info( $result['data']->build_num, $result['data']->status );
				$result['isError'] = false;
			}

			return $result;
		}

		/**
		 * Get build's status
		 */
		public static function get_build_status() {
			$build_info = get_build_info();
			$result     = array(
				'isError' => true,
				'data'    => array(
					'status' => 'not_running',
				),
			);
			if ( ! $build_info ) {
				return $result;
			}
			if ( 'success' === $build_info->status ) {
				$result['data']    = array(
					'build_num' => $build_info->build_num,
					'status'    => $build_info->status,
					'failed'    => false,
				);
				$result['isError'] = false;

				return $result;
			}
			$build_num = $build_info->build_num;
			$args      = array(
				'timeout'     => '100',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
			);
			$url       = "https://circleci.com/api/v1.1/project/bitbucket/ymese_dev/lambda_func/$build_num?circle-token=eae4a32e00de640bce426a62e9911b6f0e6ceb2a";
			$response  = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				$result['message'] = $response->get_error_message();
			} else {
				$body              = wp_remote_retrieve_body( $response );
				$data              = json_decode( $body );
				$result['data']    = $data;
				$result['isError'] = false;
				update_build_info( $result['data']->build_num, $result['data']->status );
			}
			return $result;
		}
	}
}
