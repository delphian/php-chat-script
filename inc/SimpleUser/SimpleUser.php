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
 * - users: Associative array containing user information.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */

class SimpleUser {

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
  /** Switch to indicate if the user is currently logged in or not. */
  protected $logged_in = NULL;

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

    $users = SimpleTextStorage::load()->read('SimpleUser', 'users');
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

    $users = SimpleTextStorage::load()->read('SimpleUser', 'users');
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
      'logged_in' => FALSE,
    );
    /** Save new record directly to the database. */
    $users = SimpleTextStorage::load()->read('SimpleUser', 'users');
    $users[$user_id] = $record;
    SimpleTextStorage::load()->write('SimpleUser', 'users', $users);

    return $user_id;
  }

  /**
   * Delete a user record.
   *
   * @param int $user_id
   *   Unique user identification of record.
   *
   * @return
   *   TRUE if the record was removed, FALSE otherwise. Exception will be thrown
   *   if user does not exist.
   */
  public static function delete($user_id) {
    if (!self::exists($user_id)) {
      throw new Exception('User id does not exist');
    }

    $users = SimpleTextStorage::load()->read('SimpleUser', 'users');
    unset($users[$user_id]);
    $result = SimpleTextStorge::load()->write('SimpleUser', 'users', $users);

    return $result;
  }

  /**
   * Purge (remove) anonymous users and logged out inactive registered users.
   *
   * This method should also be used to report which ids are logged in.
   *
   * @return
   *   An array of all logged in user identifications.
   */
  public static function purge() {
    $logged_in = array();

    $users = SimpleTextStorage::load()->read('SimpleUser', 'users');
    foreach($users as $user_id => $data) {
      /** Remove anonymous users that have timed out. */
      if (($data['registered'] == FALSE) && ((time() - $data['time']) > 120)) {
        self::delete($user_id);
      }
      /** Update logged in status of registered users. */
      if ($data['logged_in'] == TRUE) {
        if ((time() - $data['time']) > 120) {
          $user = new SimpleUser($user_id);
          $user->logged_in = FALSE;
          $user->save();
        }
        else {
          $logged_in[] = $user_id;
        }
      }
    }

    return $logged_in;
  }

  /**
   * Load up an instance of a user record.
   *
   * @param int $user_id
   *   Unique user identificaiton must exist.
   *
   * @return SimpleUserStorage
   *   Instance of SimpleUserStorage on success, exception thrown on failure.
   */
  public function __construct($user_id) {
    if (!SimpleUser::exists($user_id)) {
      throw new Exception('User must exist to be instantiated.');
    }

    $users = SimpleTextStorage::load()->read('SimpleUser', 'users');

    $this->user_id    = $users[$user_id]['user_id'];
    $this->secret_key = $users[$user_id]['secret_key'];

    return $this;
  }

  /** Get property. */
  public function get_user_id() {
    return $this->user_id;
  }

  /** Get property. */
  public function get_secret_key() {
    return $this->secret_key;
  }

}

?>