<?php
/*
    Set
    ---

    Allows easier array manipulation.

    @file    set.php
    @license BSD 3-Clause
    @package WPRavenAuth
    @author  Gideon Farrell <me@gideonfarrell.co.uk>
 */
namespace WPRavenAuth;

class Set {
    public static function get($array, $keys) {
        $keys = (array) $keys;

        return Set::getMapped($array, array_combine($keys, $keys));
    }

    public static function getMapped($array, $map) {
        $out = array();
        foreach($map as $from => $to) {
            $out[$to] = array_key_exists($from, $array) ? $array[$from] : null;
        }
        return $out;
    }
}
?>