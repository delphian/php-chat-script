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

  // Exact same name as the class.
  protected static $name;
  // Weight determines in which order a plugin should be executed for codes
  // that have been requestd by multiple plugins. Lower integers execute first.
  protected $weight = 0;
  // This variable will be persistant and automatically loaded at instantiation.
  // Massive data storage needs are not intended to be met by this object.
  protected $variables = array();
  // An array of strings that the plugin wants to process.
  protected static $codes = array();

  // Entire class is designed to be a singleton.
  protected static $singleton = NULL;

  // Keep track of our code handlers. Plugins for plugins.
  protected static $plugins = NULL;
  // Track which plugins actually got instantiated.
  protected $plugins_loaded = array();

  // Headers to be output at end of server execution.
  protected $headers = array();
  // Response sent to the client at end of server execution.
  protected $output = NULL;

  /**
   * Load up our class as a singleton. On the first time this function is called
   * assign the new class instantiated to our own static variable. Any other
   * time this function is called simply return the static variable if it
   * has a value. Always use this function to instantiate the plugin.
   *
   * @return mixed(ServerPlugin superclass) static::$singleton
   */
  public static function load() {
    if (!isset(self::$singleton)) {
      $class = static::$name;
      static::$singleton = new $class();
    }

    return static::$singleton;
  }

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
      static::$plugins[$code][] = $plugin_name;
    }

    return $report;
  }

  /**
   * Get the $codes variable.
   */
  public static function get_codes() {
    return static::$codes;
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
    // Plugins must process their logic before calling parent::receive_message()

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
    if (file_exists($config['path_data'] . '/variables.txt')) {
      $name = $this->get_name();
      $file_array = json_decode(file_get_contents($config['path_data'] . '/variables.txt'), TRUE);
      if (isset($file_array[$name])) {
        $this->variables = $file_array[$name];
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
      $name = $this->get_name();
      $config = Server::get_config();
      $file_array = array();
      $file_array = json_decode(file_get_contents($config['path_data'] . '/variables.txt'), TRUE);
      $file_array[$name] = $this->variables;
      $handle = fopen($config['path_data'] . '/variables.txt', 'w');
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
    return static::$name;
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

  /**
   * Set our headers.
   *
   * @param array $headers
   *   Strings to be output as header information.
   *
   * @return bool $report
   *   TRUE if headers set, FALSE if the headers were rejected as invalid.
   */
  public function set_headers($headers) {
    $report = FALSE;

    if (is_array($headers)) {
      $this->headers = $headers;
      $report = TRUE;
    }

    return $report;
  }

  /**
   * Set our output.
   *
   * @param string $output
   *   output sent to the browser.
   *
   * @return bool $report
   *   TRUE if output set, FALSE if the output was rejected as invalid.
   */
  public function set_output($output) {
    $report = FALSE;

    if (!isset($output) || is_string($output)) {
      $this->output = $output;
      $report = TRUE;
    }

    return $report;
  }

}

?>