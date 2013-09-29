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

require_once "IbisAttribute.php";
require_once "IbisAttributeScheme.php";
require_once "IbisContactPhoneNumber.php";
require_once "IbisContactRow.php";
require_once "IbisContactWebPage.php";
require_once "IbisDto.php";
require_once "IbisError.php";
require_once "IbisGroup.php";
require_once "IbisIdentifier.php";
require_once "IbisInstitution.php";
require_once "IbisPerson.php";

/**
 * Class representing the top-level container for all XML and JSON results.
 * This may be just a simple textual value or it may contain more complex
 * entities such as people, institutions, groups, attributes, etc.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisResult extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("version");

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("value", "person", "institution",
                                       "group", "identifier", "attribute",
                                       "error", "entities");

    /* Properties marked as @XmlElementWrapper in the JAXB class */
    protected static $xmlArrays = array("people", "institutions", "groups",
                                        "attributes", "attributeSchemes");

    /** The web service API version number. */
    public $version;

    /** The value returned by methods that return a simple textual value. */
    public $value;

    /**
     * The person returned by methods that return a single person.
     *
     * Note that methods that may return multiple people will always use
     * the {@link #people} field, even if only one person was returned.
     */
    public $person;

    /**
     * The institution returned by methods that return a single institution.
     *
     * Note that methods that may return multiple institutions will always
     * use the {@link #institutions} field, even if only one institution
     * was returned.
     */
    public $institution;

    /**
     * The group returned by methods that return a single group.
     *
     * Note that methods that may return multiple groups will always use
     * the {@link #groups} field, even if only one group was returned.
     */
    public $group;

    /** The identifier returned by methods that return a single identifier. */
    public $identifier;

    /**
     * The person or institution attribute returned by methods that return
     * a single attribute.
     */
    public $attribute;

    /** If the method failed, details of the error. */
    public $error;

    /**
     * The list of people returned by methods that may return multiple
     * people. This may be empty, or contain one or more people.
     */
    public $people;

    /**
     * The list of institutions returned by methods that may return multiple
     * institutions. This may be empty, or contain one or more institutions.
     */
    public $institutions;

    /**
     * The list of groups returned by methods that may return multiple
     * groups. This may be empty, or contain one or more groups.
     */
    public $groups;

    /**
     * The list of attributes returned by methods that return lists of
     * person/institution attributes.
     */
    public $attributes;

    /**
     * The list of attribute schemes returned by methods that return lists
     * of person/institution attribute schemes.
     */
    public $attributeSchemes;

    /**
     * In the flattened XML/JSON representation, all the unique entities
     * returned by the method.
     *
     * NOTE: This will be null unless the "flatten" parameter is true.
     */
    public $entities;

    /**
     * Unflatten this IbisResult object, resolving any internal ID refs
     * to build a fully fledged object tree.
     *
     * This is necessary if the IbisResult was constructed from XML/JSON in
     * its flattened representation (with the "flatten" parameter set to
     * true).
     *
     * On entry, the IbisResult object may have people, institutions or
     * groups in it with "ref" fields referring to objects held in the
     * "entities" lists. After unflattening, all such references will have
     * been replaced by actual object references, giving an object tree that
     * can be traversed normally.
     *
     * @return IbisResult This IbisResult object, with its internals
     * unflattened.
     */
    public function unflatten()
    {
        if (isset($this->entities))
        {
            $em = new IbisResultEntityMap($this);

            if (isset($this->person))
                $this->person = $this->person->unflatten($em);
            if (isset($this->institution))
                $this->institution = $this->institution->unflatten($em);
            if (isset($this->group))
                $this->group = $this->group->unflatten($em);

            IbisPerson::unflattenPeople($em, $this->people);
            IbisInstitution::unflattenInsts($em, $this->institutions);
            IbisGroup::unflattenGroups($em, $this->groups);
        }
        return $this;
    }
}

/**
 * Class to hold the full details of all the entities returned in a result
 * (a nested class in Java and Python). This is used only in the flattened
 * result representation, where each of these entities will have a unique
 * textual ID, and be referred to from the top-level objects returned (and
 * by each other).
 *
 * In the hierarchical representation, this is not used, since all entities
 * returned will be at the top-level, or directly contained in those
 * top-level entities.
 */
class IbisResultEntities extends IbisDto
{
    /* Properties marked as @XmlElementWrapper in the JAXB class */
    protected static $xmlArrays = array("people", "institutions", "groups");

    /**
     * A list of all the unique people returned by the method. This may
     * include additional people returned as a result of the
     * <code>fetch</code> parameter, so this list may contain more
     * entries than the corresponding field on the enclosing class.
     */
    public $people;

    /**
     * A list of all the unique institutions returned by the method.
     * This may include additional institutions returned as a result
     * of the <code>fetch</code> parameter, so this list may contain
     * more entries than the corresponding field on the enclosing class.
     */
    public $institutions;

    /**
     * A list of all the unique groups returned by the method. This may
     * include additional groups returned as a result of the
     * <code>fetch</code> parameter, so this list may contain more
     * entries than the corresponding field on the enclosing class.
     */
    public $groups;
}

/**
 * Class to assist during the unflattening process, maintaining efficient
 * maps from IDs to entities (people, institutions and groups). This is a
 * nested class of IbisResult in Java and Python.
 */
class IbisResultEntityMap
{
    private $peopleById;
    private $instsById;
    private $groupsById;

    /** Construct an entity map from a flattened IbisResult. */
    public function __construct($result)
    {
        $this->peopleById = array();
        $this->instsById = array();
        $this->groupsById = array();

        if (isset($result->entities->people))
            foreach ($result->entities->people as $person)
                $this->peopleById[$person->id] = $person;
        if (isset($result->entities->institutions))
            foreach ($result->entities->institutions as $inst)
                $this->instsById[$inst->id] = $inst;
        if (isset($result->entities->groups))
            foreach ($result->entities->groups as $group)
                $this->groupsById[$group->id] = $group;
    }

    /** Get a person from the entity map, given their ID */
    public function getPerson($id) { return $this->peopleById[$id]; }

    /** Get an institution from the entity map, given its ID */
    public function getInstitution($id) { return $this->instsById[$id]; }

    /** Get a group from the entity map, given its ID */
    public function getGroup($id) { return $this->groupsById[$id]; }
}

/**
 * Class to hold a XML text node's value during XML parsing.
 */
class XmlTextNode
{
    public $tagname;
    public $data;
}

/**
 * Class to parse the XML from the server and produce an IbisResult.
 */
class IbisResultParser
{
    /** The IbisResult produced from the XML */
    private $result;

    /** Stack of nodes during XML parsing */
    private $nodeStack;

    /** Start element callback function for XML parsing */
    public function startElement($parser, $tagname, $attrs)
    {
        $element = null;
        if (!empty($this->nodeStack))
        {
            if ($tagname === "person")
                $element = new IbisPerson($attrs);
            elseif ($tagname === "institution")
                $element = new IbisInstitution($attrs);
            elseif ($tagname === "group")
                $element = new IbisGroup($attrs);
            elseif ($tagname === "identifier")
                $element = new IbisIdentifier($attrs);
            elseif ($tagname === "attribute")
                $element = new IbisAttribute($attrs);
            elseif ($tagname === "error")
                $element = new IbisError($attrs);
            elseif ($tagname === "attributeScheme")
                $element = new IbisAttributeScheme($attrs);
            elseif ($tagname === "contactRow")
                $element = new IbisContactRow($attrs);
            elseif ($tagname === "phoneNumber")
                $element = new IbisContactPhoneNumber($attrs);
            elseif ($tagname === "webPage")
                $element = new IbisContactWebPage($attrs);
            elseif ($tagname === "entities")
                $element = new IbisResultEntities($attrs);
            else
            {
                $parent = end($this->nodeStack);
                if (!is_array($parent))
                    // Need a reference to the parent's child array
                    $element = &$parent->startChildElement($tagname);
            }

            if (is_null($element))
            {
                $element = new XmlTextNode();
                $element->tagname = $tagname;
            }
        }
        elseif ($tagname !== "result")
            throw new Exception("Invalid root element: '" . $tagname . "'");
        else
        {
            $element = new IbisResult($attrs);
            $this->result = $element;
        }

        // Stack the new element. If it is an array, we must stack a
        // reference to it that we can modify.
        if (is_array($element)) $this->nodeStack[] = &$element;
        else $this->nodeStack[] = $element;
    }

    /** End element callback function for XML parsing */
    public function endElement($parser, $tagname)
    {
        if (!empty($this->nodeStack))
        {
            $element = array_pop($this->nodeStack);
            if (!empty($this->nodeStack))
            {
                if (is_array(end($this->nodeStack)))
                {
                    // Add the child to the parent's child array, which
                    // means that we must use an array reference
                    $parent = &$this->nodeStack[sizeof($this->nodeStack)-1];
                    $parent[] = $element instanceof XmlTextNode ?
                                $element->data : $element;
                }
                elseif (!(end($this->nodeStack) instanceof XmlTextNode))
                {
                    $parent = end($this->nodeStack);
                    $parent->endChildElement($tagname,
                                             $element instanceof XmlTextNode ?
                                             $element->data : $element);
                }
            }
        }
        else
            throw new Exception("Unexpected closing tag: '" . $tagname . "'");
    }

    /** Character data callback function for XML parsing */
    public function charData($parser, $data)
    {
        if (!empty($this->nodeStack))
        {
            $element = end($this->nodeStack);
            if ($element instanceof IbisIdentifier)
            {
                if (isset($element->value)) $element->value .= $data;
                else $element->value = $data;
            }
            elseif ($element instanceof XmlTextNode)
            {
                if (isset($element->data)) $element->data .= $data;
                else $element->data = $data;
            }
        }
    }

    /* Parse XML data from the specified string and return an IbisResult */
    public function parseXml($data)
    {
        $parser = xml_parser_create();
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, "startElement", "endElement");
        xml_set_character_data_handler($parser, "charData");
        xml_parser_set_option ($parser, XML_OPTION_CASE_FOLDING, false);

        $this->result = null;
        $this->nodeStack = array();

        xml_parse($parser, $data);
        xml_parser_free($parser);

        return $this->result->unflatten();
    }

    /* Parse XML data from the specified string and return an IbisResult */
    public function parseXmlFile($file)
    {
        $parser = xml_parser_create();
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, "startElement", "endElement");
        xml_set_character_data_handler($parser, "charData");
        xml_parser_set_option ($parser, XML_OPTION_CASE_FOLDING, false);

        $this->result = null;
        $this->nodeStack = array();

        while ($data = fread($file, 4096))
            xml_parse($parser, $data, feof($file));
        xml_parser_free($parser);

        return $this->result->unflatten();
    }
}
