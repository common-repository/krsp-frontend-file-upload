<?php

defined('ABSPATH') or exit;

/**
* KRSP_Fronted_File_Upload
*
* @category Plugins
* @package  KRSP_Fronted_File_Upload
* @author   KRSP <agency@krsp.co>
* @license  http://www.license.com MIT
* @link     http://krsp.co
**/
class KRSP_Fronted_File_Upload
{

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
        add_action('wp_enqueue_scripts', array(&$this, 'enqueueScripts'));
        add_action('wp_ajax_krsp_file_upload', array(&$this, 'handleFileUpload'));
        add_action('wp_ajax_krsp_file_delete', array(&$this, 'handleFileDelete'));
        add_action('wp_ajax_krsp_files_save', array(&$this, 'handleBatchSave'));
        // not logged in users might need love too
        add_action('wp_ajax_krsp_get_images', array(&$this, 'handleGetImages'));
        add_shortcode('krsp_file_upload', array(&$this, 'addUploadShortcode'));

        $this->krsp_file_upload_freemius_optin();
        $this->krsp_file_upload_freemius_optin()->add_filter('connect_message_on_update', 'krsp_file_upload_custom_connect_message_on_update', 10, 6);
        do_action('krsp_file_upload_loaded');
    }

    /**
    * Enqueue Scripts
    *
    * @return void
    */
    public function enqueueScripts()
    {
        wp_enqueue_script( 'plupload-all' );
        wp_enqueue_script( 'krsp-vue', 'https://unpkg.com/vue', array(), null, false );
        wp_enqueue_script('krsp_frontend_file_upload', plugins_url("../includes/dist/build.js", __FILE__),  array('krsp-vue', 'plupload-all'), null, true);
        wp_enqueue_style('krsp-fontawesome', "//use.fontawesome.com/releases/v5.0.8/css/all.css", array('krsp_frontend_file_upload'));
        wp_enqueue_style('krsp_frontend_file_upload', plugins_url('../includes/dist/build.css', __FILE__));
        wp_localize_script('krsp_frontend_file_upload', 'krsp_file_upload', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce'=> wp_create_nonce('krsp_ajax_nonce', 'krsp_ajax_nonce'),
            'plugin_dir' => plugin_dir_url(__FILE__),
            'profile' => get_current_user_id(),
            'uploader_options' => get_option( 'krsp_file_upload_settings' )
        ));
    }
    /**
    * Insert An Attachment
    *
    * @param [type] $file
    * @param [type] $fileData
    * @return void
    */
    public function insertAttachment($file, $fileData)
    {

        if (!file_exists($file['file']) || 0 === strlen(trim($fileData['name']))) {
            error_log('The file you are attempting to upload, ' . $file['name'] . ', does not exist.');
            wp_send_json_error('The file you are attempting to upload, ' . $file['name'] . ', does not exist.');
        }

        $profile = isset($_POST['profile']) ? $_POST['profile'] : get_current_user_id();

        $wp_upload_dir = wp_upload_dir();

        $filename = basename($file['url']);
        $guid =  $wp_upload_dir['url'] . "/$filename";

        $attach_file = $wp_upload_dir['path'] . "/$filename";

        $filetype = wp_check_filetype(basename($filename), null);

        $attachment = array(
            'guid' => $guid,
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content'   => 'Image for ' . $filename,
            'post_status'    => 'inherit'
        );

        include_once ABSPATH .'wp-admin/includes/image.php';

        $attachment_obj = wp_insert_attachment($attachment, $attach_file, null);

        if (is_wp_error($attachment_obj)) {
            return $attachment_obj->get_error_message();
        }

        $attach_data = wp_generate_attachment_metadata($attachment_obj, $attach_file);
        wp_update_attachment_metadata($attachment_obj, $attach_data);

        return $attachment_obj;
    }

    /**
    * Handle Attachment Upload
    *
    * @param [type] $file_handler
    * @param [type] $post_id
    * @return void
    */
    protected function handle_attachment($file_handler,$post_id)
    {
        // check to make sure its a successful upload
        if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) __return_false();

        require_once ABSPATH . "wp-admin" . '/includes/image.php';
        require_once ABSPATH . "wp-admin" . '/includes/file.php';
        require_once ABSPATH . "wp-admin" . '/includes/media.php';

        $attachment_obj = media_handle_upload($file_handler, $post_id);
        if (is_numeric( $attachment_obj)) {
            update_post_meta($post_id, '_krsp_file_upload', $attachment_obj);
        }
    }

    public function handleFileUpload()
    {
        // Handle Plupload

        if (check_ajax_referer('krsp_ajax_nonce', 'krspnc', false)) {
            $response = array();


            if (!(is_array($_POST) && is_array($_FILES) && defined('DOING_AJAX') && DOING_AJAX)) {
                return null;
            }

            if (!function_exists('wp_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            if (isset($_FILES['async-upload']) || !empty($_FILES['async-upload'])) {

                $upload_overrides = array('test_form' => false);

                $file = $_FILES['async-upload'];

                if ($file) {
                    $wp_file = array(
                        'name'     => $file['name'],
                        'type'     => $file['type'],
                        'tmp_name' => $file['tmp_name'],
                        'error'    => $file['error'],
                        'size'     => $file['size']
                    );

                    $movefile = wp_handle_upload($wp_file, $upload_overrides);

                    if ($movefile && !isset($movefile['error'])) {
                        $attach_file = $this->insertAttachment($movefile, $wp_file);
                        // $response['debug'][] = $attach_file;
                        if (is_int($attach_file)) {

                            $profile = isset($_REQUEST['profile']) ? $_REQUEST['profile'] : get_current_user_id();

                            $this->updatePostMeta($attach_file, '_image_profile', $profile);

                            $image = wp_prepare_attachment_for_js($attach_file);

                            $response['image'] = array(
                                'id' => $image['id'],
                                'caption' => ucwords($image['caption']),
                                'size' => $image['filesizeInBytes'],
                                'height' => $image['height'],
                                'width' => $image['width'],
                                'url' => $image['url'],
                                'complete' => true,
                                'profile' => $profile,
                                'name' => $file['name']
                            );

                            if(WP_DEBUG) $response['attach_file'] = wp_prepare_attachment_for_js($attach_file);
                        }

                        $response['image']['complete'] = true;
                    } else {
                        $response['image']['complete'] = false;
                    }
                }
            }
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Security failed');
        }
    }


    /**
    * Handle File delete
    *
    * @return void
    */
    public function handleFileDelete()
    {
        if (is_user_logged_in() && current_user_can('delete_posts')) {

            $res = array();

            $permission = check_ajax_referer('krsp_ajax_nonce', 'krspnc', false);

            if ($permission == false  || !$permission) {
                wp_send_json_error('You can\'t delete posts');
            } else {
                $id = $_REQUEST['id'];


                if (!is_int(intval($id))) {
                    wp_send_json_error($res);
                }

                wp_delete_attachment($id);
            }

            wp_send_json_success($res);
        }
    }


    /**
    * Handle getting imeages
    *
    * @return json
    */
    public function handleGetImages()
    {

        if (is_user_logged_in()) {
            $permission = check_ajax_referer('krsp_ajax_nonce', 'krspnc', false);
            if ($permission) {
                $images = array();
                $profile = isset($_GET['profile']) ? $_GET['profile'] : get_current_user_id();

                $args = array(
                    'post_type' => 'attachment',
                    'post_status' => 'inherit',
                    'posts_per_page' => -1,
                    'offset' => 0,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_image_profile',
                            'value' => $profile,
                            'compare' => 'OR'
                        ),
                        )
                    );


                    $posts = get_posts($args);
                    foreach ($posts as $post) {

                        $image = wp_get_attachment_image_src($post->ID, 'full');
                        $size = filesize(get_attached_file($post->ID));

                        $images[] = array(
                            'id' => $post->ID,
                            'caption' => ucwords($post->post_title),
                            'name' => $post->post_name,
                            'size' => $size,
                            'height' => $image[2],
                            'width' => $image[1],
                            'url' => $image[0],

                        );

                    }

                    wp_send_json(array('images'=>$images ));
                }
            } else {
                wp_send_json_error(array('error'=> 'You\'re not logged in!'));
            }

        }

        /**
        * Handle Batch saving of images
        *
        * @return void
        */
        public function handleBatchSave()
        {
            if (is_user_logged_in() && current_user_can('delete_posts')) {

                $res = array('debug' => WP_DEBUG);

                $permission = check_ajax_referer('krsp_ajax_nonce', 'krspnc', false);

                if ($permission == false  || !$permission) {
                    wp_send_json_error('You can\'t update posts without being logged in');
                }
                $meta = [];
                $postedData = $_POST["uploads"];
                $meta['profile'] = isset($_POST['profile']) ? intval(sanitize_text_field($_POST['profile'])) : get_current_user_id();
                // $tempData = html_entity_decode($postedData);
                $files = json_decode(stripslashes($postedData));
                $res['error'] = json_last_error();
                if (null !== $files) {
                    foreach ($files as $file) {
                        if(WP_DEBUG) $res['files'][] = isset($file->id);
                        $attach_id = $file->id;

                        $post = get_post($attach_id);
                        if (null !== $file->caption && $file->caption <> '') {
                            if(WP_DEBUG) $res['new_caption'] = isset($file->new_caption);
                            $post->post_title = $file->caption;
                        } elseif (null !== $file->new_caption && $file->new_caption <> '') {
                            if (WP_DEBUG) $res['new_caption'] = isset($file->new_caption);
                            $post->post_title = $file->new_caption;
                        } else {
                            if ($file->caption == '' || $file->new_caption == '') {
                                $post->post_title = '';
                            }
                        }

                        $updated_file = wp_update_post($post, true);
                        $this->updatePostMeta($post->ID, '_image_profile', $meta['profile']);

                        if (is_wp_error($updated_file)) {
                            $errors[] = $updated_file->get_error_messages();
                            foreach ($errors as $error) {
                                if(WP_DEBUG) $res['errors'][] = $error;
                            }
                        } else {
                            $res['images'][] = array(
                                'updated_id'=> $updated_file,
                                'id' => $attach_id,
                                'new_caption' => $post->post_title,
                                'status' => 'complete', );
                            }
                        }
                    }

                    wp_send_json_success($res);
                }
            }

            /**
            * update_post_meta
            *
            * @param [type] $post
            * @param [type] $field
            * @param [type] $value
            * @return $content
            */
            public function updatePostMeta($post, $field, $value)
            {
                if (! add_post_meta($post, $field, $value, true)) {
                    return update_post_meta($post, $field, $value);
                }
                return false;
            }

            /**
            * addUploadShortcode
            *
            * @param [type] $atts
            * @return void
            */
            public function addUploadShortcode($atts)
            {
                extract(shortcode_atts(array(
                    'id' => 0,
                    'role' => 'member'
                ), $atts));

                ob_start();

                // $form_settings = wpuf_get_form_settings( $id );
                // $info          = apply_filters( 'wpuf_addpost_notice', '', $id, $form_settings );
                // $user_can_post = apply_filters( 'wpuf_can_post', 'yes', $id, $form_settings );

                if ( current_user_can('delete_posts') ) {
                    $this->renderForm();
                } else {
                    echo '<div class="wpuf-info">' . $info . '</div>';
                }

                $content = ob_get_contents();
                ob_end_clean();

                return $content;
            }



            /**
            * Renders Form
            *
            * @return String
            */
            public function renderForm()
            {
                ?>
                <div id="krsp_file_upload">
                <app></app>
                </div>
                <?php
            }

            /**
            * Activate Plugin
            * @return void
            **/
            public static function do_activate()
            {
                if ( ! current_user_can( 'activate_plugins' ) )
                return;

                $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
                check_admin_referer( "activate-plugin_{$plugin}" );
            }
            /**
            * Deactivate Plugin
            * @return void
            **/
            public static function do_deactivate()
            {
                if ( ! current_user_can('activate_plugins'))
                return;

                $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
                check_admin_referer("deactivate-plugin_{$plugin}");
            }
            /**
            * Uninstall Plugin
            * @return void
            * */
            public static function do_uninstall(  )
            {
                if (!current_user_can('activate_plugins'))
                return;

                check_admin_referer('bulk-plugins');

                if ( __FILE__ !== WP_UNINSTALL_PLUGIN  )
                return null;
            }


            // Add freemius analytics
            public function krsp_file_upload_freemius_optin() {
                global $krsp_file_upload;

                if ( ! isset( $krsp_file_upload ) ) {
                    // Include Freemius SDK.
                    require_once KRSP_PLUGIN_PATH . 'freemius/start.php';

                    $krsp_file_upload = fs_dynamic_init( array(
                        'id'                  => '1845',
                        'slug'                => 'krsp-file-upload',
                        'type'                => 'plugin',
                        'public_key'          => 'pk_8218a0e85fc5cb1ca46bad2879d11',
                        'is_premium'          => false,
                        'has_addons'          => false,
                        'has_paid_plans'      => false,
                        'is_org_compliant'    => false,
                        'menu'                => array(
                            'slug'           => 'krsp_file_upload',
                            'account'        => true,
                            'contact'        => true,
                            'support'        => false,
                            'parent'         => array(
                                'slug' => 'options-general.php',
                            ),
                        ),
                        )
                    );
                }

                return $krsp_file_upload;
            }

           protected function krsp_file_upload_custom_connect_message_on_update(
                $message,
                $user_first_name,
                $plugin_title,
                $user_login,
                $site_link,
                $freemius_link
                ) {
                    return sprintf(
                        __( 'Hey %1$s' ) . ',<br>' .
                        __( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'krsp-file-upload' ),
                        $user_first_name,
                        '<b>' . $plugin_title . '</b>',
                        '<b>' . $user_login . '</b>',
                        $site_link,
                        $freemius_link
                    );
                }

            }

            register_activation_hook(__FILE__, 'KRSP_Fronted_File_Upload::do_activate');
            register_deactivation_hook(__FILE__, 'KRSP_Fronted_File_Upload::do_deactivate');
            register_uninstall_hook(__FILE__, 'KRSP_Fronted_File_Upload::do_uninstall');
