<?php
/**
 * Plugin Name: KRSP Frontend File Upload
 * Description: Ajax Frontend Media/File uploader
 * Author: KRSP Digital Agency
 * Author URI: http://www.krsp.co
 * Version: 1.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: krsp_file_upload
 * Domain Path: .
 * Network: false
 *
 *
 * KRSP Frontend File Upload is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Name is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Name. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */


defined( 'ABSPATH' ) or exit;
define( 'KRSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require ('includes/uploader.class.php');
require ('includes/media.class.php');
require ('includes/settings.class.php');

add_action('plugins_loaded', 'frontend_upload_init');

function frontend_upload_init(){
  $frontend_upload = KRSP_Fronted_File_Upload::get_instance();
  $frontend_upload_media = KRSP_Fronted_Media_Upload::get_instance();
  $frontend_upload_settings = KRSP_Frontend_Settings::get_instance();
}