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
 * Exception thrown when a web service API method fails. This is wrapper
 * around the {@link IbisError} object returned by the server, which contains
 * the full details of what went wrong.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisException extends Exception
{
    private $ibisError;

    /**
     * Construct a new IbisException wrapping the specified IbisError.
     *
     * @param IbisError $ibisError The error from the server.
     */
    public function __construct($ibisError)
    {
        parent::__construct($ibisError->message);
        $this->ibisError = $ibisError;
    }

    /**
     * Returns the underlying error from the server.
     *
     * @return IbisError The underlying error from the server.
     */
    public function getError()
    {
        return $this->ibisError;
    }
}
