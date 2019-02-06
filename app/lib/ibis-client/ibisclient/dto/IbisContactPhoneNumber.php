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
 * Class representing a phone number held on an institution contact row, for
 * use by the web service API.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisContactPhoneNumber extends IbisDto
{
    /* Properties marked as @XmlAttribte in the JAXB class */
    protected static $xmlAttrs = array("phoneType");

    /* Properties marked as @XmlElement in the JAXB class */
    protected static $xmlElems = array("number", "comment");

    /** @var string The phone number's type. */
    public $phoneType;

    /** @var string The phone number. */
    public $number;

    /** @var string Any comment associated with the phone number. */
    public $comment;
}
