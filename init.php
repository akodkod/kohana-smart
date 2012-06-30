<?php defined('SYSPATH') or die('No direct script access.');

if (($path = Kohana::find_file('vendor', 'smarty/libs/Smarty.class')) === FALSE)
{
	throw new Smart_Exception('You must load Smarty in modules/smart/vendor/smarty');
}

require_once $path;
