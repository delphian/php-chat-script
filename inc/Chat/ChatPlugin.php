<?php

/**
 * @file
 *
 * Simple chat functionality.
 *
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

class ChatPlugin extends Plugin {

  protected static $routes = array(
    'chat/set_chat',
    'chat/get_chat',
  );

  // Main function to process a message.
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->user = $observed->get_user();

    $user_id = 0;
    if ($this->user) {
      $user_id = $this->user->get_user_id();
    }

    /** We will only process a route if we have a valid user. */
    if ($user_id) {
      switch($route) {
        case 'chat/get_chat':
          $this->route_get_chat($user_id);
          break;
        case 'chat/set_chat':
          $this->route_set_chat($user_id);
          break;
        case 'get_message':
          $this->cli_get_message($user_id);
          break;
      }
    }
    /** Complain that we should have never been invoked without a valid user. */
    else {
      $response = array(
        'code'    => 'chat_invalid_user',
        'payload' => NULL,
      );
      $this->output['body'] = json_encode($response);
      $this->headers_text();      
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $observed->set_output($this->output);
    }

    return;
  }

  public function cli_get_message($user_id) {
    $chat_msgs = Chat::peek(array($user_id));
    $chat_msgs = $chat_msgs[$user_id];
    Chat::delete(array($user_id));

    if (!empty($chat_msgs)) {
      $output = '';
      foreach($chat_msgs as $msg) {
        $output .= $msg['from_user_id'] . '> ' . $msg['chat'];
      }
      $response = array(
        'code'    => 'output',
        'payload' => $output,
      );
      $this->output['body'] = json_encode($response);
      $this->headers_text();
    }

    return;
  }

  /**
   * Get chat messages.
   */
  public function route_get_chat($user_id) {
    $chat_msgs = Chat::peek(array($user_id));
    $chat_msgs = $chat_msgs[$user_id];
    Chat::delete(array($user_id));

    $response = array(
      'code'    => 'chat_message',
      'payload' => $chat_msgs,
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();

    return;
  }

  /**
   * Set chat message.
   *
   * Payload parameter is associative array of:
   * - to_user_id: Identification of user to send message to.
   * - message: The message itself to send.
   */
  public function route_set_chat($user_id) {
    $input = json_decode($this->payload, TRUE);

    $user_ids = SimpleUser::purge();
    Chat::add($user_ids, $user_id, $input['payload']['message']);

    $response = array(
      'code'    => 'chat_message_received',
      'payload' => $input['payload']['message'],
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();

    return;
  }

  /**
   * Generate straight text output.
   */
  public function headers_text() {
    $this->output['headers'][] = 'Content-Type: text/text';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

}

// Register our plugin for callback.
Server::register_plugin('ChatPlugin', array(
  'chat/set_chat',
  'chat/get_chat',
));
Cli::register_plugin('ChatPlugin', array(
  'get_message',
));

?>