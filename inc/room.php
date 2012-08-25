<?php

/* ------------------------------------------------------------------ */
/*  CLASS ROOM
 *
 *  ** METHODS
 *
 *  ** PROPERTIES
 *
 *  ** CONSTANTS
 *  room::FILE_NAME
 *
 */

class room {
  const FILE_NAME = './data/room.txt';

  public $id;
  public $name;
  public $moderated;
  public $locked;

  public function room ($room_id) {
    if (!($elements = room::exists($room_id))) {
      $this->id = 0;
      return FALSE;
    }

    $this->id        = $elements[0];
    $this->name      = $elements[1];
    $this->moderated = $elements[2];
    $this->locked    = rtrim($elements[3]);

    return;
  }

  public function save() {
    if (!$this->id) return FALSE;

    $update  = $this->id."\t";
    $update .= $this->name."\t";
    $update .= $this->moderated."\t";
    $update .= $this->locked;
    $update .= "\n";

    $file = fopen(self::FILE_NAME, 'r+');
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

  public static function exists ($room_id) {
    $lines = file(self::FILE_NAME);

    if (is_array($lines)) {
      foreach ($lines as $line) {
        if (rtrim($line)) {
          $elements = explode("\t", $line);
          if (strtolower($elements[0]) == strtolower($room_id)) return $elements;
        }
      }
    }

    return FALSE;
  }

  public static function create($room_id=NULL) {
    if (!$room_id) $room_id = mt_rand();

    $file = fopen(self::FILE_NAME, 'a');
    $line  = $room_id."\t";                          // room_id
    $line .= "new\t";                                // name
    $line .= "0\t";                                  // moderated
    $line .= "0";                                    // locked
    $line .= "\n";
    fwrite($file, $line);
    fclose($file);

    return $room_id;
  }

  public static function get_all() {
    $lines  = file(self::FILE_NAME);
    $output = NULL;

    if (is_array($lines)) {
      foreach ($lines as $line) {
        if (rtrim($line)) {
          $elements = explode("\t", $line);
          $output[] = $elements[0];
        }
      }
    }

    return $output;
  }

  public static function delete($room_id) {
    $file = fopen(self::FILE_NAME, 'r+');
    flock($file, LOCK_EX);

    while (!feof($file)) {
      $line = fgets($file);
      if (rtrim($line)) {
        $elements = explode("\t", $line);
        if ($elements[0] != $room_id) {
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

}

?>