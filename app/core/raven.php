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
    public static $salt = '1wr0auZmxEsdRNVS3GZNX6Qf5XSO7yHZ';

    protected static $webauth = null;

    public function login() {
        if(is_null(Raven::webauth)) {
            Raven::webauth = new Ucam_Webauth(array(
                'key_dir'       => WPRavenAuth_keys,
                'cookie_key'    => Config::get('cookie'),
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
        // TODO
    }

    public function authenticate() {
        $crsid  = Raven::webauth->principal();

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
        // TODO
    }

    public function getWpUser($crsid) {
        return get_userdatabylogin($crsid);
    }
}
?>