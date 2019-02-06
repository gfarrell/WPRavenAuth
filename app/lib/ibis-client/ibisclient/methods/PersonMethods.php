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
 * Methods for querying and manipulating people.
 *
 * **Notes on the fetch parameter**
 *
 * All methods that return people, institutions or groups also accept an
 * optional ``fetch`` parameter that may be used to request
 * additional information about the entities returned. Without this
 * parameter, only a few basic details about each person, institution or
 * group are returned. The ``fetch`` parameter is quite flexible,
 * and may be used in a number of different ways:
 *
 * * **Attribute fetching**. Attributes may be fetched by specifying the
 *   ``schemeid`` of an attribute scheme. For example to fetch a
 *   person's email addresses, use the value ``"email"``. For people common
 *   attribute schemes include ``"jpegPhoto"``, ``"misAffiliation"``,
 *   ``"title"``, ``"universityPhone"``, ``"mobexPhone"``,
 *   ``"landlinePhone"``, ``"mobilePhone"``, ``"pager"``,
 *   ``"labeledURI"`` and ``"address"``. The full list of person
 *   attribute schemes may be obtained using {@link allAttributeSchemes}.
 *
 * * **Pseudo-attributes**. Certain special pseudo-attributes are defined
 *   for convenience. For people, the following pseudo-attributes are supported:
 *
 *   * ``"phone_numbers"`` - fetches all phone numbers. This is
 *     equivalent to
 *     ``"universityPhone,instPhone,mobexPhone,landlinePhone,mobilePhone,pager"``.
 *
 *   * ``"all_identifiers"`` - fetches all identifiers. Currently people
 *     only have CRSid identifiers, but in the future additional identifiers such
 *     as USN or staffNumber may be added.
 *
 *   * ``"all_attrs"`` - fetches all attributes from all person attribute
 *     schemes. This does not include identifiers or references.
 *
 * * **Reference fetching**. For people, the following references are
 *   supported (and will fetch only non-cancelled institutions and groups):
 *
 *   * ``"all_insts"`` - fetches all the institutions to which the person
 *     belongs (sorted in name order).
 *
 *   * ``"all_groups"`` - fetches all the groups that the person is a
 *     member of, including indirect group memberships, via groups that include
 *     other groups.
 *
 *   * ``"direct_groups"`` - fetches all the groups that the person is
 *     directly a member of. This does not include indirect group memberships -
 *     i.e., groups that include these groups.
 *
 * * **Chained reference fetching**. To fetch properties of referenced
 *   objects, the "dot" notation may be used. For example, to fetch the email
 *   addresses of all the institutions to which a person belongs, use
 *   ``"all_insts.email"``. Chains may include a number of reference
 *   following steps, for example
 *   ``"all_insts.managed_by_groups.all_members.email"`` will fetch all the
 *   institutions to which the person belongs, all the groups that manage those
 *   institutions, all the visible members of those groups and all the email
 *   addresses of those managing group members. For more information about what
 *   can be fetched from referenced institutions and groups, refer to the
 *   documentation for {@link InstitutionMethods} and {@link GroupMethods}.
 *
 * Multiple values of the ``fetch`` parameter should be separated
 * by commas.
 *
 * **Fetch parameter examples**
 *
 * ``fetch = "email"``
 * This fetches all the person's email addresses.
 *
 * ``fetch = "title,address"``
 * This fetches all the person's titles (roles) and addresses.
 *
 * ``fetch = "all_attrs"``
 * This fetches all the person's attributes.
 *
 * ``fetch = "all_groups,all_insts"``
 * This fetches all the groups and institutions to which the person belongs.
 *
 * ``fetch = "all_insts.parent_insts"``
 * This fetches all the person's institutions, and their parent institutions.
 *
 * ``fetch = "all_insts.email,all_insts.all_members.email"``
 * This fetches all the person's institutions and their email addresses, and
 * all the members of those institutions, and the email addresses of all
 * those members.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class PersonMethods
{
    // The connection to the server
    private $conn;

    /**
     * Create a new PersonMethods object.
     *
     * @param ClientConnection $conn The ClientConnection object to use to
     * invoke methods on the server.
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Return a list of all the person attribute schemes available. The
     * ``schemeid`` values of these schemes may be used in the
     * ``fetch`` parameter of other methods that return people.
     *
     * NOTE: Some of these attribute schemes are not currently used (no
     * people have attribute values in the scheme). These schemes are
     * reserved for possible future use.
     *
     * ``[ HTTP: GET /api/v1/person/all-attr-schemes ]``
     *
     * @return IbisAttributeScheme[] All the available person attribute schemes (in precedence
     * order).
     */
    public function allAttributeSchemes()
    {
        $pathParams = array();
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/all-attr-schemes',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attributeSchemes;
    }

    /**
     * Get the people with the specified identifiers (typically CRSids).
     *
     * Each identifier may be either a CRSid, or an identifier from another
     * identifier scheme, prefixed with that scheme's name and a slash. For
     * example ``"mug99"`` or ``"usn/123456789"``.
     *
     * By default, only a few basic details about each person are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references.
     *
     * The results are sorted by identifier scheme and value.
     *
     * NOTE: The number of people that may be fetched in a single call is
     * limited by the URL path length limit (around 8000 characters). A
     * CRSid is up to 7 characters long, and other identifiers are typically
     * longer, since they must also include the identifier scheme. Thus the
     * number of people that this method may fetch is typically limited to a
     * few hundred.
     *
     * NOTE: The people returned may include cancelled people. It is the
     * caller's repsonsibility to check their cancelled flags.
     *
     * ``[ HTTP: GET /api/v1/person/list?crsids=... ]``
     *
     * @param string $crsids [required] A comma-separated list of identifiers.
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisPerson[] The requested people (in identifier order).
     */
    public function listPeople($crsids,
                               $fetch=null)
    {
        $pathParams = array();
        $queryParams = array("crsids" => $crsids,
                             "fetch"  => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/list',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Search for people using a free text query string. This is the same
     * search function that is used in the Lookup web application.
     *
     * By default, only a few basic details about each person are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references.
     *
     * ``[ HTTP: GET /api/v1/person/search?query=... ]``
     *
     * @param string $query [required] The search string.
     * @param boolean $approxMatches [optional] Flag to enable more approximate
     * matching in the search, causing more results to be returned. Defaults
     * to ``false``.
     * @param boolean $includeCancelled [optional] Flag to allow cancelled people to
     * be included (people who are no longer members of the University).
     * Defaults to ``false``.
     * @param string $misStatus [optional] The type of people to search for. This may
     * be
     *
     * * ``"staff"`` - only include people whose MIS status is
     *   ``""`` (empty string), ``"staff"``, or
     *   ``"staff,student"``.
     *
     * * ``"student"`` - only include people whose MIS status is set to
     *   ``"student"`` or ``"staff,student"``.
     *
     * Otherwise all matching people will be included (the default). Note
     * that the ``"staff"`` and ``"student"`` options are not
     * mutually exclusive.
     * @param string $attributes [optional] A comma-separated list of attributes to
     * consider when searching. If this is ``null`` (the default) then
     * all attribute schemes marked as searchable will be included.
     * @param int $offset [optional] The number of results to skip at the start
     * of the search. Defaults to 0.
     * @param int $limit [optional] The maximum number of results to return.
     * Defaults to 100.
     * @param string $orderBy [optional] The order in which to list the results.
     * This may be either ``"identifier"`` or ``"surname"`` (the
     * default).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisPerson[] The matching people.
     */
    public function search($query,
                           $approxMatches=null,
                           $includeCancelled=null,
                           $misStatus=null,
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
                             "misStatus"        => $misStatus,
                             "attributes"       => $attributes,
                             "offset"           => $offset,
                             "limit"            => $limit,
                             "orderBy"          => $orderBy,
                             "fetch"            => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/search',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->people;
    }

    /**
     * Count the number of people that would be returned by a search using
     * a free text query string.
     *
     * ``[ HTTP: GET /api/v1/person/search-count?query=... ]``
     *
     * @param string $query [required] The search string.
     * @param boolean $approxMatches [optional] Flag to enable more approximate
     * matching in the search, causing more results to be returned. Defaults
     * to ``false``.
     * @param boolean $includeCancelled [optional] Flag to allow cancelled people to
     * be included (people who are no longer members of the University).
     * Defaults to ``false``.
     * @param string $misStatus [optional] The type of people to search for. This may
     * be
     *
     * * ``"staff"`` - only include people whose MIS status is
     *   ``""`` (empty string), ``"staff"``, or
     *   ``"staff,student"``.
     *
     * * ``"student"`` - only include people whose MIS status is set to
     *   ``"student"`` or ``"staff,student"``.
     *
     * Otherwise all matching people will be included (the default). Note
     * that the ``"staff"`` and ``"student"`` options are not
     * mutually exclusive.
     * @param string $attributes [optional] A comma-separated list of attributes to
     * consider when searching. If this is ``null`` (the default) then
     * all attribute schemes marked as searchable will be included.
     *
     * @return int The number of matching people.
     */
    public function searchCount($query,
                                $approxMatches=null,
                                $includeCancelled=null,
                                $misStatus=null,
                                $attributes=null)
    {
        $pathParams = array();
        $queryParams = array("query"            => $query,
                             "approxMatches"    => $approxMatches,
                             "includeCancelled" => $includeCancelled,
                             "misStatus"        => $misStatus,
                             "attributes"       => $attributes);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/search-count',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return intval($result->value);
    }

    /**
     * Get the person with the specified identifier.
     *
     * By default, only a few basic details about the person are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of the person.
     *
     * NOTE: The person returned may be a cancelled person. It is the
     * caller's repsonsibility to check its cancelled flag.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier} ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person to fetch
     * (typically their CRSid).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisPerson The requested person or ``null`` if they were not found.
     */
    public function getPerson($scheme,
                              $identifier,
                              $fetch=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->person;
    }

    /**
     * Add an attribute to a person. By default, this will not add the
     * attribute again if it already exists.
     *
     * When adding an attribute, the new attribute's scheme must be set.
     * In addition, either its value or its binaryData field should be set.
     * All the remaining fields of the attribute are optional.
     *
     * ``[ HTTP: POST /api/v1/person/{scheme}/{identifier}/add-attribute ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person to udpate
     * (typically their CRSid).
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
    public function addAttribute($scheme,
                                 $identifier,
                                 $attr,
                                 $position=null,
                                 $allowDuplicates=null,
                                 $commitComment=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array();
        $formParams = array("attr"            => $attr,
                            "position"        => $position,
                            "allowDuplicates" => $allowDuplicates,
                            "commitComment"   => $commitComment);
        $result = $this->conn->invokeMethod("POST",
                                            'api/v1/person/%1$s/%2$s/add-attribute',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attribute;
    }

    /**
     * Get one or more (possibly multi-valued) attributes of a person. The
     * returned attributes are sorted by attribute scheme precedence and
     * then attribute precedence.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/get-attributes?attrs=... ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $attrs [required] The attribute scheme(s) to fetch. This may
     * include any number of the attributes or pseudo-attributes, but it
     * may not include references or attribute chains (see the documentation
     * for the ``fetch`` parameter in this class).
     *
     * @return IbisAttribute[] The requested attributes.
     */
    public function getAttributes($scheme,
                                  $identifier,
                                  $attrs)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array("attrs" => $attrs);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/get-attributes',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attributes;
    }

    /**
     * Get all the groups to which the specified person belongs, including
     * indirect group memberships, via groups that include other groups.
     * The returned list of groups is sorted by groupid.
     *
     * Note that some group memberships may not be visible to you. This
     * method will only return those group memberships that you have
     * permission to see.
     *
     * By default, only a few basic details about each group are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each group.
     *
     * NOTE: This method will not include cancelled groups.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/groups ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisGroup[] The person's groups (in groupid order).
     */
    public function getGroups($scheme,
                              $identifier,
                              $fetch=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/groups',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->groups;
    }

    /**
     * Get all the institutions to which the specified person belongs. The
     * returned list of institutions is sorted by name.
     *
     * By default, only a few basic details about each institution are
     * returned, but the optional ``fetch`` parameter may be used
     * to fetch additional attributes or references of each institution.
     *
     * NOTE: This method will not include cancelled institutions.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/insts ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisInstitution[] The person's institutions (in name order).
     */
    public function getInsts($scheme,
                             $identifier,
                             $fetch=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/insts',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institutions;
    }

    /**
     * Test if the specified person is a member of the specified group.
     *
     * NOTE: This may be used with cancelled people and groups.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/is-member-of-group/{groupid} ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $groupid [required] The ID or name of the group.
     *
     * @return boolean ``true`` if the specified person is in the specified
     * group, ``false`` otherwise (or if the person or group does not
     * exist).
     */
    public function isMemberOfGroup($scheme,
                                    $identifier,
                                    $groupid)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier,
                            "groupid"    => $groupid);
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/is-member-of-group/%3$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return strcasecmp($result->value, "true") == 0;
    }

    /**
     * Test if the specified person is a member of the specified institution.
     *
     * NOTE: This may be used with cancelled people and institutions, but
     * it will not include cancelled membership groups.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/is-member-of-inst/{instid} ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $instid [required] The ID of the institution.
     *
     * @return boolean ``true`` if the specified person is in the specified
     * institution, ``false`` otherwise (or if the person or institution
     * does not exist).
     */
    public function isMemberOfInst($scheme,
                                   $identifier,
                                   $instid)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier,
                            "instid"     => $instid);
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/is-member-of-inst/%3$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return strcasecmp($result->value, "true") == 0;
    }

    /**
     * Get all the groups that the specified person has persmission to edit.
     * The returned list of groups is sorted by groupid.
     *
     * Note that some group memberships may not be visible to you. This
     * method will only include groups for which you have persmission to
     * see the applicable manager group memberships.
     *
     * By default, only a few basic details about each group are returned,
     * but the optional ``fetch`` parameter may be used to fetch
     * additional attributes or references of each group.
     *
     * NOTE: This method will not include cancelled groups.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/manages-groups ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisGroup[] The groups that the person manages (in groupid order).
     */
    public function getManagedGroups($scheme,
                                     $identifier,
                                     $fetch=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/manages-groups',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->groups;
    }

    /**
     * Get all the institutions that the specified person has permission to
     * edit. The returned list of institutions is sorted by name.
     *
     * Note that some group memberships may not be visible to you. This
     * method will only include institutions for which you have permission
     * to see the applicable editor group memberships.
     *
     * By default, only a few basic details about each institution are
     * returned, but the optional ``fetch`` parameter may be used
     * to fetch additional attributes or references of each institution.
     *
     * NOTE: This method will not include cancelled institutions.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/manages-insts ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param string $fetch [optional] A comma-separated list of any additional
     * attributes or references to fetch.
     *
     * @return IbisInstitution[] The institutions that the person manages (in name order).
     */
    public function getManagedInsts($scheme,
                                    $identifier,
                                    $fetch=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier);
        $queryParams = array("fetch" => $fetch);
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/manages-insts',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->institutions;
    }

    /**
     * Delete an attribute of a person. It is not an error if the attribute
     * does not exist.
     *
     * Note that in this method, the ``commitComment`` is passed
     * as a query parameter, rather than as a form parameter, for greater
     * client compatibility.
     *
     * ``[ HTTP: DELETE /api/v1/person/{scheme}/{identifier}/{attrid} ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person to udpate
     * (typically their CRSid).
     * @param int $attrid [required] The ID of the attribute to delete.
     * @param string $commitComment [recommended] A short textual description of
     * the change made (will be visible on the history tab in the web
     * application).
     *
     * @return boolean ``true`` if the attribute was deleted by this method, or
     * ``false`` if it did not exist.
     */
    public function deleteAttribute($scheme,
                                    $identifier,
                                    $attrid,
                                    $commitComment=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier,
                            "attrid"     => $attrid);
        $queryParams = array("commitComment" => $commitComment);
        $formParams = array();
        $result = $this->conn->invokeMethod("DELETE",
                                            'api/v1/person/%1$s/%2$s/%3$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return strcasecmp($result->value, "true") == 0;
    }

    /**
     * Get a specific attribute of a person.
     *
     * ``[ HTTP: GET /api/v1/person/{scheme}/{identifier}/{attrid} ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person (typically
     * their CRSid).
     * @param int $attrid [required] The ID of the attribute to fetch.
     *
     * @return IbisAttribute The requested attribute.
     */
    public function getAttribute($scheme,
                                 $identifier,
                                 $attrid)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier,
                            "attrid"     => $attrid);
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/person/%1$s/%2$s/%3$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attribute;
    }

    /**
     * Update an attribute of a person.
     *
     * The attribute's value, binaryData, comment, instid and effective date
     * fields will all be updated using the data supplied. All other fields
     * will be left unchanged.
     *
     * To avoid inadvertently changing fields of the attribute, it is
     * recommended that {@link getAttribute} be used to
     * retrieve the current value of the attribute, before calling this
     * method with the required changes.
     *
     * ``[ HTTP: PUT /api/v1/person/{scheme}/{identifier}/{attrid} ]``
     *
     * @param string $scheme [required] The person identifier scheme. Typically this
     * should be ``"crsid"``, but other identifier schemes may be
     * available in the future, such as ``"usn"`` or
     * ``"staffNumber"``.
     * @param string $identifier [required] The identifier of the person to udpate
     * (typically their CRSid).
     * @param int $attrid [required] The ID of the attribute to update.
     * @param IbisAttribute $attr [required] The new attribute values to apply.
     * @param string $commitComment [recommended] A short textual description of
     * the change made (will be visible on the history tab in the web
     * application).
     *
     * @return IbisAttribute The updated attribute.
     */
    public function updateAttribute($scheme,
                                    $identifier,
                                    $attrid,
                                    $attr,
                                    $commitComment=null)
    {
        $pathParams = array("scheme"     => $scheme,
                            "identifier" => $identifier,
                            "attrid"     => $attrid);
        $queryParams = array();
        $formParams = array("attr"          => $attr,
                            "commitComment" => $commitComment);
        $result = $this->conn->invokeMethod("PUT",
                                            'api/v1/person/%1$s/%2$s/%3$s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->attribute;
    }
}
