<?php

/**
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * Global configuration. DO NOT CHANGE THIS FILE!
 * Copy this file as config.inc and then make your changes in
 * the file new.
 */

// Setup global default configuration. This array is the only place
// where you should make changes. Make changes only after your
// have copied this file into config.inc, and then only make
// the changes in the config.inc file itself.
$php_chat_script = array(
  'password'     => 'password',
  'path_inc'     => preg_replace('/\/[^\/]*$/', '', __FILE__) . '/',
  'path_plugins' => preg_replace('/\/[^\/]*$/', '', __FILE__) . '/plugins/',
  'path_data'    => preg_replace('/\/[^\/]*$/', '', __FILE__) . '/data/',
  'plugins'      => array(),
);

// Turn on all error reporting for a development environment.
ini_set('error_reporting', E_ALL);

// If we are executing in the .inc file then this is probably a
// production environment.
$file = explode('/', __FILE__);
$file = $file[count($file) - 1];
if (($file == 'config.inc')) {
  // Turn off all error reporting.
  ini_set('error_reporting', 0);
}

// Include the .inc file if it exists and we are still in the .php file.
if (($file != 'config.inc') && file_exists($php_chat_script['path_inc'] . 'config.inc')) {
  include 'config.inc';
}

?>