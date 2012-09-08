<?php

/*
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/*
 * METHODS *************************************************************
 *
 * string msg_format  array $values
 * bool   msg_write  {message::DIRECT
 *                    message::INDIRECT_APPEND
 *                    message::INDIRECT_OVER}
 *                    string $messages
 * array  msg_read   {message::CLIENT_FROM
 *                    message::CLIENT_TO}
 *                    number $client_id
 *                    number $time
 * string code_text   number $code
 *
 * PROPERTIES **********************************************************
 *
 * const FORMAT
 * const FILE_NAME
 * const DIRECT
 * const INDIRECT_OVER
 * const CLIENT_FROM
 * const CLIENT_TO
 *
 * PROPER MESSAGE ARRAY ORDER ******************************************
 *
 * format, time, code, room, from, to, message
 */

class message {
  const FORMAT          = 1;
  const FILE_NAME       = './data/message.txt';
  const DIRECT          = 1;
  const INDIRECT_APPEND = 2;
  const INDIRECT_OVER   = 3;
  const CLIENT_FROM     = 1;
  const CLIENT_TO       = 2;

  const SRV_VERSION   = 9;
  const SRV_FAILURE   = 6;
  const SRV_IMPROPER  = 16;
  const SRV_NAC       = 8;
  const SRV_WAIT      = 2;

  const RM_LOCKED     = 1001;
  const RM_MODERATED  = 1002;
  const RM_ADMIN      = 1003;
  const RM_JOIN       = 1005;
  const RM_PART       = 1006;
  const RM_DETAIL     = 1007;
  const RM_ALL        = 1008;
  const RM_TITLE      = 1009;
  const RM_KICK       = 1012;
  const RM_VOICE      = 1013;
  const RM_MSG        = 1100;
  const RM_MSG_IMAGE  = 1101;
  const RM_MSG_FLASH  = 1102;
  const RM_MSG_ACTION = 1103;

  const CL_NAME       = 2000;
  const CL_RETRIEVE   = 2001;
  const CL_KILL       = 2004;
  const CL_DETAIL     = 2005;
  const CL_IMAGE      = 2006;
  const CL_MSG        = 2100;
  const CL_MSG_IMAGE  = 2101;
  const CL_MSG_FLASH  = 2102;
  const CL_MSG_ACTION = 2103;
  const CL_ID         = 2200;


  public $msg_to      = NULL;
  public $msg_from    = NULL;
  public $msg_time    = NULL;
  public $msg_code    = NULL;
  public $msg_payload = NULL;

  // Payloads should be configured according to the following structure.
  // 'to client' plugins are javascript classes. The class name must match
  // the filename without the .js extention. The file must be located in the
  // js/plugins/ directory.
  // 'to server' plugins are php classes. The class name must match the filename
  // without the .php extention. The file must be located in the inc/plugins/
  // directory.
  public $payload_config = array(
    'request_client_id' => array(
      'description' => 'A potential client is requesting access to the system',
      'plugin' => NULL,
      'value' => TRUE,
    ),
  );

  /* Create a message informing client of improper formating. ------- */
  public static function msg_improper_format() {
    $report = "0\t".time()."\t".message::SRV_IMPROPER."\tImproper Message Format\n";
    print $report;
    return TRUE;
  }

  /**
   * Record a message in the message file.
   */
  public function write () {
    $message = json_encode(array(
      'time'    => $this->time,
      'to'      => $this->to,
      'from'    => $this->from,
      'code'    => $this->code,
      'payload' => $this->payload,
    ));

    if ($this->from == $this->to) {
      print $message . "\n";
    }
    else {
      $file = fopen(message::FILE_NAME, 'a');
      flock($file, LOCK_EX);
      fwrite($file, $message);
      fclose($file);
    }

    return TRUE;
  }

  /* Returns array of messages from/to user. Removes messages ------- */
  /* from the file. Also removes messages that have expired. */
  public function read ($client_id, $time=NULL) {
    $report = NULL;
    $new_messages = NULL;
    if (!$time) $time = time() - 120;

    $file = fopen(message::FILE_NAME, 'r+');
    flock($file, LOCK_EX);

    while (!feof($file)) {
      $line = json_decode(rtrim(fgets($file)), TRUE);
      if ($line['to'] == $client_id) {
        $report[] = $line;
      }
      else {
        if ($line['time'] > $time) {
          $new_messages .= json_encode($line) . "\n";
        }
      }
    }

    ftruncate($file, 0);
    rewind($file);
    fwrite($file, $new_messages);
    fclose($file);

    return $report;
  }

}


?>