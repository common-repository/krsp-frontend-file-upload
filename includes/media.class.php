<?php

defined( 'ABSPATH' ) or exit;

class KRSP_Fronted_Media_Upload {

	private static $_instance = null;

	private $media_fields = array();

	protected $theme_name = 'krsp_file_upload';

	/**
	* Get Instance
	*
	* @return $instance
	*/
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
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
	private function _init()
	{

		$this->setup_fields();
		add_filter( 'attachment_fields_to_save', array($this,'saveFields'), 1, 2 );
		add_filter( 'attachment_fields_to_edit', array($this,'applyFilter'), 1, 2 );
	}

	public function setup_fields()
	{
		$this->setup_profile_field();
	}

	public function setup_profile_field()
	{

		$options = get_option( 'krsp_file_upload_settings' );
		$role = $options['krsp_file_upload_select_role'];

		$args = array(
			'role'          =>  'contributor',
			'meta_key'      =>  'account_status',
			'meta_value'    =>  'approved'
		);

		$users = get_users($args);
		$profiles[0] = 'Select A User';
		foreach ($users as $user) {
			$profiles[$user->ID] = $user->display_name;
		}

		$this->media_fields['image_profile'] = array(
			'label'       => __( 'Associated User', $this->theme_name ),
			'input'       => 'select',
			'options' => $profiles,
			'application' => 'image',
			'exclusions'   => array( 'audio', 'video' )
		);
	}

	public function saveFields($post, $attachment)
	{
		if ( ! empty( $this->media_fields ) ) {
			// Browse those fields
			foreach ( $this->media_fields as $field => $values ) {
				// If this field has been submitted (is present in the $attachment variable)
				if ( isset( $attachment[$field] ) ) {
					// If submitted field is empty
					// We add errors to the post object with the "error_text" parameter we set in the options
					if ( strlen( trim( $attachment[$field] ) ) == 0 )
					$post['errors'][$field]['errors'][] = __( $values['error_text'] );
					// Otherwise we update the custom field
					else
					update_post_meta( $post['ID'], '_' . $field, $attachment[$field] );
				}
				// Otherwise, we delete it if it already existed
				else {
					delete_post_meta( $post['ID'], $field );
				}
			}
		}

		return $post;
	}

	public function applyFilter( $form_fields, $post = null ) {
		// If our fields array is not empty
		if ( ! empty( $this->media_fields ) ) {
			// We browse our set of options
			foreach ( $this->media_fields as $field => $values ) {
				// If the field matches the current attachment mime type
				// and is not one of the exclusions
				if ( preg_match( "/" . $values['application'] . "/", $post->post_mime_type) && ! in_array( $post->post_mime_type, $values['exclusions'] ) ) {
					// We get the already saved field meta value
					$meta = get_post_meta( $post->ID, '_' . $field, true );

					// Define the input type to 'text' by default
					switch ( $values['input'] ) {
						case 'select':

						// Select type doesn't exist, so we will create the html manually
						// For this, we have to set the input type to 'html'
						$values['input'] = 'html';

						// Create the select element with the right name (matches the one that wordpress creates for custom fields)
						$html = '<select name="attachments[' . $post->ID . '][' . $field . ']">';

						// If options array is passed
						if ( isset( $values['options'] ) ) {
							// Browse and add the options
							foreach ( $values['options'] as $k => $v ) {
								// Set the option selected or not
								if ( $meta == $k )
								$selected = ' selected="selected"';
								else
								$selected = '';

								$html .= '<option' . $selected . ' value="' . $k . '">' . $v . '</option>';
							}
						}

						$html .= '</select>';

						// Set the html content
						$values['html'] = $html;

						break;

					}

					// And set it to the field before building it
					$values['value'] = $meta;

					// We add our field into the $form_fields array
					$form_fields[$field] = $values;
				}
			}
		}

		// We return the completed $form_fields array
		return $form_fields;
	}

	public static function do_activate( $network_wide )
	{
		if ( ! current_user_can( 'activate_plugins' ) )
		return;

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
	}

	public static function do_deactivate( $network_wide )
	{
		if ( ! current_user_can( 'activate_plugins' ) )
		return;

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

	public static function do_uninstall( $network_wide )
	{
		if ( ! current_user_can( 'activate_plugins' ) )
		return;

		check_admin_referer( 'bulk-plugins' );

		if ( __FILE__ != WP_UNINSTALL_PLUGIN  )
		return;
	}
}

register_activation_hook( __FILE__, 'KRSP_Fronted_Media_Upload::do_activate' );
register_deactivation_hook( __FILE__, 'KRSP_Fronted_Media_Upload::do_deactivate' );
register_uninstall_hook( __FILE__, 'KRSP_Fronted_Media_Upload::do_uninstall' );


