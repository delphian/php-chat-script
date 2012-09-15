<?php

/**
 * @file
 *
 * Observer/Observed patterns for plugins and server.
 *
 * 'Subject' class should be extended by objects wishing to be hooked by others.
 * 'Plugin' class should be extended by objects wishing to hook and be hooked.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */


/**
 * Extend this class if you want others to hook into you.
 *
 * A 'Server' only has others hook into it.
 */
abstract class Subject extends PersistentVariable {

  /** Configuration options. */
  protected $config = NULL;

  /** This variable will be persistant and automatically loaded at 
      instantiation. Massive data storage needs are not intended to be met by
      this object. */
  protected $variables = array();

  /** Keep track of our route handlers. Plugins for plugins. This is declared
      static so we don't have to actually instantiate a subject for the observer
      to attatch itself. */
  protected static $plugins = NULL;
  /** Track which plugins actually got instantiated. */
  protected $plugins_loaded = array();
  /** Weight determines in which order a plugin should be executed for routes
      that have been requestd by multiple plugins. Lower integers execute 
      first. */
  protected $weight = 0;
  /** An array of strings that the plugin wants to process. This is declared
      static so their manipulation and registration will not require an
      instantiated class. */
  protected static $routes = array();

  /** Payload set by the caller before receive_message() is invoked. This is
      where the parameters to the observer are placed. */
  protected $payload = NULL;
  /** Response sent to the calling class at end of execution. When each plugin
      is finished executing it will set the $output property of its calling
      class. */
  protected $output = NULL;

  /**
   * Plugins may register themselves here to get a callback when this class
   * processes a route.
   *
   * @param string $plugin_name
   *   The name of the class to instantiate for callback.
   * @param string $routes
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
   * All parameters for paths should be absolute and not include a trailing 
   * slash.
   *
   * @param array $config
   *   Assocative array of strings containing configuration options.
   *   - path_root: (string) Path to the base directory of script.
   *   - path_inc: (string) Path to the include directory.
   *   - path_plugins: (string) Path to the plugins directory.
   *   - path_data: (string) Path to the data directory.
   *
   * @return mixed [Plugin superclass]
   */
  public function __construct($config) {
    /** @todo throw error if instance already exists? */
    if (!isset($config)) {
      throw new Exception('Config must be specified when instantiating.');
    }
    $this->config = $config;
    parent::__construct($config);

    return $this;  
  }

  /**
   * Inform third party plugin that a message they have registered for has been
   * received.
   *
   * @param string $route
   *   Execute all handlers that have registered for this route.
   *
   * @return bool $report
   *   TRUE if no errors were encountered, FALSE if one or more errors.
   */
  public function invoke_all($route) {
    $report = TRUE;

    $plugins = (!empty(static::$plugins[$route])) ? static::$plugins[$route] : NULL;    

    if (is_array($plugins) && !empty($plugins)) {
      foreach($plugins as $plugin) {
        $class = $plugin::load($this->config);
        $class_name = get_class($class);
        $this->plugins_loaded[$plugin] = $class;
        $class->set_payload($this->payload);
        $class->receive_message($route, $this);
      }
    }

    return $report;
  }

  /**
   * Let all registered plugins know that we are shutting down now.
   */
  public function halt() {
    // Give heads up to plugins that we are terminating operations.
    $this->invoke_all('__pre_halt');

    // Tell all instantiated plugins they must halt NOW.
    foreach($this->plugins_loaded as $plugin) {
      $plugin->halt();
    }

    return;
  }

  /**
   * Get property.
   *
   * @return Subject::$routes
   */
  public static function get_routes() {
    return static::$routes;
  }

  /**
   * Get property.
   *
   * @return Subject::$payload
   */
  public function get_payload() {
    return $this->$payload;
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
  public function get_output() {
    return $this->output;
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

}


/**
 * Extend this class if you want to hook into others and get updates.
 *
 * Plugins both hook into others and get hooked into themselves.
 */
abstract class Plugin extends Subject {
  /**
   * Main function callback to process a message we have registered for.
   *
   * Caller will always call your class' set_payload() to pass in parameters
   * before receive_message() is called. Your receive_message() function should
   * set $caller->output to the results of processing.
   *
   * @param string $route
   *   The url path or code constructued custom message.
   * @param Subject $caller
   *   Class that is forwarding us a message.
   */
  abstract public function receive_message(&$route, $caller);    
}

?>