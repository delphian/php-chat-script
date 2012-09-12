<?php

/**
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * @file user.php
 *
 * Try to manage all user related activiety here.
 * This includes:
 * - Client identification.
 * - User registration.
 * - User authentication.
 * - Spam/Flood prevention.
 * We must be able to operate both against requesting clients and other
 * php calling code. Code must be able to use us to identify who is making
 * a request.
 */

class User extends Plugin {

  protected static $routes = array(
    'user/request/id',
    'user/request/login',
    'user/request/register',
  );

  // Main function to process a message.
  public function receive_message(&$route, $server) {
    switch($route) {
      case 'user/request/id':
        $this->route_request_id($server->get_payload());
        break;
      case 'user/request/login':
        $this->route_request_login();
        break;
    }

    // Allow plugins to change what we have done. Final act of parent will be
    // to set the current headers and output to the calling server.
    parent::receive_message($route, $server);

    return;
  }

  /**
   * A command was sent to us from the client.
   */
  public function route_request_id($payload) {
    $input = json_decode($payload, TRUE);

    if (isset($input['client_id'])) {
    
    }

    return;
  }

  /**
   * Login
   */
  public function route_request_login() {
    $this->variables['say']++;  
    $response = array(
      'code'    => 'output',
      'payload' => $this->variables['say'],
    );
    $this->output = json_encode($response);
    $this->headers[] = 'Content-Type: text/text';
    $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
    $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
    return;  
  }

}

// Register our plugin.
Server::register_plugin('User', User::get_routes());

?>