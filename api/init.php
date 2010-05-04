<?php

class api_init {
    private static $initialized = false;

    public static function start() {
        if (self::$initialized) {
            return;
        }
        
        /* project root
           ********************/
        define( 'PROJECT_DIR', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
        define( 'API_DIR', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
        define( 'APP_DIR', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'app');
        define( 'VENDOR_DIR', PROJECT_DIR.'vendor');

        $root = dirname(dirname(__FILE__));
        set_include_path( 
            API_DIR . PATH_SEPARATOR .
            APP_DIR . PATH_SEPARATOR .
            VENDOR_DIR . PATH_SEPARATOR .
            get_include_path()
        );
        include 'autoload.php';

        // Start sessions
        $sessions = api_session::getInstance();
        
        // Construct URL for Web home (root of current host)
        $hostname = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        $hostinfo = self::getHostConfig($hostname);
        $schema = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https' : 'http';
        $reqHostPath = '/';
        if ($hostname != '') {
            $reqHostPath = $schema.'://'.$hostname;
            if (is_null($hostinfo)) {
                $reqHostPath .= '/';
            } else {
                $reqHostPath .= $hostinfo['path'];
            }
        }
        define('API_HOST', $schema.'://' . $hostname . '/');
        define('API_WEBROOT', $reqHostPath);
        define('API_MOUNTPATH', $hostinfo['path']);


        require_once(PROJECT_DIR."config/commandmap.php");

        if (!function_exists('e')) {
            /**
            * This function is dynamically redefinable.
            * @see $GLOBALS['_global_function_callback_e']
            */
            function e($args) {
                $args = func_get_args();
                return call_user_func_array($GLOBALS['_global_function_callback_e'], $args);
            }
            if (!isset($GLOBALS['_global_function_callback_e'])) {
                $GLOBALS['_global_function_callback_e'] = NULL;
            }
        }
        if (!function_exists('__')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback___']
   */
  function __($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback___'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback___'])) {
    $GLOBALS['_global_function_callback___'] = NULL;
  }
}

if (!function_exists('t')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback_t']
   */
  function t($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback_t'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback_t'])) {
    $GLOBALS['_global_function_callback_t'] = NULL;
  }
}

if (!function_exists('url')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback_url']
   */
  function url($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback_url'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback_url'])) {
    $GLOBALS['_global_function_callback_url'] = NULL;
  }
}


        self::$initialized = true;
    }

    /**
     * Use the given host name to find it's corresponding configuration
     * in the configuration file.
     *
     * If the host is not found in the configuration, null is returned.
     *
     * Returns an associative array with the following keys:
     * @retval host string: The host name to be used for lookups in the
     *         commandmap. This is one of the following values from the
     *         configuration in that order: `host', `sld', hash key.
     * @retval sld string: Subdomain as specified in the config using `sld'.
     * @retval tld string: Topdomain as specified in the config using `tld'.
     *         If tld is not specified but the sld is, then the tld is
     *         extracted from the hostname automatically.
     * @retval path string: Path as specified in the config. Can be used to
     *         "mount" the application at the specified point. Stored in
     *         the global constants API_MOUNTPATH.
     *
     * @config <b>hosts</b> (hash): Contains all host configurations. The
     *         hash keys specify the host name.
     * @config <b>host-><em>hostname</em>->host</b> (string):
     *         Overwrite the host name from the key.
     * @config <b>host-><em>hostname</em>->sld</b> (string):
     *         Specify a sublevel domain for this host. This value can be
     *         accessed using api_request::getSld().
     * @config <b>host-><em>hostname</em>->tld</b> (string):
     *         Specify a top-level domain for this host. This value can be
     *         accessed using api_request::getTld(). If sld is specified but
     *         the value isn't, then the tld is computed automatically.
     * @config <b>host-><em>hostname</em>->path</b> (string):
     *         Path where this application is mounted on. This has
     *         implications for the routing engine (see api_routing). Defaults
     *         to "/".
     * @param $hostname: Host name to return config for.
     */
    public static function getHostConfig($hostname) {
        $hosts = array();
        $host = null;

        // Read config
        $cfg = api_config::getInstance();
        if ($cfg->hosts) {
            $hosts = $cfg->hosts;
            foreach($hosts as $key => &$hostconfig) {
                $lookupName = $key;
                if (isset($hostconfig['host'])) {
                    $lookupName = $hostconfig['host'];
                } else if (isset($hostconfig['sld'])) {
                    $lookupName = $hostconfig['sld'];
                }
                $hostconfig['host'] = $lookupName;

                if ($key == $hostname) {
                    $host = $hostconfig;
                    break;
                } else if (api_helpers_string::matchWildcard($key, $hostname)) {
                    $host = $hostconfig;
                    if ($lookupName == $key) {
                        // Replace host with current hostname
                        $host['host'] = $hostname;
                    }
                    break;
                }
            }
        }

        // Host not found
        if (is_null($host)) {
            return null;
        }

        // Calculate tld from hostname if sld is set.
        if (isset($host['sld']) && !isset($host['tld'])) {
            if (strpos($hostname, $host['sld'] . '.') === 0) {
                // Hostname starts with sld
                $host['tld'] = substr($hostname, strlen($host['sld'])+1);
            }
        }

        // Return values
        $path = (!empty($host['path'])) ? $host['path'] : '/';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return array('host' => $host['host'],
                     'tld'  => @$host['tld'],
                     'sld'  => @$host['sld'],
                     'path' => $path);
    }

}
