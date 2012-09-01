<?php

/**
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * This should be used as an example to build custom server side plugins.
 * It is actually used so don't delete it unless you know what your doing.
 * This plugin acts as a bot.
 */

class PHPChatScriptBot extends PHPChatScriptPluginBase {

  public $name = 'PHPchatScriptBot';
  public $weight = 5;
  
  public $client;
  
  // $this->variables exists as a persistant array for each plugin to store
  // information in.

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();

    return;
  }

  public function boot() {
    parent::boot();
    $client_id = (isset($this->variables['client_id'])) ? $this->variables['client_id'] : NULL;

    // If we already have a client id and we are still logged in then run bot.
    if ($client_id && client::exists($client_id)) {
      $this->client = new client($client_id, $client_id);
      // Update our timestamp so we don't get disconnected.
      $this->client->save();
      //$client->client_main(RM_MSG, $message, $server_version);
    }
    else {
      // Register our bot as a new client.
      $client_id = $this->variables['client_id'] = client::create();
      $this->client = new client($client_id, $client_id);
      $this->client->client_rm_join('new');
      $this->client->client_req_cl_name(array('Tumbleweeds'));
    }

    return;
  }

  public function format_request($request, &$server_input) {
    // If this request is from github then kill the input and process it
    // ourself. The public IP address should be from 207.97.227.253, 
    // 50.57.128.197, or 108.171.174.178.
//    if ($_SERVER['REMOTE_ADDR'] == '207.97.227.253' ||
//        $_SERVER['REMOTE_ADDR'] == '50.57.128.197' ||
//        $_SERVER['REMOTE_ADDR'] == '108.171.174.179') {
      if (isset($request['payload'])) {
        $server_input = array(
          'code'    => 'github',
          'message' => $request['payload'],
          'from'    => $this->variables['client_id'],
          'time'    => time(),
        );
        $server_input = NULL;
        $this->github($request['payload']);
      }
//    }
    return;
  }

  /**
   * Process or alter any codes and their payloads.
   *
   * @param int $code
   *   Unique code that provides context for the playload. This is the specific
   *   nature of the request or command.
   * @param mixed $payload
   *   This value, and its type, is code dependent.
   *
   * @returns bool TRUE|FALSE
   *   TRUE if we have performed any action, FALSE otherwise.
   */
  public function process_request(&$code, &$payload) {
    $processed = FALSE;
    
    if ($code == 'github') {
      $this->github($payload);
      $processed = TRUE;
    }

    return $processed;
  }

  public function halt() {
    parent::halt();

    return;
  }

  /**
   * Say something in the current room.
   *
   * @param string $message
   *   The message to post into a room.
   *
   * @return bool $result
   *   TRUE if the message was posted, FALSE otherwise.
   */
  public function say($message) {
    $result = FALSE;

    // Print $message into the room 'new'.
    $payload = array(
      'new',
      $message,
    );
    $result = $this->client->client_req_rm_msg($payload);

    return $result;
  }

  /**
   * Post any messages received from github into our room.
   */
  public function github($payload) {
    $payload = json_decode($payload, TRUE);
    $this->say('Got ping from github: ' . $payload['commits'][0]['message']);
    return;
  }
}

// Register our plugin class.
PHPChatScriptPluginBase::register('PHPChatScriptBot');

?>