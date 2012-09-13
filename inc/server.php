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

class Server extends Subject {

  // Routes are url paths. A route determines where a payload
  // will end up.
  private $route = NULL;

  /**
   * @see Plugin::load()
   */
  public static function load($config) {
    $singleton = parent::load($config);

    if ($singleton) {
      $singleton->load_plugins();
    }

    return $singleton;
  }

  /**
   * Plugins may register themselves here to get a callback when the server
   * encounters a route. All $routes must be registered first. If the server
   * receives a route for which there is no handler the server will abort.
   *
   * @param string $plugin_name
   *   The name of the class to instantiate for callback.
   * @param string array $routes
   *   An array of strings containing which routes the requestor should be
   *   notified on if received.
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
   * Load all php files in the plugin directory.
   *
   * This should be called by the static load method after the configuration
   * has been set.
   *
   * @param string $base (optional)
   *   Directory to load all php files from. Primarily used for recursion.
   *
   * @return bool TRUE
   */
  public function load_plugins($base = NULL) {
    $recursion = FALSE;
    if (!isset($base)) {
      $report = FALSE;
      $base   = $this->config['path_plugins'];
      $recursion = TRUE;
    }

    if ($handle = opendir($base)) {
      while (false !== ($entry = readdir($handle))) {
        if ($recursion && is_dir($base . '/' . $entry)) {
          $this->load_plugins($base . '/' . $entry);
        }
        if (preg_match('/\.php$/', $entry)) {
          require_once($base . '/' . $entry);
        }
      }
      closedir($handle);
    }

    return TRUE;
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
    $route = '/';
    if (isset($_REQUEST['route'])) {
      $route = $_REQUEST['route'];  
    }

    if ($this->set_route($route)) {
      if (isset($_GET['payload'])) {
        if (!$this->set_payload($_GET['payload'])) {
          if (isset($_POST['payload'])) {
            $this->set_payload($_POST['payload']);
          }
        }
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

    // Call all plugin handlers.
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
    $file   = $this->config['path_data'] . '/server_log.txt';
    $output = array(
      'time'    => time(),
      'class'   => $class,
      'method'  => $method,
      'route'   => $this->route,
      'payload' => $this->payload,
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
   * Clean up gracefully before ending server operation. This must be called
   * as the last activity before the script ends.
   */
  public function halt() {
    parent::halt();

    // Send out our headers.
    if (isset($this->output['headers'])) {
      if (is_array($this->output['headers'])) {
        foreach ($this->output['headers'] as $header) {
          header($header);
        }
      }
      else {
        throw new Exception('Headers must be array');
      }
    }
    // Send out our output.
    print $this->output['body'];

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
  public function set_route($route) {
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

}

?>