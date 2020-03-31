<?php
/**
 * Plugin Name: You Tube Oauth
 * Plugin URI: http://msp-media.org/
 * Description: Youtube OAuth Plugin to support virtual small group bible study
 * Author: megphillips91
 * Author URI: http://msp-media.org/
 * Version: 0.0.01
 * License: GPL2+
 * http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

 /*
 Youtube OAuth is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 Charter Boat Bookings is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Charter Boat Bookings. If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */
namespace YouTube_Oauth_VSG_Lessons;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Include plugin files
 */
require_once plugin_dir_path( __FILE__ ) . 'youtube-api.php';


?>