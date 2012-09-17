<?php
// http://www.oocities.org/maurice_osborn/Serpo.htm
/**
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * This should be used as an example to build custom server side plugins.
 * It is actually used so don't delete it unless you know what your doing.
 */

class Cli extends Plugin {

  protected static $routes = array(
    '/',
    '__user',
    'cli/get_message',
    'cli/set_message',
    'cli/get_id',
  );

  // Main function to process a message.
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->user = $observed->get_user();

    switch($route) {
      case '/':
        $this->route_root();
        break;
      case 'cli/get_id':
        $this->route_get_id();
        break;
      case 'cli/set_message':
        $this->route_set_message();
        break;
      case 'cli/get_message':
        $this->route_get_message();
        break;
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
          $user = new SimpleUser($user_id);
          $server->set_user($user);
        }
      }
    }
  }

  // Load up the javascript bare bones interface.
  public function route_root() {
    // Load up the interface.
    $client_file = file_get_contents('inc/Cli/files/client.html');

    $this->output['body'] = $client_file;
    $this->output['headers'][] = 'Content-Type: text/html';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

  /**
   * Grant and report to client their new user identification.
   *
   * @return
   *   Response to client will be associative array:
   *   - code: 'user_id'.
   *   - payload: (int) New unique user identification.
   */
  public function route_get_id() {
    // Create new anonymous use.
    $user = new SimpleUser(SimpleUser::create());
    $response = array(
      'code' => 'user_id',
      'payload' => array(
        'user_id'    => $user->get_user_id(),
        'secret_key' => $user->get_secret_key(),
      ),
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();

    return;
  }

  public function route_get_message() {
    $response = array(
      'code' => 'NAC',
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();

    $this->invoke_all('get_message');

    return;
  }

  /**
   * A command was sent to us from the client.
   */
  public function route_set_message() {
    $input = json_decode($this->payload, TRUE);

    switch ($input['code']) {
      case 'help':
        $this->code_help();
        break;
      case 'say':
        $this->code_say();
        break;
    }

    // Let plugins change what we have done.
    $this->invoke_all($input['code']);

    return;
  }

  /**
   * Help
   */
  public function code_help() {
    $response = array(
      'code'    => 'output',
      'payload' => 'Everybody wants help...',
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();

    return;  
  }

  /**
   * Say
   */
  public function code_say() {
    $user_id = 0;
    if ($this->user) {
      $user_id = $this->user->get_user_id();
    }
    $this->variables['say']++;
    $response = array(
      'code'    => 'output',
      'payload' => $this->variables['say'] . ' ' . $user_id,
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

// Register our plugin.
Server::register_plugin('Cli', Cli::get_routes());

?>