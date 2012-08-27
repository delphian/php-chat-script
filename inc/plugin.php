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
    
  // Weight will define the order in which our plugins are executed. A plugin
  // with the highest weight (number with the highest value) will execute
  // last and have the last opportunity to change information and process
  // data. The default weight is 0. Increase the weight to a positive number
  // relative to other plugins to execute after they do. Decrease the weight
  // to a negative number relative to other plugins to execute before they do.
  private $weight  = 0;
  private $classes = array();

  /**
   * Constructor.
   */
  public function __construct($classes = array()) {
    $this->classes = $classes;

    return;
  }

  /**
   * Plugins opportunity to do stuff before any main server code has been 
   * executed.
   */
  public function boot() {
    // Execute this method for all other plugins.
    if (!empty($this->classes)) {
      foreach($this->classes as $class) {
        if (method_exists($class, 'boot')) {
          $class->boot();
        }
      }
    }
    return;
  }

  /**
   * Create the initial message that the server will process. If the plugin
   * has nothing to offer for this web server request then don't make any
   * changes!
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
  public function message_from_request($request, &$server_input) {
    // Execute this method for all other plugins.
    if (!empty($this->classes)) {
      foreach($this->classes as $class) {
        if (method_exists($class, 'message_from_request')) {
          $class->message_from_request($request, $server_input);
        }
      }
    }
    return;
  }

  /**
   * The main server code is finished operating. This allows the plugins
   * to clean up anything they have been working on.
   */
  public function halt() {
    // Execute this method for all other plugins.
    if (!empty($this->classes)) {
      foreach($this->classes as $class) {
        if (method_exists($class, 'halt')) {
          $class->halt();
        }
      }
    }
    return;
  }
}

/* *********************************************************************** */

/**
 * Include all files in the plugins directory if they are php files. These
 * plugins will extend the above class and then register themselves with
 * the global variable.
 */
if ($handle = opendir($php_chat_script['path_plugins'])) {
  /* This is the correct way to loop over the directory. */
  while (false !== ($entry = readdir($handle))) {
    if (preg_match('/.*\.php$/', $entry, $matches)) {
      include_once($php_chat_script['path_plugins'] . $entry);
    }
  }
  
  closedir($handle);
}


?>