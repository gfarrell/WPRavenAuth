<?php
/**
 *  Install Add-ons
 *  
 *  The following code will include all 4 premium Add-Ons in your theme.
 *  Please do not attempt to include a file which does not exist. This will produce an error.
 *  
 *  The following code assumes you have a folder 'add-ons' inside your theme.
 *
 *  IMPORTANT
 *  Add-ons may be included in a premium theme/plugin as outlined in the terms and conditions.
 *  For more information, please read:
 *  - http://www.advancedcustomfields.com/terms-conditions/
 *  - http://www.advancedcustomfields.com/resources/getting-started/including-lite-mode-in-a-plugin-theme/
 */

if(!defined('DS'))
define('DS', '/');
if (!defined('WPRavenAuth_dir'))
define('WPRavenAuth_dir', substr(__FILE__, 0, strpos(__FILE__, 'app') - 1));

// Include ACF in lite mode
define('ACF_LITE', true);
require_once(WPRavenAuth_dir . '/app/lib/advanced-custom-fields/acf.php');

// Add-ons 
// include_once('add-ons/acf-repeater/acf-repeater.php');
// include_once('add-ons/acf-gallery/acf-gallery.php');
// include_once('add-ons/acf-flexible-content/acf-flexible-content.php');
// include_once( 'add-ons/acf-options-page/acf-options-page.php' );

/**
 *  Register Field Groups
 *
 *  The register_field_group function accepts 1 array which holds the relevant data to register a field group
 *  You may edit the array as you see fit. However, this may result in errors if the array is not compatible with ACF
 */

if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_custom-visibility-settings',
		'title' => 'Custom Visibility Settings',
		'fields' => array (
			array (
				'key' => 'field_524709c1c56b3',
				'label' => 'Custom Visibility',
				'name' => 'custom_visibility',
				'type' => 'checkbox',
				'instructions' => 'Select all of the groups who should be able to view this page/post.',
				'required' => 1,
				'choices' => array (
					'public' => 'Public',
					'raven' => 'Require Raven',
					'KINGS' => 'All King\'s Members',
				),
				'default_value' => 'public',
				'layout' => 'horizontal',
			),
			array (
				'key' => 'field_52481eeb0d8ab',
				'label' => 'Error Message',
				'name' => 'error_message',
				'type' => 'text',
				'instructions' => 'Enter the error message to be displayed to users who cannot access a post or page.',
				'required' => 1,
				'default_value' => 'You do not have sufficient permissions to access this page.',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'formatting' => 'none',
				'maxlength' => '',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'user_type',
					'operator' => '!=',
					'value' => 'subscriber',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'default',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 100,
	));
}
?>