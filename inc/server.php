<?php

/**
 * Distribute a code and payload to the proper handler.
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * @code
 *  // Create server.
 *  $server = Server::load($config);
 *  // Craete plugin.
 *  class MyPlugin {
 *    protected static $codes = array('code_one', 'code_two', 'code_three');
 *  }
 *  // Register plugin to handle codes.
 *  $server->register_handler('MyPlugin', MyPlugin::codes);
 *  // Get the request from $_GET, $_POST, or a plugin.
 *  $server->receive_request();
 *  // Run the server. Invoke all plugins registered for this code.
 *  $server->process_request();
 *  // Close down the server gracefully.
 *  $server->halt();
 * @endcode
 */

class Server {

  // Codes are used to route payloads to the appropriate destination.
  private $code = NULL;
  private $payload = NULL;

  // Entire class is designed to be a singleton.
  private static $singleton = NULL;
  // Path variables and other configuration options.
  private static $config = NULL;

  // Keep track of our code handlers.
  private $plugins = NULL;
  // Track which plugins actually got instantiated.
  private $plugins_loaded = array();

  // Headers to be output at end of server execution.
  private $headers = array();
  // Response sent to the client at end of server execution.
  private $output = NULL;

  /**
   * Load up our class as a singleton. On the first time this function is called
   * assign the new class instantiated to our own static variable. Any other
   * time this function is called simply return the static variable if it
   * has a value. Always use this function to instantiate the server.
   *
   * All parameters for paths should be absolute and not include a trailing 
   * slash.
   *
   * @param array $config
   *   Assocative array of strings containing configuration options.
   *   - 'path_root'        string  Path to the base directory of script.
   *   - 'path_inc'         string  Path to the include directory.
   *   - 'path_plugins'     string  Path to the plugins directory.
   *   - 'path_data'        string  Path to the data directory.
   *
   * @return Server self::$singleton
   */
  public static function load($config = NULL) {
    if (!isset(self::$singleton)) {
      if (!isset($config)) {
        throw new Exception('Config must be specified when instantiating.');
      }
      self::$config = $config;
      self::$singleton = new Server();
      self::$singleton->load_plugins();
    }

    return self::$singleton;
  }

  /**
   * Return config variable to whoever may want it.
   *
   * @return array $this->config
   *
   * @see Server::load()
   */
  public static function get_config() {
    return self::$config;  
  }

  /**
   * Constsructor
   *
   * @return Server $this
   *   Insantiated server class on success, exception will be thrown on failure.
   */
  public function __constructor() {

    return $this;
  }

  /**
   * Load all php files in the plugin directory.
   *
   * This should be called by the static load method after the configuration
   * has been set.
   *
   * @return bool $report
   *   TRUE on success, FALSE if there was one or more errors.
   */
  function load_plugins() {
    $report = FALSE;
    $config = self::get_config();

    if ($handle = opendir($config['path_plugins'])) {
      while (false !== ($entry = readdir($handle))) {
        if (preg_match('/\.php$/', $entry)) {
          require_once($config['path_plugins'] . '/' . $entry);
        }
      }
      closedir($handle);
    }

    return $report;
  }

  /**
   * Plugins may register themselves here to get a callback when the server
   * encounters a code. All codes must be registered first. If the server
   * receives a code for which there is no handler the server will abort.
   *
   * @param string $plugin_name
   *   The name of the class to instantiate for callback.
   * @param array string $codes
   *   When these codes are encountered the server will inform the callback.
   *
   * @return bool $report
   *   TRUE on success, FALSE if there was any possible failure.
   */
  public function register_plugin($plugin_name, $codes) {
    $report = TRUE;

    $codes = is_array($codes) ? $codes : array($codes);
    foreach($codes as $code) {
      $this->plugins[$code][] = $plugin_name;
    }

    return $report;
  }

  /**
   * Plugins should use the boot hook to set or alter the code or payload
   * received by the server. 
   *
   * @return $this->code
   *   The current code, or NULL on failure.
   */
  public function receive_request() {
    // Get a code from get or post.
    if ($this->set_code($_GET['code'])) {
      $this->set_payload($_GET['payload']);
    } elseif ($this->set_code($_POST['code'])) {
      $this->set_payload($_POST['payload']);
    }
    // Give plugins the opportunity to alter or set the code.
    $this->invoke_all('__code');

    return $this->code;
  }

  /**
   * Execute the code recieved and pass the payload to the appropriate handler.
   */
  public function process_request() {
    switch ($this->code) {
      case '__version':
        $this->headers[] = 'Content-Type: text/plain';
        $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
        $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
        $this->output = '0.4.8';
        break;
    }
    // Call all plugin handles.
    $this->invoke_all($this->code);
  }

  /**
   * Inform plugins that a message they have registered for has been received.
   *
   * @param string $code
   *   Execute all handlers that have registered for this code.
   *
   * @return bool $report
   *   TRUE if no errors were encountered, FALSE if one or more errors.
   */
  public function invoke_all($code) {
    $report = TRUE;

    if (!array_key_exists($code, $this->handlers)) {
      throw new Exception('No handler found to process code.');
    }
    $plugins = $this->plugins[$code];

    if (is_array($plugins)) {
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
   * Clean up gracefully before ending server operation. This must be called
   * as the last activity before the script ends.
   */
  public function halt() {
    // Give plugins the opportunity to shut down gracefully.
    foreach ($this->plugins_loaded as $plugin_name => $plugin_class) {
      $plugin_class->halt($this);
    }

    // Send out our headers.
    foreach ($this->headers as $header) {
      header($header);
    }
    // Send out our output.
    print $output;

    return;
  }

  /**
   * Receive our code and make sure its safe.
   *
   * @param string $code
   *
   * @return bool $report
   *   TRUE if code was set, FALSE if the code was rejected as invalid.
   */
  public function set_code($code) {
    $report = FALSE;

    // @todo make sure this code has a handler.
    // @todo make sure the code is not prepended with 2 underscores. These are
    // reserved for server messages to plugins.
    if (is_string($code)) {
      $this->code = $code;
      $report = TRUE;
    }

    return $report;
  }

  /**
   * Receive our payload and make sure its safe. Not much will really be
   * done since ensuring a proper payload is code dependent.
   *
   * @param string|NULL $payload
   *
   * @return bool $report
   *   TRUE if payload was set, FALSE if the payload was rejected as invalid.
   */
  public function set_payload($payload) {
    $report = FALSE;

    if (!isset($payload) || is_string($payload)) {
      $this->payload = $payload;
      $report = TRUE;
    }

    return $report;
  }

}


?>