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

/**
 * Class representing an attribute scheme. This may apply to attributes of
 * people or institutions.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisAttributeScheme extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("schemeid", "precedence", "multiValued",
                                       "multiLined", "searchable");

     /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("ldapName", "displayName",
                                       "dataType", "regexp");

    /** @var string The unique identifier of the attribute scheme. */
    public $schemeid;

    /**
     * @var int The attribute scheme's precedence. Methods that return or
     * display attributes sort the results primarily in order of increasing
     * values of attribute scheme precedence.
     */
    public $precedence;

    /**
     * @var string The name of the attribute scheme in LDAP, if it is
     * exported to LDAP. Note that many attributes are not exported to LDAP,
     * in which case this name is typically just equal to the scheme's ID.
     */
    public $ldapName;

    /**
     * @var string The display name for labelling attributes in this scheme.
     */
    public $displayName;

    /** @var string The attribute scheme's datatype. */
    public $dataType;

    /**
     * @var boolean Flag indicating whether attributes in this scheme can be
     * multi-valued.
     */
    public $multiValued;

    /**
     * @var boolean Flag for textual attributes schemes indicating whether
     * they are multi-lined.
     */
    public $multiLined;

    /**
     * @var boolean Flag indicating whether attributes of this scheme are
     * searched by the default search functionality.
     */
    public $searchable;

    /**
     * @var string For textual attributes, an optional regular expression
     * that all attributes in this scheme match.
     */
    public $regexp;

    /**
     * @ignore
     * Create an IbisAttributeScheme from the attributes of an XML node.
     *
     * @param array $attrs The attributes on the XML node.
     */
    public function __construct($attrs=array())
    {
        parent::__construct($attrs);
        if (isset($this->precedence))
            $this->precedence = strcasecmp($this->precedence, "true") == 0;
        if (isset($this->multiValued))
            $this->multiValued = strcasecmp($this->multiValued, "true") == 0;
        if (isset($this->multiLined))
            $this->multiLined = strcasecmp($this->multiLined, "true") == 0;
        if (isset($this->searchable))
            $this->searchable = strcasecmp($this->searchable, "true") == 0;
    }
}
