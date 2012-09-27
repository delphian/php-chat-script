<?php

/**
 * @file
 *
 * Plugin for SimpleUser backend.
 *
 * Will hook into __user to automatically provide server with a user if the
 * credentials are available.
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

class SimpleUserPlugin extends Plugin {

  /**
   * Main callback used to process messages.
   */
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->output = $observed->get_output();
    $this->user = $observed->get_user();

    switch($route) {
      case '__user':
        $this->route__user($observed);
        break;
      case '__cli/command/help':
        $this->cli_command_help(NULL);
        break;
      case '__cli/command/who':
        $this->cli_command_who(NULL);
        break;
      case 'api/user/list/ids':
        $this->route_api_user_list_ids($observed);
        break;
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $observed->set_output($this->output);
    }

    return;
  }

  /**
   * Set the logged in user based on credentials provided in the request.
   *
   * Input post associative array:
   * - payload: Associative array:
   *   - user: Associative array:
   *     - user_id: (int) Unique user identification.
   *     - secret_key: (int) Secret password.
   */
  public function route__user(Server $server) {
    /** Setup our user if authentiction credentials are provided. */
    if (isset($this->payload)) {
      $payload = json_decode($this->payload, TRUE);
      if (isset($payload['user'])) {
        $user_id = $payload['user']['user_id'];
        $secret  = $payload['user']['secret_key'];
        if (SimpleUser::authenticate($user_id, $secret)) {
          SimpleUser::purge($user_id);
          $user = new SimpleUser($user_id);
          $server->set_user($user);
        }
      }
    }
  }

  /**
   * Report a list of all user identifications.
   *
   * - Associative array of single message:
   *   - type: (string) 'user_list_ids'
   *   - ids: Array of integers. Each element is a user identification.
   */
  public function route_api_user_list_ids(Server $server) {
    $user_ids = array();
    $users = SimpleUser::purge();
    if (!empty($users)) {
      $user_ids = array_keys($users);
    }
    $response = array(
      'type' => 'user_list_ids',
      'ids'  => $user_ids,
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Add our commands to the CLI help text.
   */
  public function cli_command_help($variables) {
    $output = json_decode($this->output['body'], TRUE);
    $output['payload'] .= '<b>/who</b> List users logged into server.<br />';

    $response = array(
      'code' => 'output',
      'payload' => $output['payload'],
    );
    $this->output['body'] = json_encode($response);
  }

  /**
   * List the users currently logged into the system.
   */
  public function cli_command_who($variables) {
    $users = SimpleUser::purge();
    $response = array(
      'code' => 'output',
      'payload' => $users,
    );
    $this->output['body'] = json_encode($response);
  }

}

/** Hook into other functions. */
Server::register_plugin('SimpleUserPlugin', array(
  '__user',
  'api/user/list/ids',
));
Cli::register_plugin('SimpleUserPlugin', array(
  '__cli/command/help',
  '__cli/command/who',
  '__cli/javascript',
));

?>