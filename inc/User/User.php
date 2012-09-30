<?php

/**
 * @file
 *
 * Uses SimpleTextStorage to create a basic user management functionality.
 *
 * The name 'User' does not imply registration. Users may be registered or
 * anonymous. If the user is anonymous it may be easier to think of this class
 * as tracking clients (anonymous users). This class is intended to do both at
 * once.
 *
 * Anonymous users will automatically be purged from the database after a time
 * of inactivity.
 *
 * The associative array stored in SimpleTextStorage is:
 * - users: Associative array containing user informatio keyed by user id:
 *   - user_id:    (int) The unique user identification.
 *   - secret_key: (int) Secret authetnication key only this user knows about.
 *   - name:       (string) Name of the user.
 *   - time:       (int) Unix time stamp. Last time this user accessed the system.
 *   - registered: (bool) Is the user registered? If not they are anonymous.
 *   - online:     (bool) Online status. All anonymous users are always online.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */

class User extends Observed {

  /** The unique user identification. This is first created by a registration
      process and the value will not change for the life of the user. */
  protected $user_id = NULL;
  /** The secret key is used on a session basis for the client to authenticate
      it's curent connection. A user id and secret key must be sent back to
      the server to process most requests. */
  protected $secret_key = NULL;
  /** Name of the user. */
  protected $name = NULL;
  /** Time the user last accessed the system. */
  protected $time = NULL;
  /** Switch to indicate if the user is currently online or not. */
  protected $online = NULL;
  /** Switch indicating this account is registered and therefore persistent. */
  protected $registered = NULL;
  
  /**
   * All remaining fields are only used for registered users.
   */
   
  /** Email address, required for registration. A combination of email address
      and secret_key are used to authenticate a registered user. This should
      not be confused with regulary user authentication, which only confirms
      that the current requestor has at least an anonymous user id. */
  protected $email = NULL;

  /**
   * Determine if a user identification exists or not.
   *
   * @param int $user_id
   *   The unique user id.
   *
   * @return int $user_id|FALSE
   *   Returns the user id if found, FALSE if not found.
   */
  public static function exists($user_id) {
    $user_exists = FALSE;

    $users = SimpleTextStorage::load()->read('User', 'users');
    if (array_key_exists($user_id, $users)) {
      $user_exists = $user_id;
    }

    return $user_exists;
  }

  /**
   * Authenticate user by compairing the claimed user id against the secret key.
   *
   * @param int $user_id
   *   The unique user identification.
   * @param string $secret_key
   *   The secret key that is assigned to a user.
   *
   * @return bool TRUE|FALSE
   *   TRUE if the user claim is authentic, FALSE otherwise.
   */
  public static function authenticate($user_id, $secret_key) {
    $authentic = FALSE;

    $users = SimpleTextStorage::load()->read('User', 'users');
    if (array_key_exists($user_id, $users)) {
      if ($users[$user_id]['secret_key'] == $secret_key) {
        $authentic = TRUE;
      }
    }

    return $authentic;
  }

  /**
   * Create a new user in the database.
   *
   * Will create a new user in the database by randomizing a user id. The new
   * user id will be used in constructing a guest_{id} name and the registered
   * status of the new user will be set to NULL.
   *
   * @return int $user_id
   *   The newly created user identification.
   */
  public static function create() {
    /** Generate a unique user id. */
    while (self::exists($user_id = mt_rand()));
    /** Construct default user record. */
    $record = array(
      'user_id' => $user_id,
      'secret_key' => mt_rand(),
      'name' => 'guest_' . $user_id,
      'time' => time(),
      'registered' => FALSE,
      'online' => FALSE,
      'email' => FALSE,
    );
    /** Save new record directly to the database. */
    $users = SimpleTextStorage::load()->read('User', 'users');
    $users[$user_id] = $record;
    SimpleTextStorage::load()->write('User', 'users', $users);

    return $user_id;
  }

  /**
   * Login a user based on their email address and password.
   *
   * If the email address and password find a match in the user records then
   * the user identification and it's password will be returned to the client.
   *
   * @param string $email
   *   Email of user account.
   * @param string $password
   *   Password of user account.
   *
   * @return
   *   (int) User identification if found, FALSE otherwise.
   */
  public static function login($email, $password) {
    $report = NULL;
    $users = self::purge(NULL, 'online');
    if (!empty($users)) {
      foreach ($users as $user_id => $data) {
        if ($data['email'] == $email && $data['secret_key'] == $password) {
          $report = $user_id;
        }
      }
    }
    return $report;
  }

  /**
   * Purge (remove) anonymous users and logged out inactive registered users.
   *
   * This method should also be used to report which ids are logged in.
   *
   * @param int $ping_user_id
   *   (optional) Update this user identification's last access time with
   *   the current time.
   * @param string $login_state
   *   (optional) Restrict which ids will be returned. Possible values are:
   *   'all'|'registered'|'online'
   *
   * @return
   *   An array of all logged in user identifications.
   */
  public static function purge($ping_user_id = NULL, $login_state = 'all') {
    $user_report = array();

    $users = SimpleTextStorage::load()->read('User', 'users');
    foreach($users as $user_id => $data) {
      $user = new User($user_id);
      /** Update specified user's last access time. */
      if ($ping_user_id == $user_id) {
        $user->save();
      }
      /** Update records of users that have timed out. */
      elseif ((time() - $data['time']) > 120) {
        /** Remove any records that have timed out and not registered. These
            are anonymous users. Records will be removed entirely. */
        if ($data['registered'] == FALSE) {
          $user->delete();
          unset($user);
        }
        /** @todo Update any registered users that have timed out. */
        else {
          $user->set_online(FALSE);
          $user->save();
        }
      }
      /** Construct our return value. */
      if (isset($user)) {
        if ($login_state == 'all') {
          $user_report[] = $user_id;
        }
        elseif (($login_state == 'registered') && ($user->get_registered())) {
          $user_report[] = $user_id;
        }
        elseif (($login_state == 'online') && ($user->get_online())) {
          $user_report[] = $user_id;    
        }
      }
    }

    return $user_report;
  }

  /**
   * Load up an instance of a user record.
   *
   * @param int $user_id
   *   Unique user identificaiton must exist.
   *
   * @return
   *   Instance of User on success, exception thrown on failure.
   */
  public function __construct($user_id) {
    if (!User::exists($user_id)) {
      throw new Exception('User must exist to be instantiated.');
    }

    $users = SimpleTextStorage::load()->read('User', 'users');

    $this->user_id    = $users[$user_id]['user_id'];
    $this->secret_key = $users[$user_id]['secret_key'];
    $this->name       = $users[$user_id]['name'];
    $this->time       = $users[$user_id]['time'];
    $this->online     = $users[$user_id]['online'];
    $this->registered = $users[$user_id]['registered'];
    $this->email      = $users[$user_id]['email'];

    return $this;
  }

  /**
   * Save a instance of a user record.
   *
   * @todo return something more useful.
   *
   * @return
   *  Always returns TRUE.
   */
  public function save() {
    $users = SimpleTextStorage::load()->read('User', 'users');
    $user_id = $this->user_id;

    $users[$user_id]['user_id']    = $user_id;
    $users[$user_id]['secret_key'] = $this->secret_key;
    $users[$user_id]['name']       = $this->name;
    $users[$user_id]['time']       = time();
    $users[$user_id]['online']     = $this->online;
    $users[$user_id]['registered'] = $this->registered;
    $users[$user_id]['email']      = $this->email;

    SimpleTextStorage::load()->write('User', 'users', $users);

    return TRUE;
  }

  /**
   * Delete this user record.
   *
   * Once this method is called the user instantiation should be unset.
   *
   * @return
   *   Throws exception if user could not be deleted.
   */
  public function delete() {
    $users = SimpleTextStorage::load()->read('User', 'users');
    if (is_array($users) && array_key_exists($this->user_id, $users)) {
      unset($users[$this->user_id]);
      try {
        SimpleTextStorage::load()->write('User', 'users', $users);
      } catch (Exception $e) {
        throw new Exception('Can not delete user record.');
      }
    }
  }

  /** Set property. */
  public function set_online($value) {
    $this->online = (isset($value)) ? TRUE : FALSE;
  }

  /**
   * Set property.
   *
   * @param string $password
   *   New value to set property to.
   *
   * @return
   *   The value of the propety after operation.
   */
  public function set_password($password) {
    if (preg_match('/[a-z0-9_\-]{3,}/i', $password)) {
      $this->password = $password;
      $this->invoke_all('__' . __CLASS__ . '/' . __FUNCTION__);
    }
    return $this->password;
  }

  /**
   * Set the name property.
   *
   * @param string $name
   *   New value to set name to.
   *
   * @return
   *   The value of the propety after operation.
   */
  public function set_name($name) {
    if (preg_match('/[a-zA-Z0-9_\- ]{3,}/', $name)) {
      $this->name = $name;
      $this->invoke_all('__' . __CLASS__ . '/' . __FUNCTION__);
    }
    return $this->name;
  }

  /**
   * Set the email property.
   *
   * @param string $email
   *   The email to save.
   *
   * @return
   *   The value of the propety after operation.
   */
  public function set_email($email) {
    if (preg_match('/[a-z0-9_\-\.]@[a-z0-9_\-\.]/i', $email)) {
      $this->email = $email;
      $this->invoke_all('__' . __CLASS__ . '/' . __FUNCTION__);
    }
    return $this->email;
  }

  /**
   * Set property.
   *
   * @param bool $registered
   *   New value to set property to.
   *
   * @return
   *   The value of the propety after operation.
   */
  public function set_registered($registered) {
    $this->registered = ($registered) ? TRUE : FALSE;
    $this->invoke_all('__' . __CLASS__ . '/' . __FUNCTION__);
    return $this->registered;
  }

  /** Get property. */
  public function get_user_id() {
    return $this->user_id;
  }

  /** Get property. */
  public function get_name() {
    return $this->name;
  }

  /** Get property. */
  public function get_online() {
    return $this->online;
  }

  /** Get property. */
  public function get_time() {
    return $this->time;
  }

  /** Get property. */
  public function get_secret_key() {
    return $this->secret_key;
  }

  /** Get property. */
  public function get_registered() {
    return $this->registered;
  }
  
  /** Get property. */
  public function get_email() {
    return $this->email;
  }

}

?>