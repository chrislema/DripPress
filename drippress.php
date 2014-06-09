<?php
/*
Plugin Name: DripPress
Plugin URI: http://reaktivstudios.com/
Description: Control content visibility based on user signup date
Author: Andrew Norcross
Version: 1.0.0
Requires at least: 3.7
Author URI: http://reaktivstudios.com/
*/
/*  Copyright 2014 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if( ! defined( 'DRIPPRESS_BASE ' ) ) {
	define( 'DRIPPRESS_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'DRIPPRESS_DIR' ) ) {
	define( 'DRIPPRESS_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'DRIPPRESS_VER' ) ) {
	define( 'DRIPPRESS_VER', '1.0.0' );
}


class DripPress_Core
{

	/**
	 * Static property to hold our singleton instance
	 * @var $instance
	 */
	static $instance = false;

	/**
	 * this is our constructor.
	 * there are many like it, but this one is mine
	 */
	private function __construct() {
		add_action		(	'plugins_loaded',					array(  $this,  'textdomain'					)			);
		add_action		(	'plugins_loaded',					array(  $this,  'load_files'					)			);
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function getInstance() {

		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * load our textdomain for localization
	 *
	 * @return void
	 */
	public function textdomain() {

		load_plugin_textdomain( 'drippress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * load our secondary files
	 *
	 * @return void
	 */
	public function load_files() {

		require_once( DRIPPRESS_DIR . 'lib/data.php'	);
		require_once( DRIPPRESS_DIR . 'lib/front.php'	);
		if ( is_admin() ) {
			require_once( DRIPPRESS_DIR . 'lib/admin.php'	);
			require_once( DRIPPRESS_DIR . 'lib/meta.php'	);
		}

	}


/// end class
}

// Instantiate our class
$DripPress_Core = DripPress_Core::getInstance();