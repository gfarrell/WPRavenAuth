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
    
    $root = substr(__FILE__, 0, strpos(__FILE__, 'wp-content'));
    require_once( $root . 'wp-load.php' );
    require_once('raven.php');

    Raven::getInstance()->login();
    
?>