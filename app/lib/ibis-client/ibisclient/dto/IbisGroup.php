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
require_once "IbisInstitution.php";
require_once "IbisPerson.php";

/**
 * Class representing a group returned by the web services API.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisGroup extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("cancelled", "groupid", "id", "ref");

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("name", "title", "description",
                                       "emails", "membersOfInst");

    /* Properties marked as @XmlElementWrapper in the JAXB class */
    protected static $xmlArrays = array("members", "directMembers",
                                        "owningInsts", "managesInsts",
                                        "managesGroups", "managedByGroups",
                                        "readsGroups", "readByGroups",
                                        "includesGroups", "includedByGroups");

    /** Flag indicating if the group is cancelled. */
    public $cancelled;

    /** The group's numeric ID (actual text e.g., "100656"). */
    public $groupid;

    /** The group's unique name (e.g., "cs-editors"). */
    public $name;

    /** The group's title. */
    public $title;

    /** The more detailed description of the group. */
    public $description;

    /** The group's email address. */
    public $email;

    /**
     * The details of the institution for which this group forms all or
     * part of the membership. This will only be set for groups that are
     * membership groups of institutions if the <code>fetch</code> parameter
     * includes the "members_of_inst" option.
     */
    public $membersOfInst;

    /**
     * A list of the group's members, including (recursively) any members of
     * any included groups. This will only be populated if the
     * <code>fetch</code> parameter includes the "all_members" option.
     */
    public $members;

    /**
     * A list of the group's direct members, not including any members
     * included via groups included by this group. This will only be
     * populated if the <code>fetch</code> parameter includes the
     * "direct_members" option.
     */
    public $directMembers;

    /**
     * A list of the institutions to which this group belongs. This will only
     * be populated if the <code>fetch</code> parameter includes the
     * "owning_insts" option.
     */
    public $owningInsts;

    /**
     * A list of the institutions managed by this group. This will only be
     * populated if the <code>fetch</code> parameter includes the
     * "manages_insts" option.
     */
    public $managesInsts;

    /**
     * A list of the groups managed by this group. This will only be populated
     * if the <code>fetch</code> parameter includes the "manages_groups"
     * option.
     */
    public $managesGroups;

    /**
     * A list of the groups that manage this group. This will only be populated
     * if the <code>fetch</code> parameter includes the "managed_by_groups"
     * option.
     */
    public $managedByGroups;

    /**
     * A list of the groups that this group has privileged access to. Members
     * of this group will be able to read the members of any of those groups,
     * regardless of the membership visibilities. This will only be populated
     * if the <code>fetch</code> parameter includes the "reads_groups" option.
     */
    public $readsGroups;

    /**
     * A list of the groups that have privileged access to this group. Members
     * of those groups will be able to read the members of this group,
     * regardless of the membership visibilities. This will only be populated
     * if the <code>fetch</code> parameter includes the "read_by_groups"
     * option.
     */
    public $readByGroups;

    /**
     * A list of the groups directly included in this group. Any members of
     * the included groups (and recursively any groups that they include) will
     * automatically be included in this group. This will only be populated if
     * the <code>fetch</code> parameter includes the "includes_groups" option.
     */
    public $includesGroups;

    /**
     * A list of the groups that direcly include this group. Any members of
     * this group will automatically be included in those groups (and
     * recursively in any groups that include those groups). This will only
     * be populated if the <code>fetch</code> parameter includes the
     * "included_by_groups" option.
     */
    public $includedByGroups;

    /**
     * An ID that can uniquely identify this group within the returned
     * XML/JSON document. This is only used in the flattened XML/JSON
     * representation (if the "flatten" parameter is specified).
     */
    public $id;

    /**
     * A reference (by id) to a group element in the XML/JSON document.
     * This is only used in the flattened XML/JSON representation (if the
     * "flatten" parameter is specified).
     */
    public $ref;

    /* Flag to prevent infinite recursion due to circular references. */
    private $unflattened;

    /**
     * Create an IbisGroup from the attributes of an XML node.
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

    /* Unflatten a single IbisGroup. */
    public function unflatten($em)
    {
        if (isset($this->ref))
        {
            $group = $em->getGroup($this->ref);
            if (!$group->unflattened)
            {
                $group->unflattened = true;
                if (isset($group->membersOfInst))
                    $group->membersOfInst = $group->membersOfInst->unflatten(em);
                IbisPerson::unflattenPeople($em, $group->members);
                IbisPerson::unflattenPeople($em, $group->directMembers);
                IbisInstitution::unflattenInsts($em, $group->owningInsts);
                IbisInstitution::unflattenInsts($em, $group->managesInsts);
                IbisGroup::unflattenGroups($em, $group->managesGroups);
                IbisGroup::unflattenGroups($em, $group->managedByGroups);
                IbisGroup::unflattenGroups($em, $group->readsGroups);
                IbisGroup::unflattenGroups($em, $group->readByGroups);
                IbisGroup::unflattenGroups($em, $group->includesGroups);
                IbisGroup::unflattenGroups($em, $group->includedByGroups);
            }
            return $group;
        }
        return $this;
    }

    /** Unflatten a list of IbisGroup objects (done in place). */
    public static function unflattenGroups($em, &$groups)
    {
        if (isset($groups))
            foreach ($groups as $idx => $group)
                $groups[$idx] = $group->unflatten($em);
    }
}
