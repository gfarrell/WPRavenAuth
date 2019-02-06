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
 * Common methods for searching for objects in the Lookup/Ibis database.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisMethods
{
    // The connection to the server
    private $conn;

    /**
     * Create a new IbisMethods object.
     *
     * @param ClientConnection $conn The ClientConnection object to use to
     * invoke methods on the server.
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get the current API version number.
     *
     * ``[ HTTP: GET /api/v1/version ]``
     *
     * @return String The API version number string.
     */
    public function getVersion()
    {
        $pathParams = array();
        $queryParams = array();
        $formParams = array();
        $result = $this->conn->invokeMethod("GET",
                                            'api/v1/version',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return $result->value;
    }
}
