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

    if ($route == '__user') {
      $this->route__user($observed);
    }
    elseif ($route == '__cli/command/help') {
      $this->cli_command_help(NULL);
    }
    elseif ($route == '__cli/command/who') {
      $this->cli_command_who(NULL);
    }
    elseif ($route == '__cli/javascript') {
      $this->cli_javascript($observed);
    }
    elseif (preg_match('@api/simpleuserplugin/list/id.*@', $route, $matches)) {
      $this->route_api_user_list_id($observed);
    }
    elseif ($route == 'api/user/request/id') {
      $this->route_api_user_request_id($observed);
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
   * Grant and report to client their new user identification.
   */
  public function route_api_user_request_id(Server $server) {
    /** Create new anonymous user. */
    $user = new SimpleUser(SimpleUser::create());
    $response = array(
      'type' => 'api_request_id',
      'user' => array(
        'user_id' => $user->get_user_id(),
        'secret_key' => $user->get_secret_key(),
      ),
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Report a list of all user identifications.
   */
  public function route_api_user_list_id(Server $server) {
    $args = $server->get_args();
    if (isset($args[4])) {
      $user = new SimpleUser($args[4]);
      $response = array(
        'type' => 'api_list_id',
        'user' => array(
          'user_id'   => $user->get_user_id(),
          'name'      => $user->get_name(),
          'time'      => $user->get_time(),
          'logged_in' => $user->get_logged_in(),
        ),
      );
    }
    else {
      $user_ids = array();
      $users = SimpleUser::purge();
      $response = array(
        'type' => 'api_list_ids',
        'ids'  => $users,
      );
    }
    $server->add_json_output(__CLASS__, $response);
  }

  /** 
   * Add our javascript files to the command line interface.
   */
  public function cli_javascript($observed) {
    $javascript = $observed->get_javascript();
    $javascript[] = 'inc/SimpleUser/files/SimpleUserCli.js';
    $observed->set_javascript($javascript);
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
  'api/simpleuserplugin/list/id',
  'api/user/request/id',
));
Cli::register_plugin('SimpleUserPlugin', array(
  '__cli/command/help',
  '__cli/command/who',
  '__cli/javascript',
));

?>