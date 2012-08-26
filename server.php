<?php

/*
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

require_once('./inc/config.php');
require_once('./inc/room.php');
require_once('./inc/message.php');
require_once('./inc/client.php');
require_once('./inc/plugin.php');

header("Content-Type: text/plain");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$server_version = "0.4.8";

$raw_time     = $_REQUEST['time'];
$raw_code     = $_REQUEST['code'];
$raw_from     = $_REQUEST['from'];
$raw_message  = $_REQUEST['message'];

$code    = preg_replace("[^A-Za-z0-9]", '', $raw_code);
$from    = preg_replace("[^A-Za-z0-9]", '', $raw_from);
$time    = time();
$message = $raw_message; // Individual messages must filter.

$message = str_replace('%25', '%', $message);
$message = str_replace('%3b', ';', $message);
$message = explode(';', $message);

/**********************************************************************/
/*                                                                    */
/* Main processing for messages sent by clients.                      */
/*                                                                    */
/**********************************************************************/

if ($code) {
  if ($code == message::CL_ID) {
    $from = client::create();
    $client = new client($from, $from);
    $client->ip = $_SERVER['REMOTE_ADDR'];
    $client->port = $_SERVER['REMOTE_PORT'];
    $client->save();
    $client->client_cl_id();
    unset($client);
  } else {
    if (!client::exists($from)) {
      print "Not Logged In. From: $from, Code: $code\n";
      return FALSE;
    }
    $client = new client($from, $from);
    $client->client_main($code, $message, $server_version);
    unset($client);
  }
} else {
  message::msg_improper_format();
}

?>