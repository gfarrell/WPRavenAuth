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
 * Interface representing a connection to the Lookup/Ibis server, capable
 * of invoking methods on the web service API and unmarshalling the results.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
interface ClientConnection
{
    /**
     * Set the username to use when connecting to the Lookup/Ibis web service.
     * By default connections are anonymous, which gives read-only access.
     * This method enables authentication as a group, using the group's
     * password, which gives read/write access and also access to certain
     * non-public data, based on the group's privileges.
     *
     * This method may be called at any time, and affects all subsequent
     * access using this connection, but should not affect any other
     * ClientConnection objects.
     *
     * @param string $username The username to connect as. This should either
     * be ``"anonymous"`` (the default) or the name of a group.
     * @return void
     */
    public function setUsername($username);

    /**
     * Set the password to use when connecting to the Lookup/Ibis web service.
     * This is only necessary when connecting as a group, in which case it
     * should be that group's password.
     *
     * @param string $password The group password.
     * @return void
     */
    public function setPassword($password);

    /**
     * Invoke a web service GET method.
     *
     * The path should be the relative path to the method with standard
     * Java/PHP format specifiers for any path parameters, for example
     * ``"api/v1/person/%1$s/%2$s"``. Any path parameters specified
     * are then substituted into the path according to the standard Java
     * formatting rules.
     *
     * @param string $path The path to the method to invoke.
     * @param string[] $pathParams Any path parameters that should be inserted
     * into the path in place of any format specifiers.
     * @param array $queryParams Any query parameters to add as part of the
     * URL's query string.
     * @return IbisResult The result of invoking the method.
     */
    public function invokeGetMethod($path, $pathParams, $queryParams);

    /**
     * Invoke a web service GET, POST, PUT or DELETE method.
     *
     * The path should be the relative path to the method with standard
     * Java/PHP format specifiers for any path parameters, for example
     * ``"api/v1/person/%1$s/%2$s"``. Any path parameters specified
     * are then substituted into the path according to the standard Java
     * formatting rules.
     *
     * @param string $method The method type (``"GET"``, ``"POST"``,
     * ``"PUT"`` or ``"DELETE"``).
     * @param string $path The path to the method to invoke.
     * @param string[] $pathParams Any path parameters that should be inserted
     * into the path in place of any format specifiers.
     * @param array $queryParams Any query parameters to add as part of the
     * URL's query string.
     * @param array $formParams Any form parameters to submit.
     * @return IbisResult The result of invoking the method.
     */
    public function invokeMethod($method, $path, $pathParams,
                                 $queryParams, $formParams);
}
