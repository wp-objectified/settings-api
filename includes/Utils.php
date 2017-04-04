<?php

namespace WPObjectified\SettingsAPI;

abstract class Utils {
	/**
	 * @param $callback
	 * @return callable|false
	 */
	public static function check_callback( $callback ) {
		return is_callable( $callback ) ? $callback : false;
	}
}
