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

require('ucam_webauth.php');

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
    public static function login() {
        if(is_null($this->webauth)) {
            $this->webauth = new Ucam_Webauth(array(
                'key_dir'       => WPRavenAuth_keys,
                'cookie_key'    => Config::get('cookie'),
                'cookie_name'   => Config::get('cookie'),
                'hostname'      => home_url()
            ));
        }

        $auth = $this->webauth->authenticate();

        if(!$auth) throw new WPRavenAuth\AuthException($this->webauth->status(), $this->webauth->msg());

        if($this->webauth->success()) {
            $this->authenticate();
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
        return md5(Raven::salt);
    }
}
?>
