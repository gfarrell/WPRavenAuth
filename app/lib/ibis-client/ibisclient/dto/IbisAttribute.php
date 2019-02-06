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
 * Class representing an attribute of a person or institution returned by
 * the web service API. Note that for institution attributes, the
 * {@link instid}, {@link visibility} and {@link owningGroupid} fields will
 * be ``null``.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisAttribute extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("attrid", "scheme", "instid",
                                       "visibility", "effectiveFrom",
                                       "effectiveTo", "owningGroupid");

     /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("value", "binaryData", "comment");

    /** @var int The unique internal identifier of the attribute. */
    public $attrid;

    /** @var string The attribute's scheme. */
    public $scheme;

    /** @var string The attribute's value (except for binary attributes). */
    public $value;

    /**
     * @var string The binary data held in the attribute (e.g., a JPEG
     * photo).
     */
    public $binaryData;

    /** @var string Any comment associated with the attribute. */
    public $comment;

    /**
     * @var string For a person attribute, the optional institution that the
     * attribute is associated with. This will not be set for institution
     * attributes.
     */
    public $instid;

    /**
     * @var string For a person attribute, it's visibility (``"private"``,
     * ``"institution"``, ``"university"`` or ``"world"``). This
     * will not be set for institution attributes.
     */
    public $visibility;

    /**
     * @var DateTime For time-limited attributes, the date from which it
     * takes effect.
     */
    public $effectiveFrom;

    /**
     * @var DateTime For time-limited attributes, the date after which it is
     * no longer effective.
     */
    public $effectiveTo;

    /**
     * @var string For a person attribute, the ID of the group that owns it
     * (typically the user agent group that created it).
     */
    public $owningGroupid;

    /**
     * @ignore
     * Create an IbisAttribute from the attributes of an XML node.
     *
     * @param array $attrs The attributes on the XML node.
     */
    public function __construct($attrs=array())
    {
        parent::__construct($attrs);
        if (isset($this->attrid))
            $this->attrid = (int )$this->attrid;
        if (isset($this->effectiveFrom))
            $this->effectiveFrom = new DateTime($this->effectiveFrom);
        if (isset($this->effectiveTo))
            $this->effectiveTo = new DateTime($this->effectiveTo);
    }

    /**
     * @ignore
     * Overridden end element callback to decode binary data.
     *
     * @param string $tagname The name of the XML element.
     * @param string $data The textual value of the XML element.
     * @return void.
     */
    public function endChildElement($tagname, $data)
    {
        parent::endChildElement($tagname, $data);
        if ($tagname === "binaryData" && isset($this->binaryData))
            $this->binaryData = base64_decode($this->binaryData);
    }

    /**
     * Encode this attribute as an ASCII string suitable for passing as a
     * parameter to a web service API method. This string is compatible with
     * ``valueOf(java.lang.String)`` on the corresponding Java class,
     * used on the server to decode the attribute parameter.
     *
     * NOTE: This requires that the attribute's {@link scheme} field be
     * set, and typically the {@link value} or {@link binaryData} should
     * also be set.
     *
     * @return string The string encoding of this attribute.
     */
    public function encodedString()
    {
        if (is_null($this->scheme))
            throw new Exception("Attribute scheme must be set");

        $result = "scheme:" . base64_encode($this->scheme);
        if (isset($this->attrid))
            $result .= ",attrid:" . $this->attrid;
        if (isset($this->value))
            $result .= ",value:" . base64_encode($this->value);
        if (isset($this->binaryData))
            $result .= ",binaryData:" . base64_encode($this->binaryData);
        if (isset($this->comment))
            $result .= ",comment:" . base64_encode($this->comment);
        if (isset($this->instid))
            $result .= ",instid:" . base64_encode($this->instid);
        if (isset($this->visibility))
            $result .= ",visibility:" . base64_encode($this->visibility);
        if (isset($this->effectiveFrom))
            $result .= ",effectiveFrom:" . $this->effectiveFrom->format("d M Y");
        if (isset($this->effectiveTo))
            $result .= ",effectiveTo:" . $this->effectiveTo->format("d M Y");
        if (isset($this->owningGroupid))
            $result .= ",owningGroupid:" . base64_encode($this->owningGroupid);
        return $result;
    }
}
