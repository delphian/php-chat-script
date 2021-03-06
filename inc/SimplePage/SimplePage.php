<?php

/**
 * @file
 *
 * Simple html page storage and retrieval. Nothing fancy here.
 *
 * Pages are stored with SimpleTextStorage and are keyed by the page path which
 * must be unique.
 *
 * http://www.phpchatscript.com
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 */

class SimplePage extends Observed {

  /** URL Path to this page. */
  protected $path = NULL;
  /** Contents of the title tag. */
  protected $title = NULL;
  /** An array containing paths to css files. */
  protected $css = array();
  /** An array containing paths to javascript files. */
  protected $javascript = array();
  /** An array containing the contents of the file based on carriage returns. */
  protected $body = array();
  /** The user identification that owns this page. */
  protected $user_id = NULL;
  /** Unix timestamp containing page creation time. */
  protected $time_created = NULL;
  /** Unix timestamp containing the last time page was updated. */
  protected $time_updated = NULL;
  /** Unix timestamp containing the last time page was viewed. */
  protected $time_viewed = NULL;

  /**
   * Determine if a page path exists or not.
   *
   * @param string $path
   *   The unique path of a page.
   *
   * @return string $path|FALSE
   *   Returns the path if found, FALSE if not found.
   */
  public static function exists($path) {
    $path_exists = FALSE;

    $pages = SimpleTextStorage::load()->read('SimplePage', 'paths');
    if (is_array($pages) && array_key_exists($path, $pages)) {
      $path_exists = $path;
    }

    return $path_exists;
  }

  /**
   * Fetch a list of all paths used by pages.
   *
   * @return array
   *   An array of path strings used by pages.
   */
  public static function fetch_paths() {
    $keys = array();
    $pages = SimpleTextStorage::load()->read('SimplePage', 'paths');
    if (is_array($pages)) {
      $keys = array_keys($pages);
    }

    return $keys;
  }

  /**
   * Constructor.
   *
   * @param string $path
   *   If provided then all class properties will be populated with data from
   *   an existing page.
   */
  public function __construct($path = NULL) {
    if ($path) {
      if (!SimplePage::exists($path)) {
        throw new Exception('Tried to instnatiated page that does not exist.');
      }
      $pages = SimpleTextStorage::load()->read('SimplePage', 'paths');
      $this->set_path($pages[$path]['path']);
      $this->set_title($pages[$path]['title']);
      $this->set_css($pages[$path]['css']);
      $this->set_javascript($pages[$path]['javascript']);
      $this->set_body($pages[$path]['body']);
      $this->set_user_id($pages[$path]['user_id']);
      $this->set_time_created($pages[$path]['time_created']);
      $this->set_time_updated($pages[$path]['time_updated']);
      $this->set_time_viewed($pages[$path]['time_viewed']);
      $this->invoke_all('__simplepage/construct/old');
    }
    else {
      $this->invoke_all('__simplepage/construct/new');
    }
  }

  /**
   * Save this page instance to the database.
   */
  public function save() {
    /** Allow plugins to alter our data before saving. */
    $this->invoke_all('__simplepage/save');
    $pages = SimpleTextStorage::load()->read('SimplePage', 'paths');
    $path = $this->path;

    /** If we are saving the record for the first time. */
    if (!isset($pages[$path])) {
      $pages[$path] = array();
    }
    $pages[$path]['path'] = $path;
    $pages[$path]['title'] = $this->title;
    $pages[$path]['css'] = $this->css;
    $pages[$path]['javascript'] = $this->javascript;
    $pages[$path]['body'] = $this->body;
    $pages[$path]['user_id'] = $this->user_id;
    $pages[$path]['time_created'] = $this->time_created;
    $pages[$path]['time_updated'] = $this->time_updated;
    $pages[$path]['time_viewed'] = $this->time_viewed;

    SimpleTextStorage::load()->write('SimplePage', 'paths', $pages);
  }

  /**
   * Format all properties as a single json string.
   *
   * @return string|NULL
   *   A json formated string of class' properties or NULL if unavailable.
   */
  public function make_json() {
    $json_string = NULL;

    $json_string = json_encode(get_class_vars($this));

    return $json_string;
  }

  /**
   * Get property.
   */
  public function get_path() {
    return $this->path;
  }

  /**
   * Get property.
   */
  public function get_title() {
    return $this->title;
  }

  /**
   * Get property.
   */
  public function get_css() {
    return $this->css;
  }

  /**
   * Get property.
   */
  public function get_javascript() {
    return $this->javascript;
  }

  /**
   * Get property.
   */
  public function get_body() {
    return $this->body;
  }

  /**
   * Get property.
   */
  public function get_user_id() {
    return $this->user_id;
  }

  /**
   * Get property.
   */
  public function get_time_created() {
    return $this->time_created;
  }

  /**
   * Get property.
   */
  public function get_time_updated() {
    return $this->time_updated;
  }

  /**
   * Get property.
   */
  public function get_time_viewed() {
    return $this->time_viewed;
  }

  /**
   * Set property.
   */
  public function set_path($path) {
    $this->path = $path;
  }

  /**
   * Set property.
   */
  public function set_title($title) {
    $this->title = $title;
  }

  /**
   * Set property.
   */
  public function set_css($css) {
    $this->css = $css;
  }

  /**
   * Set property.
   */
  public function set_javascript($javascript) {
    $this->javascript = $javascript;
  }

  /**
   * Set property.
   */
  public function set_body($body) {
    $this->body = $body;
  }

  /**
   * Set property.
   */
  public function set_user_id($user_id) {
    $this->user_id = $user_id;
  }

  /**
   * Set property.
   */
  public function set_time_created($time_created) {
    $this->time_created = $time_created;
  }

  /**
   * Set property.
   */
  public function set_time_updated($time_updated) {
    $this->time_updated = $time_updated;
  }

  /**
   * Set property.
   */
  public function set_time_viewed($time_viewed) {
    $this->time_viewed = $time_viewed;
  }

}

?>