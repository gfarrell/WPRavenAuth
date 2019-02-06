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
require_once "IbisPerson.php";

/**
 * Class representing an institution contact row, for use by the web
 * services API.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisContactRow extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("bold", "italic");

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("description");

    /* Properties marked as @XmlElementWrapper in the JAXB class */
    protected static $xmlArrays = array("addresses", "emails", "people",
                                        "phoneNumbers", "webPages");

    /** @var string The contact row's text. */
    public $description;

    /**
     * @var boolean Flag indicating if the contact row's text is normally
     * displayed in bold.
     */
    public $bold;

    /**
     * @var boolean Flag indicating if the contact row's text is normally
     * displayed in italics.
     */
    public $italic;

    /**
     * @var string[] A list of the contact row's addresses. This will always
     * be non-null, but may be an empty list.
     */
    public $addresses;

    /**
     * @var string[] A list of the contact row's email addresses. This will
     * always be non-null, but may be an empty list.
     */
    public $emails;

    /**
     * @var IbisPerson[] A list of the people referred to by the contact row.
     * This will always be non-null, but may be an empty list.
     */
    public $people;

    /**
     * @var IbisContactPhoneNumber[] A list of the contact row's phone
     * numbers. This will always be non-null, but may be an empty list.
     */
    public $phoneNumbers;

    /**
     * @var IbisContactWebPage[] A list of the contact row's web pages. This
     * will always be non-null, but may be an empty list.
     */
    public $webPages;

    /* Flag to prevent infinite recursion due to circular references. */
    private $unflattened;

    /**
     * @ignore
     * Create an IbisContactRow from the attributes of an XML node.
     *
     * @param array $attrs The attributes on the XML node.
     */
    public function __construct($attrs=array())
    {
        parent::__construct($attrs);
        if (isset($this->bold))
            $this->bold = strcasecmp($this->bold, "true") == 0;
        if (isset($this->italic))
            $this->italic = strcasecmp($this->italic, "true") == 0;
        $this->unflattened = false;
    }

    /**
     * @ignore
     * Unflatten a single IbisContactRow.
     *
     * @param IbisResultEntityMap $em The mapping from IDs to entities.
     */
    public function unflatten($em)
    {
        if (!$this->unflattened)
        {
            $this->unflattened = true;
            IbisPerson::unflattenPeople($em, $this->people);
        }
        return $this;
    }

    /**
     * @ignore
     * Unflatten a list of IbisContactRow objects (done in place).
     *
     * @param IbisResultEntityMap $em The mapping from IDs to entities.
     * @param IbisContactRow[] $contactRows The contact rows to unflatten. 
     */
    public static function unflattencontactRows($em, &$contactRows)
    {
        if (isset($contactRows))
            foreach ($contactRows as $idx => $contactRow)
                $contactRows[$idx] = $contactRow->unflatten($em);
    }
}
