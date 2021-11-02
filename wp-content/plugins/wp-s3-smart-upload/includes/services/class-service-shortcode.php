<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/28/19
 * Time: 11:24
 */

/**
 *
 * Class PPW_Shortcode
 */
class SSU_Shortcode {
	/**
	 * Instance of SSU_Shortcode class.
	 *
	 * @var SSU_Shortcode
	 */
	protected static $instance = null;

	/**
	 * SSU_Shortcode constructor.
	 */
	public function __construct() {
	}

	public function register() {
		add_shortcode( 'ssu_upload', array( $this, 'render_shortcode_ssu_s3' ) );
	}

	/**
	 * Get short code instance
	 *
	 * @return SSU_Shortcode
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			// Use static instead of self due to the inheritance later.
			// For example: ChildSC extends this class, when we call get_instance
			// it will return the object of child class. On the other hand, self function
			// will return the object of base class.
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Enqueue script.
	 */
	public function enqueue_script() {
		wp_enqueue_script( S3_SMART_UPLOAD_PLUGIN_BASE_NAME . '-shortcode', S3_SMART_UPLOAD_PLUGIN_BASE_URL . '/public/js/ssu-shortcode.js', array( 'jquery' ), S3_SMART_UPLOAD_VERSION, false );

		wp_localize_script( S3_SMART_UPLOAD_PLUGIN_BASE_NAME . '-shortcode', 'ssuData', array(
			'rest_url'           => get_rest_url(),
			'nonce'              => wp_create_nonce( 'wp_rest' ),
			'max_file_size'      => SSU_CONSTANTS::MAX_FILE_UPLOAD_SIZE,
			'enable_restriction' => ssu_enable_restriction(),
			'messages'           => ssu_get_messages(),
		) );
	}

	/**
	 * Render signed url for shortcode.
	 *
	 * @param array  $attrs   list of attributes including password.
	 * @param string $content the content inside short code.
	 *
	 * @return string
	 */
	public function render_shortcode_ssu_s3( $attrs, $content = null ) {
		$attrs = shortcode_atts(
			array(
				'button'      => 'Upload to S3',
				'error_msg'   => 'An unexpected error occurred while uploading your file, please try again later.',
				'success_msg' => 'File is uploaded successfully',
				'class'       => '',
			),
			$attrs,
			'ssu_upload'
		);

		static $position = 0;
		$position ++;

		if ( 1 === $position ) {
			$this->enqueue_script();

			do_action( 'ssu_s3_enqueue_script', $attrs );
		}
		$post_id = get_the_ID();
		ob_start();
		?>
		<div class="ssu-upload <?php echo esc_attr( $attrs['class'] ); ?>">
			<form class="ssu-upload-form">
				<input type="file" class="ssu-file">
				<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
				<input type="submit" value="<?php esc_attr_e( $attrs['button'], 's3-smart-upload' ); ?>">
			</form>
			<p class="ssu-message"
			   data-error="<?php esc_attr_e( $attrs['error_msg'], 's3-smart-upload' ); ?>"
			   data-success="<?php esc_attr_e( $attrs['success_msg'], 's3-smart-upload' ); ?>"
			   style="display: none"
			></p>
		</div>
		<?php

		return ob_get_clean();
	}

}
