<?php
/*
    Login
    -----

    Provides log in redirection

    @file    login.php
    @license BSD 3-Clause
    @package WPRavenAuth
    @author  Conor Burgess <Burgess.Conor@gmail.com>
 */

//namespace WPRavenAuth;
    
    $root = substr($_SERVER["SCRIPT_FILENAME"], 0, strpos($_SERVER["SCRIPT_FILENAME"], 'wp-content'));
    require_once( $root . 'wp-load.php' );
    require_once('raven.php');

    Raven::getInstance()->login();
    
?>