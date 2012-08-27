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

class PHPChatScriptDefault extends PHPChatScriptPluginBase {

  public $weight = 5;

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();

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
    $raw_time     = $request['time'];
    $raw_code     = $request['code'];
    $raw_from     = $request['from'];
    $raw_message  = $request['message'];

    $code    = preg_replace("[^A-Za-z0-9]", '', $raw_code);
    $from    = preg_replace("[^A-Za-z0-9]", '', $raw_from);
    $time    = time();
    $message = $raw_message;

    $message = str_replace('%25', '%', $message);
    $message = str_replace('%3b', ';', $message);
    $message = explode(';', $message);

    $server_input['time'] = $time;
    $server_input['code'] = $code;
    $server_input['from'] = $from;
    $server_input['message'] = $message;

    return;
  }

  public function halt() {
    return;
  }
}


$php_chat_script['plugins'][] = new PHPChatScriptDefault();

?>