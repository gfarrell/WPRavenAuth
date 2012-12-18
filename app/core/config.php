<?php
/*
    Config
    ------

    Basic configuration wrapper class to get/set options from WP for this plugin.

    @file    config.php
    @license BSD 3-Clause
    @package WPRavenAuth
    @author  Gideon Farrell <me@gideonfarrell.co.uk>
 */

namespace WPRavenAuth;

class Config {
    /**
     * $_prefix
     * The prefix to use for all options to avoid namespace collisions.
     * @static
     * @access private
     */
    private static $_prefix = 'wpravenauth_';

    /**
     * prefix
     * Prefixes a string with the option prefix.
     * 
     * @static
     * @access public
     * @param  string $what the string to prefix
     * @return string       a prefixed string
     */
    public static prefix($what = null) {
        return Config::_prefix . ((string)$what);
    }

    /**
     * bootstrap
     * Bootstraps the configuration of the plugin by creating options.
     * 
     * @static
     * @access public
     * @return void
     */
    public static function bootstrap() {
        // LDAP options
        Config::__create('ldap', array(
            'server' => 'ldap.lookup.cam.ac.uk',
            'base'   => 'ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk',
            'port'   => '636'
        ));

        // ...
    }

    /**
     * get
     * Retrieves a configuration value. Can process dotted (option.suboption) options.
     * 
     * @static
     * @access public
     * @param  string $what the option name
     * @return mixed        the option value
     */
    public function get($what) {
        if(strpos($what, '.')) {
            $names = explode('.', $what);
            $result = get_option(Config::prefix(array_shift($names));
            if(is_array($result)) {
                $result = Config::__getNested($result, $names);
            } else {
                $result = null;
            }
        } else {
            $result = get_option(Config::prefix($what));
        }

        return $result;
    }

    /**
     * set
     * Sets a configuration value.
     * 
     * @static
     * @access public
     * @param  string $what  the option name
     * @param  mixed  $value the option value
     * @return void
     */
    public function set($what, $value) {
        if(strpos($what, '.')) {
            $names = explode('.', $what);
            $key = array_shift($names);
            $result = get_option(Config::prefix($key);
            if(is_array($result)) {
                Config::__setNested($result, $names, $value);
                update_option(Config::prefix($key), $result);
            } else {
                update_option($what, $value);
            }
        } else {
            update_option(Config::prefix($what), $value);
        }
    }

    /**
     * __create
     * Creates a configuration option in the database.
     * 
     * @static
     * @access private
     * @param  string  $option   the option name
     * @param  mixed   $value    the initial value to use
     * @param  string  $autoload whether or not to automatically load this option ('yes' or 'no')
     * @return void
     */
    private static function __create($option, $value = null, $autoload = 'yes') {
        if(!in_array($autoload, array('yes','no'))) {
            $autoload = 'no';
        }
        add_option(Config::prefix($option), $value, null, $autoload);
    }

    /**
     * __getNested
     * Parses a (possibly) nested options array.
     * 
     * @static
     * @access private
     * @param  array   $arr  the array to parse
     * @param  array   $keys the keys to walk over
     * @return mixed         value of keys if found, null if not found
     */
    private static function __getNested($arr, $keys) {
        $current = array_shift($keys);
        if(array_key_exists($current, $arr)) {
            return Config::__getNested($arr[$current], $keys);
        } else {
            return null;
        }
    }

    /**
     * __setNested
     * Sets a value in a (possibly) nested options array.
     * 
     * @static
     * @access private
     * @param  array   $arr   the array to parse
     * @param  array   $keys  the keys to walk over
     * @param  mixed   $value the value to set to
     * @return void
     */
    private static function __setNested(&$arr, $keys, $value) {
        $pointer &= $arr;

        while(count($keys) > 1) {
            $key = array_shift($keys);
            if(!array_key_exists($key, $pointer)) {
                $pointer[$key] = array();
            }
            $pointer &= $pointer[$key];
        }

        $pointer[array_shift($keys)] = $value;
    }
}
?>