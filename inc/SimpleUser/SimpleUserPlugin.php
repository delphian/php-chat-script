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

  // Main function to process a message.
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->user = $observed->get_user();

    switch($route) {
      case '__user':
        $this->route__user($observed);
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


}

// Register our plugin.
Server::register_plugin('SimpleUserPlugin', array(
  '__user',
));

?>