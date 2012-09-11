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

class Admin extends ServerPlugin {

  protected static $name = 'Admin';
  protected static $codes = array(
    '__halt',
    '/',
    'admin/get_message',
  );

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
    return;
  }

  // Main function to process a message.
  public function receive_message(&$code, Server $server) {
    switch($code) {
      case '__halt':
        $this->code_halt();
        break;
      case '/':
        $this->code_root();
        break;
      case 'admin/get_message':
        $this->code_get_message();
        break;
    }

    // Allow plugins to change what we have done.
    parent::receive_message($code, $server);

    if ($this->output) {
      $server->set_output($this->output);
    }
    if (!empty($this->headers)) {
      $server->set_headers($this->headers);
    }

    return;
  }

  // halt.
  public function code_halt() {
    // Parent will persist our variables.
    parent::halt();
    return;
  }

  // Load up the javascript bare bones interface.
  public function code_root() {
    // Load up the interface.
    $client_file = file_get_contents('inc/plugins/admin/files/client.html');
    $this->output = $client_file;

    $this->headers[] = 'Content-Type: text/html';
    $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
    $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

  public function code_get_message() {
    $response = array(
      'code' => 'NAC',
    );
    $this->output = json_encode($response);

    $this->headers[] = 'Content-Type: text/text';
    $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
    $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
    
    return;
  }

}


// Register our plugin.
Server::register_plugin('Admin', Admin::get_codes());

?>