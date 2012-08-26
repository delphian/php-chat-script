<?php

/*
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * Main class that plugins should extend.
 */
abstract class PHPChatScriptPlugin {
    
  // Weight will define the order in which our plugins are executed. A plugin
  // with the highest weight (number with the highest value) will execute
  // last and have the last opportunity to change information and process
  // data. The default weight is 0. Increase the weight to a positive number
  // relative to other plugins to execute after they do. Decrease the weight
  // to a negative number relative to other plugins to execute before they do.
  private $weight = 0;

  /**
   * Constructor.
   */
  public function __construct() {
    // @todo register this class so we can call its functions.
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
  abstract public function message_from_request($request, &$server_input);

}

?>