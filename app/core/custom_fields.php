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

namespace WPRavenAuth;
    
if(!defined('DS'))
define('DS', '/');
if (!defined('WPRavenAuth_dir'))
define('WPRavenAuth_dir', substr(__FILE__, 0, strpos(__FILE__, 'app') - 1));

// Include ACF in lite mode
global $acf;
 
if( !$acf )
{
    define( 'ACF_LITE' , true );
    include_once(WPRavenAuth_dir . '/app/lib/advanced-custom-fields/acf.php');
}

// All of the available college ids and institutions
global $available_colleges;
$available_colleges = array(
                           'FTHEO'   => array(
                                              'COLL-FTHEO' => 'All Cambridge Theological Federation Members',
                                              'INST-FTHEO' => 'All Cambridge Theological Federation Institution Members',
                                             ),
                           'CHRISTS' => array(
                                              'COLL-CHRISTS' => 'All Christ\'s Members',
                                              'INST-CHRISTS' => 'All Christ\'s Institution Members',
                                              'INST-CHRSTPG' => 'Christ\'s Postgrads Only',
                                              'INST-CHRSTUG' => 'Christ\'s Undergrads Only',
                                              ),
                           'CHURCH' => array(
                                             'COLL-CHURCH' => 'All Churchill Members',
                                             'INST-CHURCH' => 'All Churchill Institution Members',
                                             'INST-CHURPG' => 'Churchill Postgrads Only',
                                             'INST-CHURUG' => 'Churchill Undergrads Only',
                                             ),
                           'CLARE'  => array(
                                             'COLL-CLARE' => 'All Clare Members',
                                             'INST-CLARE' => 'All Clare Institution Members',
                                             'INST-CLAREPG' => 'Clare Postgrads Only',
                                             'INST-CLAREUG' => 'Clare Undergrads Only',
                                             ),
                           'CLAREH' => array(
                                             'COLL-CLAREH' => 'All Clare Hall Members',
                                             'INST-CLAREH' => 'All Clare Hall Institution Members',
                                             'INST-CLARHPG' => 'Clare Hall Postgrads Only',
                                             ),
                           'CORPUS' => array(
                                             'COLL-CORPUS' => 'All Corpus Members',
                                             'INST-CORPUS' => 'All Corpus Institution Members',
                                             'INST-CORPPG' => 'Corpus Postgrads Only',
                                             'INST-CORPUG' => 'Corpus Undergrads Only',
                                             ),
                           'DARWIN' => array(
                                             'COLL-DARWIN' => 'All Darwin Members',
                                             'INST-DARWIN' => 'All Darwin Institution Members',
                                             'INST-DARPG' => 'Darwin Postgrads Only',
                                             ),
                           'DOWN'   => array(
                                             'COLL-DOWN' => 'All Downing Members',
                                             'INST-DOWN' => 'All Downing Institution Members',
                                             'INST-DOWNPG' => 'Downing Postgrads Only',
                                             'INST-DOWNUG' => 'Downing Undergrads Only',
                                             ),
                           'EMM'    => array(
                                             'COLL-EMM' => 'All Emmanuel Members',
                                             'INST-EMM' => 'All Emmanuel Institution Members',
                                             'INST-EMMPG' => 'Emmanuel Postgrads Only',
                                             'INST-EMMUG' => 'Emmanuel Undergrads Only',
                                             ),
                           'FITZ'   => array(
                                             'COLL-FITZ' => 'All Fitzwilliam Members',
                                             'INST-FITZ' => 'All Fitzwilliam Institution Members',
                                             'INST-FITZPG' => 'Fitzwilliam Postgrads Only',
                                             'INST-FITZUG' => 'Fitzwilliam Undergrads Only',
                                             ),
                           'GIRTON' => array(
                                             'COLL-GIRTON' => 'All Girton Members',
                                             'INST-GIRTON' => 'All Girton Institution Members',
                                             'INST-GIRTPG' => 'Girton Postgrads Only',
                                             'INST-GIRTUG' => 'Girton Undergrads Only',
                                             ),
                           'CAIUS'  => array(
                                             'COLL-CAIUS' => 'All Caius Members',
                                             'INST-CAIUS' => 'All Caius Institution Members',
                                             'INST-CAIUSPG' => 'Caius Postgrads Only',
                                             'INST-CAIUSUG' => 'Caius Undergrads Only',
                                             ),
                           'HOM'    => array(
                                             'COLL-HOM' => 'All Homerton Members',
                                             'INST-HOM' => 'All Homerton Institution Members',
                                             'INST-HOMPG' => 'Homerton Postgrads Only',
                                             'INST-HOMUG' => 'Homerton Undergrads Only',
                                             ),
                           'HUGHES' => array(
                                             'COLL-HUGHES' => 'All Hughes Hall Members',
                                             'INST-HUGHES' => 'All Hughes Institution Hall Members',
                                             'INST-HUGHPG' => 'Hughes Hall Postgrads Only',
                                             'INST-HUGHUG' => 'Hughes Hall Undergrads Only',
                                             ),
                           'JESUS'  => array(
                                             'COLL-JESUS' => 'All Jesus Members',
                                             'INST-JESUS' => 'All Jesus Institution Members',
                                             'INST-JESUSPG' => 'Jesus Postgrads Only',
                                             'INST-JESUSUG' => 'Jesus Undergrads Only',
                                             ),
                           'KINGS'  => array(
                                             'COLL-KINGS' => 'All King\'s Members',
                                             'INST-KINGS' => 'All King\'s Institution Members',
                                             'INST-KINGSPG' => 'King\'s Postgrads Only',
                                             'INST-KINGSUG' => 'King\'s Undergrads Only',
                                             ),
                           'LCC'    => array(
                                             'COLL-LCC' => 'All Lucy Cavendish Members',
                                             'INST-LCC' => 'All Lucy Cavendish Institution Members',
                                             'INST-LCCPG' => 'Lucy Cavendish Postgrads Only',
                                             'INST-LCCUG' => 'Lucy Cavendish Undergrads Only',
                                             ),
                           'MAGD'   => array(
                                             'COLL-MAGD' => 'All Magdalene Members',
                                             'INST-MAGD' => 'All Magdalene Institution Members',
                                             'INST-MAGDPG' => 'Magdalene Postgrads Only',
                                             'INST-MAGDUG' => 'Magdalene Undergrads Only',
                                             ),
                           'NEWH'   => array(
                                             'COLL-NEWH' => 'All Murray Edwards Members',
                                             'INST-NEWH' => 'All Murray Edwards Institution Members',
                                             'INST-NEWHPG' => 'Murray Edwards Postgrads Only',
                                             'INST-NEWHUG' => 'Murray Edwards Undergrads Only',
                                             ),
                           'NEWN'   => array(
                                             'COLL-NEWN' => 'All Newnham Members',
                                             'INST-NEWN' => 'All Newnham Institution Members',
                                             'INST-NEWNPG' => 'Newnham Postgrads Only',
                                             'INST-NEWNUG' => 'Newnham Undergrads Only',
                                             ),
                           'PEMB'   => array(
                                             'COLL-PEMB' => 'All Pembroke Members',
                                             'INST-PEMB' => 'All Pembroke Institution Members',
                                             'INST-PEMBPG' => 'Pembroke Postgrads Only',
                                             'INST-PEMBUG' => 'Pembroke Undergrads Only',
                                             ),
                           'PET'    => array(
                                             'COLL-PET' => 'All Peterhouse Members',
                                             'INST-PET' => 'All Peterhouse Institution Members',
                                             'INST-PETPG' => 'Peterhouse Postgrads Only',
                                             'INST-PETUG' => 'Peterhouse Undergrads Only',
                                             ),
                           'QUEENS' => array(
                                             'COLL-QUEENS' => 'All Queens\' Members',
                                             'INST-QUEENS' => 'All Queens\' Institution Members',
                                             'INST-QUENPG' => 'Queens\' Postgrads Only',
                                             'INST-QUENUG' => 'Queens\' Undergrads Only',
                                             ),
                           'ROBIN'  => array(
                                             'COLL-ROBIN' => 'All Robinson Members',
                                             'INST-ROBIN' => 'All Robinson Institution Members',
                                             'INST-ROBINPG' => 'Robinson Postgrads Only',
                                             'INST-ROBINUG' => 'Robinson Undergrads Only',
                                             ),
                           'SEL'    => array(
                                             'COLL-SEL' => 'All Selywn Members',
                                             'INST-SEL' => 'All Selywn Institution Members',
                                             'INST-SELPG' => 'Selwyn Postgrads Only',
                                             'INST-SELUG' => 'Selwyn Undergrads Only',
                                             ),
                           'SID'    => array(
                                             'COLL-SID' => 'All Sidney Members',
                                             'INST-SID' => 'All Sidney Institution Members',
                                             'INST-SIDPG' => 'Sidney Postgrads Only',
                                             'INST-SIDUG' => 'Sidney Undergrads Only',
                                             ),
                           'CATH'   => array(
                                             'COLL-CATH' => 'All St Catharine\'s Members',
                                             'INST-CATH' => 'All St Catharine\'s Institution Members',
                                             'INST-CATHPG' => 'St Catharine\'s Postgrads Only',
                                             'INST-CATHUG' => 'St Catharine\'s Undergrads Only',
                                             ),
                           'EDMUND' => array(
                                             'COLL-EDMUND' => 'All St Edmund\'s Members',
                                             'INST-EDMUND' => 'All St Edmund\'s Institution Members',
                                             'INST-EDMPG' => 'St Edmund\'s Postgrads Only',
                                             'INST-EDMUG' => 'St Edmund\'s Undergrads Only',
                                             ),
                           'JOHNS'  => array(
                                             'COLL-JOHNS' => 'All St John\'s Members',
                                             'INST-JOHNS' => 'All St John\'s Institution Members',
                                             'INST-JOHNSPG' => 'St John\'s Postgrads Only',
                                             'INST-JOHNSUG' => 'St John\'s Undergrads Only',
                                             ),
                           'TRIN'   => array(
                                             'COLL-TRIN' => 'All Trinity Members',
                                             'INST-TRIN' => 'All Trinity Institution Members',
                                             'INST-TRINPG' => 'Trinity Postgrads Only',
                                             'INST-TRINUG' => 'Trinity Undergrads Only',
                                             ),
                           'TRINH'  => array(
                                             'COLL-TRINH' => 'All Trinity Hall Members',
                                             'INST-TRINH' => 'All Trinity Hall Institution Members',
                                             'INST-TRINHPG' => 'Trinity Hall Postgrads Only',
                                             'INST-TRINHUG' => 'Trinity Hall Undergrads Only',
                                             ),
                           'WOLFC'  => array(
                                             'COLL-WOLFC' => 'All Wolfson Members',
                                             'INST-WOLFC' => 'All Wolfson Institution Members',
                                             'INST-WOLFCPG' => 'Wolfson Postgrads Only',
                                             'INST-WOLFCUG' => 'Wolfson Undergrads Only',
                                             ),
                           );

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
				'instructions' => 'Select all of the groups who should be able to view this page/post. Note that Public overrides everything, and Raven overrides all college restrictions.',
				'required' => 1,
				'choices' => getVisibilityOptions(),
				'default_value' => 'public',
				'layout' => 'vertical',
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
    
function getVisibilityOptions()
{
    global $available_colleges;
    $colleges = Config::get('colleges'); 
    $output = array(
                     'public' => 'Public',
                     'raven'  => 'Require Raven',
                    );
    if (is_array($colleges))
    {
        foreach ($colleges as $college)
        {
            $output = array_merge($output, $available_colleges[$college]);
        }
    }
    return $output;
}
    
?>
