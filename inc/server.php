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

  /** Routes are url paths. A route determines where a payload will end up.
      This is the currentl url path that was requested of us. */
  private $route = NULL;

  /**
   * Constructor. Load all our plugins from the plugin directory.
   */
  public function __construct($config = NULL) {
    parent::__construct($config);
  }

  /**
   * Retrieve the route and payload from the submission.
   *
   * Hooks will be invoked to allow plugins to alter the route, alter the
   * payload, and alter the logged in user (if any).
   *
   * @return $this->route
   *   The current route, or NULL on failure.
   */
  public function receive_request() {
    /** Get the route from the url. The route is the url. */
    $route = '/';
    if (isset($_REQUEST['route'])) {
      $route = $_REQUEST['route'];  
    }
    /** Plugins may alter the route. */
    $this->invoke_all('__route');

    /** Grab our payload (parameters to the route handler). */
    if ($this->set_route($route)) {
      if (isset($_GET['payload'])) {
        if (!$this->set_payload($_GET['payload'])) {
          if (isset($_POST['payload'])) {
            $this->set_payload($_POST['payload']);
          }
        }
      }
    }
    /** Plugins may alter the payload. */
    $this->invoke_all('__payload');

    /** Its up to the plugins to set the user property. This property is
        used to determine if a user is logged into the server and will contain
        information about the user. */
    $this->invoke_all('__user');

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

    /** Call all plugin handlers. */
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
/*
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
*/
    return TRUE;
  }

  /**
   * Clean up gracefully before ending server operation. This must be called
   * as the last activity before the script ends.
   */
  public function halt() {
    parent::halt();

    /** Send out our headers. */
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
    /** Send out our output. */
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

    /** @todo make sure this code has a handler? */
    if (is_string($route)) {
      $this->route = $route;
      $report = TRUE;
    }

    return $report;
  }

}

?>