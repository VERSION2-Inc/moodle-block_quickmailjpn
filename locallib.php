<?php
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/blocks/quickmailjpn/constants.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/jphpmailer.php';

function block_quickmailjpn_autoload($classname) {
	global $CFG;

	if (strpos($classname, 'ver2\\quickmailjpn') === 0) {
		$classname = preg_replace('/^ver2\\\\quickmailjpn\\\\/', '', $classname);

		$classdir = $CFG->dirroot . '/blocks/quickmailjpn/class/';
		$path = $classdir . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
		if (is_readable($path)) {
			require $path;
		}
	}
}

spl_autoload_register('block_quickmailjpn_autoload');
