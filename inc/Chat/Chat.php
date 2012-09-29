<?php

/**
 * @file
 *
 * Simple live chat functionality provided to users.
 *
 * @todo Add a method to return an array of all user identifications that
 *   currently have at least one chat message in the chat queue. This is
 *   required to purge chat messages to registered users that are no longer
 *   logged in.
 *
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */


class Chat extends Observed {

  /**
   * Update the chat message queue with a new chat message.
   *
   * Chat message are constructed with the following associative array:
   * - to_user_id: (int) An array containing all messages for this user:
   *   - An associative array containing a single chat message:
   *     - time: (int) The time the message was recorded to the queue.
   *     - from_user_id: (int) The user that originated the message.
   *     - chat: (string) The chat message itself.
   *
   * @param array $to_user_ids
   *   Array of unique user identifications which will be the receipiant of
   *   the chat.
   * @param int $from_user_id
   *   Identification of the user responsible for sending the chat.
   * @param string $chat
   *   The chat itself.
   */
  public static function add($to_user_ids, $from_user_id, $chat) {
    $user = new User($from_user_id);
    /** Force the first parameter into an array. */
    $to_user_id = is_array($to_user_ids) ? $to_user_ids : array($to_user_ids);
    /** Load all existing chat messages waiting to be delivered. */
    $chats = SimpleTextStorage::load()->read('Chat', 'chats');
    /** Iterate over each destination user. Enter a new chat into the current
        iterated user's chat queue. */
    foreach($to_user_ids as $to_user_id) {
      /** Do not enter a chat message to a user that does not exist. */
      if (User::exists($to_user_id)) {
        $chats[$to_user_id][] = array(
          'time' => time(),
          'from_user_id' => $from_user_id,
          'from_user_name' => $user->get_name(),
          'chat' => $chat,
        );
      }
    }
    /** Update the chat message queue to include the new chat messages. */
    SimpleTextStorage::load()->write('Chat', 'chats', $chats);
  }

  /**
   * Report all chat messages for a specific user.
   *
   * @param array $user_ids
   *   (int) An array of user identifications to retrieve all messages for.
   *
   * @return
   *   An array of chat messages for the users, keyed by user identification.
   *   Each specified user will have an array of chat messages. The array will
   *   be empty if no messages exist.
   *
   * @see Chat::set() for individual message construction.
   */
  public static function peek($user_ids) {
    /** Store select users chat queue for return value. */
    $queue = array();
    /** Force the first parameter into an array. */
    $user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
    /** Load all existing chat messages waiting to be delivered. */
    $chats = SimpleTextStorage::load()->read('Chat', 'chats');
    /** Iterate over each destination user. Retrieve queue for select users. */
    foreach($user_ids as $user_id) {
      if (isset($chats[$user_id])) {
        $queue[$user_id] = $chats[$user_id];
      } else {
        $queue[$user_id] = array();
      }
    }

    return $queue;
  }

  /**
   * Remove all chat message queued for specified users.
   *
   * @param array $user_ids
   *   (int) An array of user identifications to remove all messages for.
   *
   * @return
   *   (int) The number of individual chat messages removed.
   */
  public static function delete($user_ids) {
    /** Store number of messages deleted for return value. */
    $deleted = 0;
    /** Force the first parameter into an array. */
    $user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
    /** Load all existing chat messages waiting to be delivered. */
    $chats = SimpleTextStorage::load()->read('Chat', 'chats');
    /** Iterate over each user. Remove their entire queue. */
    foreach($user_ids as $user_id) {
      if (isset($chats[$user_id])) {
        $deleted += count($chats[$user_id]);
        unset($chats[$user_id]);
      }
    }
    /** Remove messages from chat queue. */
    SimpleTextStorage::load()->write('Chat', 'chats', $chats);

    return $deleted;
  }

}