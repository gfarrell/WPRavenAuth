<?php
/*
    LDAP lookup library
    -------------------

    Wraps functionality for looking up Raven users using the UCS' lookup service.

    @file    ldap.php
    @license BSD 3-Clause
    @package WPRavenAuth
    @author  Gideon Farrell <me@gideonfarrell.co.uk>
 */
namespace WPRavenAuth;

class LDAP {
    protected static $_config = array(
        'server' => 'ldap.lookup.cam.ac.uk',
        'base'   => 'ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk',
        'port'   => '636'
    );

    public static function config($what = null) {
        if(is_null($what)) {
            return LDAP::_config;
        }

        if(array_key_exists($what, LDAP::_config) {
            return LDAP::_config[$what];
        } else {
            return false;
        }
    }
    public static function connect() {
        static $con = null;

        if(is_null($con)) {
            $con = ldap_connect(LDAP::config('server'));
        }

        return $con;
    }
    public static function search($query, $field='givenname') {
        $ds = LDAP::connect();
        $cfg = LDAP::config();

        $result = array();

        if ($ds) {
            $resource = ldap_bind($ds);
            $search   = ldap_search($ds, Ldap::config('base'), "($field=$query)");
            $entries  = ldap_get_entries($ds, $search);
            $prop_map = array(
                'uid'           =>  'crsid',
                'sn'            =>  'surname',
                'cn'            =>  'name',
                'ou'            =>  'college',
                'instid'        =>  'collegecode',
                'displayname'   =>  'display'
            );

            foreach($entries as $i => $r) {
                if(!is_integer($i)) { continue; }
                array_push($result, array_map(function($a) { return $a[0]; }, Set::getMapped($r, $prop_map)));
            }
        } else {
            throw new Exception('Unable to connect to LDAP server: '+ldap_error($ds));
        }

        return $result;
    }
}
?>