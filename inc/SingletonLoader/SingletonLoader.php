<?php

/**
 * @file
 *
 * Operate as a singleton class with an easy loader.
 *
 * Copyright (c) 2012 "delphian" Bryan Hazelbaker
 * Licensed under the MIT license.
 * http://www.phpchatscript.com
 */

/**
 * Extend this class if you want others to be a singleton.
 */
abstract class SingletonLoader {

  /** Keep track of our singletons based on their class names. */
  protected static $singletons = array();

  /**
   * Load up our class as a singleton. On the first time this function is called
   * assign the new class instantiated to our own static variable. Any other
   * time this function is called simply return the static variable if it
   * has a value. Always use this function to instantiate the class.
   *
   * @param mixed $config
   *   Optional configuration variable to pass to superclass constructor.
   *
   * @return Server self::$singleton
   */
  public static function load($config = NULL) {
    $class = get_called_class();
    if (array_key_exists($class, self::$singletons) === FALSE) {
      self::$singletons[$class] = new $class($config);
    }

    return self::$singletons[$class];
  }
}

?>