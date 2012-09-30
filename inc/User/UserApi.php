<?php

/**
 * @file
 *
 * Provides an API for javascript applications to access the User services.
 *
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
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
      self::$rp . 'list/id',
      self::$rp . 'request/id',
      self::$rp . 'register',
      self::$rp . 'login',
    ));
    /** Hook into the command line interface. */
    Cli::register_plugin(__CLASS__, array(
      '__cli/javascript',
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
    elseif (preg_match('@'.self::$rp.'list/id.*@', $route, $matches)) {
      $this->route_api_user_list_id($observed);
    }
    elseif ($route == self::$rp.'request/id') {
      $this->route_api_user_request_id($observed);
    }
    elseif ($route == self::$rp.'register') {
      $this->route_api_user_register($observed);
    }
    elseif ($route == self::$rp.'login') {
      $this->route_api_user_login($observed);
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
        if (User::authenticate($user_id, $secret)) {
          User::purge($user_id);
          $user = new User($user_id);
          if (!$server->set_user($user)) {
            throw new Exception('Can not set user property on server.');
          }
        }
      }
    }
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
   * - __CLASS__: Associative array:
   *   - type: (string) 'api_login'
   *   - user: NULL on failure or Associative array:
   *     - user_id: (int) User id associated with email and password.
   *     - secret_key: (mixed) Password.
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
   */
  public function route_api_user_request_id(Server $server) {
    /** Create new anonymous user. */
    $user = new User(User::create());
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
    else if (!$email || $user->set_email($email) != $email) {
      $msg = "Bad email:{$email}";
    }
    else if (!$password || $user->set_password($password) != $password) {
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
   * Report a list of all user identifications.
   */
  public function route_api_user_list_id(Server $server) {
    if ($server->get_user() == NULL) {
      exit('Jesus I know, and Paul I\'ve heard of, but who are you?');
    }
    $args = $server->get_args();
    if (isset($args[4])) {
      $user = new User($args[4]);
      $response = array(
        'type' => 'api_list_id',
        'user' => array(
          'user_id'    => $user->get_user_id(),
          'name'       => $user->get_name(),
          'time'       => $user->get_time(),
          'logged_in'  => $user->get_logged_in(),
          'registered' => $user->get_registered(),
        ),
      );
    }
    else {
      $user_ids = array();
      $users = User::purge();
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
    $javascript[] = 'inc/User/files/UserCli.js';
    $observed->set_javascript($javascript);
  }

}

/** Hook into other functions. */
UserApi::init();

?>