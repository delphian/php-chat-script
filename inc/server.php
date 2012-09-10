<?php

/**
 * Distribute a route and payload to the proper handler.
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
 *    protected static $routes = array('/myplugin/one', '/myplugin/two');
 *  }
 *  // Register plugin to handle routes.
 *  $server->register_handler('MyPlugin', MyPlugin::routes);
 *  // Get the request from $_GET, $_POST, or a plugin.
 *  $server->receive_request();
 *  // Run the server. Invoke all plugins registered for this route.
 *  $server->process_request();
 *  // Close down the server gracefully.
 *  $server->halt();
 * @endcode
 */

class Server {

  // Routes are url paths. A route determines where a payload
  // will end up.
  private $route = NULL;
  private $payload = NULL;

  // Entire class is designed to be a singleton.
  private static $singleton = NULL;
  // Path variables and other configuration options.
  private static $config = NULL;

  // Keep track of our route handlers.
  private static $plugins = array();
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
   * Plugins may register themselves here to get a callback when the server
   * encounters a route. All routes must be registered first. If the server
   * receives a route for which there is no handler the server will abort.
   *
   * @param string $plugin_name
   *   The name of the class to instantiate for callback.
   * @param array string $codes
   *   When these routes are encountered the server will inform the callback.
   *
   * @return bool $report
   *   TRUE on success, FALSE if there was any possible failure.
   */
  public static function register_plugin($plugin_name, $routes) {
    $report = TRUE;

    $routes = is_array($routes) ? $routes : array($routes);
    foreach($routes as $route) {
      self::$plugins[$route][] = $plugin_name;
    }

    return $report;
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
   * Plugins should use the boot hook to set or alter the route or payload
   * received by the server.
   *
   * @return $this->route
   *   The current route, or NULL on failure.
   */
  public function receive_request() {
    // Get the route from the url. The route is the url.
    $url_path = $_REQUEST['route'];
    if ($this->set_route($url_path)) {
      if (!$this->set_payload($_GET['payload'])) {
        $this->set_payload($_POST['payload']);
      }
    }
    // Give plugins the opportunity to alter or set the route.
    $this->invoke_all('__route');

    return $this->route;
  }

  /**
   * Execute the route recieved and pass the payload to the 
   * appropriate handler.
   */
  public function process_request() {
    $this->log(__CLASS__, __METHOD__);
    switch ($this->route) {
      case '__version':
        $this->headers[] = 'Content-Type: text/plain';
        $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
        $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
        $this->output = '0.4.8';
        break;
    }
    // Call all plugin handles.
    $this->invoke_all($this->route);
  }

  /**
   * Log the current server state into a text file.
   *
   * @param string $class
   *   Class that is invoking log, generally __CLASS__.
   * @param string $method
   *   Method that is invoking log, generally __METHOD__.
   *
   * @return bool TRUE
   *   TRUE on success, throws error on failure.
   */
  public function log($class = NULL, $method = NULL) {
    $config = self::get_config();
    $file   = $config['path_data'] . '/server_log.txt';
    $output = array(
      'time'    => time(),
      'class'   => $class,
      'method'  => $method,
      'route'   => $this->route,
      'payload' => $this->payload,
      'headers' => $this->headers,
      'output'  => $this->output,
    );
    $handle = (file_exists($file)) ? fopen($file, 'a') : fopen($file, 'w');
    if ($handle) {
      $encoded = json_encode($output) . "\n";
      if (!$x = fwrite($handle, $encoded)) {
        throw new Exception('Could not write to variables file.');
      }
      fclose($handle);
    } else {
      throw new Exception('Could not open variable file for writing.');
    }

    return TRUE;
  }

  /**
   * Inform plugins that a message they have registered for has been received.
   *
   * @param string $route
   *   Execute all handlers that have registered for this route.
   *
   * @return bool $report
   *   TRUE if no errors were encountered, FALSE if one or more errors.
   */
  public function invoke_all($route) {
    $report = TRUE;

    // If this is a message not generated by the server or a plugin and the
    // message has no handler then complain.
    if (!preg_match('/^__/', $route) &&
       (empty(self::$plugins) || !array_key_exists($route, self::$plugins))) {
      throw new Exception("No handler found to process route:{$route}");
    }
    $plugins = (!empty(self::$plugins[$route])) ? self::$plugins[$route] : NULL;

    if (is_array($plugins)) {
      foreach($plugins as $plugin) {
        $class = $plugin::load();
        if ($report) {
          $report = $class->receive_message($route, $this);
        }
        else {
          $class->receive_message($route, $this);
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
    $this->invoke_all('__halt');

    $this->log(__CLASS__, __METHOD__);
    // Send out our headers.
    foreach ($this->headers as $header) {
      header($header);
    }
    // Send out our output.
    print $this->output;

    return;
  }

  /**
   * Receive our route and make sure its safe.
   *
   * @param string $route
   *
   * @return bool $report
   *   TRUE if route was set, FALSE if the route was rejected as invalid.
   */
  public function set_code($route) {
    $report = FALSE;

    // @todo make sure this code has a handler.
    // @todo make sure the code is not prepended with 2 underscores. These are
    // reserved for server messages to plugins.
    if (is_string($route)) {
      $this->route = $route;
      $report = TRUE;
    }

    return $report;
  }

  /**
   * Receive our payload and make sure its safe. Not much will really be
   * done since ensuring a proper payload is route dependent.
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