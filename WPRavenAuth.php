<?php
/*
    WPRavenAuth - Raven authentication for Wordpress
    ================================================

    @license BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
    @author  Gideon Farrell <me@gideonfarrell.co.uk>, Conor Burgess <Burgess.Conor@gmail.com>
    @url     https://github.com/gfarrell/WPRavenAuth
 
    Plugin Name: WPRavenAuth
    Plugin URI: https://github.com/gfarrell/WPRavenAuth
    Description: Replace wordpress login with Raven authentication.
    Version: 1.0.0
    Author: Gideon Farrell <me@gideonfarrell.co.uk>, Conor Burgess <Burgess.Conor@gmail.com>
 
 */
    
namespace WPRavenAuth {

// Some quickly bootstrapped definitions
if(!defined('DS')) {
    define('DS', '/');
}
define('WPRavenAuth_parent', end(explode(DS, dirname(__FILE__))));
define('WPRavenAuth_dir', dirname(__file__));
define('WPRavenAuth_keys', WPRavenAuth_dir . DS . 'keys');

// Load required files
require('app/core/set.php');          // Array manipulation library
require('app/core/config.php');       // Configuration wrapper
require('app/core/ldap.php');         // LDAP lookups for users
require('app/lib/ucam_webauth.php');  // Cantab authentication library
require('app/core/raven.php');        // Interface between WP and Raven

// Initialise Raven
    
add_action('init', 'WPRavenAuth\setup');

function setup()
{
    // Add action hooks and filters
    add_action('lost_password', 'WPRavenAuth\disable_function');                    // Raven has no passwords
    add_action('retrieve_password', 'WPRavenAuth\disable_function');                // ditto
    add_action('password_reset', 'WPRavenAuth\disable_function');                   // ditto
    add_action('check_passwords', 'WPRavenAuth\disable_function');                  // ditto
    add_filter('show_password_fields','WPRavenAuth\show_password_fields');          // ditto so return false
    add_action('register_form','WPRavenAuth\disable_function');                     // Registration is automatic
    add_action('login_init', 'WPRavenAuth\login_init');                             // Intercept login
    add_action('wp_logout', array(Raven::getInstance(), 'logout'));                 // Intercept logout
}
    
// Decide if login should use raven or not, and initiate raven if required
function login_init()
{
    if (isset($_REQUEST['super_admin']) && $_REQUEST['super-admin'] == 1)
        return;
    
    header_remove()
    Raven::getInstance()->login();

}
    
// Don't show password fields on user profile page
function show_password_fields($show_password_fields)
{
    return false;
}

// Used to disable unnecessary functions
function  disable_function()
{
    die('Disabled');
}
    
} // End namespace
    
namespace {// Global namespace
    
// Don't send any notifications (needs to be outisde namespace to work)
if (!function_exists('wp_new_user_notification')) { // this is to stop problems with activation
    function wp_new_user_notification($user_id, $plaintext_pass = '')
    {
    }
}

} // End global namespace
    
?>