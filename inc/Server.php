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

class Server extends Observed {

  /** Keep track of our singletons based on their class names. */
  protected static $singletons = array();

  /** Routes are url paths. A route determines where a payload will end up.
      This is the currentl url path that was requested of us. */
  private $route = NULL;
  
  /** Arguments are the individual elmeents of the url route deliniated by
      forward slashes. */
  private $args = array();

  /** Payload set by the caller before receive_message() is invoked. This is
      where the parameters to the observer are placed. */
  protected $payload = NULL;
  /** User will be set by the caller before receive_message() is invoked. This
      contains the authenticated user which is accessing the server. */
  protected $user = NULL;
  /** Response sent to the calling class at end of execution. When each plugin
      is finished executing it will set the $output property of its calling
      class. */
  protected $output = NULL;
  /** Response sent to the client at end of execution. This array will be json
      encoded before being output. If this value is not empty then the above
      $output variable will be ignored in favore of $json_output. */
  protected $json_output = NULL;

  /**
   * Constructor. We will force the use of SingletonLoader::load() even if
   * The requesting code tries to instantiate a new class by hand.
   */
  public function __construct() {
    /** Retrieve the name of the concrete class being instantiated. */
    $class = get_called_class();
    /** If a singleton does not exist then create it. */
    if (array_key_exists($class, self::$singletons) === FALSE) {
      self::$singletons[$class] = $this;
    }

    return self::$singletons[$class];
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
    $this->set_route($route);
    /** Plugins may alter the route. */
    $this->invoke_all('__route');
    /** Setup our url path arguments based on the route. */
    $this->set_args();

    /** Grab our payload (parameters to the route handler). */
    if (isset($_GET['payload'])) {
      if (!$this->set_payload($_GET['payload'])) {
        if (isset($_POST['payload'])) {
          $this->set_payload($_POST['payload']);
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
    if (!empty($this->json_output)) {
      print json_encode($this->json_output);
    }
    else {
      print $this->output['body'];
    }

    return;
  }

  /**
   * Get payload property.
   *
   * Often the payload is a json encoded string containing an associative array.
   * If parameters are passed into function they will be treated as keys into
   * this array, the value of which will be returned instead of the entire
   * payload. If more than one parameter is passed they will all be interpreted
   * as nested keys, subsequent parameters being nested keys of the previous.
   *
   * Example to retrieve $payload['user']['id']:
   * @code
   *   $server->get_payload('user', 'id');
   * @endcode
   */
  public function get_payload() {
    $payload = $this->payload;
    if ($num = func_num_args()) {
      $x = 0;
      $payload = json_decode($payload, TRUE);
      while ($x < $num) {
        $arg = func_get_arg($x);
        if (is_array($payload)) {
          $payload = (array_key_exists($arg, $payload)) ? $payload[$arg] : NULL;
        }
        else {
          $payload = NULL;
        }
        $x++;
      }
    }
    return $payload;
  }

  /** Get property. */
  public function get_output() {
    return $this->output;
  }

  /** Get property. */
  public function get_user() {
    return $this->user;
  }

  /** Get property. */
  public function get_args() {
    return $this->args;
  }

  /** Get property. */
  public function get_route() {
    return $this->route;
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

    if (TRUE) {
      $this->output = $output;
      $report = TRUE;
    }

    return $report;
  }

  /**
   * Set our payload.
   *
   * This is set by the calling class before Plugin::receive_message() is 
   * invoked.
   *
   * @param mixed $payload
   *   Idealy this is what was passed in by the external application.
   *
   * @return bool $report
   *   TRUE if payload set, FALSE if the payload was rejected as invalid.
   */
  public function set_payload($payload) {
    $report = FALSE;

    if (TRUE) {
      $this->payload = $payload;
      $report = TRUE;
    }

    return $report;
  }

  /**
   * Set our user.
   *
   * @param SimpleUser $user
   *   A SimpleUser instantiated class.
   *
   * @return
   *   the SimpleUser instantiated class will be returnd on success, NULL
   *   otherwise.
   */
  public function set_user($user) {
    $report = NULL;

    if (is_object($user) && (get_class($user) == 'User')) {
      $this->user = $user;
      $report = $this->user;
    }

    return $report;
  }

  /**
   * Parse the route into individual elements.
   */
  public function set_args() {
    $this->args = explode('/', $this->route);
  }

  /**
   * Append another message into the json array.
   *
   * @param string $class
   *   The name of the class that is appending output.
   * @param array $message
   *   The structure to append as a message.
   *
   * @return
   *   (bool) TRUE. Always returns TRUE. An exception will be thrown on failure.
   */
  public function add_json_output($class, $message) {
    if (!class_exists($class)) {
      throw new Exception('Specified class does not exist');
    }
    if (empty($message)) {
      throw new Exception('Empty message is not allowed.');
    }
    $this->json_output[$class][] = $message;

    return TRUE;
  }

}

?>