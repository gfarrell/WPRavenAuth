<?php
/*
Plugin Name: WPRavenAuth
Plugin URI: http://github.com/gfarrell/WPRavenAuth
Description: Raven authentication for Wordpress
Version: 0.0.1
Author: Gideon Farrell and Conor Burgess
License: BSD-3
*/

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
add_action('lost_password', 'disabled');                     // Raven has no passwords
add_action('retrieve_password', 'disabled');                 // ditto
add_action('password_reset', 'disabled');                    // ditto
add_action('register_form','disabled');                      // Registration is automatic
add_action('check_passwords', 'check_passwords', 10, 3);     // ! check priority (10) is good
add_filter('show_password_fields','show_password_fields');   // 
add_action('wp_authenticate', 'Raven', 10, 2);  // authenticate
add_action('wp_logout', 'raven_logout');                     // logout

// Create admin menu
add_action('admin_menu', __NAMESPACE__.'\register_pages');

function register_pages() {
    add_users_page('Raven Authentication', 'Raven', 'edit_users', 'raven-options', __NAMESPACE__.'\create_options_page');
}
function create_options_page() {
    ob_start();
    require('pages/options.php');
    echo ob_get_clean();
}