<?php

/**
 * Plugins for the server.
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * All plugins must extend this class.
 *
 * @todo where/how do we open all plugin php files?
 */
abstract class ServerPlugin {

  // Machine friendly name (A-Za-z_) of class.
  protected $name;
  // This variable will be persistant and automatically loaded at instantiation.
  // Massive data storage needs are not intended to be met by this object.
  private $variables;
  // An array of strings that the plugin wants to process.
  private $codes;

  // Main function to process a message.
  abstract function receive_message(&$code, Server $server);

  // Constructor will automatically fill $variables from storage.
  public function __construct() {
    $this->variables_read();
  }

  /**
   * Manage limited variable retention for plugins.
   */
  public function variables_read() {
    $config = Server::get_config();
    if (file_exists($config['path_data'] . 'variables.txt')) {
      $file_array = json_decode(file_get_contents($config['path_data'] . 'variables.txt'), TRUE);
      if (isset($file_array[$this->name])) {
        $this->variables = $file_array[$this->name];
      }
    }

    return;
  }

  /**
   * Persist $variables into permenant storage.
   *
   * If the class is ever insantiated it will receive a halt message to allow
   * gracefull shutdown before the server stops. All superclasses should call
   * parent::halt()
   */
  public function halt() {
    $this->variables_write();

    return;
  }

  /**
   * Write plugin variables to file.
   */
  public function variables_write() {
    if (!empty($this->variables)) {
      $config = Server::get_config();
      $file_array = array();
      $file_array = json_decode(file_get_contents($config['path_data'] . 'variables.txt'), TRUE);
      $file_array[$this->name] = $this->variables;
      $handle = fopen($config['path_data'] . 'variables.txt', 'w');
      if ($handle) {
        $encoded = json_encode($file_array);
        if (!$x = fwrite($handle, $encoded)) {
          throw new Exception('Could not write to variables file.');
        }
        fclose($handle);
      } else {
        throw new Exception('Could not open variable file for writing.');
      }
    }
  }

}

?>