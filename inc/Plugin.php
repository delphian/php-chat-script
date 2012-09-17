<?php

/**
 * @file
 *
 * Plugins hook into other classes and may be hooked themselves.
 *
 * Plugins provide a service to a remote client by processing payloads forwarded
 * to the plugin through a predefiend rotue.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */

/**
 * Extend this class if you want to hook into others and get updates.
 *
 * Plugins both hook into others and get hooked into themselves.
 */
abstract class Plugin extends Observed {

  /** Keep track of our singletons based on their class names. */
  protected static $singletons = array();

  /** This variable will be persistant and automatically loaded at 
      instantiation. Massive data storage needs are not intended to be met by
      this object. */
  protected $variables = array();

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
 
  /**
   * Constructor. We will force the use of SingletonLoader::load() even if
   * The requesting code tries to instantiate a new class by hand.
   */
  public static function load() {
    /** Retrieve the name of the concrete class being instantiated. */
    $class = get_called_class();
    /** If a singleton does not exist then create it. */
    if (array_key_exists($class, self::$singletons) === FALSE) {
      /** Only load variables if we are actually instantiating. */
      $plugin = new $class();
      $plugin->variables = SimpleTextStorage::load()->read($class, 'variables');
      $plugin->variables['test'] = 'one';
      self::$singletons[$class] = $plugin;
    }

    return self::$singletons[$class];
  }

  /**
   * Main function callback to process a message we have registered for.
   *
   * Caller will always call your class' set_payload() to pass in parameters
   * before receive_message() is called. Your receive_message() function should
   * set $caller->output to the results of processing.
   *
   * @param string $route
   *   The url path or code constructued custom message.
   * @param Subject $observed
   *   Class that is forwarding us a message.
   */
  abstract public function receive_message(&$route, $observed);

  /**
   * Save any changes to our variables.
   */
  public function __destruct() {
    // Persist our variables.
    $class = get_called_class();
    SimpleTextStorage::load()->write($class, 'variables', $this->variables);

    return;
  }

  /**
   * Get property.
   */
  public function get_variables() {
    $class = get_called_class();
    return static::$singletons[$class]->variables;
  }

  /**
   * Get property.
   */
  public function get_weight() {
    return $this->weight;
  }

  /**
   * Get property.
   *
   * @return Subject::$payload
   */
  public function get_payload() {
    return $this->payload;
  }

  /**
   * Get property.
   */
  public function get_output() {
    return $this->output;
  }

  /**
   * Get property.
   */
  public function get_user() {
    return $this->user;
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

    if (is_object($user) && (get_class($user) == 'SimpleUser')) {
      $this->user = $user;
      $report = $this->user;
    }

    return $report;
  }
 
}

?>