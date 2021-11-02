<?php

/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 8/11/18
 * Time: 16:02
 */
class SSU_CONSTANTS {
	const AWS_OPTION_NAME  = 'ssu_aws_option';
	const BUILD_INFO       = 'ssu_build_info';
	const GOLD_TYPE_PLUGIN = 'gold';
	const AWS_BUCKET_CONFIG = 'ssu_bucket_configuration';
	const AWS_BUCKET_POST_FIX = '-free-user';

	const AWS_KEY_VAR = 'SSU_AWS_KEY';
	const AWS_SECRET_VAR = 'SSU_AWS_SECRET';
	const AWS_BUCKET_VAR = 'SSU_AWS_BUCKET';
	const SSU_AWS_REGION_VAR = 'SSU_AWS_REGION';
	const SSU_AWS_SUB_FOLDER_VAR = 'SSU_AWS_SUB_FOLDER';
	const SSU_ACL = 'SSU_ACL';
	const SSU_WP_REMOVE_VAR = 'SSU_WP_REMOVE';

	const KEY_VAR = 'SSU_KEY';
	const PROVIDER_VAR = 'SSU_PROVIDER';
	const SECRET_VAR = 'SSU_SECRET';
	const BUCKET_VAR = 'SSU_BUCKET';
	const REGION_VAR = 'SSU_REGION';
	const FOLDER_VAR = 'SSU_FOLDER';
	const MAX_FILE_UPLOAD_SIZE = 512;

	const BAD_REQUEST_MESSAGE = 'Our server cannot understand the data request!';

	const TYPE_SERVICE = array(
		'AWS'    => 'aws',
		'WASABI' => 'wasabi',
	);

	const SUB_MENU = 's3-smart-upload-settings';
}
