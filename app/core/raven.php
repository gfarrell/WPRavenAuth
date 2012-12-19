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
    protected static $webauth = null;

    public function login() {
        if(is_null(Raven::webauth)) {
            Raven::webauth = new Ucam_Webauth(array(
                'key_dir'       => WPRavenAuth_keys,
                'cookie_key'    => Config::get('cookie'),
                'cookie_name'   => Config::get('cookie'),
                'hostname'      => home_url()
            ));
        }

        $auth = Raven::webauth->authenticate();

        if(!$auth) throw new WPRavenAuth\AuthException($webauth->status(), $webauth->msg());

        if(Raven::webauth->success()) {
            $this->authenticate();
        }
    }

    public function logout() {
        setcookie(Config::get('cookie', '');
        session_destroy();
                  
        // redirect to homepage?
    }

    public function authenticate() {
        $crsid  = Raven::webauth->principal();
        
        if($this->userExists($crsid)) {
            $user = $this->getWpUser($crsid);
            $password = $this->_pwd($user->user_pass);
        }

        // If authorised, continue, otherwise throw them out.
    }

    public function userExists($crsid) {
        return (get_user_by('login', $crsid) != false);
    }

    public function getWpUser($crsid) {
        return get_user_by('login', $crsid);
    }
}
?>