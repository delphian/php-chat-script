<?php

/* ------------------------------------------------------------------ */
/*  CLASS CLIENT
 *
 *  ** METHODS
 *  assoc array  client::exists  (client_id);
 *  client_id    client::create  ();
 *  bool         client::delete  (client_id);
 *  bool         client::purge   (time);
 *  array        client::get_all ();
 *  bool         client->save    ();
 *
 *  bool         mute($value, $from_id, $message=NULL, $silent=NULL)
 *
 *  ** PROPERTIES
 *  id
 *  time
 *  name
 *  domain
 *  ip
 *  port
 *  admin
 *  mute
 *  room_id
 *  live         this client is accessing the script.
 *
 *  ** CONSTANTS
 *  client::FILE_NAME
 *
 */

class client extends message {
  const FILE_NAME = './data/user.txt';
  const FORMAT = 1;

  public $id;
  public $orig_id;
  public $name;
  public $domain;
  public $ip;
  public $port;
  public $admin;
  public $voice;
  public $room;

  public function client ($client_id, $orig_id) {
    if (!($elements = client::exists($client_id))) {
      $this->id = 0;
      return FALSE;
    }

    $this->id      = $elements[0];
    $this->orig_id = $orig_id;
    $this->time    = $elements[1];
    $this->name    = $elements[2];
    $this->domain  = $elements[3];
    $this->ip      = $elements[4];
    $this->port    = $elements[5];
    $this->admin   = $elements[6];
    $this->voice   = $elements[7];
    $this->room    = new room($elements[8]);
    return;
  }

  public function save() {
    if (!$this->id) return FALSE;

    $update  = $this->id."\t";
    $update .= time()."\t";
    $update .= $this->name."\t";
    $update .= $this->domain."\t";
    $update .= $this->ip."\t";
    $update .= $this->port."\t";
    $update .= $this->admin."\t";
    $update .= $this->voice."\t";
    $update .= $this->room->id."\n";

    $file = fopen(client::FILE_NAME, 'r+');
    flock($file, LOCK_EX);

    while (!feof($file)) {
      $line = fgets($file);
      if (rtrim($line)) {
        $elements = explode("\t", $line);
        if ($elements[0] != $this->id) {
          $new_lines .= $line;
        } else {
          $new_lines .= $update;
        }
      }
    }

    ftruncate($file, 0);
    rewind($file);
    fwrite($file, $new_lines);
    fclose($file);

    return TRUE;
  }

  public static function exists($client_id) {
    $lines = file(self::FILE_NAME);

    if (is_array($lines)) {
      foreach ($lines as $line) {
        if (rtrim($line)) {
          $elements = explode("\t", rtrim($line));
          if ($elements[0] == $client_id) return $elements;
        }
      }
    }

    return FALSE;
  }

  public static function create() {
    $client_id = mt_rand();

    $line  = $client_id."\t";                        // client_id
    $line .= time()."\t";                            // time
    $line .= "guest_".substr($client_id, 0, 4)."\t"; // name
    $line .= "0\t";                                  // domain
    $line .= "0\t";                                  // ip
    $line .= "0\t";                                  // port
    $line .= "0\t";                                  // admin
    $line .= "1\t";                                  // voice
    $line .= "0\n";                                  // room_id

    $file = fopen(self::FILE_NAME, 'a');
    flock($file, LOCK_EX);
    fwrite($file, $line);
    fclose($file);

    return $client_id;
  }

  /* Returns an array of client ids that have timed out. */
  public static function purge($time=NULL) {
    $lines  = file(self::FILE_NAME);
    $report = NULL;

    if (!$time) $time = time() - 120;

    if (is_array($lines)) {
      foreach ($lines as $line) {
        if (rtrim($line)) {
          $elements = explode("\t", $line);
          if ($elements[1] < $time) {
            $report[] = $elements[0];
          }
        }
      }
    }

    return $report;
  }

  public static function get_all($room_id=NULL) {
    $lines  = file(self::FILE_NAME);
    $output = NULL;

    if (is_array($lines)) {
      foreach ($lines as $line) {
        if (rtrim($line)) {
          $elements = explode("\t", rtrim($line));
          if (!isset($room_id) || strtolower($elements[8]) == strtolower($room_id)) {
            $output[] = $elements[0];
          }
        }
      }
    }

    return $output;
  }

  public static function delete($client_id) {
    $file = fopen(client::FILE_NAME, 'r+');
    flock($file, LOCK_EX);

    while (!feof($file)) {
      $line = fgets($file);
      if (rtrim($line)) {
        $elements = explode("\t", $line);
        if ($elements[0] != $client_id) {
          $new_lines .= $line;
        }
      }
    }

    ftruncate($file, 0);
    rewind($file);
    fwrite($file, $new_lines);
    fclose($file);

    return TRUE;
  }

  /********************************************************************/
  /*                                                                  */
  /* INCOMING COMMANDS/REQUESTS FROM CLIENT.                          */
  /* All sanity checks should be placed here.                         */
  /*                                                                  */
  /********************************************************************/

  /* ---------------------------------------------------------------- */
  /* ---------------------------------------------------------------- */
  public function client_main($code, $message, $version) {
    switch ($code) {
      case message::SRV_VERSION:
        $this->client_req_srv_version($message, $version);
        break;
      case message::RM_MSG:
        $this->client_req_rm_msg($message);
        break;
      case message::RM_MSG_ACTION:
        $this->client_req_rm_msg_action($message);
        break;
      case message::RM_MSG_IMAGE:
        $this->client_req_rm_msg_image($message);
        break;
      case message::RM_MSG_FLASH:
        $this->client_req_rm_msg_flash($message);
        break;
      case message::RM_TITLE:
        $this->client_req_rm_title($message);
        break;
      case message::RM_LOCKED:
        $this->client_req_rm_locked($message);
        break;
      case message::RM_ADMIN:
        $this->client_req_rm_admin($message);
        break;
      case message::RM_MODERATED:
        $this->client_req_rm_moderated($message);
        break;
      case message::RM_JOIN:
        $this->client_req_rm_join($message);
        break;
      case message::RM_PART:
        $this->client_req_rm_part($message);
        break;
      case message::RM_DETAIL:
        $this->client_req_rm_details($message);
        break;
      case message::RM_KICK:
        $this->client_req_rm_kick($message);
        break;
      case message::RM_VOICE:
        $this->client_req_rm_voice($message);
        break;
      case message::CL_NAME:
        $this->client_req_cl_name($message);
        break;
      case message::CL_RETRIEVE:
        $this->client_req_cl_retrieve();
        break;
      default:
        $this->client_srv_failure($this->id, "Unrecogized code from client: ($code).");
        return FALSE;
    }
    return TRUE;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_srv_version ($message, $version) {
    $report = $this->client_srv_version($version);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_cl_retrieve () {
    if (is_array($messages = $this->msg_read($this->id))) {
      foreach ($messages as $message) {
        print $message;
      }
    } else {
      $this->client_srv_nac($this->id, "No Messages Found");
    }
    $this->save();

    // Clear out any old users.
    if (is_array($client_ids = $this->purge())) {
      foreach($client_ids as $id) {
        $client = new client($id, $this->orig_id);
        $client->client_rm_part($client->room->id);
        unset($client);
        client::delete($id);
      }
    }

    return TRUE;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_cl_name ($message) {
    if ((!$this->voice) && (!$this->admin)) {
      $this->client_srv_failure($this->id, "May not change name while muted.");
      return FALSE;
    }
    $value = ereg_replace("[^A-Za-z0-9._]", "", $message[0]);
    if (!strlen($message[0])) {
      $this->client_srv_failure($this->id, "Name contains invalide characters.");
      return FALSE;
    }
    $report = $this->client_cl_name($value);
    $this->name = $value;
    $this->save();
    return $report;
  }

  /**
   * Process a request to promote a client to room administrator.
   *
   * @param array $message
   *  - Identification of the client that we want to promote.
   *  - Identificatino of the room to promote in.
   *  - Boolean 1 or 0, 1 to make admin, 0 to remove admin.
   *  - The password.
   *
   * @code
   * /admin {client_id} {1|0} {password}
   * @endcode
   */
  public function client_req_rm_admin ($message) {
    $client_id = $message[0];
    $room_id   = $message[1];
    $switch    = $message[2];
    $password  = $message[3];
    if ((!$this->admin) && ($password != 'depot')) return FALSE;
    if (!room::exists($room_id)) return FALSE;
    if (!client::exists($client_id)) return FALSE;
    $client = new client($client_id, $this->orig_id);
    $report = $client->client_rm_admin($this->id, $room_id, $switch);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_kick ($message) {
    $client_id = $message[0];
    $room_id   = $message[1];
    $msg       = $message[2];
    if (!$this->admin) return FALSE;
    if (!room::exists($room_id)) return FALSE;
    if (!client::exists($client_id)) return FALSE;
    $client = new client($client_id, $this->orig_id);
    $report = $client->kick($room_id, $this->id, $msg);
    unset($client);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_voice ($message) {
    $client_id = $message[0];
    $room_id   = $message[1];
    $switch    = $message[2];
    $msg       = $message[3];
    if (!$this->admin) return FALSE;
    if (!room::exists($room_id)) return FALSE;
    if (!client::exists($client_id)) return FALSE;
    $client = new client($client_id, $this->orig_id);
    $report = $client->voice($switch, $room_id, $this->id, $msg);
    unset($client);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_title ($message) {
    if ((!$this->admin) || ($this->voice)) return FALSE;
    $report = $this->client_rm_title ($message[0], $message[1]);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_locked ($message) {
    $room_id = $message[0];
    $mode    = $message[1];
    $msg     = $message[2];
    if (!$this->admin) return FALSE;
    $report = $this->client_rm_locked ($room_id, $mode, $msg);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_moderated ($message) {
    $room_id = $message[0];
    $mode    = $message[1];
    $msg     = $message[2];
    if (!$this->admin) return FALSE;
    $report = $this->client_rm_moderated ($room_id, $mode, $msg);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_msg ($message) {
    $room_id = $message[0];
    $msg     = $message[1];
    if ((!$this->admin) && (!$this->voice)) {
      $this->client_srv_failure($this->id, "The room is moderated. You may not speak.");
      return FALSE;
    }
    if ((strtolower($this->room->id) != strtolower($room_id)) || !$room_id) {
      $this->client_srv_failure($this->id, "Unable to deliver message. You are not in (".$room_id.").");
      return FALSE;
    }
    if (!room::exists($this->room->id)) {
      $this->client_srv_failure($this->id, "Unable to deliver message. Room does not exist.");
      return FALSE;
    }
    $msg = ereg_replace("[^A-Za-z0-9 .,?!/:-_%&]", "", $msg);
    $report = $this->client_rm_msg($room_id, $msg);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_msg_action ($message) {
    $room_id = $message[0];
    $msg     = $message[1];
    if ((!$this->voice) && (!$this->admin)) {
      $this->client_srv_failure($this->id, "The room is moderated. You may not speak.");
      return FALSE;
    }
    if (strtolower($this->room->id) != strtolower($room_id)) return FALSE;
    $msg = ereg_replace("[^A-Za-z0-9 .,?!/:-_%&]", "", $msg);
    $report = $this->client_rm_msg_action($room_id, $msg);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_msg_image ($message) {
    $room_id   = $message[0];
    $url       = $message[1];
    $comment   = $message[2];
    if ((!$this->voice) && (!$this->admin)) {
      $this->client_srv_failure($this->id, "The room is moderated. You may not speak.");
      return FALSE;
    }
    if (strtolower($this->room->id) != strtolower($room_id)) return FALSE;
    $url = ereg_replace("[^A-Za-z0-9 .,?!/:-_%&]", "", $url);
    $report = $this->client_rm_msg_image($room_id, $url, $comment);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_msg_flash ($message) {
    $room_id   = $message[0];
    $url       = $message[1];
    $comment   = $message[2];
    if ((!$this->voice) && (!$this->admin)) {
      $this->client_srv_failure($this->id, "The room is moderated. You may not speak.");
      return FALSE;
    }
    if (strtolower($this->room->id) != strtolower($room_id)) return FALSE;
    $url = preg_replace('[^A-Za-z0-9 .,?!/:\-_%&()+]', "", $url);
$this->client_srv_failure($this->id, "Loaded : ".$url);
    $report = $this->client_rm_msg_flash($room_id, $url, $comment);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_join ($message) {
    $room_id  = $message[0];
    $password = $message[1];
    if (room::exists($room_id)) {
      $room = new room($room_id);
      if ($room->locked) {
        $this->client_srv_failure($this->id, "The room \"".$room->name."\" (".$room->id.") is locked (".$room->locked."). You may not enter.");
        return FALSE;
      }
      unset($room);
    }
    $report = $this->client_rm_join($room_id);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_part ($message) {
    $report = $this->client_rm_part($message[0]);
    return $report;
  }

  /* ---------------------------------------------------------------- */
  public function client_req_rm_details ($message) {
    $report = $this->client_rm_details($message[0]);
    return $report;
  }

  /********************************************************************/
  /*                                                                  */
  /* OUTGOING COMMANDS/REQUESTS TO CLIENTS                            */
  /* If the command is called, "Just Do It". Admins may be playing.   */
  /*                                                                  */
  /********************************************************************/

  /* ---------------------------------------------------------------- */
  private function __sm ($to_id, $code) {
    $message = '';
    $time = time();
    $line = '';
    $line .= "{$to_id}\t";
    $line .= "{$time}\t";
    $line .= "{$code}\t";

    for ($x=2;$x<func_num_args();$x++) {
      if ($x != 2) $message .= ';';
      $parm = func_get_arg($x);
      $part = str_replace('%', '%25', $parm);
      $part = str_replace(';', '%3b', $part);
      $message .= $part;
    }
    $line .= $message;
    $line .= "\n";

    if ($this->orig_id == $to_id) {
      $this->msg_write(message::DIRECT, $line);
    } else {
      $this->msg_write(message::INDIRECT_APPEND, $line);
    }
    return TRUE;
  }

  /* Report to client reception of message, but no response --------- */
  public function client_srv_nac ($client_id) {
    $this->__sm($client_id, message::SRV_NAC);
    return TRUE;
  }

  /* Report to client a script failure ------------------------------ */
  public function client_srv_failure ($client_id, $message=NULL) {
    $this->__sm($client_id, message::SRV_FAILURE, $message);
    return TRUE;
  }

  /* Report to client a server version ------------------------------ */
  public function client_srv_version ($message) {
    $this->__sm($this->id, message::SRV_VERSION, $message);
    return TRUE;
  }

  /* Report to room a client's name change -------------------------- */
  public function client_cl_name ($value) {
    if (is_array($client_ids = $this->get_all($this->room->id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::CL_NAME, $this->id, $value);
      }
    }
    return TRUE;
  }

  /* Report to client its new ID ------------------------------------ */
  public function client_cl_id () {
    $this->__sm($this->id, message::CL_ID, $this->id);
    $this->client_rm_all();
    return TRUE;
  }

  /* Report all room detail to client. ------------------------------ */
  public function client_rm_all () {
    if (is_array($room_ids = room::get_all())) {
      foreach ($room_ids as $id) {
        $this->client_rm_detail($id);
      }
    }
    return TRUE;
  }

  /* Report room detail to client. ---------------------------------- */
  public function client_rm_detail ($room_id) {
    if (!room::exists($room_id)) return FALSE;
    $room = new room($id);
    $this->__sm($id, message::RM_DETAIL, $room->id, $room->title, $room->moderated, $room->locked);
    return TRUE;
  }

  /* Report to room client's posted image --------------------------- */
  public function client_rm_msg_image ($room_id, $msg, $comment=NULL) {
    if (!room::exists($room_id)) return FALSE;
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_MSG_IMAGE, $this->id, $this->room->id, $msg, $comment);
      }
    }
    return TRUE;
  }

  /* Report to room client's posted flash --------------------------- */
  public function client_rm_msg_flash ($room_id, $msg, $comment=NULL) {
    if (!room::exists($room_id)) return FALSE;
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_MSG_FLASH, $this->id, $this->room->id, $msg, $comment);
      }
    }
    return TRUE;
  }

  /* Join client to room. Report to room client's join -------------- */
  public function client_rm_join ($room_id) {
    // Create room if it does not exist.
    $this->voice  = 1;
    $this->admin = 0;
    if (!room::exists($room_id)) {
      $room_id = room::create($room_id);
      $this->admin = 1;
    }
    // Part last room.
    if ($this->room->id != $room_id) {
      if ($this->room->id) {
        $this->client_rm_part($this->room->id);
      }
    }
    $this->room = new room($room_id);
    if ($this->room->moderated) $this->voice = 0;
    $this->save();
    if (is_array($client_ids = $this->get_all($this->room->id))) {
      foreach($client_ids as $id) {
        $client = new client($id, $this->orig_id);
        $this->__sm($id, message::RM_JOIN, $this->id, $this->room->id, $this->name);
        $this->__sm($id, message::CL_DETAIL, $this->id, $room_id, $this->name, $this->voice, $this->admin, $this->image);
        $this->__sm($this->id, message::CL_DETAIL, $client->id, $room_id, $client->name, $client->voice, $client->admin, $client->image);
        unset($client);
      }
    }
    $this->__sm($this->id, message::RM_DETAIL, $room_id, $this->room->title, $this->room->moderated, $this->room->locked);
    return TRUE;
  }

  /* Part client from room. Report to room client's part ------------ */
  public function client_rm_part ($room_id, $message=NULL) {
    $count = 0;
    if (!room::exists($room_id)) return FALSE;
    $room = new room($room_id);
    if (is_array($client_ids = $this->get_all($room->id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_PART, $this->id, $room_id, $message);
        $count++;
      }
    }
    if ($count == 1) {
      room::delete($room_id);
    }
    $this->room->id = 0;
    $this->save();
    return TRUE;
  }

  /* Client sets admin status --------------------------------------- */
  public function client_rm_admin ($client_id, $room_id, $switch) {
    if (!room::exists($room_id)) return FALSE;
    $this->admin = $switch;
    $this->save();
    if (is_array($client_ids = $this->get_all($room->id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_ADMIN, $client_id, $this->id, $room_id, $switch);
        $this->__sm($id, message::CL_DETAIL, $this->id, $room_id, $this->name, $this->voice, $this->admin, $this->image);
      }
    }
    return TRUE;
  }

  /* Client sends room message. ------------------------------------- */
  public function client_rm_msg ($room_id, $message) {
    if (!room::exists($room_id)) return FALSE;
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_MSG, $this->id, $room_id, $message);
      }
    }
    return TRUE;
  }

  /* Client sends room action --------------------------------------- */
  public function client_rm_msg_action ($room_id, $message) {
    if (!room::exists($room_id)) return FALSE;
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_MSG_ACTION, $this->id, $room_id, $message);
      }
    }
    return TRUE;
  }

  /* ---------------------------------------------------------------- */
  public function client_rm_locked ($room_id, $value, $message=NULL) {
    if (!room::exists($room_id)) return FALSE;
    $this->room->locked = $value;
    $this->room->save();
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_LOCKED, $this->id, $room_id, $value, $message);
        $this->__sm($id, message::RM_DETAIL, $this->room->id, $this->room->title, $this->room->moderated, $this->room->locked);
      }
    }
    return TRUE;
  }

  /* ---------------------------------------------------------------- */
  public function client_rm_moderated ($room_id, $value, $message=NULL) {
    if (!room::exists($room_id)) return FALSE;
    $this->room->moderated = $value;
    $this->room->save();
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_MODERATED, $this->id, $room_id, $value, $message);
        $this->__sm($id, message::RM_DETAIL, $this->room->id, $this->room->title, $this->room->moderated, $this->room->locked);
        if ($id != $this->id) {
          $client = new client($id, $this->orig_id);
          $client->voice($value?0:1, $room_id, $this->orig_id, $message, 1);
          unset($client);
        }
      }
    }
    return TRUE;
  }

  /* ---------------------------------------------------------------- */
  public function client_rm_title ($room_id, $value) {
    if (!room::exists($room_id)) return FALSE;
    if (is_array($client_ids = $this->get_all($room_id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::RM_TITLE, $this->id, $room_id, $value);
      }
    }
    return TRUE;
  }

  /********************************************************************/
  /*                                                                  */
  /* CHANGING CLIENT VALUES.                                          */
  /*                                                                  */
  /********************************************************************/

  /* ---------------------------------------------------------------- */
  public function voice ($value, $room_id, $from_id, $message=NULL, $silent=NULL) {
    $this->voice = $value;
    $this->save();

    if (is_array($client_ids = $this->get_all($this->room->id))) {
      foreach($client_ids as $id) {
        $this->__sm($id, message::CL_DETAIL, $this->id, $this->room->id, $this->name, $this->voice, $this->admin, $this->image);
        if (!$silent) $this->__sm($id, message::RM_VOICE, $from_id, $this->id, $this->room->id, $value, $message);
      }
    }
    return TRUE;
  }

  /* ---------------------------------------------------------------- */
  public function kick ($room_id, $from_id, $message=NULL, $silent=NULL) {
    if (is_array($client_ids = $this->get_all($this->room->id))) {
      foreach($client_ids as $id) {
        if (!$silent) $this->__sm($id, message::RM_KICK, $from_id, $this->id, $this->room->id, $message);
      }
    }
    $this->room->id=0;
    $this->save();
    return TRUE;
  }

}

?>