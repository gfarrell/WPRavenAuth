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

require('set.php');

class Config {
    /**
     * $key
     * The database key to use for the configuration array.
     * 
     * @var    string
     * @static
     * @access private
     */
    private static $key = 'WPRavenAuthOptions';

    /**
     * $cfg
     * The configuration array.
     * 
     * @var    array
     * @static
     * @access private
     */
    private static $cfg = array(
        // default options
        'ldap'   => array(
            'server' => 'ldap.lookup.cam.ac.uk',
            'base'   => 'ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk',
            'port'   => '636'
        ),
        'cookie' => 'WPRavenAuth'
    );

    /**
     * $bootstrapped
     * Whether or not the class has been bootstrapped.
     * 
     * @var    boolean
     * @static
     * @access private
     */
    private static $bootstrapped = false;

    /**
     * bootstrap
     * Bootstraps the configuration of the plugin by creating options.
     * 
     * @static
     * @access public
     * @return void
     */
    public static function bootstrap() {
        if(Config::bootstrapped) return;

        // fetch from DB, if non-existent, then create
        $db = get_option(Config::key);
        if(!$db) {
            Config::install();
        } else {
            // initialise config, merging with the defaults
            Config::cfg = Set::merge(Config::cfg, $db);
        }

        Config::bootstrapped = true;
    }

    /**
     * get
     * Retrieves a configuration value. Can process dotted (option.suboption) options.
     * 
     * @static
     * @access public
     * @param  string|array $what the option name(s)
     * @return mixed              the option value
     */
    public function get($what = null) {
        if(!Config::bootstrapped) Config::bootstrap();

        if(is_null($what)) {
            return Config::cfg;
        }

        if(is_array($what)) {
            return Set::select(Config::cfg, $what);
        } else {
            return Set::extract(Config::cfg, $what);
        }
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
        if(!Config::bootstrapped) Config::bootstrap();

        Set::set(Config::cfg, $value);

        Config::update();
    }

    /**
     * install
     * Installs the options to the database.
     * 
     * @static
     * @access private
     * @return void
     */
    private static function install() {
        add_option(Config::key, Config::cfg);
    }

    /**
     * update
     * Updates the database with the new options.
     * 
     * @static
     * @access private
     * @return void
     */
    private static function update() {
        update_option(Config::key, Config::cfg);
    }
}
?>