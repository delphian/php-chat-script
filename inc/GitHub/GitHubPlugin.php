<?php

/**
 * @file
 *
 * Display a github ping to all current chat users..
 *
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

class GitHubPlugin extends Plugin {

  /**
   * Main callback used to process messages.
   */
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->output = $observed->get_output();
    $this->user = $observed->get_user();

    switch($route) {
      case '__route':
        $this->route__route($observed);
        break;
      case 'github/ping':
        $this->route_github_ping($observed);
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
  public function route__route(Server $server) {
    if (isset($_REQUEST['payload'])) {
      $payload = json_decode($_REQUEST['payload'], TRUE);
      if (isset($payload['commits'])) {
        $user_ids = SimpleUser::purge();
        Chat::add($user_ids, 0, 'Got a ping from GitHub.');
      }
      if (isset($payload['commits'][0]['message'])) {
        $server->set_route('github/ping');
      }
    }
  }

  /**
   * Send a message to all active chat users about the github ping.
   */
  public function route_github_ping(Server $server) {
    $user_ids = SimpleUser::purge();

    Chat::add($user_ids, 0, 'Got a ping from GitHub.');
  }

}

/** Hook into other functions. */
Server::register_plugin('GitHubPlugin', array(
  '__route',
  'github/ping',
));

?>