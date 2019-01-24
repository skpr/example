<?php

/**
 * @file
 * Contains Skipper helper functions.
 */

/**
 * Gets skipper config.
 *
 * @param string $key
 *   The config key.
 *
 * @return bool|string
 *   The config value, or FALSE if not found.
 */
function skpr_config($key) {
  static $confs;

  if (empty($confs)) {
    $confs = [];

    // Check default config, followed by overridden config, then the same
    // from secrets.
    $dirs = [
      '/etc/skpr/config/default',
      '/etc/skpr/config/override',
      '/etc/skpr/secret/default',
      '/etc/skpr/secret/override',
    ];
    foreach ($dirs as $dir) {
      // Here is an adventure into how PHP caches stat data on the filesystem.
      // Kubernetes ConfigMaps structure mounted configuration as follows:
      //   /etc/skpr/var.foo -> /etc/skpr/..data/var.foo -> /etc/skpr/..4984_21_04_13_51_28.237024315/var.foo
      // The issue is here is when values are updated there is a short TTL of time where PHP will
      // keep looking at a non existant timestamped directory.
      // After looking into opcache and apc it turns out core php has a cache for this as well.
      // These lines ensure that our Skipper configuration is always fresh and readily available for
      // the remaing config lookups by the application.
      foreach (realpath_cache_get() as $path => $cache) {
        if (strpos($path, $dir) === 0) {
          clearstatcache(TRUE, $path);
        }
      }

      foreach (glob($dir . '/*') as $file) {
        $confs[basename($file)] = str_replace("\n", '', file_get_contents(realpath($file)));
      }
    }
  }

  return !empty($confs[$key]) ? $confs[$key] : FALSE;
}