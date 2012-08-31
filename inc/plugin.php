<?php

/*
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/* *********************************************************************** */

/**
 * Main class that plugins should extend. The main purpose of this class
 * is really to call the methods of any other plugins that have been
 * registered.
 */
class PHPChatScriptPluginBase {

  // Entire class is designed to be a singleton.
  public static $plugin_base = NULL;

  // Weight will define the order in which our plugins are executed. A plugin
  // with the highest weight (number with the highest value) will execute
  // last and have the last opportunity to change information and process
  // data. The default weight is 0. Increase the weight to a positive number
  // relative to other plugins to execute after they do. Decrease the weight
  // to a negative number relative to other plugins to execute before they do.
  private $weight  = 0;

  // List of all other plugins. This is an array of actual instantiated classes.
  public static $plugins = array();

  // Our global configuration.
  public static $config = NULL;

  // This is a persistant variable that can be used by extending classes. It
  // Will be saved when the child class calls parent::halt() and will be loaded
  // again when the child calls parent::boot().
  public $variables = array();

  public static function register($class_name) {
    // Check class->name for valid.
    PHPChatScriptPluginBase::$plugins[] = new $class_name();

    return;
  }

  /**
   * Load up our class as a singleton. On the first time this function is called
   * assign the new class instantiated to our own static variable. Any other
   * time this function is called simply return the static variable if it
   * has a value. Always use this function to instantiate the base plugin class.
   */
  public static function load($config = NULL) {
    if (!isset(self::$plugin_base)) {
      self::$plugin_base = new PHPChatScriptPluginBase();
      self::$config = $config;
    }

    return self::$plugin_base;
  }

  /**
   * Include all files in the plugins directory if they are php files. These
   * plugins will extend the above class and then register themselves with
   * the global variable.
   */
  public static function load_plugins() {
    if ($handle = opendir(self::$config['path_plugins'])) {
      /* This is the correct way to loop over the directory. */
      while (false !== ($entry = readdir($handle))) {
        if (preg_match('/.*\.php$/', $entry, $matches)) {
          include_once(self::$config['path_plugins'] . $entry);
        }
      }

      closedir($handle);
    }
  }

  /**
   * Constructor.
   */
  public function __construct() {

    return;
  }

  /**
   * Execute a method for all plugins.
   *
   * @param string $method
   *   The name of the method to invoke on all plugins.
   */
  public function invoke_all($method, &$p1=NULL, &$p2=NULL, &$p3=NULL) {
    // Wow this is ugly...
    $arguments = array();
    $arguments[0] = &$p1;
    $arguments[1] = &$p2;
    $arguments[2] = &$p3;

    $plugins = PHPChatScriptPluginBase::$plugins;
    if (!empty($plugins)) {
      foreach($plugins as $plugin) {
        if (method_exists($plugin, $method)) {
          call_user_func_array(array($plugin, $method), $arguments);
        }
      }
    }
  }

  /**
   * Manage limited variable retention for plugins.
   */
  public function variables_read() {
    if (file_exists(self::$config['path_data'] . 'variables.txt')) {
      $file_array = json_decode(file_get_contents(self::$config['path_data'] . 'variables.txt'), TRUE);
      if (isset($file_array[$this->name])) {
        $this->variables = $file_array[$this->name];
      }
    }

    return;
  }

  /**
   * Write plugin variables to file.
   */
  public function variables_write() {
    if (!empty($this->variables)) {
      $file_array = array();
      $file_array = json_decode(file_get_contents(self::$config['path_data'] . 'variables.txt'), TRUE);
      $file_array[$this->name] = $this->variables;
      $handle = fopen(self::$config['path_data'] . 'variables.txt', 'w');
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
   * Plugins opportunity to do stuff before any main server code has been 
   * executed.
   */
  public function boot() {
    $this->variables_read();

    return;
  }

  /**
   * Recieve the http request and format the message that the server will
   * process. All requests to the server must be formatted the same way. If
   * the plugin has nothing to offer for this web server request then don't
   * make any changes!
   *
   * @param array $request
   *   The raw information submitted to the web server. Generally his will be 
   *   the $_REQUEST variable.
   * @param array $&server_input
   *   An associative array containing the data the server will process:
   *    - 'code'
   *    - 'from'
   *    - 'time'
   *    - 'message'
   *   Another plugin may have already filled this with values. This is our
   *   opportunity to change the data.
   */
  public function format_request($request, &$server_input) {

    return;
  }

  /**
   * The main server code is finished operating. This allows the plugins
   * to clean up anything they have been working on.
   */
  public function halt() {
    $this->variables_write();

    return;
  }
}

?>