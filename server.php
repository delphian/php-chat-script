<?php

/*
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

$config = array(
  'path_root'    => preg_replace('/\/[^\/]*$/', '', __FILE__),
  'path_inc'     => preg_replace('/\/[^\/]*$/', '', __FILE__) . '/inc',
  'path_data'    => preg_replace('/\/[^\/]*$/', '', __FILE__) . '/data',
);

// Turn on all error reporting for a development environment.
ini_set('error_reporting', E_ALL);



require_once($config['path_inc'] . '/SingletonLoader/SingletonLoader.php');
require_once($config['path_inc'] . '/Observed/Observed.php');
require_once($config['path_inc'] . '/SimpleTextStorage/SimpleTextStorage.php');
require_once($config['path_inc'] . '/SimpleUser/SimpleUser.php');
require_once($config['path_inc'] . '/Chat/Chat.php');
require_once($config['path_inc'] . '/Plugin.php');
require_once($config['path_inc'] . '/Server.php');

require_once($config['path_inc'] . '/Cli/Cli.php');
require_once($config['path_inc'] . '/Chat/ChatPlugin.php');

/**
 * Begin running server.
 */
// Create server.
$server = new Server();
// Get the request from $_GET, $_POST, or a plugin.
$server->receive_request();
// Run the server. Invoke all plugins registered for this code.
$server->process_request();
// Close down the server gracefully.
$server->halt();

?>