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
 * Class representing an error returned by the web service API.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisError extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("status");

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("code", "message", "details");

    /** @var int The HTTP error status code. */
    public $status;

    /** @var string A short textual description of the error status code. */
    public $code;

    /**
     * @var string A short textual description of the error message
     * (typically one line).
     */
    public $message;

    /**
     * @var string The full details of the error (e.g., a Java stack trace).
     */
    public $details;

    /**
     * @ignore
     * Create an IbisError from the attributes of an XML node.
     *
     * @param array $attrs The attributes on the XML node.
     */
    public function __construct($attrs=array())
    {
        parent::__construct($attrs);
        if (isset($this->status))
            $this->status = (int )$this->status;
    }
}
