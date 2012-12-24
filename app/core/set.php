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
    /**
     * select
     * Selects certain keys from an array
     * 
     * @static
     * @access public
     * @param  array   $array the array select from
     * @param  array   $keys  the keys to select
     * @return array          an array of the selection
     */
    public static function select($array, $keys) {
        $keys = (array) $keys;

        $select = array();

        foreach($keys as $k) {
            $select[$k] = (array_key_exists($k, $array) ? $array[$k] : null);
        }

        return $select;
    }

    /**
     * reassignKeys
     * Re-maps the keys of an array.
     * 
     * @static
     * @access public
     * @param  array  $array   the array to re-map
     * @param  array  $key_map a key=>value mapping of original keys => new keys
     * @return array           newly assigned array
     */
    public static function reassignKeys($array, $key_map) {
        $new = array();
        foreach($array as $key => $value) {
            $mapped = array_key_exists($key, $key_map) ? $key_map[$key] : $key;
            $new[$mapped] = $value;
        }

        return $new;
    }

    /**
     * extract
     * Extracts a path from an array using dot notation (parent.child)
     * See extractReference for dot notation usage.
     * 
     * @static
     * @access public
     * @param  array  $array the source array
     * @param  string $path  the path to extract
     * @return mixed         the extracted value(s)
     */
    public static function extract($array, $path) {
        $a = Set::extractReference($array, $path);
        return (is_object($a) ? clone $a : $a); // make sure we don't play with references
    }

    /**
     * extractReference
     * Extracts a path from an array using dot notation (parent.child) and returns a reference to it
     * Usage:
     *     MyModel.name    = $array[MyModel][name]
     *     MyModel.*.name  = an array of $array[MyModel][_all_][name]
     *     MyModel.*       = $array[MyModel][_all_]
     *     MyModel[name=x] = $array[MyModel][_all_] where MyModel[*][name] = x
     *                         can also use !=, <, >, >=, <=
     * 
     * @static
     * @access public
     * @param  array  $array the source array
     * @param  string $path  the path to extract
     * @return mixed         the extracted value(s)
     */
    public static function &extractReference(&$array, $path) {
        if($path == '') {
            return $array;
        }

        // Split the path and recombine without the first part
        $ps   = explode('.', $path);
        $c    = array_shift($ps);
        $path = implode('.', $ps);

        // Check for special commands in the key
        // 
        // * = all
        if($c == '*') {
            $extract = array();
            foreach($array as $index => &$item) {
                array_push($extract, Set::extractReference($item, $path));
            }
            return $extract;
        }
        // [x=y] = conditional check
        $pattern = '/\[([A-Za-z0-9\-_\ ]+)(=|<|>|!=|>=|<=)([A-Za-z0-9\-_\ ]+)\]/';
        if(preg_match($pattern, $c, $matches)) {
            $key = $matches[1];
            $op  = $matches[2]; // operator
            $val = $matches[3];

            switch($op) {
                case '=':
                    $test = function($a, $b) { return ($a == $b); };
                    break;
                case '>':
                    $test = function($a, $b) { return ($a > $b); };
                    break;
                case '<':
                    $test = function($a, $b) { return ($a < $b); };
                    break;
                case '>=':
                    $test = function($a, $b) { return ($a >= $b); };
                    break;
                case '<=':
                    $test = function($a, $b) { return ($a <= $b); };
                    break;
                case '!=':
                    $test = function($a, $b) { return ($a != $b); };
                    break;
                default:
                    $test = function($a, $b) { return false; };
            }

            // check each item
            $extract = array();
            foreach($array as $index => &$item) {
                if($test($item[$key], $val)) {
                   array_push($extract, Set::extractReference($item, $path)); 
                }
            }
            return $extract;
        }

        // Check if anything is here...
        if(!array_key_exists($c, $array)) {
            return null;
        }

        if(count($ps) > 0) {
            return Set::extractReference($array[$c], $path);
        } else {
            return $array[$c];
        }
    }

    /**
     * set
     * Sets a value in a nested array using a path spec (as above).
     * See extractReference for dot notation usage.
     * 
     * @static
     * @access public
     * @param  array  $array the array to set in
     * @param  string $path  the path to set at
     * @param  mixed  $value the value to set to
     * @return void
     */
    public static function set(&$array, $path, $value) {
        $reference =& Set::extractReference($array, $path);
        if(is_array($reference) && !is_array($value)) {
            foreach($reference as $key => &$ref) {
                $ref = $value;
            }
        } else {
            $reference = $value;
        }
    }

    /**
     * merge
     * Merges the values of two arrays completely.
     * 
     * @static
     * @access public
     * @param  array  $a the first array to merge (least priority)
     * @param  array  $b the second array to merge (overwrites $a)
     * @return array     the merged result
     */
    public static function merge($a, $b) {
        foreach($b as $key => $value) {
            if(array_key_exists($key, $a) && is_array($a[$key]) && is_array($value)) {
                $a[$key] = Set::merge($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}
?>