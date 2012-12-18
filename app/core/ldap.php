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
    public static function connect() {
        static $con = null;

        if(is_null($con)) {
            $con = ldap_connect(Config::get('ldap.server'));
        }

        return $con;
    }
    public static function search($query, $field='givenname') {
        $ds = LDAP::connect();

        $result = array();

        if ($ds) {
            $resource = ldap_bind($ds);
            $search   = ldap_search($ds, Config::get('ldap.base'), "($field=$query)");
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