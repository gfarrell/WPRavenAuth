<?php
/*
Copyright (c) 2012, University of Cambridge Computing Service

This file is part of the Lookup/Ibis client library.

This library is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This library is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'PHPUnit/Autoload.php';

require_once dirname(__FILE__) . "/../ibisclient/client/IbisClientConnection.php";
require_once dirname(__FILE__) . "/../ibisclient/client/IbisException.php";
require_once dirname(__FILE__) . "/../ibisclient/methods/GroupMethods.php";
require_once dirname(__FILE__) . "/../ibisclient/methods/InstitutionMethods.php";
require_once dirname(__FILE__) . "/../ibisclient/methods/PersonMethods.php";

class UnitTests extends PHPUnit_Framework_TestCase
{
    private static $localConnection = false;
    private static $runEditTests = false;
    private static $initialised = false;
    private static $conn = null;
    private static $pm = null;
    private static $im = null;
    private static $gm = null;

    public function setUp()
    {
        if (!UnitTests::$initialised)
        {
            UnitTests::$conn = UnitTests::$localConnection ?
                               IbisClientConnection::createLocalConnection() :
                               IbisClientConnection::createTestConnection();
            UnitTests::$pm = new PersonMethods(UnitTests::$conn);
            UnitTests::$im = new InstitutionMethods(UnitTests::$conn);
            UnitTests::$gm = new GroupMethods(UnitTests::$conn);
            UnitTests::$initialised = true;
        }

        print(" " . $this->getName() . "()\n");
    }

    // --------------------------------------------------------------------
    // Person tests.
    // --------------------------------------------------------------------

    public function testPersonAttributeSchemes()
    {
        $schemes = UnitTests::$pm->allAttributeSchemes();

        $this->assertTrue(sizeof($schemes) > 10);
        $this->assertEquals("displayName", $schemes[0]->schemeid);
    }

    public function testNoSuchPerson()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar1734toolong");
        $this->assertNull($person);
    }

    public function testNoSuchIdentifierScheme()
    {
        $person = UnitTests::$pm->getPerson("crs", "dar17");
        $this->assertNull($person);
    }

    public function testGetPersonDetails()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar17", null);
        $this->assertEquals("Rasheed", $person->surname);
        $this->assertEquals("staff", $person->misAffiliation);
        $this->assertTrue($person->isStaff());
        $this->assertFalse($person->isStudent());
    }

    public function testGetPersonIdentifiers()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar17", "all_identifiers");
        $this->assertEquals("crsid", $person->identifiers[0]->scheme);
        $this->assertEquals("dar17", $person->identifiers[0]->value);
    }

    public function testGetPersonTitle()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar17", "title");
        $this->assertEquals("title", $person->attributes[0]->scheme);
        $this->assertEquals("Database administrator and developer", $person->attributes[0]->value);
    }

    public function testGetPersonAttributes()
    {
        $attrs = UnitTests::$pm->getAttributes("crsid", "dar17", "email,title");
        $this->assertEquals("title", $attrs[0]->scheme);
        $this->assertEquals("Database administrator and developer", $attrs[0]->value);
        $this->assertEquals("email", $attrs[1]->scheme);

        $attr = UnitTests::$pm->getAttribute("crsid", "dar17", $attrs[1]->attrid);
        $this->assertEquals($attrs[1]->scheme, $attr->scheme);
        $this->assertEquals($attrs[1]->value, $attr->value);
    }

    public function testGetPersonInstsAndGroups()
    {
        $person = UnitTests::$pm->getPerson("crsid", "mug99", "all_insts,all_groups");
        $this->assertEquals("UISTEST", $person->institutions[0]->instid);
        $this->assertEquals("uistest-members", $person->groups[0]->name);
    }

    public function testGetPersonInstManagers()
    {
        $person = UnitTests::$pm->getPerson("crsid", "mug99", "all_insts.managed_by_groups.all_members");

        $inst = $person->institutions[0];
        $this->assertEquals("UISTEST", $inst->instid);

        $mgrGroup = $inst->managedByGroups[0];
        $this->assertEquals("uistest-editors", $mgrGroup->name);
        $this->assertEquals("Dr D.A. Rasheed", $mgrGroup->members[0]->registeredName);
    }

    public function testListPeople()
    {
        $people = UnitTests::$pm->listPeople("ijl20,rjd4,pms52,dar17,prb34,dcs38", "email");
        $this->assertEquals(6, sizeof($people));
        $this->assertEquals("dar17", $people[0]->identifier->value);
        $this->assertEquals("dcs38@cam.ac.uk", $people[1]->attributes[0]->value);
        $this->assertEquals("Lewis", $people[2]->surname);
        $this->assertEquals("P.M. Shore", $people[3]->registeredName);
        $this->assertEquals("prb34", $people[4]->identifier->value);
        $this->assertEquals("Dowling", $people[5]->surname);
    }

    public function testPersonSearch()
    {
        $people = UnitTests::$pm->search("ian lewis");
        $this->assertEquals(1, sizeof($people));
        $this->assertEquals("ijl20", $people[0]->identifier->value);

        $people = UnitTests::$pm->search("rasheed UIS", false, false,
                                         "staff", "surname,jdInstid", 0, 100, null, "address,title");
        $this->assertEquals(2, sizeof($people));
        $this->assertEquals("dar17", $people[0]->identifier->value);
        $this->assertEquals("Database administrator and developer", $people[0]->attributes[0]->value);
        $this->assertTrue(strpos($people[0]->attributes[1]->value, "Roger Needham") !== false);

        $people = UnitTests::$pm->search("dar54", false, true);
        $this->assertEquals(1, sizeof($people));
        $this->assertEquals("dar54", $people[0]->identifier->value);
    }

    public function testPersonSearchCount()
    {
        $count = UnitTests::$pm->searchCount("j smith");
        $this->assertTrue($count > 10);
    }

    public function testIsPersonMemberOfInst()
    {
        $this->assertTrue(UnitTests::$pm->isMemberOfInst("crsid", "dar17", "UIS"));
        $this->assertFalse(UnitTests::$pm->isMemberOfInst("crsid", "dar17", "ENG"));
        $this->assertFalse(UnitTests::$pm->isMemberOfInst("crs", "dar1734-sdfr", "CS"));
    }

    public function testIsPersonMemberOfGroup()
    {
        $this->assertTrue(UnitTests::$pm->isMemberOfGroup("crsid", "rjd4", "100656"));
        $this->assertTrue(UnitTests::$pm->isMemberOfGroup("crsid", "rjd4", "cs-editors"));
        $this->assertFalse(UnitTests::$pm->isMemberOfGroup("crsid", "dar99", "100656"));
        $this->assertFalse(UnitTests::$pm->isMemberOfGroup("crsid", "rjd4", "3g3rsfh"));
        $this->assertFalse(UnitTests::$pm->isMemberOfGroup("crsid", "34sf_rjd", "3g3rsfh"));
        $this->assertFalse(UnitTests::$pm->isMemberOfGroup("crs", "34sf_rjd", "3g3rsfh"));
    }

    public function testGetPersonsInsts()
    {
        $person = UnitTests::$pm->getPerson("crsid", "ijl20", "all_insts");
        $insts = UnitTests::$pm->getInsts("crsid", "ijl20");

        $this->assertEquals(2, sizeof($person->institutions));
        $this->assertEquals(2, sizeof($insts));
        for ($i=0; $i<sizeof($insts); $i++)
        {
            $i1 = $person->institutions[$i];
            $i2 = $insts[$i];
            $this->assertEquals($i1->instid, $i2->instid);
            $this->assertEquals($i1->name, $i2->name);
        }
    }

    public function testGetPersonsManagedInsts()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar99", "all_groups.manages_insts");
        $insts = UnitTests::$pm->getManagedInsts("crsid", "dar99");

        $managedInsts = array();
        foreach ($person->groups as $group)
            foreach ($group->managesInsts as $inst)
                $managedInsts[] = $inst;

        $this->assertEquals(1, sizeof($managedInsts));
        $this->assertEquals(1, sizeof($insts));
        for ($i=0; $i<sizeof($insts); $i++)
        {
            $i1 = $managedInsts[$i];
            $i2 = $insts[$i];
            $this->assertEquals($i1->instid, $i2->instid);
            $this->assertEquals($i1->name, $i2->name);
        }
    }

    public function testGetPersonsGroups()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar99", "all_groups");
        $groups = UnitTests::$pm->getGroups("crsid", "dar99");

        $this->assertTrue(sizeof($person->groups) > 2);
        $this->assertTrue(sizeof($person->groups) == sizeof($groups));
        for ($i=0; $i<sizeof($groups); $i++)
        {
            $g1 = $person->groups[$i];
            $g2 = $groups[$i];
            $this->assertEquals($g1->groupid, $g2->groupid);
            $this->assertEquals($g1->name, $g2->name);
        }
    }

    public function testGetPersonsManagedGroups()
    {
        $person = UnitTests::$pm->getPerson("crsid", "dar99", "all_groups.manages_groups");
        $groups = UnitTests::$pm->getManagedGroups("crsid", "dar99");

        $managedGroups = array();
        foreach ($person->groups as $group)
            foreach ($group->managesGroups as $managedGroup)
                $managedGroups[] = $managedGroup;

        function cmp($g1, $g2)
        {
            return strcmp($g1->groupid, $g2->groupid);
        }
        usort($managedGroups, "cmp");

        $this->assertTrue(sizeof($managedGroups) == sizeof($groups));
        for ($i=0; $i<sizeof($groups); $i++)
        {
            $g1 = $managedGroups[$i];
            $g2 = $groups[$i];
            $this->assertEquals($g1->groupid, $g2->groupid);
            $this->assertEquals($g1->name, $g2->name);
        }
    }

    public function testPersonEdit()
    {
        if (!UnitTests::$runEditTests) return;
        $ex = null;
        try
        {
            UnitTests::$conn->setUsername("cstest-editors");
            UnitTests::$conn->setPassword("foobar");

            // Delete any test emails from previous runs
            $person = UnitTests::$pm->getPerson("crsid", "dar99", "email");
            foreach ($person->attributes as $attr)
                if ($attr->owningGroupid === "100668")
                    UnitTests::$pm->deleteAttribute("crsid", "dar99", $attr->attrid,
                                                    "Unit test: delete existing test emails");

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "email");
            foreach ($person->attributes as $attr)
                if ($attr->owningGroupid === "100668")
                    throw new Exception("There should be no test emails left");

            // Test adding a new email
            $newAttr = new IbisAttribute();
            $newAttr->scheme = "email";
            $newAttr->value = "dev-group@ucs.cam.ac.uk";
            $newAttr->comment = "Unit testing";
            $newAttr->instid = "CS";
            $newAttr->effectiveFrom = new DateTime();

            $newAttr = UnitTests::$pm->addAttribute("crsid", "dar99", $newAttr, 0, true,
                                                    "Unit test: add a test email");
            $this->assertEquals("dev-group@ucs.cam.ac.uk", $newAttr->value);
            $this->assertEquals("Unit testing", $newAttr->comment);
            $this->assertEquals("CS", $newAttr->instid);
            $this->assertNotNull($newAttr->effectiveFrom);
            $this->assertNull($newAttr->effectiveTo);
            $this->assertEquals("100668", $newAttr->owningGroupid);

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "email");

            $found = false;
            foreach ($person->attributes as $attr)
            {
                if ($attr->owningGroupid === "100668")
                {
                    $this->assertEquals($newAttr->attrid, $attr->attrid);
                    $this->assertEquals($newAttr->value, $attr->value);
                    $this->assertEquals($newAttr->comment, $attr->comment);
                    $this->assertEquals($newAttr->instid, $attr->instid);
                    $this->assertEquals($newAttr->effectiveFrom, $attr->effectiveFrom);
                    $this->assertEquals($newAttr->effectiveTo, $attr->effectiveTo);
                    $this->assertEquals($newAttr->owningGroupid, $attr->owningGroupid);
                    $this->assertFalse($found);
                    $found = true;
                }
            }
            $this->assertTrue($found);

            // Test updating the new email
            $newAttr->value = "foo@bar.com";
            $newAttr->comment = "Unit test update";
            $newAttr->instid = "CSTEST";
            $newAttr->effectiveFrom = null;
            $newAttr->effectiveTo = new DateTime();
            $updatedAttr = UnitTests::$pm->updateAttribute("crsid", "dar99", $newAttr->attrid,
                                                           $newAttr, "Unit test: update email");
            $this->assertEquals($newAttr->value, $updatedAttr->value);
            $this->assertEquals($newAttr->comment, $updatedAttr->comment);
            $this->assertEquals($newAttr->instid, $updatedAttr->instid);
            $this->assertNull($updatedAttr->effectiveFrom);
            $this->assertNotNull($updatedAttr->effectiveTo);
            $this->assertEquals($newAttr->owningGroupid, $updatedAttr->owningGroupid);

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "email");

            $found = false;
            foreach ($person->attributes as $attr)
            {
                if ($attr->owningGroupid === "100668")
                {
                    $this->assertEquals($updatedAttr->attrid, $attr->attrid);
                    $this->assertEquals($updatedAttr->value, $attr->value);
                    $this->assertEquals($updatedAttr->comment, $attr->comment);
                    $this->assertEquals($updatedAttr->instid, $attr->instid);
                    $this->assertEquals($updatedAttr->effectiveFrom, $attr->effectiveFrom);
                    $this->assertEquals($updatedAttr->effectiveTo, $attr->effectiveTo);
                    $this->assertEquals($updatedAttr->owningGroupid, $attr->owningGroupid);
                    $this->assertFalse($found);
                    $found = true;
                }
            }
            $this->assertTrue($found);

            // Test deleting the new email
            $deleted = UnitTests::$pm->deleteAttribute("crsid", "dar99", $updatedAttr->attrid,
                                                       "Unit test: delete email");
            $this->assertTrue($deleted);

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "email");

            foreach ($person->attributes as $attr)
                if ($attr->owningGroupid === "100668")
                    throw new Exception("There should be no test emails left");
        }
        // Fake finally block
        catch (Exception $e) { $ex = $e; }

        UnitTests::$conn->setUsername("anonymous");
        UnitTests::$conn->setPassword("");

        if ($ex) throw $ex;
    }

    public function testPersonEditImage()
    {
        if (!UnitTests::$runEditTests) return;
        $ex = null;
        try
        {
            UnitTests::$conn->setUsername("cstest-editors");
            UnitTests::$conn->setPassword("foobar");

            // The image to use for testing
            $imageData = file_get_contents("http://www.lookup.cam.ac.uk/images/ibis.jpg");

            // Delete any test images from previous runs
            $person = UnitTests::$pm->getPerson("crsid", "dar99", "jpegPhoto");
            foreach ($person->attributes as $attr)
                if ($attr->owningGroupid === "100668")
                    UnitTests::$pm->deleteAttribute("crsid", "dar99", $attr->attrid,
                                                    "Unit test: delete existing test photos");

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "jpegPhoto");
            foreach ($person->attributes as $attr)
                if ($attr->owningGroupid === "100668")
                    throw new Exception("There should be no test photos left");

            // Test adding a new photo
            $newAttr = new IbisAttribute();
            $newAttr->scheme = "jpegPhoto";
            $newAttr->binaryData = $imageData;
            $newAttr->comment = "Unit testing";

            $newAttr = UnitTests::$pm->addAttribute("crsid", "dar99", $newAttr, 0, true,
                                                    "Unit test: add a test photo");
            $this->assertNull($newAttr->value);
            $this->assertEquals($imageData, $newAttr->binaryData);
            $this->assertEquals("Unit testing", $newAttr->comment);
            $this->assertEquals("100668", $newAttr->owningGroupid);

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "jpegPhoto");

            $found = false;
            foreach ($person->attributes as $attr)
            {
                if ($attr->owningGroupid === "100668")
                {
                    $this->assertEquals($newAttr->attrid, $attr->attrid);
                    $this->assertNull($attr->value);
                    $this->assertEquals($attr->binaryData, $imageData);
                    $this->assertEquals($newAttr->comment, $attr->comment);
                    $this->assertEquals($newAttr->instid, $attr->instid);
                    $this->assertEquals($newAttr->effectiveFrom, $attr->effectiveFrom);
                    $this->assertEquals($newAttr->effectiveTo, $attr->effectiveTo);
                    $this->assertEquals($newAttr->owningGroupid, $attr->owningGroupid);
                    $this->assertFalse($found);
                    $found = true;
                }
            }
            $this->assertTrue($found);

            // Test deleting the new photo
            $deleted = UnitTests::$pm->deleteAttribute("crsid", "dar99", $newAttr->attrid,
                                                       "Unit test: delete photo");
            $this->assertTrue($deleted);

            $person = UnitTests::$pm->getPerson("crsid", "dar99", "jpegPhoto");

            foreach ($person->attributes as $attr)
                if ($attr->owningGroupid === "100668")
                    throw new Exception("There should be no test photos left");
        }
        // Fake finally block
        catch (Exception $e) { $ex = $e; }

        UnitTests::$conn->setUsername("anonymous");
        UnitTests::$conn->setPassword("");

        if ($ex) throw $ex;
    }

    // --------------------------------------------------------------------
    // Institution tests.
    // --------------------------------------------------------------------

    public function testInstAttributeSchemes()
    {
        $schemes = UnitTests::$im->allAttributeSchemes();
        $this->assertTrue(sizeof($schemes) > 10);
        $this->assertEquals("name", $schemes[0]->schemeid);
    }

    public function testNoSuchInst()
    {
        $inst = UnitTests::$im->getInst("54jkn4", null);
        $this->assertNull($inst);
    }

    public function testGetInstDetails()
    {
        $inst = UnitTests::$im->getInst("CS", null);
        $this->assertEquals("UCS", $inst->acronym);
        $this->assertEquals("University Computing Service", $inst->name);
    }

    public function testGetInstEmailAndPhoneNumbers()
    {
        $inst = UnitTests::$im->getInst("CS", "email,phone_numbers");

        $foundEmail = false;
        $foundPhoneNumber = false;
        foreach ($inst->attributes as $attr)
        {
            if ($attr->scheme === "email" &&
                $attr->value === "reception@ucs.cam.ac.uk")
                $foundEmail = true;
            if ($attr->scheme === "universityPhone" &&
                $attr->value === "34600")
                $foundPhoneNumber = true;
        }
        $this->assertTrue($foundEmail);
        $this->assertTrue($foundPhoneNumber);
    }

    public function testGetInstAttributes()
    {
        $attrs = UnitTests::$im->getAttributes("CS", "acronym,email");
        $this->assertEquals("acronym", $attrs[0]->scheme);
        $this->assertEquals("UCS", $attrs[0]->value);
        $this->assertEquals("email", $attrs[1]->scheme);

        $attr = UnitTests::$im->getAttribute("CS", $attrs[0]->attrid);
        $this->assertEquals($attrs[0]->scheme, $attr->scheme);
        $this->assertEquals($attrs[0]->value, $attr->value);
    }

    public function testGetInstMembers()
    {
        $inst = UnitTests::$im->getInst("UIS", "all_members");
        $people = UnitTests::$im->getMembers("UIS");

        $this->assertTrue(sizeof($people) > 100);
        $this->assertTrue(sizeof($inst->members) == sizeof($people));
        for ($i=0; $i<sizeof($people); $i++)
        {
            $p1 = $inst->members[$i];
            $p2 = $people[$i];
            $this->assertEquals($p1->identifier->scheme, $p2->identifier->scheme);
            $this->assertEquals($p1->identifier->value, $p2->identifier->value);
            $this->assertEquals($p1->displayName, $p2->displayName);
        }
    }

    public function testGetInstMembersJdInstid()
    {
        $inst = UnitTests::$im->getInst("UIS", "all_members.jdInstid");
        $people = UnitTests::$im->getMembers("UIS", "jdInstid");

        $this->assertTrue(sizeof($inst->members) == sizeof($people));
        for ($i=0; $i<sizeof($people); $i++)
        {
            $p1 = $inst->members[$i];
            $p2 = $people[$i];
            $this->assertEquals($p1->identifier->scheme, $p2->identifier->scheme);
            $this->assertEquals($p1->identifier->value, $p2->identifier->value);
            $this->assertEquals($p1->attributes[0]->value, $p2->attributes[0]->value);
        }
    }

    public function testGetInstCancelledMembers()
    {
        $people = UnitTests::$im->getCancelledMembers("CS", null);

        $this->assertTrue(sizeof($people) > 30);

        $found = false;
        foreach ($people as $person)
        {
            if ($person->identifier->scheme == "crsid" &&
                $person->identifier->value == "dar54")
            {
                $this->assertEquals("Dr D.A. Rasheed", $person->registeredName);
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetInstParents()
    {
        $inst = UnitTests::$im->getInst("CHURCH", "parent_insts");
        $this->assertEquals("COLL", $inst->parentInsts[0]->instid);
    }

    public function testGetInstParentsChildren()
    {
        $inst = UnitTests::$im->getInst("CHURCH", "parent_insts.child_insts");
        $this->assertEquals("COLL", $inst->parentInsts[0]->instid);
        $this->assertEquals("FTHEO", $inst->parentInsts[0]->childInsts[0]->instid);
        $this->assertEquals("CHRISTS", $inst->parentInsts[0]->childInsts[1]->instid);
        $this->assertEquals("CHURCH", $inst->parentInsts[0]->childInsts[2]->instid);
        $this->assertEquals("CLARE", $inst->parentInsts[0]->childInsts[3]->instid);
        $this->assertEquals("CLAREH", $inst->parentInsts[0]->childInsts[4]->instid);

        $this->assertTrue($inst->parentInsts[0]->childInsts[2] == $inst);
    }

    public function testGetInstGroups()
    {
        $inst = UnitTests::$im->getInst("CS", "inst_groups,managed_by_groups.managed_by_groups,members_groups");
        $this->assertEquals("cs-members", $inst->groups[0]->name);
        $this->assertEquals("cs-managers", $inst->groups[1]->name);
        $this->assertEquals("cs-editors", $inst->groups[2]->name);
        $this->assertEquals("cs-members", $inst->membersGroups[0]->name);
        $this->assertEquals("cs-editors", $inst->managedByGroups[0]->name);
        $this->assertEquals("cs-managers", $inst->managedByGroups[0]->managedByGroups[0]->name);

        $this->assertTrue($inst->membersGroups[0] == $inst->groups[0]);
        $this->assertTrue($inst->managedByGroups[0] == $inst->groups[2]);
        $this->assertTrue($inst->managedByGroups[0]->managedByGroups[0] == $inst->groups[1]);
    }

    public function testInstFetchDepth()
    {
        $f = ".child_insts.parent_insts";
        $fok = substr($f.$f.$f.$f.$f, 1);
        $ferr = $fok . ".child_insts";

        $inst = UnitTests::$im->getInst("UIS", $fok);
        $cpInst = $inst->childInsts[0]->parentInsts[0];
        $this->assertEquals("UIS", $cpInst->instid);
        $cpInst = $cpInst->childInsts[0]->parentInsts[0];
        $this->assertEquals("UIS", $cpInst->instid);
        $cpInst = $cpInst->childInsts[0]->parentInsts[0];
        $this->assertEquals("UIS", $cpInst->instid);
        $cpInst = $cpInst->childInsts[0]->parentInsts[0];
        $this->assertEquals("UIS", $cpInst->instid);
        $cpInst = $cpInst->childInsts[0]->parentInsts[0];
        $this->assertEquals("UIS", $cpInst->instid);
        $cpInst = $cpInst->childInsts[0]->parentInsts[0];
        $this->assertEquals("UIS", $cpInst->instid);

        try
        {
            $inst = UnitTests::$im->getInst("UIS", $ferr);
            throw new Exception("Should have failed due to fetch depth too large");
        }
        catch (Exception $e)
        {
            // Seems to be no way to get the actual error message
            //$this->assertEquals(500, $e->getError()->status);
            //$this->assertEquals("Nested fetch depth too large.", $e->getError()->message);
        }
    }

    public function testListInsts()
    {
        $insts = UnitTests::$im->listInsts("CS,CSTEST,dsrlgnr,ENG", "email");
        $this->assertEquals(3, sizeof($insts));
        $this->assertEquals("CS", $insts[0]->instid);
        $this->assertEquals("reception@ucs.cam.ac.uk", $insts[0]->attributes[0]->value);
        $this->assertEquals("CSTEST", $insts[1]->instid);
        $this->assertEquals("Department of Engineering", $insts[2]->name);
    }

    public function testInstSearch()
    {
        $insts = UnitTests::$im->search("information services", false, false,
                                        null, 0, 100, "instid", null);
        $this->assertEquals("UIS", $insts[0]->instid);

        $insts = UnitTests::$im->search("CB3 0RB", false, false,
                                        "address", 0, 100, null, "phone_numbers");
        $this->assertEquals(1, sizeof($insts));
        $this->assertEquals("UIS", $insts[0]->instid);
        $this->assertEquals("34600", $insts[0]->attributes[0]->value);
    }

    public function testInstSearchCount()
    {
        $count = UnitTests::$im->searchCount("computing");
        $this->assertTrue($count > 5);
    }

    public function testGetInstContactRows()
    {
        $inst = UnitTests::$im->getInst("CS", "contact_rows.jdInstid");
        $contactRows = UnitTests::$im->getContactRows("CS", "jdInstid");

        $this->assertTrue(sizeof($contactRows) > 20);
        $this->assertEquals("Enquiries and Reception", $contactRows[0]->description);
        $this->assertEquals("reception@ucs.cam.ac.uk", $contactRows[0]->emails[0]);
        $this->assertEquals("universityPhone", $contactRows[0]->phoneNumbers[0]->phoneType);
        $this->assertEquals("34600", $contactRows[0]->phoneNumbers[0]->number);
        $this->assertEquals("Director", $contactRows[2]->description);
        $this->assertEquals("Ian Lewis", $contactRows[2]->people[0]->displayName);
        $this->assertEquals("UIS", $contactRows[2]->people[0]->attributes[0]->value);

        $this->assertEquals(sizeof($inst->contactRows), sizeof($contactRows));
        for ($i=0; $i<sizeof($contactRows); $i++)
        {
            $r1 = $contactRows[$i];
            $r2 = $inst->contactRows[$i];
            $this->assertEquals($r1->bold, $r2->bold);
            $this->assertEquals($r1->italic, $r2->italic);
            $this->assertEquals($r1->description, $r2->description);

            $this->assertEquals(sizeof($r1->addresses), sizeof($r2->addresses));
            for ($j=0; $j<sizeof($r1->addresses); $j++)
                $this->assertEquals($r1->addresses[$j], $r2->addresses[$j]);

            $this->assertEquals(sizeof($r1->emails), sizeof($r2->emails));
            for ($j=0; $j<sizeof($r1->emails); $j++)
                $this->assertEquals($r1->emails[$j], $r2->emails[$j]);

            $this->assertEquals(sizeof($r1->people), sizeof($r2->people));
            for ($j=0; $j<sizeof($r1->people); $j++)
            {
                $this->assertEquals($r1->people[$j]->identifier->value, $r2->people[$j]->identifier->value);
                $this->assertEquals($r1->people[$j]->displayName, $r2->people[$j]->displayName);
                $this->assertEquals($r1->people[$j]->attributes[0]->value, $r2->people[$j]->attributes[0]->value);
            }

            $this->assertEquals(sizeof($r1->phoneNumbers), sizeof($r2->phoneNumbers));
            for ($j=0; $j<sizeof($r1->phoneNumbers); $j++)
            {
                $this->assertEquals($r1->phoneNumbers[$j]->phoneType, $r2->phoneNumbers[$j]->phoneType);
                $this->assertEquals($r1->phoneNumbers[$j]->number, $r2->phoneNumbers[$j]->number);
                $this->assertEquals($r1->phoneNumbers[$j]->comment, $r2->phoneNumbers[$j]->comment);
            }

            $this->assertEquals(sizeof($r1->webPages), sizeof($r2->webPages));
            for ($j=0; $j<sizeof($r1->webPages); $j++)
            {
                $this->assertEquals($r1->webPages[$j]->url, $r2->webPages[$j]->url);
                $this->assertEquals($r1->webPages[$j]->label, $r2->webPages[$j]->label);
            }
        }
    }

    public function testInstEdit()
    {
        if (!UnitTests::$runEditTests) return;
        $ex = null;
        try
        {
            UnitTests::$conn->setUsername("cstest-editors");
            UnitTests::$conn->setPassword("foobar");

            // Delete any test emails from previous runs
            $inst = UnitTests::$im->getInst("CSTEST", "email");
            foreach ($inst->attributes as $attr)
                UnitTests::$im->deleteAttribute("CSTEST", $attr->attrid,
                                                "Unit test: delete existing test emails");

            $inst = UnitTests::$im->getInst("CSTEST", "email");
            $this->assertEquals(0, sizeof($inst->attributes));

            // Test adding a new email
            $newAttr = new IbisAttribute();
            $newAttr->scheme = "email";
            $newAttr->value = "cstest@ucs.cam.ac.uk";
            $newAttr->comment = "Unit testing";
            $newAttr->effectiveFrom = new DateTime();

            $newAttr = UnitTests::$im->addAttribute("CSTEST", $newAttr, 0, true,
                                                    "Unit test: add a test email");
            $this->assertEquals("cstest@ucs.cam.ac.uk", $newAttr->value);
            $this->assertEquals("Unit testing", $newAttr->comment);
            $this->assertNotNull($newAttr->effectiveFrom);
            $this->assertNull($newAttr->effectiveTo);

            $inst = UnitTests::$im->getInst("CSTEST", "email");

            $found = false;
            foreach ($inst->attributes as $attr)
            {
                $this->assertEquals($newAttr->attrid, $attr->attrid);
                $this->assertEquals($newAttr->value, $attr->value);
                $this->assertEquals($newAttr->comment, $attr->comment);
                $this->assertEquals($newAttr->effectiveFrom, $attr->effectiveFrom);
                $this->assertEquals($newAttr->effectiveTo, $attr->effectiveTo);
                $this->assertFalse($found);
                $found = true;
            }
            $this->assertTrue($found);

            // Test updating the new email
            $newAttr->value = "foo@bar.com";
            $newAttr->comment = "Unit test update";
            $newAttr->effectiveFrom = null;
            $newAttr->effectiveTo = new DateTime();
            $updatedAttr = UnitTests::$im->updateAttribute("CSTEST", $newAttr->attrid,
                                                           $newAttr, "Unit test: update email");
            $this->assertEquals($newAttr->value, $updatedAttr->value);
            $this->assertEquals($newAttr->comment, $updatedAttr->comment);
            $this->assertNull($updatedAttr->effectiveFrom);
            $this->assertNotNull($updatedAttr->effectiveTo);

            $inst = UnitTests::$im->getInst("CSTEST", "email");

            $found = false;
            foreach ($inst->attributes as $attr)
            {
                $this->assertEquals($updatedAttr->attrid, $attr->attrid);
                $this->assertEquals($updatedAttr->value, $attr->value);
                $this->assertEquals($updatedAttr->comment, $attr->comment);
                $this->assertEquals($updatedAttr->effectiveFrom, $attr->effectiveFrom);
                $this->assertEquals($updatedAttr->effectiveTo, $attr->effectiveTo);
                $this->assertFalse($found);
                $found = true;
            }
            $this->assertTrue($found);

            // Test deleting the new email
            $deleted = UnitTests::$im->deleteAttribute("CSTEST", $updatedAttr->attrid,
                                                       "Unit test: delete email");
            $this->assertTrue($deleted);

            $inst = UnitTests::$im->getInst("CSTEST", "email");
            $this->assertEquals(0, sizeof($inst->attributes));
        }
        // Fake finally block
        catch (Exception $e) { $ex = $e; }

        UnitTests::$conn->setUsername("anonymous");
        UnitTests::$conn->setPassword("");

        if ($ex) throw $ex;
    }

    public function testInstEditImage()
    {
        if (!UnitTests::$runEditTests) return;
        $ex = null;
        try
        {
            UnitTests::$conn->setUsername("cstest-editors");
            UnitTests::$conn->setPassword("foobar");

            // The image to use for testing
            $imageData = file_get_contents("http://www.lookup.cam.ac.uk/images/ibis.jpg");

            // Delete any test images from previous runs
            $inst = UnitTests::$im->getInst("CSTEST", "jpegPhoto");
            foreach ($inst->attributes as $attr)
                UnitTests::$im->deleteAttribute("CSTEST", $attr->attrid,
                                                "Unit test: delete existing test photos");

            $inst = UnitTests::$im->getInst("CSTEST", "jpegPhoto");
            $this->assertEquals(0, sizeof($inst->attributes));

            // Test adding a new photo
            $newAttr = new IbisAttribute();
            $newAttr->scheme = "jpegPhoto";
            $newAttr->binaryData = $imageData;
            $newAttr->comment = "Unit testing";

            $newAttr = UnitTests::$im->addAttribute("CSTEST", $newAttr, 0, true,
                                                    "Unit test: add a test photo");
            $this->assertNull($newAttr->value);
            $this->assertEquals($imageData, $newAttr->binaryData);
            $this->assertEquals("Unit testing", $newAttr->comment);

            $inst = UnitTests::$im->getInst("CSTEST", "jpegPhoto");

            $found = false;
            foreach ($inst->attributes as $attr)
            {
                $this->assertEquals($newAttr->attrid, $attr->attrid);
                $this->assertNull($attr->value);
                $this->assertEquals($attr->binaryData, $imageData);
                $this->assertEquals($newAttr->comment, $attr->comment);
                $this->assertEquals($newAttr->effectiveFrom, $attr->effectiveFrom);
                $this->assertEquals($newAttr->effectiveTo, $attr->effectiveTo);
                $this->assertFalse($found);
                $found = true;
            }
            $this->assertTrue($found);

            // Test deleting the new photo
            $deleted = UnitTests::$im->deleteAttribute("CSTEST", $newAttr->attrid,
                                                       "Unit test: delete photo");
            $this->assertTrue($deleted);

            $inst = UnitTests::$im->getInst("CSTEST", "jpegPhoto");
            $this->assertEquals(0, sizeof($inst->attributes));
        }
        // Fake finally block
        catch (Exception $e) { $ex = $e; }

        UnitTests::$conn->setUsername("anonymous");
        UnitTests::$conn->setPassword("");

        if ($ex) throw $ex;
    }

    // --------------------------------------------------------------------
    // Group tests.
    // --------------------------------------------------------------------

    public function testNoSuchGroup()
    {
        $group = UnitTests::$gm->getGroup("654321");
        $this->assertNull($group);
        $group = UnitTests::$gm->getGroup("no-such-group-exists");
        $this->assertNull($group);
        $group = UnitTests::$gm->getGroup("x4fu89fd");
        $this->assertNull($group);
    }

    public function testGetGroupDetails()
    {
        $group1 = UnitTests::$gm->getGroup("100656");
        $group2 = UnitTests::$gm->getGroup("cs-editors");

        $this->assertEquals("100656", $group2->groupid);
        $this->assertEquals("cs-editors", $group1->name);
        $this->assertEquals($group1->description, $group2->description);
    }

    public function testGetGroupMembers()
    {
        $group = UnitTests::$gm->getGroup("cs-editors", "all_members");
        $people = UnitTests::$gm->getMembers("cs-editors");
        $directPeople = UnitTests::$gm->getDirectMembers("cs-editors");

        $this->assertTrue(sizeof($people) > 10);
        $this->assertTrue(sizeof($group->members) == sizeof($people));
        $this->assertTrue(sizeof($directPeople) <= sizeof($people));
        for ($i=0; $i<sizeof($people); $i++)
        {
            $p1 = $group->members[$i];
            $p2 = $people[$i];
            $this->assertEquals($p1->identifier->scheme, $p2->identifier->scheme);
            $this->assertEquals($p1->identifier->value, $p2->identifier->value);
            $this->assertEquals($p1->displayName, $p2->displayName);
        }
        foreach ($directPeople as $p1)
        {
            $found = false;
            foreach ($people as $p2)
            {
                if ($p2->identifier->scheme === $p1->identifier->scheme &&
                    $p2->identifier->value === $p1->identifier->value)
                {
                    $this->assertFalse($found);
                    $found = true;
                }
            }
            $this->assertTrue($found);
        }
    }

    public function testGetGroupCancelledMembers()
    {
        $people = UnitTests::$gm->getCancelledMembers("cs-members", null);

        $this->assertTrue(sizeof($people) > 30);

        $found = false;
        foreach ($people as $person)
        {
            if ($person->identifier->scheme == "crsid" &&
                $person->identifier->value == "dar54")
            {
                $this->assertEquals("Dr D.A. Rasheed", $person->registeredName);
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetGroupInsts()
    {
        $group = UnitTests::$gm->getGroup("uis-editors", "owning_insts,manages_insts");
        $this->assertEquals("UIS", $group->owningInsts[0]->instid);
        $this->assertEquals("UIS", $group->managesInsts[0]->instid);
        $this->assertTrue($group->owningInsts[0] == $group->managesInsts[0]);
    }

    public function testGetGroupMembersOfInst()
    {
        $group = UnitTests::$gm->getGroup("uis-members", "members_of_inst");
        $this->assertEquals("UIS", $group->membersOfInst->instid);
        $this->assertEquals("University Information Services", $group->membersOfInst->name);
    }

    public function testGetGroupMgrs()
    {
        $group = UnitTests::$gm->getGroup("cs-editors", "managed_by_groups.managed_by_groups");
        $this->assertEquals("cs-managers", $group->managedByGroups[0]->name);
        $this->assertEquals("cs-managers", $group->managedByGroups[0]->managedByGroups[0]->name);
        $this->assertTrue($group->managedByGroups[0] == $group->managedByGroups[0]->managedByGroups[0]);
    }

    public function testListGroups()
    {
        $groups = UnitTests::$gm->listGroups("cs-editors,000000,cs-members", "managed_by_groups");
        $this->assertEquals(3, sizeof($groups));
        $this->assertEquals("000000", $groups[0]->groupid);
        $this->assertEquals("cs-editors", $groups[1]->managedByGroups[0]->name);
        $this->assertEquals("cs-managers", $groups[2]->managedByGroups[0]->name);
    }

    public function testGroupSearch()
    {
        $groups = UnitTests::$gm->search("Editors group for UIS");
        $this->assertEquals("uis-editors", $groups[0]->name);

        $groups = UnitTests::$gm->search("information services test accounts members", false, false,
                                         0, 1, null, "all_members");
        $this->assertEquals(1, sizeof($groups));
        $this->assertEquals("uistest-members", $groups[0]->name);
        $this->assertTrue(sizeof($groups[0]->members) > 10);
        $this->assertEquals("abc123", $groups[0]->members[0]->identifier->value);
    }

    public function testGroupSearchCount()
    {
        $count = UnitTests::$gm->searchCount("maths editors");
        $this->assertEquals(6, $count);
    }

    public function testEditGroupMembers()
    {
        if (!UnitTests::$runEditTests) return;
        $ex = null;
        try
        {
            UnitTests::$conn->setUsername("cstest-editors");
            UnitTests::$conn->setPassword("foobar");

            // Initial members of cstest-members (should include mug99)
            $originalMembers = UnitTests::$gm->getDirectMembers("cstest-members");

            $found = false;
            foreach ($originalMembers as $person)
            {
                if ($person->identifier->scheme === "crsid" &&
                    $person->identifier->value === "mug99")
                {
                    $this->assertFalse($found);
                    $found = true;
                }
            }
            $this->assertTrue($found);

            // Test removing mug99, and a non-existent USN
            $newMembers = UnitTests::$gm->updateDirectMembers("cstest-members", null, "mug99,usn/123456789",
                                                              "Unit test: remove mug99 from cstest-members");
            $this->assertEquals(sizeof($originalMembers)-1, sizeof($newMembers));

            foreach ($newMembers as $person)
                $this->assertFalse($person->identifier->scheme === "crsid" &&
                                   $person->identifier->value === "mug99");

            // Test adding mug99 back and removing all other members
            $newMembers = UnitTests::$gm->updateDirectMembers("cstest-members", "mug99", "all-members",
                                                              "Unit test: make mug99 the only member of cstest-members");
            $this->assertEquals(1, sizeof($newMembers));
            $this->assertEquals("crsid", $newMembers[0]->identifier->scheme);
            $this->assertEquals("mug99", $newMembers[0]->identifier->value);

            // Test restoring all the original members (including mug99 again)
            $newIds = "";
            foreach ($originalMembers as $person)
                $newIds .= $person->identifier->scheme."/".$person->identifier->value.",";

            $newMembers = UnitTests::$gm->updateDirectMembers("cstest-members", $newIds, null,
                                                              "Unit test: restore members of cstest-members");
            $this->assertEquals(sizeof($originalMembers), sizeof($newMembers));

            for ($i=0; $i<sizeof($originalMembers); $i++)
            {
                $p1 = $originalMembers[$i];
                $p2 = $newMembers[$i];
                $this->assertEquals($p1->identifier->scheme, $p2->identifier->scheme);
                $this->assertEquals($p1->identifier->value, $p2->identifier->value);
            }
        }
        // Fake finally block
        catch (Exception $e) { $ex = $e; }

        UnitTests::$conn->setUsername("anonymous");
        UnitTests::$conn->setPassword("");

        if ($ex) throw $ex;
    }
}
