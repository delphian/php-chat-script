<?php

/**
 * http://www.phpchatscript.com
 * 
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

/**
 * This should be used as an example to build custom server side plugins.
 * It is actually used so don't delete it unless you know what your doing.
 */

class PHPChatScriptGithub extends PHPChatScriptPluginBase {

  public $name = 'Github';
  public $weight = 5;
  
  // $this->variables exists as a persistant array for each plugin to store
  // information in.

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();

    return;
  }

  public function boot() {
    parent::boot();

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
  public function format_request($request, &$server_input) {

    return;
  }

  public function halt() {
    parent::halt();

    return;
  }
}

// Register our plugin class.
PHPChatScriptPluginBase::register('PHPChatScriptGithub');

?>