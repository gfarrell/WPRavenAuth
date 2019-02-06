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
 * Methods for querying and manipulating institutions.
 *
 * **The fetch parameter for institutions**
 *
 * All methods that return institutions also accept an optional
 * ``fetch`` parameter that may be used to request additional
 * information about the institutions returned. For more details about
 * the general rules that apply to the ``fetch`` parameter,
 * refer to the {@link PersonMethods} documentation.
 *
 * For institutions the ``fetch`` parameter may be used to fetch
 * any institution attribute by specifying the ``schemeid`` of an
 * institution attribute scheme. Examples include ``"address"``,
 * ``"jpegPhoto"``, ``"universityPhone"``, ``"instPhone"``,
 * ``"landlinePhone"``, ``"mobilePhone"``, ``"faxNumber"``,
 * ``"email"`` and ``"labeledURI"``. The full list (which may be
 * extended over time) may be obtained using {@link allAttributeSchemes}.
 *
 * In addition the following pseudo-attributes are supported:
 *
 * * ``"phone_numbers"`` - fetches all phone numbers. This is
 *   equivalent to
 *   ``"universityPhone,instPhone,landlinePhone,mobilePhone"``.
 *
 * * ``"all_attrs"`` - fetches all attributes from all institution
 *   attribute schemes. This does not include references.
 *
 * * ``"contact_rows"`` - fetches all institution contact rows. Any
 *   chained fetches from contact rows are used to fetch attributes from any
 *   people referred to by the contact rows.
 *
 * The ``fetch`` parameter may also be used to fetch referenced
 * people, institutions or groups. This will only include references to
 * non-cancelled entities. The following references are supported:
 *
 * * ``"all_members"`` - fetches all the people who are members of the
 *   institution.
 *
 * * ``"parent_insts"`` - fetches all the parent institutions. Note
 *   that currently all institutions have only one parent, but this may change
 *   in the future, and client applications should be prepared to handle
 *   multiple parents.
 *
 * * ``"child_insts"`` - fetches all the child institutions.
 *
 * * ``"inst_groups"`` - fetches all the groups that belong to the
 *   institution.
 *
 * * ``"members_groups"`` - fetches all the groups that form the
 *   institution's membership list.
 *
 * * ``"managed_by_groups"`` - fetches all the groups that manage the
 *   institution's data (commonly called "Editor" groups).
 *
 * As with person ``fetch`` parameters, the references may be used
 * in a chain by using the "dot" notation to fetch additional information
 * about referenced people, institutions or groups. For example
 * ``"all_members.email"`` will fetch the email addresses of all members
 * of the institution. For more information about what can be fetched from
 * referenced people and groups, refer to the documentation for
 * {@link PersonMethods} and {@link GroupMethods}.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class InstitutionMethods
{
    // The connection to the server
    private $conn;

    /**
     * Create a new InstitutionMethods object.
     *
     * @param ClientConnection $conn The ClientConnection object to use to
     * invoke methods on the server.
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Return a list of all the institution attribute schemes available.
     * The ``schemeid`` values of these schemes may be used in the
     * ``fetch`` parameter of other methods that return institutions.
     *
     * ``[ HTTP: GET /api/v1/inst/all-attr-schemes ]``
     *
     * @return IbisAttributeScheme[] All the available institution attribute schemes (in precedence
     * order).
     */
    public function allAttributeSchemes()
    {
        $pathParams = array();
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/all-attr-schemes',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attributeSchemes;
    }

    /**
     * Return a list of all institutions.
     *
     * By default, only a few basic details about each institution are
     * returned, but the optional ``fetch`` parameter may be used
     * to fetch additional attributes or references.
     *
     * ``[ HTTP: GET /api/v1/inst/all-insts ]``
     *
     * @param boolean $includeCancelled [optional] Whether or not to include cancelled
     * institutions. By default, only live institutions are returned.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisInstitution[] The requested institutions (in instid order).
     */
    public function allInsts($includeCancelled,
                             $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("includeCancelled" => $includeCancelled,
                             "fetch"            => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/all-insts',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institutions;
    }

    /**
     * Get the institutions with the specified IDs.
     *
     * By default, only a few basic details about each institution are
     * returned, but the optional ``fetch`` parameter may be used
     * to fetch additional attributes or references.
     *
     * The results are sorted by ID.
     *
     * NOTE: The URL path length is limited to around 8000 characters, and
     * an instid is up to 8 characters long. Allowing for comma separators
     * and URL encoding, this limits the number of institutions that this
     * method may fetch to around 700.
     *
     * NOTE: The institutions returned may include cancelled institutions.
     * It is the caller's repsonsibility to check their cancelled flags.
     *
     * ``[ HTTP: GET /api/v1/inst/list?instids=... ]``
     *
     * @param string $instids [required] A comma-separated list of instids.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisInstitution[] The requested institutions (in instid order).
     */
    public function listInsts($instids,
                              $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("instids" => $instids,
                             "fetch"   => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/list',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institutions;
    }

    /**
     * Search for institutions using a free text query string. This is the
     * same search function that is used in the Lookup web application.
     *
     * By default, only a few basic details about each institution are
     * returned, but the optional ``fetch`` parameter may be used
     * to fetch additional attributes or references.
     *
     * ``[ HTTP: GET /api/v1/inst/search?query=... ]``
     *
     * @param string $query [required] The search string.
     * @param boolean $approxMatches [optional] Flag to enable more approximate
     * matching in the search, causing more results to be returned. Defaults
     * to ``false``.
     * @param boolean $includeCancelled [optional] Flag to allow cancelled institutions
     * to be included. Defaults to ``false``.
     * @param string $attributes [optional] A comma-separated list of attributes to
     * consider when searching. If this is ``null`` (the default) then
     * all attribute schemes marked as searchable will be included.
     * @param int $offset [optional] The number of results to skip at the start
     * of the search. Defaults to 0.
     * @param int $limit [optional] The maximum number of results to return.
     * Defaults to 100.
     * @param string $orderBy [optional] The order in which to list the results.
     * This may be either ``"instid"`` or ``"name"`` (the default).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisInstitution[] The matching institutions.
     */
    public function search($query,
                           $approxMatches=null,
                           $includeCancelled=null,
                           $attributes=null,
                           $offset=null,
                           $limit=null,
                           $orderBy=null,
                           $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("query"            => $query,
                             "approxMatches"    => $approxMatches,
                             "includeCancelled" => $includeCancelled,
                             "attributes"       => $attributes,
                             "offset"           => $offset,
                             "limit"            => $limit,
                             "orderBy"          => $orderBy,
                             "fetch"            => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/search',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institutions;
    }

    /**
     * Count the number of institutions that would be returned by a search
     * using a free text query string.
     *
     * ``[ HTTP: GET /api/v1/inst/search-count?query=... ]``
     *
     * @param string $query [required] The search string.
     * @param boolean $approxMatches [optional] Flag to enable more approximate
     * matching in the search, causing more results to be returned. Defaults
     * to ``false``.
     * @param boolean $includeCancelled [optional] Flag to allow cancelled institutions
     * to be included. Defaults to ``false``.
     * @param string $attributes [optional] A comma-separated list of attributes to
     * consider when searching. If this is ``null`` (the default) then
     * all attribute schemes marked as searchable will be included.
     *
     * @return int The number of matching institutions.
     */
    public function searchCount($query,
                                $approxMatches=null,
                                $includeCancelled=null,
                                $attributes=null)
    {
        $pathParams = array();
        $queryParams = array("query"            => $query,
                             "approxMatches"    => $approxMatches,
                             "includeCancelled" => $includeCancelled,
                             "attributes"       => $attributes);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/search-count',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return intval($result->value);
    }

    /**
     * Get the institution with the specified ID.
     *
     * By default, only a few basic details about the institution are
     * returned, but the optional ``fetch`` parameter may be used
     * to fetch additional attributes or references of the institution.
     *
     * NOTE: The institution returned may be a cancelled institution. It is
     * the caller's repsonsibility to check its cancelled flag.
     *
     * ``[ HTTP: GET /api/v1/inst/{instid} ]``
     *
     * @param string $instid [required] The ID of the institution to fetch.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisInstitution The requested institution or ``null`` if it was not found.
     */
    public function getInst($instid,
                            $fetch=null)
    {
        $pathParams = array("instid" => $instid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/%1$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institution;
    }

    /**
     * Add an attribute to an institution. By default, this will not add the
     * attribute again if it already exists.
     *
     * When adding an attribute, the new attribute's scheme must be set.
     * In addition, either its value or its binaryData field should be set.
     * All the remaining fields of the attribute are optional.
     *
     * ``[ HTTP: POST /api/v1/inst/{instid}/add-attribute ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param IbisAttribute $attr [required] The new attribute to add.
     * @param int $position [optional] The position of the new attribute in the
     * list of attributes of the same attribute scheme (1, 2, 3,...). A value
     * of 0 (the default) will cause the new attribute to be added to the end
     * of the list of existing attributes for the scheme.
     * @param boolean $allowDuplicates [optional] If ``true``, the new attribute
     * will always be added, even if another identical attribute already
     * exists. If ``false`` (the default), the new attribute will only be
     * added if it doesn't already exist.
     * @param string $commitComment [recommended] A short textual description of
     * the change made (will be visible on the history tab in the web
     * application).
     *
     * @return IbisAttribute The newly created or existing attribute.
     */
    public function addAttribute($instid,
                                 $attr,
                                 $position=null,
                                 $allowDuplicates=null,
                                 $commitComment=null)
    {
        $pathParams = array("instid" => $instid);
        $queryParams = array();
        $formParams = array("attr"            => $attr,
                            "position"        => $position,
                            "allowDuplicates" => $allowDuplicates,
                            "commitComment"   => $commitComment);
        $result = $this->conn->invokeMethod("POST",
                                            'api/v1/inst/%1$s/add-attribute',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attribute;
    }

    /**
     * Get all the cancelled members of the specified institution.
     *
     * By default, only a few basic details about each member are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each person.
     *
     * NOTE: This method returns only cancelled people. It does not include
     * people who were removed from the institution. Cancelled people are no
     * longer considered to be current staff, students or accredited visitors,
     * and are no longer regarded as belonging to any groups or institutions.
     * The list returned here reflects their institutional memberships just
     * before they were cancelled, and so is out-of-date data that should be
     * used with caution.
     *
     * ``[ HTTP: GET /api/v1/inst/{instid}/cancelled-members ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch for each person.
     *
     * @return IbisPerson[] The institution's cancelled members (in identifier order).
     */
    public function getCancelledMembers($instid,
                                        $fetch=null)
    {
        $pathParams = array("instid" => $instid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/%1$s/cancelled-members',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Get all the contact rows of the specified institution.
     *
     * Any addresses, email addresses, phone numbers and web pages
     * associated with the contact rows are automatically returned, as
     * well as any people referred to by the contact rows.
     *
     * If any of the contact rows refer to people, then only a few basic
     * details about each person are returned, but the optional
     * ``fetch`` parameter may be used to fetch additional
     * attributes or references of each person.
     *
     * NOTE: This method will not include cancelled people.
     *
     * ``[ HTTP: GET /api/v1/inst/{instid}/contact-rows ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch for any people referred to by any
     * of the contact rows.
     *
     * @return IbisContactRow[] The institution's contact rows.
     */
    public function getContactRows($instid,
                                   $fetch=null)
    {
        $pathParams = array("instid" => $instid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/%1$s/contact-rows',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institution->contactRows;
    }

    /**
     * Get one or more (possibly multi-valued) attributes of an institution.
     * The returned attributes are sorted by attribute scheme precedence and
     * then attribute precedence.
     *
     * ``[ HTTP: GET /api/v1/inst/{instid}/get-attributes?attrs=... ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param string $attrs [required] The attribute scheme(s) to fetch. This may
     * include any number of the attributes or pseudo-attributes, but it
     * may not include references or attribute chains (see the documentation
     * for the ``fetch`` parameter in this class).
     *
     * @return IbisAttribute[] The requested attributes.
     */
    public function getAttributes($instid,
                                  $attrs)
    {
        $pathParams = array("instid" => $instid);
        $queryParams = array("attrs" => $attrs);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/%1$s/get-attributes',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attributes;
    }

    /**
     * Get all the members of the specified institution.
     *
     * By default, only a few basic details about each member are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each person.
     *
     * NOTE: This method will not include cancelled people.
     *
     * ``[ HTTP: GET /api/v1/inst/{instid}/members ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch for each person.
     *
     * @return IbisPerson[] The institution's members (in identifier order).
     */
    public function getMembers($instid,
                               $fetch=null)
    {
        $pathParams = array("instid" => $instid);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/%1$s/members',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Delete an attribute of an institution. It is not an error if the
     * attribute does not exist.
     *
     * Note that in this method, the ``commitComment`` is passed
     * as a query parameter, rather than as a form parameter, for greater
     * client compatibility.
     *
     * ``[ HTTP: DELETE /api/v1/inst/{instid}/{attrid} ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param int $attrid [required] The ID of the attribute to delete.
     * @param string $commitComment [recommended] A short textual description of
     * the change made (will be visible on the history tab in the web
     * application).
     *
     * @return boolean ``true`` if the attribute was deleted by this method, or
     * ``false`` if it did not exist.
     */
    public function deleteAttribute($instid,
                                    $attrid,
                                    $commitComment=null)
    {
        $pathParams = array("instid" => $instid,
                            "attrid" => $attrid);
        $queryParams = array("commitComment" => $commitComment);
        $formParams = array();
        $result = $this->conn->invokeMethod("DELETE",
                                            'api/v1/inst/%1$s/%2$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return strcasecmp($result->value, "true") == 0;
    }

    /**
     * Get a specific attribute of an institution.
     *
     * ``[ HTTP: GET /api/v1/inst/{instid}/{attrid} ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param int $attrid [required] The ID of the attribute to fetch.
     *
     * @return IbisAttribute The requested attribute.
     */
    public function getAttribute($instid,
                                 $attrid)
    {
        $pathParams = array("instid" => $instid,
                            "attrid" => $attrid);
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/inst/%1$s/%2$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attribute;
    }

    /**
     * Update an attribute of an institution.
     *
     * The attribute's value, binaryData, comment and effective date fields
     * will all be updated using the data supplied. All other fields will be
     * left unchanged.
     *
     * To avoid inadvertently changing fields of the attribute, it is
     * recommended that {@link getAttribute} be used to
     * retrieve the current value of the attribute, before calling this
     * method with the required changes.
     *
     * ``[ HTTP: PUT /api/v1/inst/{instid}/{attrid} ]``
     *
     * @param string $instid [required] The ID of the institution.
     * @param int $attrid [required] The ID of the attribute to update.
     * @param IbisAttribute $attr [required] The new attribute values to apply.
     * @param string $commitComment [recommended] A short textual description of
     * the change made (will be visible on the history tab in the web
     * application).
     *
     * @return IbisAttribute The updated attribute.
     */
    public function updateAttribute($instid,
                                    $attrid,
                                    $attr,
                                    $commitComment=null)
    {
        $pathParams = array("instid" => $instid,
                            "attrid" => $attrid);
        $queryParams = array();
        $formParams = array("attr"          => $attr,
                            "commitComment" => $commitComment);
        $result = $this->conn->invokeMethod("PUT",
                                            'api/v1/inst/%1$s/%2$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attribute;
    }
}
