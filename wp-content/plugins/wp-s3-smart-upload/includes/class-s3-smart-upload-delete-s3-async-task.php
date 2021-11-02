<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/17/18
 * Time: 13:57
 */

if ( ! class_exists( 'S3_Smart_Upload_Delete_Async_Task' ) ) {
	/**
	 * Class S3_Smart_Upload_Delete_Async_Task
	 */
	class S3_Smart_Upload_Delete_Async_Task extends WP_Async_Task {

		/**
		 * Action name
		 *
		 * @var string Action name
		 */
		protected $action = 'delete_attachment';

		/**
		 * Prepare data for async task
		 *
		 * @param mixed $data Async data.
		 *
		 * @return array
		 */
		protected function prepare_data( $data ) {
			$post_id = $data['0'];

			return array( 'post_id' => $post_id );
		}

		/**
		 * Run async task
		 */
		protected function run_action() {
			if ( ! isset( $_POST['post_id'] ) ) { // phpcs:ignore
				return;
			}
			$post_id = sanitize_key( $_POST['post_id'] ); // phpcs:ignore
			if ( isset( $post_id ) && 0 < absint( $post_id ) ) {
				do_action( "wp_async_$this->action", $post_id );
			}
		}

	}
}
