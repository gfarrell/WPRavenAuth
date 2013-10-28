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
require('app/core/set.php');                // Array manipulation library
require('app/core/config.php');             // Configuration wrapper
//require('app/core/ldap.php');             // LDAP lookups for users
require('app/core/ibis.php');               // Use Ibis database; much more robust than ldap
require('app/lib/ucam_webauth.php');        // Cantab authentication library
require('app/core/raven.php');              // Interface between WP and Raven
require('app/error/auth_exception.php');    // Exceptions
require('pages/options.php');               // Options page for wp-admin
    
// Initialise Raven
add_action('init', 'WPRavenAuth\setup');

function setup()
{
    // Need to require here so other ACF plugins are loaded first
    require('app/core/custom_fields.php');      // Custom fields for visibility settings
    
    // Add action hooks and filters for login and logout
    add_action('lost_password', 'WPRavenAuth\disable_function');                    // Raven has no passwords
    add_action('retrieve_password', 'WPRavenAuth\disable_function');                // ditto
    add_action('password_reset', 'WPRavenAuth\disable_function');                   // ditto
    add_action('check_passwords', 'WPRavenAuth\check_passwords', 10, 3);            // need to play with passwords a little
    add_filter('show_password_fields','WPRavenAuth\show_password_fields');          // ditto so return false
    add_action('register_form','WPRavenAuth\disable_function');                     // Registration is automatic
    add_action('login_init', 'WPRavenAuth\login_init');                             // Intercept login
    add_action('wp_logout', array(Raven::getInstance(), 'logout'));                 // Intercept logout
    
    // Add filters for authentication on pages
    add_filter('the_posts', 'WPRavenAuth\showPost');
    add_filter('get_pages', 'WPRavenAuth\showPost');
}
    
// Decide if login should use raven or not, and initiate raven if required
function login_init()
{
    if (isset($_REQUEST["super-admin"]) && $_REQUEST["super-admin"] == 1)
        return;
    
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "logout") {
        do_action('wp_logout');
        wp_safe_redirect(home_url());
        return;
    }
    
    if (isset($_REQUEST["loggedout"])) {
        wp_safe_redirect(home_url());
        return;
    }
    
    if (isset($_REQUEST["redirect_to"]))
    {
        session_start();
        $_SESSION["raven_redirect_to"] = $_REQUEST["redirect_to"];
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url = preg_replace('/\?.*/', '', $url);
        wp_safe_redirect($url);
        return;
    }
    
    header_remove();
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
// Just make sure we return the passwords
function check_passwords($username, $password1, $password2)
{
	return $password1 = $password2 = Raven::_pwd($username); // This is how Raven does passwords
}
    
/**
 * Returns the current user.
 *
 * @return WP_User
 */
function getCurrentUser()
{
    if (!function_exists('get_userdata')) {
        require_once(ABSPATH . WPINC . '/pluggable.php');
    }
    
    //Force user information
    return wp_get_current_user();
}
    
function userCanAccessPost($postID, $crsid)
{
    $postVisibility = get_field('custom_visibility', $postID);
    
    if (!is_array($postVisibility))
        $postVisibility = array('public');
    
    if (in_array('public', $postVisibility))
        return true;
    elseif (in_array('raven', $postVisibility))
        return is_user_logged_in();
    elseif (is_user_logged_in())
    {
        $person = Ibis::getPerson($crsid);
        foreach ($postVisibility as $inst)
        {
            $inst_split = explode('-',$inst);
            if (strcmp($inst_split[0], 'COLL') == 0)
            {
                if (Ibis::isMemberOfCollege($person, $inst_split[1]))
                    return true;
            }
            elseif (strcmp($inst_split[0], 'INST') == 0)
            {
                if (Ibis::isMemberOfInst($person, $inst_split[1]))
                    return true;
            }
        }
    }
    return false;
}
    
function showPost($aPosts = array())
{
    $aShowPosts = array();
    $userCRSID = '';
    if (is_user_logged_in())
    {
        $currentUser = getCurrentUser();
        $userCRSID = $currentUser->user_login;
    }
    foreach ($aPosts as $aPost)
    {
        if (!userCanAccessPost($aPost->ID,$userCRSID))
        {
            $aPost->post_title = "Restricted Content";
            $postContent = get_field('error_message', $aPost->ID);
            if (!is_user_logged_in())
            {
                $postContent .= '<p>You may be able to access this content if you <a href="' . wp_login_url() . '">login</a>.</p>';
            }
            $aPost->post_content = $postContent;
            $aPost->post_excerpt = get_field('error_message', $aPost->ID);
        }
        $aShowPosts[] = $aPost;
    }
    
    $aPosts = $aShowPosts;
    
    return $aPosts;
}
    
} // End namespace
    
namespace { // Global namespace
    
// Don't send any notifications (needs to be outisde namespace to work)
if (!function_exists('wp_new_user_notification')) { // this is to stop problems with activation
    
    function wp_new_user_notification($user_id, $plaintext_pass = '')
    {
    }
    
}

} // End Global Namespace
    
?>
