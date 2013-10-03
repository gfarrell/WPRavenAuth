<?php
/*
    Ibis
    -----

    Provides wrappers for testing whether a user should be able to access a
    page

    @file    ibis.php
    @license BSD 3-Clause
    @package WPRavenAuth
    @author  Conor Burgess <Burgess.Conor@gmail.com> 
 */

namespace WPRavenAuth;

if(!defined('DS'))
    define('DS', '/');
if (!defined('WPRavenAuth_dir'))
    define('WPRavenAuth_dir', substr(__FILE__, 0, strpos(__FILE__, 'app') - 1));
    
require_once(WPRavenAuth_dir . '/app/lib/ibis-client/ibisclient/client/IbisClientConnection.php');
require_once(WPRavenAuth_dir . '/app/lib/ibis-client/ibisclient/methods/PersonMethods.php');
    
class Ibis {
    
    /**
     * ibisPM
     * Get a PersonMethods obejct for Ibis
     *
     * @access protected
     *
     * @returns PersonMethods object for Ibis API
     */
    protected static function ibisPM()
    {
        static $ibisConn = null;
        static $ibisPM = null;
        
        if(is_null($ibisConn)) {
            $ibisConn = \IbisClientConnection::createConnection();
        }
        
        if(is_null($ibisPM)) {
            $ibisPM = new \PersonMethods($ibisConn);
        }
        
        return $ibisPM;
    }
    
    /**
     * getPerson
     * Fetches a person and their attributes from Ibis
     *
     * @param string $crsid User's CRSID.
     *
     * @access public
     *
     * @return IbisPerson
     */
    public static function getPerson($crsid)
    {
        $pm = Ibis::ibisPM();
        return $pm->getPerson("crsid", $crsid, "jdCollege,all_insts");
    }
    
    /**
     * isMemberOfCollege
     * Checks whether a person belongs to a certain college
     *
     * @param string $crsid User's CRSID.
     * @param string $collegeID The college identifier. Full list can be obtained from
     *                                  https://www.lookup.cam.ac.uk/api/v1/inst/COLL?fetch=child_insts
     *
     * @access public
     *
     * @return Boolean
     */
    public static function isMemberOfCollege($person, $collegeID)
    {
        return (strcmp($person->attributes[0]->value, $collegeID) == 0);
    }
    
    /**
     * isMemberOfInst
     * Checks whether a person belongs to a certain institution, useful for checking for undergrad status etc.
     *
     * @param string $crsid User's CRSID.
     * @param string $instID The institution identifier. Full list can be obtained from
     *                       https://www.lookup.cam.ac.uk/api/v1/inst/COLL?fetch=child_insts.child_insts
     *
     * @access public
     *
     * @return Boolean
     */
    public static function isMemberOfInst($person, $instID)
    {
        $result = false;
        foreach ($person->institutions as $inst)
        {
            if (strcmp($inst->instid, $instID) == 0)
            {
                $result = true;
                break;
            }
        }
        return $result;
    }
}
?>