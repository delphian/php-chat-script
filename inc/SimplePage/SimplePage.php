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

    $paths = SimpleTextStorage::load()->read('SimplePage', 'paths');
    if (array_key_exists($path, $paths)) {
      $path_exists = $path;
    }

    return $path_exists;
  }

  /**
   * Save this page instance to the database.
   */
  public function save() {
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

    SimpleTextStorage::load()->write('SimplePage', 'paths', $paths);
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

}

?>