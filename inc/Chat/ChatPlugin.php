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

  // Main function to process a message.
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->output = $observed->get_output();
    $this->user = $observed->get_user();

    $user_id = 0;
    if ($this->user) {
      $user_id = $this->user->get_user_id();
    }

    /** Setup parameters. */
    $variables = array(
      'caller' => $observed,
      'user_id' => $user_id,
    );

    /** We will only process a route if we have a valid user. */
    if ($user_id) {
      switch($route) {
        case 'chat/get_chat':
          $this->route_get_chat($user_id);
          break;
        case 'chat/set_chat':
          $this->route_set_chat($user_id);
          break;
        case '__cli/get_message':
          $this->cli_get_message($user_id);
          break;
        case '__cli/command/help':
          $this->cli_command_help($variables);
          break;
        case '__cli/javascript':
          $this->cli_javascript($observed);
          break;
      }
    }
    else {
      switch($route) {
        case '__cli/javascript':
          $this->cli_javascript($observed);
          break;
      }
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
      $response = array(
        'code'    => 'chat',
        'payload' => $chat_msgs,
      );
      $this->output['body'] = json_encode($response);
      $this->headers_text();
    }

    return;
  }

  /** Add our javascript files to the command line interface. */
  public function cli_javascript($observed) {
    $javascript = $observed->get_javascript();
    $javascript[] = 'inc/Chat/files/ChatCli.js';
    $observed->set_javascript($javascript);
  }

  /**
   * Add our command to the CLI help text.
   */
  public function cli_command_help($variables) {
    $output = json_decode($this->output['body'], TRUE);
    $output['payload'] .= 'Anything typed without a forward slash will be spoken in common chat.<br />';

    $response = array(
      'code' => 'output',
      'payload' => $output['payload'],
    );
    $this->output['body'] = json_encode($response);
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
   * @param int $user_id
   *   The unique user identification that is the source of the chat message.
   *
   * Payload parameter is associative array of:
   * - to_user_id: Identification of user to send message to.
   * - message: The message itself to send.
   */
  public function route_set_chat($user_id) {
    $input = json_decode($this->payload, TRUE);

    $user_ids = SimpleUser::purge($user_id);
    unset($user_ids[$user_id]);

    Chat::add($user_ids, $user_id, $input['payload']);

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
    $this->output['headers'] = NULL;
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
  '__cli/get_message',
  '__cli/command/help',
  '__cli/javascript',
));

?>