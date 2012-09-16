<?php

/**
 * @file
 *
 * Observer/Observed patterns. Allow other classes to hook into us.
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
abstract class Observed {

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
      static::$plugins[$route][] = $plugin_name;
    }

    return $report;
  }

  /**
   * Inform third party plugin that a message they have registered for has been
   * received. After class is instantied it's set_payload() will be called
   * and then set_user(). Only after these variables of the class have been
   * populated will we call receive_message().
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
        $class = $plugin::load();
        $this->plugins_loaded[$plugin] = $class;
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

}

?>