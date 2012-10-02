<?php

/**
 * @file UserApi.php
 *
 * Provides an API for javascript applications to access the User services.
 *
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * @class UserApi
 *
 * @ingroup WebApi
 *
 * Provides a http based API for remote clients to utilize user services.
 *
 * Routes:
 * - __user: @see UserApi::route__user()
 * - api/user/list/all:
 * - api/user/list/online:
 * - api/user/request: @see UserApi::route_api_user_request()
 */
class UserApi extends Plugin {

  /** Route prefix to be used to access this api. Placing this into a central
      location allows us to update the entire route structure easy. */
  public static $rp = 'api/user/';

  /**
   * Setup routines that must be run once each runtime before api can be used.
   *
   * Generally this function is invoked at the end of this file. Register all
   * hooks against other classes we wan't to monitor.
   */
  public static function init() {
    /** Hook into the server. */
    Server::register_plugin(__CLASS__, array(
      '__user',
      self::$rp.'list/all',
      self::$rp.'list/online',
      self::$rp.'list/registered',
      self::$rp.'list',
      self::$rp.'request',
      self::$rp.'register',
      self::$rp.'login',
      self::$rp.'update',
    ));
    /** Hook into the command line interface. */
    Cli::register_plugin(__CLASS__, array(
      '__cli/javascript',
      '__cli/command/help',
    ));    
  }

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
    elseif ($route == '__cli/javascript') {
      $this->cli_javascript($observed);
    }
    elseif ($route == '__cli/command/help') {
      $this->cli_command_help($observed);
    }
    elseif ($route == self::$rp.'list/all') {
      $this->route_api_user_list_all($observed);
    }
    elseif ($route == self::$rp.'list/registered') {
      $this->route_api_user_list_registered($observed);
    }
    elseif ($route == self::$rp.'list/online') {
      $this->route_api_user_list_online($observed);
    }
    elseif (preg_match('@'.self::$rp.'list/([0-9]+)@', $route, $matches)) {
      $this->route_api_user_list_id($observed, $matches[1]);
    }
    elseif ($route == self::$rp.'request') {
      $this->route_api_user_request($observed);
    }
    elseif ($route == self::$rp.'register') {
      $this->route_api_user_register($observed);
    }
    elseif ($route == self::$rp.'login') {
      $this->route_api_user_login($observed);
    }
    elseif (preg_match('@'.self::$rp.'update/([0-9]+)@', $route, $matches)) {
      $this->route_api_user_update($observed, $matches[1]);
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $observed->set_output($this->output);
    }

    return;
  }

  /**
   * Tell the server who the request is being made by.
   *
   * Set the logged in user based on credentials provided in the request. All
   * requests should include this if they want to be received by the server as
   * an authenticated (having a user account, even if anonymous) request. Most
   * Api calls will require they be made from a user (again, even if anonymous).
   *
   * Route: __user
   *
   * JSON encoded request:
   * - payload: Associative array:
   *   - user: Associative array:
   *     - user_id: (int) Unique user identification.
   *     - secret_key: (int) Secret password.
   *
   * @see route_api_user_request()
   */
  public function route__user(Server $server) {
    /** Setup our user if authentiction credentials are provided. */
    $user_id = $server->get_payload('api', 'user', 'auth', 'user_id');
    $secret  = $server->get_payload('api', 'user', 'auth', 'secret_key');
    if ($user_id && $secret) {
      if (User::authenticate($user_id, $secret)) {
        User::purge($user_id);
        $user = new User($user_id);
        /** Set the current request's user. */
        if (!$server->set_user($user)) {
          throw new Exception('Can not set user property on server.');
        }
      }
    }
  }

  /**
   * Modify the properties of an existing user record.
   *
   * @param Server $server
   *   Server that received this route.
   * @param int $id
   *   Unique user identification to modify properties on.
   *
   * Route: /api/user/update/{user_id}
   *
   * JSON encoded request:
   * - payload: Associative array:
   *   - api: Associative array:
   *     - user: Associative array for user specific requests.
   *       - update: Associative array of properties that should be updated. Array
   *         keys should be set to the property name to update.
   *         - email: (string)
   *         - password: (string)
   *         - name: (string)
   *
   * JSON encoded response:
   * - UserApi: Associative array:
   *   - type: (string) 'api_update'.
   *   - success: (array) Associative array:
   *     - value:   (bool) TRUE if updated, FALSE if update failed.
   *     - message: (string) Error message, if any.
   *
   * @ingroup Route
   */
  public function route_api_user_update(Server $server, $id) {
    $msg = NULL;
    $updated = FALSE;
    // Allow current user to update their own information or allow
    // administrators to update anything.
    if (($id == $server->get_user()->get_user_id()) || ($server->get_user()->get_admin())) {
      $email    = $server->get_payload('api', 'user', 'update', 'email');
      $password = $server->get_payload('api', 'user', 'update', 'password');
      $name     = $server->get_payload('api', 'user', 'update', 'name');
      $user = new User($id);
      if (!$user) {
        $msg = 'Invalid user identification';
      }
      elseif ($name && ($user->set_name($name) != $name)) {
        $msg = 'Invalid name';
      }
      else {
        $user->save();
        $updated = TRUE;
      }
    }
    else {
      $msg = 'Not authorized';
    }
    // Send response to requestor.
    $response = array(
      'type' => 'api_update',
      'success' => array(
        'value' => $updated,
        'message' => $msg,
      ),
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Report the user identification associated with an email and password.
   *
   * JSON encoded request:
   * - payload: Associative array:
   *   - api: Associative array:
   *     - user: Associative array:
   *       - login: Associative array:
   *         - email: (string) Valid email address.
   *         - password: (string) New password that user should remember.
   *
   * JSON encoded response of associative array:
   * - UserApi: Associative array:
   *   - type: (string) 'api_login'
   *   - user: NULL on failure or Associative array:
   *     - user_id: (int) User id associated with email and password.
   *     - secret_key: (mixed) Password.
   *
   * @ingroup Route
   */
  public function route_api_user_login(Server $server) {
    $user = $server->get_user();
    $email = $server->get_payload('api', 'user', 'login', 'email');
    $password = $server->get_payload('api', 'user', 'login', 'password');

    $user_id = User::login($email, $password);
    if ($user_id) {
      $user = new User($user_id);
      $user_array = array(
        'user_id' => $user->get_user_id(),
        'secret_key' => $user->get_secret_key(),
      );
    }
    else {
      $user_array = FALSE;
    }

    $response = array(
      'type' => 'api_login',
      'user' => $user_array,
    );
    $server->add_json_output(__CLASS__, $response);    
  }

  /**
   * Grant and report to client their new user identification.
   *
   * JSON encoded response:
   * - __CLASS__: Associative array:
   *   - type: (string) 'api_request'.
   *   - user: (array) Associative array:
   *     - user_id:    (int) Unique user identification.
   *     - secret_key: (mixed) Password.
   */
  public function route_api_user_request(Server $server) {
    /** Create new anonymous user. */
    $user = new User(User::create());
    $user->set_online(TRUE);
    $user->set_time(time());
    $user->save();
    $response = array(
      'type' => 'api_request',
      'user' => array(
        'user_id' => $user->get_user_id(),
        'secret_key' => $user->get_secret_key(),
      ),
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Register a user.
   *
   * A user must first be authenticated with an anonymous user identification.
   * Request must provide email address and an updated password. These will be
   * used for login requests later on.
   *
   * JSON encoded request:
   * - payload: Associative array:
   *   - api: Associative array:
   *     - user: Associative array:
   *       - register: Associative array:
   *         - email: (string) Valid email address.
   *         - password: (string) New password that user should remember.
   *
   * JSON encoded response:
   * - __CLASS__: Associative array:
   *   - type: (string) 'api_register'.
   *   - success: Associative array:
   *     - value: (bool) TRUE on sucessful registration, FALSE otherwise.
   *     - message: (string) Mostly used as an error message.
   */
  public function route_api_user_register(Server $server) {
    $registered = FALSE;
    $msg = '';
    $user = $server->get_user();
    $email = $server->get_payload('api', 'user', 'register', 'email');
    $password = $server->get_payload('api', 'user', 'register', 'password');

    if (!$user) {
      $msg = 'Invalid access';
    }
    elseif (!$email || $user->set_email($email) != $email) {
      $msg = "Bad email:{$email}";
    }
    elseif (!$password || $user->set_password($password) != $password) {
      $msg = "Bad password:{$password}";
    }
    else {
      $user->set_registered(TRUE);
      if ($user->save()) {
        $registered = TRUE;
      }
      else {
        $msg = "Unable to register.";
      }
    }

    $response = array(
      'type' => 'api_register',
      'success' => array(
        'value' => $registered,
        'message' => $msg,
      ),
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Report user information for a specific user account.
   */
  public function route_api_user_list_id(Server $server, $id) {
    $user = new User($id);
    $response = array(
      'type' => 'api_list_id',
      'user' => array(
        'user_id'    => $user->get_user_id(),
        'name'       => $user->get_name(),
        'time'       => $user->get_time(),
        'online'     => $user->get_online(),
        'registered' => $user->get_registered(),
      ),
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Report a list of user identifications that are online right now.
   *
   * JSON encoded response:
   * - __CLASS__: Associative array:
   *   - type: (string) 'api_list_online'.
   *   - ids: (array) Array of integer user identifications that are online.
   */
  public function route_api_user_list_online(Server $server) {
    $users = User::purge($server->get_user()->get_user_id(), 'online');
    $response = array(
      'type' => 'api_list_online',
      'ids'  => $users,
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Report a list of all user identifications.
   *
   * JSON encoded response:
   * - __CLASS__: Associative array:
   *   - type: (string) 'api_list_online'.
   *   - ids: (array) Array of integers of all user identifications.
   */
  public function route_api_user_list_all(Server $server) {
    $users = User::purge($server->get_user()->get_user_id(), 'all');
    $response = array(
      'type' => 'api_list_all',
      'ids'  => $users,
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /**
   * Report a list of user identifications that are registered.
   *
   * JSON encoded response:
   * - __CLASS__: Associative array:
   *   - type: (string) 'api_list_online'.
   *   - ids: (array) Array of integers of all registered user identifications.
   */
  public function route_api_user_list_registered(Server $server) {
    $users = User::purge($server->get_user()->get_user_id(), 'registered');
    $response = array(
      'type' => 'api_list_registered',
      'ids'  => $users,
    );
    $server->add_json_output(__CLASS__, $response);
  }

  /** 
   * Add our javascript files to the command line interface.
   */
  public function cli_javascript($observed) {
    $javascript = $observed->get_javascript();
    $javascript[] = 'inc/User/files/UserCli.js';
    $observed->set_javascript($javascript);
  }

  /**
   * Add our commands to the CLI help text.
   */
  public function cli_command_help($variables) {
    $output = json_decode($this->output['body'], TRUE);

    $output['payload'] .= '<b>/user list {all|online|registered|#}</b> Show a list of user ids<br />';
    $output['payload'] .= '<b>/user login {email} {password}</b> Login to registered account.<br />';
    $output['payload'] .= '<b>/user register {email} {password}</b> Register for an account.<br />';
    $output['payload'] .= '<b>/user update {user id} {property}={value}.<br />';

    $response = array(
      'code' => 'output',
      'payload' => $output['payload'],
    );
    $this->output['body'] = json_encode($response);
  }

}

/** Hook into other functions. */
UserApi::init();

?>