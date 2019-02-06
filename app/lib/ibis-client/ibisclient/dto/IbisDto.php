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

/**
 * Abstract base class for all DTO classes. This defines a couple of methods
 * used when unmarshalling DTOs from XML.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
abstract class IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array(); // Set in sub-classes

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array(); // Set in sub-classes

    /* Properties marked as @XmlElementWrapper in the JAXB class */
    protected static $xmlArrays = array(); // Set in sub-classes

    /**
     * @ignore
     * Create an IbisDto from the attributes of an XML node. This just sets
     * the properties marked as @XmlAttribute in the JAXB class.
     *
     * @param array $attrs The attributes on the XML node.
     */
    public function __construct($attrs=array())
    {
        foreach (static::$xmlAttrs as $attr)
            $this->$attr = isset($attrs[$attr]) ? $attrs[$attr] : null;
    }

    /**
     * @ignore
     * Start element callback invoked during XML parsing when the opening
     * tag of a child element is encountered. This creates and returns any
     * properties marked as @XmlElementWrapper in the JAXB class, so that
     * child collections can be populated.
     *
     * @param string $tagname The name of the XML wrapper element.
     * @return IbisDto[] A reference to the array of child elements
     * corresponding to the XML wrapper element.
     */
    public function &startChildElement($tagname)
    {
        if (in_array($tagname, static::$xmlArrays, true))
        {
            if (is_null($this->$tagname)) $this->$tagname = array();
            return $this->$tagname;
        }

        // Keep PHP quiet (must return a reference to a variable)
        $dummy = null;
        return $dummy;
    }

    /**
     * @ignore
     * End element callback invoked during XML parsing when the end tag of
     * a child element is encountered, and the tag's data is available. This
     * sets the value of any properties marked as @XmlElement in the JAXB
     * class.
     *
     * @param string $tagname The name of the XML element.
     * @param string $data The textual value of the XML element.
     * @return void.
     */
    public function endChildElement($tagname, $data)
    {
        if (in_array($tagname, static::$xmlElems, true))
            $this->$tagname = $data;
    }
}
