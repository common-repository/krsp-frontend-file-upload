<?php

class KRSP_Frontend_Settings {
	private static $_instance = null;

	/**
	* Get Instance
	*
	* @return void
	*/
	public static function get_instance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	* Constructor
	*
	* @return void
	*/
	private function __construct()
	{

		$this->_init();

	}

	/**
	* Initialize Plugin
	*
	* @return void
	*/
	private function _init() {
		add_action( 'admin_menu', array(&$this, "krspAdminMenuPage") );
		add_action( 'admin_enqueue_scripts', array(&$this, 'krspAdminScripts') );
		add_action( 'rest_api_init', array( &$this, 'krspRestInit') );
	}


	/**
	* Adding our settings page menu item to the WP Admin menu
	*/
	public function krspAdminMenuPage($page)
	{
		$page_title = 'KRSP Frontend File Upload';

		# The menu item title for our settings page
		$menu_title = 'KRSP Frontend File Upload';

		# The user permission required to view our settings page
		$capability = 'manage_options';

		# The URL slug for our settings page
		$menu_slug = 'krsp_file_upload';

		# The callback function for rendering our settings page HTML
		$callback = array(&$this, 'krspRenderSettingsPage');

		# Adding a new top level menu item
		add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback );

		/**
		* Other possible locations for adding our menu item
		*/

		#$parent_menu_item = 'options-general.php';
		#$parent_menu_item = 'tools.php';
		#$parent_menu_item = 'edit.php';
		#$parent_menu_item = 'edit.php?post_type=page';

		#add_submenu_page( $parent_menu_item, $page_title, $menu_title, $capability, $menu_slug, $callback );
	}

	/**
	* Callback for rendering the plugin settings page
	*/
	function krspRenderSettingsPage() {
		require_once __DIR__ . '/views/settings.html';
	}

	/**
	* Enqueueing the JS and CSS for our plugin settings page
	*/
	public function krspAdminScripts($page)
	{
		$menu_slug = 'krsp_file_upload';

		# Check if we are currently viewing our setting page
		if( $menu_slug === substr( $page, -1 * strlen( $menu_slug ) ) ) {

			# Vue.js
			wp_enqueue_script( 'krsp-vue', 'https://unpkg.com/vue', array(), null, false );

			# Our plugin settings JS file
			wp_enqueue_script( 'krsp-settings', plugins_url( 'js/settings.js', __FILE__ ), array( 'krsp-vue', 'jquery' ), null, true );

			# Sending data to our plugin settings JS file
			wp_localize_script( 'krsp-settings', 'KRSP_Data', array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'siteUrl' => set_url_scheme(get_home_url()),
				'options' => get_option( 'krsp_file_upload_settings', ['limitUploads', 'uploadLimit', 'fileExtensions'] ),
				)
			);

			# Our plugin settings CSS file
			wp_enqueue_style( 'krsp-settings', plugins_url( 'css/settings.css', __FILE__ ) );
		}
	}

	/**
	* Saving our options via AJAX
	*/
	public function krspRestInit()
	{
		register_rest_route( 'krsp/v1', '/save', array(
			'methods' => 'POST',
			'callback' => function() {

				$uploadLimit = sanitize_text_field( $_POST['uploadLimit'] );
				$fileExtensions = sanitize_text_field( $_POST['fileExtensions'] );
				$sizeLimit = sanitize_text_field( $_POST['sizeLimit'] );
				$limitUploads = ( $_POST['limitUploads'] );

				update_option( 'krsp_file_upload_settings', array(
					'uploadLimit' => $uploadLimit,
					'limitUploads' => $limitUploads == 'true' ? true : false,
					'fileExtensions' => $fileExtensions,
					'sizeLimit' => $sizeLimit
					)
				);
				// die('1');
				wp_send_json(get_option('krsp_file_upload_settings'));
			},
			)
		);
	}
}