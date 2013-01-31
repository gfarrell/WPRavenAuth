<?php
/*
    Raven
    -----

    Provides wrappers for logging in and out, as well as user validation.

    @file    raven.php
    @license BSD 3-Clause
    @package WPRavenAuth
    @author  Gideon Farrell <me@gideonfarrell.co.uk>  
 */

namespace WPRavenAuth;

require_once(ABSPATH . '/wp-settings.php');
require_once(ABSPATH . WPINC . '/pluggable.php');
require_once(ABSPATH . WPINC . '/registration.php');

class Raven {
    /**
     * $salt
     * Contains the password hashing salt.
     * 
     * @static
     * @var    string
     * @access public
     */
    public static $salt = '1wr0auZmxEsdRNVS3GZNX6Qf5XSO7yHZ';

    /**
     * $webauth
     * Contains the ucam_webauth instance.
     * 
     * @var    Ucam_Webauth
     * @access protected
     */
    protected $webauth = null;
    
    /**
     * __construct
     * Stop anyone else making a Raven instance.
     * 
     * @access private
     */
    private function __construct()
    {
    }
    
    /**
     * getInstance
     * Creates or retrieves the Singleton instance.
     * 
     * @access public
     *
     * @return Raven instance
     */
    public function &getInstance() {
        static $instance;

        if(is_null($instance)) {
            $instance = new Raven();
        }

        return $instance;
    }


    /**
     * login
     * Logs the user in.
     * 
     * @access public
     *
     * @return void
     */
    public function login() {
        if(is_null($this->webauth)) {
            $this->webauth = new Ucam_Webauth(array(
                'key_dir'       => WPRavenAuth_keys,
                'cookie_key'    => Config::get('cookie'),
                'cookie_name'   => Config::get('cookie'),
                'hostname'      => home_url()
            ));
        }

        $auth = $this->webauth->authenticate();

        if(!$auth) throw new AuthException($this->webauth->status(), $this->webauth->msg());

        if($this->webauth->success()) {
            $this->authenticate();
        }
        
        $username = $this->webauth->principal();
		$email = $username . '@cam.ac.uk';
		
		if (function_exists('get_user_by') && function_exists('wp_create_user'))
		{
			if (!$this->userExists($username))
            {
                // User is not in the WordPress database
                // they passed Raven and so are authorized
                // add them to the database (password field is arbitrary, but must be hard to guess)
				$user_id = wp_create_user( $username, $this->_pwd(), $email );
				
				if ( !$user_id )
					throw new AuthException('Could not create user');
				else {
					wp_new_user_notification($user_id, '');
                    $user = get_user_by('login', $username);
				}
			}
            $user = $this->getWpUser($username);
            wp_set_auth_cookie( $user->id, false, '' );
		}				
		else {
			die("Could not load user data");
		}
    }

    /**
     * logout
     * Logs the user out.
     * 
     * @access public
     *
     * @return void
     */
    public function logout() {
        setcookie(Config::get('cookie'), '');
        session_destroy();
        wp_clear_auth_cookie();
    }

    /**
     * authenticate
     * Tests if a user is allowed access.
     * 
     * @access public
     *
     * @return boolean authorised?
     */
    public function authenticate() {
        $crsid = $this->webauth->principal();
        
        // If authorised, continue, otherwise throw them out.
        $restrictions = Config::get('users.restrictions');
        if(!is_null($restrictions)) {
            foreach($restrictions as $restriction) {
                if(!$this->_testRestriction($restriction, $user)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * _testRestriction
     * Tests a restriction (type, allowed) against a user
     * 
     * @param array  $restriction The restriction specification (array[type],[allowed]=>array).
     * @param WPUser $user        The user object.
     *
     * @access protected
     *
     * @return boolean test result
     */
    protected function _testRestriction($restriction, $user) {
        switch($restriction['type']) {
            case 'crsid':
                $test = $user->user_login;   // check!
                break;
            case 'college':
                $test = $user->college;      // check!
                break;
        }

        return in_array($test, $restriction['allowed']);
    }

    public function userExists($crsid) {
        return (get_user_by('login', $crsid) != false);
    }

    /**
     * getWpUser
     * Retrieves the WP User object
     * 
     * @param string $crsid User's CRSID.
     *
     * @access public
     *
     * @return WPUser object
     */
    public function getWpUser($crsid) {
        return get_user_by('login', $crsid);
    }

    /**
     * _pwd
     * Returns the generic password hash, since passwords aren't important for SSO, but are for WP.
     * 
     * @access private
     *
     * @return string password
     */
    private function _pwd() {
        return md5(Raven::$salt);
    }
}
?>
