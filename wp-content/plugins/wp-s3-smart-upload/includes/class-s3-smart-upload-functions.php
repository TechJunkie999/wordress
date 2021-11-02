<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/4/18
 * Time: 16:36
 *
 * @package SSU
 */

/**
 * Get AWS Options defined in wp-config.php
 *
 * @return array|bool
 */
function ssu_get_s3_options() {
	$new_options = ssu_get_s3_new_options();
	if ( false !== $new_options ) {
		return $new_options;
	}

	$keys = [
		SSU_CONSTANTS::AWS_KEY_VAR,
		SSU_CONSTANTS::AWS_SECRET_VAR,
		SSU_CONSTANTS::AWS_BUCKET_VAR,
		SSU_CONSTANTS::SSU_AWS_REGION_VAR,
	];

	foreach ( $keys as $key ) {
		if ( ! defined( $key ) ) {
			return false;
		}
	}

	$subfolder = defined( SSU_CONSTANTS::SSU_AWS_SUB_FOLDER_VAR ) && ! empty( SSU_AWS_SUB_FOLDER )
		? SSU_AWS_SUB_FOLDER
		: '';

	return array(
		'aws_key'    => SSU_AWS_KEY,
		'aws_secret' => SSU_AWS_SECRET,
		'bucket'     => SSU_AWS_BUCKET,
		'region'     => SSU_AWS_REGION,
		'prefix_key' => ssu_get_upload_folder( $subfolder ),
		'domain'     => home_url() // phpcs:ignore
	);
}

/**
 * Get upload folder.
 *
 * @param string $folder Folder.
 *
 * @return string Upload folder.
 */
function ssu_get_upload_folder( $folder ) {
	return apply_filters( 'ssu_upload_folder', $folder );
}

/**
 * Get AWS Options defined in wp-config.php
 *
 * @return array|bool
 */
function ssu_get_s3_new_options() {
	$keys = [
		SSU_CONSTANTS::PROVIDER_VAR,
		SSU_CONSTANTS::KEY_VAR,
		SSU_CONSTANTS::SECRET_VAR,
		SSU_CONSTANTS::BUCKET_VAR,
		SSU_CONSTANTS::REGION_VAR,
	];

	foreach ( $keys as $key ) {
		if ( ! defined( $key ) ) {
			return false;
		}
	}

	$subfolder = defined( SSU_CONSTANTS::FOLDER_VAR ) && ! empty( SSU_FOLDER )
		? SSU_FOLDER
		: '';

	return array(
		'type'       => SSU_PROVIDER,
		'aws_key'    => SSU_KEY,
		'aws_secret' => SSU_SECRET,
		'bucket'     => SSU_BUCKET,
		'region'     => SSU_REGION,
		'prefix_key' => ssu_get_upload_folder( $subfolder ),
		'domain'     => home_url(), // phpcs:ignore,
	);
}

/**
 * Get s3 base url
 *
 * @return string
 */
function get_s3_baseurl( $url ) {
	$split = explode( '/', $url );
	array_pop( $split );

	return implode( '/', $split ) . '/';
}


/**
 * Insert size to link
 *
 * @param string $link   Link.
 * @param int    $width  Width.
 * @param int    $height Height.
 *
 * @return mixed
 */
function ssu_insert_size_to_link( $link, $width, $height ) {
	preg_match( '/\.[^.]+$/', $link, $match );
	if ( count( $match ) === 1 ) {
		$file_extensions = end( $match );

		return str_replace( end( $match ), "-$width" . 'x' . "$height$file_extensions", $link );
	}
}

/**
 * Get baseupload URL
 *
 * @param string $image_meta Image Data.
 *
 * @return string
 */
function get_base_upload_url( $image_meta ) {
	$upload_dir    = wp_get_upload_dir();
	$dirname       = _wp_get_attachment_relative_path( $image_meta['file'] );
	$image_baseurl = trailingslashit( $upload_dir['baseurl'] ) . $dirname;
	if ( is_ssl() && 'https' !== substr( $image_baseurl, 0, 5 ) && parse_url( $image_baseurl, PHP_URL_HOST ) === $_SERVER['HTTP_HOST'] ) {
		$image_baseurl = set_url_scheme( $image_baseurl, 'https' );
	}

	return $image_baseurl;
}

/**
 * Get file name from URL.
 *
 * @param string $url Input URL.
 *
 * @return mixed
 */
function get_file_name_from_url( $url ) {
	$file_url = wp_basename( $url );
	$tmp      = explode( '?', $file_url );
	// support unicode file name, example: اللغة العربية الفصحى.
	$file_name = urldecode( reset( $tmp ) );

	return $file_name;
}

/**
 * Update build info
 *
 * @param int    $build_num Build number.
 * @param string $status    Build 's status.
 *
 * @return mixed Update's result.
 */
function update_build_info( $build_num, $status = 'running' ) {
	$info = array(
		'build_num' => $build_num,
		'status'    => $status,
	);

	return update_option( SSU_CONSTANTS::BUILD_INFO, wp_json_encode( $info ) );
}

/**
 * Get build info
 *
 * @return mixed Build information
 */
function get_build_info() {
	$info = get_option( SSU_CONSTANTS::BUILD_INFO );

	return json_decode( $info );
}

/**
 * Delete aws options.
 *
 * @return mixed Delete option's result
 */
function delete_aws_options() {
	return delete_option( SSU_CONSTANTS::AWS_OPTION_NAME );
}

/**
 * Delete build options.
 *
 * Clear build options data
 */
function delete_build_options() {
	return delete_option( SSU_CONSTANTS::BUILD_INFO );
}

/**
 * Check the plugin type
 *
 * @param string $type Plugin type.
 *
 * @return bool
 */
function check_plugin_type( $type ) {
	$configs = require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-configs.php';

	return $configs->type === $type;
}

/**
 * Get attachment's sizes
 *
 * @param int $attachment_id Attachment's ID.
 *
 * @return array Sizes
 */
function get_attachment_sizes_file_name( $attachment_id ) {
	$metadata   = wp_get_attachment_metadata( $attachment_id );
	$file_names = array();
	if ( $metadata && array_key_exists( 'sizes', $metadata ) ) {
		$sizes      = $metadata['sizes'];
		$file_names = array_map( function ( $item ) {
			return $item['file'];
		}, $sizes );
	}

	return $file_names;
}


/**
 * Return the upload dir wp-content/upload/yy/mm/dd
 *
 * @return string
 */
function ssu_get_upload_dir() {
	$upload       = wp_upload_dir();
	$upload_path  = str_replace( site_url( '/' ), '', $upload['baseurl'] );
	$current_date = date( 'Y/m/d' );

	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		return "$upload_path/$current_date";
	}

	return $upload_path;
}

/**
 * Check compatibility with PDA S3
 *
 * @return bool
 */
function ssu_check_conflict_with_pda_s3() {
	return defined( 'pda_s3_VERSION' ) && version_compare(pda_s3_VERSION, '1.1.5.2', '<=');
}

/**
 * Get mime type.
 *
 * @param string $file_name File name.
 *
 * @return bool|string
 * @since 1.2.0
 */
function ssu_get_mime_type( $file_name ) {
	$wp_filetype = wp_check_filetype( $file_name );
	if ( $wp_filetype ) {
		return $wp_filetype['type'];
	}

	$mime_types = array(
		'txt'  => 'text/plain',
		'htm'  => 'text/html',
		'html' => 'text/html',
		'php'  => 'text/html',
		'css'  => 'text/css',
		'js'   => 'application/javascript',
		'json' => 'application/json',
		'xml'  => 'application/xml',
		'swf'  => 'application/x-shockwave-flash',
		'flv'  => 'video/x-flv',

		// images
		'png'  => 'image/png',
		'jpe'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'gif'  => 'image/gif',
		'bmp'  => 'image/bmp',
		'ico'  => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif'  => 'image/tiff',
		'svg'  => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// archives
		'zip'  => 'application/zip',
		'rar'  => 'application/x-rar-compressed',
		'exe'  => 'application/x-msdownload',
		'msi'  => 'application/x-msdownload',
		'cab'  => 'application/vnd.ms-cab-compressed',

		// audio/video
		'mp3'  => 'audio/mpeg',
		'qt'   => 'video/quicktime',
		'mov'  => 'video/quicktime',
		'mp4'  => 'video/mp4',
		'm4v'  => 'video/mp4',

		// adobe
		'pdf'  => 'application/pdf',
		'psd'  => 'image/vnd.adobe.photoshop',
		'ai'   => 'application/postscript',
		'eps'  => 'application/postscript',
		'ps'   => 'application/postscript',

		// ms office
		'doc'  => 'application/msword',
		'rtf'  => 'application/rtf',
		'xls'  => 'application/vnd.ms-excel',
		'ppt'  => 'application/vnd.ms-powerpoint',

		// open office
		'odt'  => 'application/vnd.oasis.opendocument.text',
		'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
	);

	$file_array = explode( '.', $file_name );
	$ext        = strtolower( array_pop( $file_array ) );
	if ( isset( $mime_types[ $ext ] ) ) {
		return $mime_types[ $ext ];
	}

	return false;
}

/**
 * Get capability.
 *
 * @return string
 * @since 1.2.0
 */
function ssu_get_capability() {
	return defined( 'SSU_CAPABILITY' ) ? SSU_CAPABILITY : 'manage_options';
}

/**
 * Log message
 *
 * @param string $message Message
 */
function ssu_log_message( $message ) {
	error_log( 'SSU Message: ' . print_r( $message, true ) );
}

/**
 * Enable plugin restriction.
 *
 * @return bool
 *  True if PDA Gold activated and have valid license.
 */
function ssu_enable_restriction() {
	if ( ! defined( 'PDA_GOLD_V3_VERSION' ) ) {
		return true;
	}

	$have_licensed = get_option( PDA_v3_Constants::LICENSE_OPTIONS );
	if ( ! $have_licensed ) {
		return true;
	}

	return false;
}

/**
 * Get client common messages.
 * @return array
 */
function ssu_get_messages() {
	return [
		'SSU_BUTTON_LABEL'           => __( 'Offload to S3', 's3-smart-upload' ),
		'SSU_BUTTON_PROCESS_STATUS'  => __( 'Processing', 's3-smart-upload' ),
		'SSU_BUTTON_MEDIA_STATUS'    => __( 'Creating media', 's3-smart-upload' ),
		'SSU_ADD_TO_MEDIA_OPT'       => __( 'Add to Media Library', 's3-smart-upload' ),
		'SSU_SERVER_ERROR'           => __( 'Something went wrong. Please double check your configuration.', 's3-smart-upload' ),
		'SSU_FILE_UPLOAD_ERROR'      => __( 'Failed to upload file. Please see Debugging in WordPress or browser console for more information.', 's3-smart-upload' ),
		'SSU_FILE_MAX_FILE_ERROR'    => __( 'The maximum upload file size in the free version is 512MB', 's3-smart-upload' ),
		'SSU_FILE_MAX_SIZE_ERROR'    => __( 'The maximum upload file size is 5GB', 's3-smart-upload' ),
		'SSU_SUCCESS_TO_UPLOAD_FILE' => __( 'You’ve successfully uploaded the following file:', 's3-smart-upload' ),
	];
}

/**
 * Is folder exist.
 *
 * @param string $folder Folder
 *
 * @return bool
 */
function ssu_is_folder_empty( $folder ) {
	return '' === $folder || false === $folder || null === $folder;
}
