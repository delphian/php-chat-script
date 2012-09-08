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
  private $name;
  // Weight determines in which order a plugin should be executed for codes
  // that have been requestd by multiple plugins. Lower integers execute first.
  private $weight = 0;
  // This variable will be persistant and automatically loaded at instantiation.
  // Massive data storage needs are not intended to be met by this object.
  private $variables = array();
  // An array of strings that the plugin wants to process.
  private static $codes = array();

  // Keep track of our code handlers. Plugins for plugins.
  private static $plugins = NULL;
  // Track which plugins actually got instantiated.
  private $plugins_loaded = array();
 

  /**
   * Other plugins may register themselves here to get a callback when this
   * plugin encounters a code.
   *
   * @param string $plugin_name
   *   The name of the class to instantiate for callback.
   * @param array string $codes
   *   When these codes are encountered this plugin will inform the callback.
   *
   * @return bool $report
   *   TRUE on success, FALSE if there was any possible failure.
   */
  public static function register_plugin($plugin_name, $codes) {
    $report = TRUE;

    $codes = is_array($codes) ? $codes : array($codes);
    foreach($codes as $code) {
      self::$plugins[$code][] = $plugin_name;
    }

    return $report;
  }

  /**
   * Get the $codes variable.
   */
  public static function get_codes() {
    return self::$codes;
  }

  /**
   * Constructor will automatically fill $variables from storage and inform
   * any registered plugins that it has been instantiated.
   */
  public function __construct() {
    // Call potential registered third party plugins.
    $this->invoke_all('__boot');
    $this->variables_read();

    return;
  }

  /**
   * Main function callback to process a message we have registered for.
   */
  public function receive_message(&$code, Server $server) {
    // Call potential registered third party plugins.
    $this->invoke_all($code);

    return;
  }

  /**
   * Inform third party plugin that a message they have registered for has been
   * received.
   *
   * @param string $code
   *   Execute all handlers that have registered for this code.
   *
   * @return bool $report
   *   TRUE if no errors were encountered, FALSE if one or more errors.
   */
  public function invoke_all($code) {
    $report  = TRUE;
    $plugins = array();

    if (!empty($this->plugins) && array_key_exists($code, $this->plugins)) {
      $plugins = $this->plugins[$code];
    }

    if (!empty($plugins)) {
      foreach($plugins as $plugin) {
        $this->plugins_loaded[$plugin] = new $plugin();
        $class =& $this->plugins_loaded[$plugin];
        if ($report) {
          $report = $class->receive_message($code, $this);
        }
        else {
          $class->receive_message($code, $this);
        }
      }
    }

    return $report;
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
   */
  public function halt() {
    $this->variables_write();
    // Call potential registered third party plugins.
    $this->invoke_all('__halt');

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

  /**
   * Get property.
   */
  public function get_name() {
    return $this->name;
  }

  /**
   * Get property.
   */
  public function get_weight() {
    return $this->weight;
  }

  /**
   * Get property.
   */
  public function get_variables() {
    return $this->variables;
  }

}

?>