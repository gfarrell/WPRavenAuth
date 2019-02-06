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

require_once "IbisDto.php";
require_once "IbisGroup.php";
require_once "IbisInstitution.php";

/**
 * Class representing a person returned by the web service API. Note that
 * the identifier is the person's primary identifier (typically their CRSid),
 * regardless of which identifier was used to query for the person.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisPerson extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("cancelled", "id", "ref");

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("identifier", "displayName",
                                       "registeredName", "surname",
                                       "visibleName", "misAffiliation");

    /* Properties marked as @XmlElementWrapper in the JAXB class */
    protected static $xmlArrays = array("identifiers", "attributes",
                                        "institutions", "groups",
                                        "directGroups");

    /** @var boolean Flag indicating if the person is cancelled. */
    public $cancelled;

    /**
     * @var IbisIdentifier The person's primary identifier (typically their
     * CRSid).
     */
    public $identifier;

    /** @var string The person's display name (if visible). */
    public $displayName;

    /** @var string The person's registered name (if visible). */
    public $registeredName;

    /** @var string The person's surname (if visible). */
    public $surname;

    /**
     * @var string The person's display name if that is visible, otherwise
     * their registered name if that is visible, otherwise their surname if
     * that is visible, otherwise the value of their primary identifier
     * (typically their CRSid) which is always visible.
     */
    public $visibleName;

    /**
     * @var string The person's MIS status (``"staff"``, ``"student"``,
     * ``"staff,student"`` or ``""``).
     */
    public $misAffiliation;

    /**
     * @var IbisIdentifier[] A list of the person's identifiers. This will
     * only be populated if the ``fetch`` parameter includes the
     * ``"all_identifiers"`` option.
     */
    public $identifiers;

    /**
     * @var IbisAttribute[] A list of the person's attributes. This will only
     * be populated if the ``fetch`` parameter includes the ``"all_attrs"``
     * option, or any specific attribute schemes such as ``"email"`` or
     * ``"title"``, or the special pseudo-attribute scheme
     * ``"phone_numbers"``.
     */
    public $attributes;

    /**
     * @var IbisInstitution[] A list of all the institution's to which the
     * person belongs. This will only be populated if the ``fetch`` parameter
     * includes the ``"all_insts"`` option.
     */
    public $institutions;

    /**
     * @var IbisGroup[] A list of all the groups to which the person belongs,
     * including indirect group memberships, via groups that include other
     * groups. This will only be populated if the ``fetch`` parameter
     * includes the ``"all_groups"`` option.
     */
    public $groups;

    /**
     * @var IbisGroup[] A list of all the groups that the person directly
     * belongs to. This does not include indirect group memberships - i.e.,
     * groups that include these groups. This will only be populated if the
     * ``fetch`` parameter includes the ``"direct_groups"`` option.
     */
    public $directGroups;

    /**
     * @ignore
     * @var string An ID that can uniquely identify this person within the
     * returned XML/JSON document. This is only used in the flattened
     * XML/JSON representation (if the "flatten" parameter is specified).
     */
    public $id;

    /**
     * @ignore
     * @var string A reference (by id) to a person element in the XML/JSON
     * document. This is only used in the flattened XML/JSON representation
     * (if the "flatten" parameter is specified).
     */
    public $ref;

    /* Flag to prevent infinite recursion due to circular references. */
    private $unflattened;

    /**
     * @ignore
     * Create an IbisPerson from the attributes of an XML node.
     *
     * @param array $attrs The attributes on the XML node.
     */
    public function __construct($attrs=array())
    {
        parent::__construct($attrs);
        if (isset($this->cancelled))
            $this->cancelled = strcasecmp($this->cancelled, "true") == 0;
        $this->unflattened = false;
    }

    /**
     * Returns ``true`` if the person is a member of staff.
     *
     * Note that this tests for an misAffiliation of ``""``, ``"staff"`` or
     * ``"staff,student"`` since some members of staff will have a blank
     * misAffiliation.
     *
     * @return boolean ``true`` if the person is a member of staff.
     */
    public function isStaff()
    {
        return is_null($this->misAffiliation) ||
               $this->misAffiliation !== "student";
    }

    /**
     * Returns ``true`` if the person is a student.
     *
     * This tests for an misAffiliation of ``"student"`` or
     * ``"staff,student"``.
     *
     * @return boolean ``true`` if the person is a student.
     */
    public function isStudent()
    {
        return isset($this->misAffiliation) &&
               strpos($this->misAffiliation, "student") !== false;
    }

    /**
     * @ignore
     * Unflatten a single IbisPerson.
     *
     * @param IbisResultEntityMap $em The mapping from IDs to entities.
     */
    public function unflatten($em)
    {
        if (isset($this->ref))
        {
            $person = $em->getPerson($this->ref);
            if (!$person->unflattened)
            {
                $person->unflattened = true;
                IbisInstitution::unflattenInsts($em, $person->institutions);
                IbisGroup::unflattenGroups($em, $person->groups);
                IbisGroup::unflattenGroups($em, $person->directGroups);
            }
            return $person;
        }
        return $this;
    }

    /**
     * @ignore
     * Unflatten a list of IbisPerson objects (done in place).
     *
     * @param IbisResultEntityMap $em The mapping from IDs to entities.
     * @param IbisPerson[] $people The people to unflatten.
     */
    public static function unflattenPeople($em, &$people)
    {
        if (isset($people))
            foreach ($people as $idx => $person)
                $people[$idx] = $person->unflatten($em);
    }
}
