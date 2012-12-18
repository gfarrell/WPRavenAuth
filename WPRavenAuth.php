<?php
/*
    WPRavenAuth - Raven authentication for Wordpress
    ================================================

    @license BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
    @author  Gideon Farrell <me@gideonfarrell.co.uk>
    @url     https://github.com/gfarrell/WPRavenAuth
 */

// Some quickly bootstrapped definitions
if(!defined('DS')) {
    define('DS', '/');
}
define('WPRavenAuth_dir', dirname(__file__));
define('WPRavenAuth_keys', WPRavenAuth_dir . DS . 'keys');

// Load required files
require('app/config.php');  // Configuration wrapper
require('app/ldap.php');    // LDAP lookups for users
require('app/raven.php');   // Login/out/etc. library
?>