<?php

namespace WPRavenAuth;
    
class OptionsPage
{
    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Raven Auth Admin', 
            'WPRavenAuth', 
            'edit_users', 
            'wpravenauth-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Raven Auth Settings</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'raven-auth-group' );   
                do_settings_sections( 'wpravenauth-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'raven-auth-group', // Option group
            'WPRavenAuthOptions' // Option name
        );

        add_settings_section(
            'raven-section', // ID
            'Plugin Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'wpravenauth-admin' // Page
        );  

        add_settings_field(
            'cookie', // ID
            'Cookie Name', // Title 
            array( $this, 'cookie_callback' ), // Callback
            'wpravenauth-admin', // Page
            'raven-section' // Section           
        );      

        add_settings_field(
            'salt', 
            'Random Salt', 
            array( $this, 'salt_callback' ), 
            'wpravenauth-admin', 
            'raven-section'
        );      
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below, make salt really random!';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function cookie_callback()
    {
        printf(
            '<input type="text" id="cookie" name="%s[cookie]" value="%s" />',
               Config::key(),
               Config::get('cookie')
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function salt_callback()
    {
        printf(
            '<input type="text" id="salt" name="%s[salt]" value="%s" />',
               Config::key(),
               Config::get('salt')
        );
    }
}

if( is_admin() )
    $WPRavenAuthSettings = new OptionsPage();