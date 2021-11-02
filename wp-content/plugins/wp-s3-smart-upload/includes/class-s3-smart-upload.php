<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://preventdirectaccess.com
 * @since      1.0.0
 *
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    S3_Smart_Upload
 * @subpackage S3_Smart_Upload/includes
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class S3_Smart_Upload {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      S3_Smart_Upload_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 's3-smart-upload';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - S3_Smart_Upload_Loader. Orchestrates the hooks of the plugin.
	 * - S3_Smart_Upload_i18n. Defines internationalization functionality.
	 * - S3_Smart_Upload_Admin. Defines all hooks for the admin area.
	 * - S3_Smart_Upload_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-i18n.php';

		/**
		 * The class responsible for defining the plugin constants
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-constants.php';
		/**
		 * The class responsible for defining helper functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-functions.php';

		/**
		 * The class responsible for AWS Services
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-s3-service.php';

		/**
		 * The class responsible for defining the API.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-api.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-s3-smart-upload-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-s3-smart-upload-public.php';

		/**
		 * Render ui for settings
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-page.php';

//		/**
//		 * Require delete attachment async task
//		 */
//		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-delete-s3-async-task.php';

		/**
		 * WooCommerce Services.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/services/class-service-woocommerce.php';

		/**
		 * Shortcode Services.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/services/class-service-shortcode.php';

		/**
		 * Dokan services.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/services/dokan/class-service-dokan.php';

		$this->load_gold_dependencies();

		$this->loader = new S3_Smart_Upload_Loader();

	}

	/**
	 * Function to load file for gold version
	 */
	private function load_gold_dependencies() {
		if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-configs.php' ) ) {
			$configs = require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-s3-smart-upload-configs.php';
			if ( 'gold' === $configs->type ) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gold/loader.php';
			}
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the S3_Smart_Upload_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new S3_Smart_Upload_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new S3_Smart_Upload_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 's3_smart_upload_create_plugin_submenu' );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'ssu_rest_api_init_cb', 10, 2 );
		$this->loader->add_filter( 'wp_get_attachment_url', $plugin_admin, 'change_file_url_in_media', 10, 2 );
//		$this->loader->add_filter( 'image_downsize', $plugin_admin, 'ssu_image_downsize', 10, 3 );
		$this->loader->add_filter( 'wp_calculate_image_srcset', $plugin_admin, 'ssu_image_srcset_meta', 10, 5 );
		$this->loader->add_filter( 'plugin_action_links_' . S3_SMART_UPLOAD_PLUGIN_BASE_NAME, $plugin_admin, 'handle_plugin_links', 30 );

		$this->loader->add_action( 'delete_attachment', $plugin_admin, 'handle_delete_post_meta', 10 );

		/**
		 * WooCommerce Actions.
		 */
		SSU_Service_Woo::get_instance();

		/**
		 * Dokan integration.
		 */
		SSU_Service_Dokan::get_instance($this->get_version());
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new S3_Smart_Upload_Public( $this->get_plugin_name(), $this->get_version() );

		/**
		 * Shortcode.
		 */
		SSU_Shortcode::get_instance()->register();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    S3_Smart_Upload_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

}
