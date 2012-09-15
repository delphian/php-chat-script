<?php

/**
 * @file
 *
 * Uses SimpleTextStorage to create a basic user management functionality.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */

class SimpleUserStorage {

  /** The unique user identification. This is first created by a registration
      process and the value will not change for the life of the user. */
  protected $id = NULL;
  /** The secret key is used on a session basis for the client to authenticate
      it's curent connection. A user id and secret key must be sent back to
      the server to process most requests. */
  protected $secret_key = NULL;

  /**
   * Determine if a user identification exists or not.
   *
   * @param int $user_id
   *   The unique user id.
   *
   * @return bool TRUE|FALSE
   *   TRUE if the user identification exists, FALSE otherwise.
   */
  public static function exists($user_id) {
    $user_exists = FALSE;

    $users = SimpleTextStorage::load()->read('SimpleUserStorage', 'users');
    if (array_key_exists($user_id, $users)) {
      $user_exists = TRUE;
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
   * @return
   *   TRUE if the user claim is authentic, FALSE otherwise.
   */
  public static function authenticate($user_id, $secret_key) {
    $authentic = FALSE;

    $users = SimpleTextStorage::load()->read('SimpleUserStorage', 'users');
    if (array_key_exists($user_id, $users)) {
      if ($users[$user_id]['secret_key'] == $secret_key) {
        $authentic = TRUE;
      }
    }

    return $authentic;
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
    if (!SimpleUserStorage::exists($user_id)) {
      throw new Exception('User must exist to be instantiated.');
    }

    $users = SimpleTextStorage::load()->read('SimpleUserStorage', 'users');

    $this->id         = $users['id'];
    $this->secret_key = $users['secret_key'];

    return $this;
  }

}

?>