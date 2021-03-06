<?php

/**
 * @file
 * Provides route handlers for basic SimplePage manipulations.
 *
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

class SimplePagePlugin extends Plugin {

  // Main function to process a message.
  public function receive_message(&$route, $observed) {
    $this->payload = $observed->get_payload();
    $this->output = $observed->get_output();
    $this->user = $observed->get_user();

    $user_id = 0;
    if ($this->user) {
      $user_id = $this->user->get_user_id();
    }

    /** Setup parameters. */
    $variables = array(
      'caller' => $observed,
      'user_id' => $user_id,
    );

    /** We will only process a route if we have a valid user. */
    if ($user_id) {
      switch($route) {
        case 'simplepage/page/get':
          $this->route_simplepage_page_get($observed);
          break;
        case 'simplepage/page/new':
          $this->route_simplepage_page_new($observed);
          break;
        case 'simplepage/page/list':
          $this->route_simplepage_page_list($observed);
          break;
        case 'simplepage/help':
          $this->route_simplepage_help($observed);
          break;
        case '__cli/command/help':
          $this->cli_command_help($observed);
          break;
      }
    }
    else {
      /** Do nothing for the moment. */
      switch($route) {
        case '__cli/javascript':
          $this->cli_javascript($observed);
          break;
      }
    }

    // Overwrite callers output with ours.
    if ($this->output) {
      $observed->set_output($this->output);
    }

    return;
  }

  /**
   * List all active routes used by pages.
   */
  public function route_simplepage_page_list($observed) {
    $list = SimplePage::fetch_paths();
    $response = array(
      'code' => 'simplepage',
      'payload' => array(
        'code' => 'page_list',
        'payload' => $list,
      ),
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();
  }

  /**
   * Describe our command line help.
   */
  public function route_simplepage_help($observed) {
    $help_text  = '<b>/simplepage help</b> This command.<br />';
    $help_text .= '<b>/simplepage new <i>{path}</i></b> Create a new page.<br />';
    $help_text .= '<b>/simplepage list</b> List all existing pages by path.<br />';
    $response = array(
      'code' => 'simplepage',
      'payload' => array(
        'code' => 'help',
        'payload' => $help_text,
      ),
    );
    $this->output['body'] = json_encode($response);
    $this->headers_text();
  }

  /**
   * Return page information to requester.
   */
  public function route_simplepage_page_get($observed) {
    $path = $observed->payload['simplepage']['path'];
    if (SimplePage::exists($path)) {
      $page = new SimplePage($path);
      $page_array = $page->make_array();
      $response = array(
        'code' => 'simplepage',
        'payload' => array(
          'code' => 'page_get',
          'payload' => get_class_vars($page),
        ),
      );
      $this->output['body'] = json_encode($response);
      $this->headers_text();
    }

    return;
  }

  /**
   * Create a new page, set the title, and save to database.
   */
  public function route_simplepage_page_new($observed) {
    $payload = $observed->get_payload();
    $payload = json_decode($payload, TRUE);
    $path = $payload['payload']['simplepage']['path'];
    $path = preg_replace('/(^a-zA-Z_-\.\/)/', $path);
    if (strlen($path) && !SimplePage::exists($path)) {
      $page = new SimplePage($path);
      $page->set_path($path);
      $page->save();
      $response = array(
        'code'    => 'simplepage',
        'payload' => array(
          'code' => 'page_new',
          'payload' => $path,
        ),
      );
      $this->output['body'] = json_encode($response);
      $this->headers_text();
    }

    return;
  }


  /** Add our javascript files to the command line interface. */
  public function cli_javascript($observed) {
    $javascript = $observed->get_javascript();
    $javascript[] = 'inc/SimplePage/files/SimplePageCli.js';
    $observed->set_javascript($javascript);
  }

  /**
   * Add our commands to the CLI help text.
   */
  public function cli_command_help($variables) {
    $output = json_decode($this->output['body'], TRUE);
    $output['payload'] .= '<b>/simplepage</b> Perform page related functions.<br />';

    $response = array(
      'code' => 'output',
      'payload' => $output['payload'],
    );
    $this->output['body'] = json_encode($response);
  }

  /**
   * Generate straight text output.
   */
  public function headers_text() {
    $this->output['headers'] = NULL;
    $this->output['headers'][] = 'Content-Type: text/text';
    $this->output['headers'][] = 'Cache-Control: no-cache, must-revalidate';
    $this->output['headers'][] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';

    return;
  }

}

// Register our plugin for callback.
Server::register_plugin('SimplePagePlugin', array(
  'simplepage/page/get',
  'simplepage/page/new',
  'simplepage/page/list',
  'simplepage/help',
));
Cli::register_plugin('SimplePagePlugin', array(
  '__cli/javascript',
  '__cli/command/help',
));

?>