<?php
/*
    WPRavenAuth - Raven authentication for Wordpress
    ================================================

    @license BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
    @author  Gideon Farrell <me@gideonfarrell.co.uk>
    @url     https://github.com/gfarrell/WPRavenAuth
 */

namespace WPRavenAuth;

// Some quickly bootstrapped definitions
if(!defined('DS')) {
    define('DS', '/');
}
define('WPRavenAuth_dir', dirname(__file__));
define('WPRavenAuth_keys', WPRavenAuth_dir . DS . 'keys');

// Load required files
require('app/core/set.php');          // Array manipulation library
require('app/core/config.php');       // Configuration wrapper
require('app/core/ldap.php');         // LDAP lookups for users
require('app/lib/ucam_webauth.php');  // Cantab authentication library
require('app/core/raven.php');        // Interface between WP and Raven

// Initialise Raven

// Add action hooks
add_action('lost_password', 'WPRavenAuth_disable_function');                    // Raven has no passwords
add_action('retrieve_password', 'WPRavenAuth_disable_function');                // ditto
add_action('password_reset', 'WPRavenAuth_disable_function');                   // ditto
add_action('check_passwords', 'WPRavenAuth_disable_function');                  // ditto
add_filter('show_password_fields','WPRavenAuth_show_password_fields');          // ditto so return false
add_action('register_form','WPRavenAuth_disable_function');                     // Registration is automatic
add_action('login_head',  array(Raven->getInstance(), 'login'));           // authenticate
add_action('wp_logout', array(Raven->getInstance(), 'logout'));                 // logout
    
// Don't show password fields on user profile page
function WPRavenAuth_show_password_fields($show_password_fields) {
    return false;
}

// Used to disable unnecessary functions
function  WPRavenAuth_disable_function() {
    die('Disabled');
}
    
if ( !function_exists('wp_new_user_notification') ) :
function wp_new_user_notification($user_id, $plaintext_pass = '')
{
    // Don't send any notifications
}
endif;
    
?>