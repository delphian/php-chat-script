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
        $this->route_request_id($this->payload);
        break;
      case 'user/request/login':
        //$this->route_request_login();
        break;
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $caller->set_output($this->output);
    }

    return;
  }

  /**
   * User is requesting a new identification.
   *
   * If an old identification number is provided then the old record will be
   * updated, otherwise a new identification will be generated.
   *
   * @param mixed $payload
   *   An optional associative array containing the old identification number:
   *   - user_id: (string) The old client identification number to change.
   *   - user_secret: (string) The secret password for this client.
   *
   * @return
   *   A json encoded array:
   *   - user_id: (string) The new client identification granted or 0 if
   *                denied.
   */
  public function route_request_id($payload) {
    $input = json_decode($payload, TRUE);

    if ($new_id = $this->request_id($input)) {
      /** Format up our response. */
      $response = array(
        'user_id' => $new_id,
      );
      $this->output['body'] = json_encode($response);
    }

    return;
  }

  /**
   * Create a new user or update the identification of an old user.
   */
  public function request_id($old) {
    $id = FALSE;

    /** Update old identification. */
    if (isset($old['client_id'])) {
      // @todo Change old identification to new.
    }
    /** Generate new identification. */
    else {
      $new_id = mt_rand();
      $this->variables['clients'][$new_id] = array(
        'id'     => $new_id,
        'time'   => time(),
        'secret' => mt_rand(),
      );
      $id = $new_id;
    }

    return $id;
  }

}

// Register our plugin.
Server::register_plugin('User', User::get_routes());

?>