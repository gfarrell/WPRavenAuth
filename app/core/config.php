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
     * $key
     * The database key to use for the configuration array.
     * 
     * @var    string
     * @access private
     */
    private $key = 'WPRavenAuthOptions';

    /**
     * $cfg
     * The configuration array.
     * 
     * @var    array
     * @access private
     */
    private $cfg = array(
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
     * @access private
     */
    private $bootstrapped = false;

    /**
     * getInstance
     * Retrieves the Singleton instance.
     * 
     * @static
     * @access public
     * @return Config config instance
     */
    public static function &getInstance() {
        static $instance;

        if(is_null($instance)) {
            $instance = new Config();
        }

        return $instance;
    }

    /**
     * Constructor
     * Bootstraps the configuration of the plugin by creating options.
     * 
     * @access private
     * @return void
     */
    private function __construct() {
        if(!$this->bootstrapped) {
            // fetch from DB, if non-existent, then create
            $db = get_option($this->key);
            if(!$db) {
                $this->install();
            } else {
                // initialise config, merging with the defaults
                $this->cfg = Set::merge($this->cfg, $db);
            }

            $this->bootstrapped = true;
        }
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
        $_this =& Config::getInstance();

        if(is_null($what)) {
            return $_this->cfg;
        }

        if(is_array($what)) {
            return Set::select($_this->cfg, $what);
        } else {
            return Set::extract($_this->cfg, $what);
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
        $_this =& Config::getInstance();

        Set::set($_this->cfg, $value);

        $_this->update();
    }

    /**
     * install
     * Installs the options to the database.
     * 
     * @access private
     * @return void
     */
    private function install() {
        add_option($this->key, $this->cfg);
    }

    /**
     * update
     * Updates the database with the new options.
     * 
     * @access private
     * @return void
     */
    private function update() {
        $_this =& Config::getInstance();
        update_option($_this->key, $_this->cfg);
    }
}
?>