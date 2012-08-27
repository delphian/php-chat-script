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

$plugins = new PHPChatScriptPluginBase($php_chat_script);
//$plugins->variables_read();
$plugins->variables['test1'] = 1;
$plugins->variables['test3'] = 3;
//$plugins->variables_write();
$plugins->boot();

$server_input = array(
  'time'    => NULL,
  'code'    => NULL,
  'from'    => NULL,
  'message' => NULL,
);
$plugins->format_request($_REQUEST, $server_input);

header("Content-Type: text/plain");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$server_version = "0.4.8";

/**********************************************************************/
/*                                                                    */
/* Main processing for messages sent by clients.                      */
/*                                                                    */
/**********************************************************************/

if ($server_input['code']) {
  // Something is requesting to be a client.
  if ($server_input['code'] == message::CL_ID) {
    $from   = client::create();
    $client = new client($from, $from);
    $client->ip = $_SERVER['REMOTE_ADDR'];
    $client->port = $_SERVER['REMOTE_PORT'];
    $client->save();
    $client->client_cl_id();
    unset($client);
  } else {
    if (!client::exists($server_input['from'])) {
      print "Not Logged In. From: {$server_input['code']}, Code: {$server_input['code']}\n";
      return FALSE;
    }
    $client = new client($server_input['from'], $server_input['from']);
    $client->client_main($server_input['code'], $server_input['message'], $server_version);
    unset($client);
  }
} else {
  message::msg_improper_format();
}

$plugins->halt();

?>