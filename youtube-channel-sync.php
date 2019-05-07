<?php
/**
 * Plugin Name:       YouTube Channel Sync by WP Perf
 * Plugin URI:        https://wp-perf.io
 * Description:       Sync Channel, Playlists, and Videos from YouTube with your WordPress website.
 * Version:           1.0.0
 * Author:            WP Perf
 * Author URI:        https://wp-perf.io
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       wpp-youtube
 * Domain Path:       /languages
 *
 * @package           WP_Perf\YouTube_Channel_Sync
 * @author            WP Perf <wpperf@gmail.com>
 * @version           1.0.0
 *
 * Copyright (C) 2019 WP Perf
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */


use \WP_Perf\YouTube_Channel_Sync\Plugin;

defined( 'ABSPATH' ) || die( 'No script kiddies.' );

/**
 * Autoloader
 */
require 'autoload.php';

/**
 * Vendor packages
 */
require 'vendor/autoload.php';

/**
 * Provide access to the plugin without adding to $GLOBALS
 *
 * @return Plugin The Plugin singleton
 */
function wpp_youtube() {
	$youtube = Plugin::get_instance();

	return $youtube;
}

// Go!
wpp_youtube();

/**
 * Plugin lifecycle hooks.
 */
register_activation_hook( __FILE__, [ '\WP_Perf\YouTube_Channel_Sync\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\WP_Perf\YouTube_Channel_Sync\Plugin', 'deactivate' ] );
register_uninstall_hook( __FILE__, [ '\WP_Perf\YouTube_Channel_Sync\Plugin', 'uninstall' ] );

