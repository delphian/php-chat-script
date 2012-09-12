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

class Cli extends ServerPlugin {

  protected static $name = 'Cli';
  protected static $codes = array(
    '/',
    'cli/get_message',
    'cli/set_message',
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
      case '/':
        $this->code_root();
        break;
      case 'cli/set_message':
        $this->code_set_message($server->get_payload());
        break;
      case 'cli/get_message':
        $this->code_get_message();
        break;
    }

    // Allow plugins to change what we have done. Final act of parent will be
    // to set the current headers and output to the calling server.
    parent::receive_message($code, $server);

    return;
  }

  // Load up the javascript bare bones interface.
  public function code_root() {
    // Load up the interface.
    $client_file = file_get_contents('inc/plugins/cli/files/client.html');
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

  /**
   * A command was sent to us from the client.
   */
  public function code_set_message($payload) {
    $input = json_decode($payload, TRUE);

    switch ($input['code']) {
      case 'help':
        $this->subcode_help();
        break;
      case 'say':
        $this->subcode_say();
        break;
    }

    // Let plugins change what we have done.
    $this->invoke_all($input['code']);

    return;
  }

  /**
   * Help
   */
  public function subcode_help() {
    $response = array(
      'code'    => 'output',
      'payload' => 'Everybody wants help...',
    );
    $this->output = json_encode($response);
    $this->headers[] = 'Content-Type: text/text';
    $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
    $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
    return;  
  }

  /**
   * Say
   */
  public function subcode_say() {
    $this->variables['say']++;  
    $response = array(
      'code'    => 'output',
      'payload' => $this->variables['say'],
    );
    $this->output = json_encode($response);
    $this->headers[] = 'Content-Type: text/text';
    $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
    $this->headers[] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
    return;  
  }

}

// Register our plugin.
Server::register_plugin('Cli', Cli::get_codes());

?>