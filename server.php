<?php

/*
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/** Turn on all error reporting for a development environment. */
ini_set('error_reporting', E_ALL);

/** If this is our first time running install the default .htaccess.
    @todo This entire first time install thing should be handled better. */
if (!file_exists('.htaccess')) {
  if (!copy('install_files/install_htaccess', '.htaccess')) {
    throw new Exception('Can not install htaccess file.');
  }
}

/** Include our class (backend) libraries. */
require_once('inc/SingletonLoader/SingletonLoader.php');
require_once('inc/Observed/Observed.php');
require_once('inc/SimpleTextStorage/SimpleTextStorage.php');
require_once('inc/SimpleUser/SimpleUser.php');
require_once('inc/Chat/Chat.php');
require_once('inc/Plugin.php');
require_once('inc/Server.php');

/** Include our plugins. */
require_once('inc/Cli/Cli.php');
require_once('inc/Chat/ChatPlugin.php');
require_once('inc/SimpleUser/SimpleUserPlugin.php');

/**
 * Begin running server.
 */
/** Create server. */
$server = new Server();
/** Get the request from $_GET, $_POST, or a plugin. */
$server->receive_request();
/** Run the server. Invoke all plugins registered for this code. */
$server->process_request();
/** Close down the server gracefully. */
$server->halt();

?>