<?php
/* === AUTO-GENERATED - DO NOT EDIT === */

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

require_once dirname(__FILE__) . "/../client/IbisException.php";

/**
 * Methods for querying and manipulating groups.
 *
 * **The fetch parameter for groups**
 *
 * All methods that return groups also accept an optional ``fetch``
 * parameter that may be used to request additional information about the
 * groups returned. For more details about the general rules that apply to
 * the ``fetch`` parameter, refer to the {@link PersonMethods}
 * documentation.
 *
 * For groups the ``fetch`` parameter may be used to fetch references
 * to people, institutions or other groups. In each case, only non-cancelled
 * people, institutions and groups will be included when fetching references.
 * The following references are supported:
 *
 * * ``"all_members"`` - fetches all the people who are members of the
 *   group, including members of groups included by the group, and groups
 *   included by those groups, and so on.
 *
 * * ``"direct_members"`` - fetches all the poeple who are direct
 *   members of the group, not taking into account any included groups.
 *
 * * ``"members_of_inst"`` - if the group is a membership group for an
 *   institution, this fetches that institution.
 *
 * * ``"owning_insts"`` - fetches all the institutions to which the
 *   group belongs.
 *
 * * ``"manages_insts"`` - fetches all the institutions that the group
 *   manages. Typically this only applies to "Editor" groups.
 *
 * * ``"manages_groups"`` - fetches all the groups that this group
 *   manages. Note that some groups are self-managed, so this may be a
 *   self-reference.
 *
 * * ``"managed_by_groups"`` - fetches all the groups that manage this
 *   group.
 *
 * * ``"reads_groups"`` - fetches all the groups that this group has
 *   privileged access to. This means that members of this group can see the
 *   members of the referenced groups regardless of the membership visibility
 *   settings.
 *
 * * ``"read_by_groups"`` - fetches all the groups that have privileged
 *   access to this group.
 *
 * * ``"includes_groups"`` - fetches all the groups included by this
 *   group.
 *
 * * ``"included_by_groups"`` - fetches all the groups that include
 *   this group.
 *
 * As with person ``fetch`` parameters, the references may be used
 * in a chain by using the "dot" notation to fetch additional information
 * about referenced people, institutions or groups. For example
 * ``"all_members.email"`` will fetch the email addresses of all members
 * of the group. For more information about what can be fetched from
 * referenced people and institutions, refer to the documentation for
 * {@link PersonMethods} and {@link InstitutionMethods}.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class GroupMethods
{
    // The connection to the server
    private $conn;

    /**
     * Create a new GroupMethods object.
     *
     * @param ClientConnection $conn The ClientConnection object to use to
     * invoke methods on the server.
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Return a list of all groups.
     *
     * By default, only a few basic details about each group are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references.
     *
     * ``[ HTTP: GET /api/v1/group/all-groups ]``
     *
     * @param boolean $includeCancelled [optional] Whether or not to include cancelled
     * groups. By default, only live groups are returned.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisGroup[] The requested groups (in groupid order).
     */
    public function allGroups($includeCancelled,
                              $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("includeCancelled" => $includeCancelled,
                             "fetch"            => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/all-groups',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->groups;
    }

    /**
     * Get the groups with the specified IDs or names.
     *
     * By default, only a few basic details about each group are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references.
     *
     * The results are sorted by groupid.
     *
     * NOTE: The URL path length is limited to around 8000 characters,
     * which limits the number of groups that this method can fetch. Group
     * IDs are currently 6 characters long, and must be comma separated and
     * URL encoded, which limits this method to around 800 groups by ID,
     * but probably fewer by name, depending on the group name lengths.
     *
     * NOTE: The groups returned may include cancelled groups. It is the
     * caller's repsonsibility to check their cancelled flags.
     *
     * ``[ HTTP: GET /api/v1/group/list?groupids=... ]``
     *
     * @param string $groupids [required] A comma-separated list of group IDs or
     * group names (may be a mix of both).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisGroup[] The requested groups (in groupid order).
     */
    public function listGroups($groupids,
                               $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("groupids" => $groupids,
                             "fetch"    => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/list',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->groups;
    }

    /**
     * Search for groups using a free text query string. This is the same
     * search function that is used in the Lookup web application.
     *
     * By default, only a few basic details about each group are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references.
     *
     * ``[ HTTP: GET /api/v1/group/search?query=... ]``
     *
     * @param string $query [required] The search string.
     * @param boolean $approxMatches [optional] Flag to enable more approximate
     * matching in the search, causing more results to be returned. Defaults
     * to ``false``.
     * @param boolean $includeCancelled [optional] Flag to allow cancelled groups to
     * be included. Defaults to ``false``.
     * @param int $offset [optional] The number of results to skip at the start
     * of the search. Defaults to 0.
     * @param int $limit [optional] The maximum number of results to return.
     * Defaults to 100.
     * @param string $orderBy [optional] The order in which to list the results.
     * This may be ``"groupid"``, ``"name"`` (the default) or
     * ``"title"``.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisGroup[] The matching groups.
     */
    public function search($query,
                           $approxMatches=null,
                           $includeCancelled=null,
                           $offset=null,
                           $limit=null,
                           $orderBy=null,
                           $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("query"            => $query,
                             "approxMatches"    => $approxMatches,
                             "includeCancelled" => $includeCancelled,
                             "offset"           => $offset,
                             "limit"            => $limit,
                             "orderBy"          => $orderBy,
                             "fetch"            => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/search',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->groups;
    }

    /**
     * Count the number of groups that would be returned by a search using
     * a free text query string.
     *
     * ``[ HTTP: GET /api/v1/group/search-count?query=... ]``
     *
     * @param string $query [required] The search string.
     * @param boolean $approxMatches [optional] Flag to enable more approximate
     * matching in the search, causing more results to be returned. Defaults
     * to ``false``.
     * @param boolean $includeCancelled [optional] Flag to allow cancelled groups to
     * be included. Defaults to ``false``.
     *
     * @return int The number of matching groups.
     */
    public function searchCount($query,
                                $approxMatches=null,
                                $includeCancelled=null)
    {
        $pathParams = array();
        $queryParams = array("query"            => $query,
                             "approxMatches"    => $approxMatches,
                             "includeCancelled" => $includeCancelled);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/search-count',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return intval($result->value);
    }

    /**
     * Get the group with the specified ID or name.
     *
     * By default, only a few basic details about the group are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of the group.
     *
     * NOTE: The group returned may be a cancelled group. It is the caller's
     * repsonsibility to check its cancelled flag.
     *
     * ``[ HTTP: GET /api/v1/group/{groupid} ]``
     *
     * @param string $groupid [required] The ID or name of the group to fetch. This
     * may be either the numeric ID or the short hyphenated group name (for
     * example ``"100656"`` or ``"cs-editors"``).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisGroup The requested group or ``null`` if it was not found.
     */
    public function getGroup($groupid,
                             $fetch=null)
    {
        $pathParams = array("groupid" => $groupid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/%1$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->group;
    }

    /**
     * Get all the cancelled members of the specified group, including
     * cancelled members of groups included by the group, and groups included
     * by those groups, and so on.
     *
     * By default, only a few basic details about each member are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each person.
     *
     * NOTE: This method returns only cancelled people. It does not include
     * people who were removed from the group. Cancelled people are no longer
     * considered to be current staff, students or accredited visitors, and
     * are no longer regarded as belonging to any groups or institutions. The
     * list returned here reflects their group memberships just before they
     * were cancelled, and so is out-of-date data that should be used with
     * caution.
     *
     * ``[ HTTP: GET /api/v1/group/{groupid}/cancelled-members ]``
     *
     * @param string $groupid [required] The ID or name of the group.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch for each person.
     *
     * @return IbisPerson[] The group's cancelled members (in identifier order).
     */
    public function getCancelledMembers($groupid,
                                        $fetch=null)
    {
        $pathParams = array("groupid" => $groupid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/%1$s/cancelled-members',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Get the direct members of the specified group, not including members
     * included via groups included by the group.
     *
     * By default, only a few basic details about each member are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each person.
     *
     * NOTE: This method will not include cancelled people.
     *
     * ``[ HTTP: GET /api/v1/group/{groupid}/direct-members ]``
     *
     * @param string $groupid [required] The ID or name of the group.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch for each person.
     *
     * @return IbisPerson[] The group's direct members (in identifier order).
     */
    public function getDirectMembers($groupid,
                                     $fetch=null)
    {
        $pathParams = array("groupid" => $groupid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/%1$s/direct-members',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Update the list of people who are direct members of the group. This
     * will not affect people who are included in the group due to the
     * inclusion of other groups.
     *
     * Any non-cancelled people in the list of identifiers specified by
     * ``addIds`` will be added to the group. This list should be a
     * comma-separated list of identifiers, each of which may be either a
     * CRSid or an identifier from another identifier scheme, prefixed with
     * that scheme's name and a slash. For example ``"mug99"`` or
     * ``"usn/123456789"``.
     *
     * Any people in the list of identifiers specified by ``removeIds``
     * will be removed from the group, except if they are also in the list
     * ``addIds``. The special identifier ``"all-members"`` may be
     * used to remove all existing group members, replacing them with the
     * list specified by ``newIds``.
     *
     * **Examples:**
     * <pre>
     * updateDirectMembers("test-group",
     *                     "mug99,crsid/yyy99,usn/123456789",
     *                     "xxx99",
     *                     "Remove xxx99 and add mug99, yyy99 and usn/123456789 to test-group");
     * </pre>
     * <pre>
     * updateDirectMembers("test-group",
     *                     "xxx99,yyy99",
     *                     "all-members",
     *                     "Set the membership of test-group to include only xxx99 and yyy99");
     * </pre>
     *
     * ``[ HTTP: PUT /api/v1/group/{groupid}/direct-members ]``
     *
     * @param string $groupid [required] The ID or name of the group.
     * @param string $addIds [optional] The identifiers of people to add to the group.
     * @param string $removeIds [optional] The identifiers of people to remove from
     * the group.
     * @param string $commitComment [recommended] A short textual description of
     * the change made (will be visible on the history tab of the group and
     * all the affected people in the web application).
     *
     * @return IbisPerson[] The updated list of direct members of the group (in identifier
     * order).
     */
    public function updateDirectMembers($groupid,
                                        $addIds=null,
                                        $removeIds=null,
                                        $commitComment=null)
    {
        $pathParams = array("groupid" => $groupid);
        $queryParams = array();
        $formParams = array("addIds"        => $addIds,
                            "removeIds"     => $removeIds,
                            "commitComment" => $commitComment);
        $result = $this->conn->invokeMethod("PUT",
                                            'api/v1/group/%1$s/direct-members',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Get all the members of the specified group, including members of
     * groups included by the group, and groups included by those groups,
     * and so on.
     *
     * By default, only a few basic details about each member are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each person.
     *
     * NOTE: This method will not include cancelled people.
     *
     * ``[ HTTP: GET /api/v1/group/{groupid}/members ]``
     *
     * @param string $groupid [required] The ID or name of the group.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch for each person.
     *
     * @return IbisPerson[] The group's members (in identifier order).
     */
    public function getMembers($groupid,
                               $fetch=null)
    {
        $pathParams = array("groupid" => $groupid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/group/%1$s/members',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }
}
