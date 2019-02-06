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

require_once "ClientConnection.php";
require_once dirname(__FILE__) . "/../dto/IbisResult.php";

/**
 * Default implementation of the {@link ClientConnection} interface, to allow
 * methods in the Lookup/Ibis web service API to be invoked.
 *
 * @author Dean Rasheed (dev-group@ucs.cam.ac.uk)
 */
class IbisClientConnection implements ClientConnection
{
    /** The base URL to the Lookup/Ibis web service API. */
    protected $urlBase = "";

    /** Whether or not to allow self-signed certificates. */
    protected $allowSelfSigned = false;

    /** Username for HTTP basic authentication. */
    private $username = "anonymous";

    /** Password for HTTP basic authentication. */
    private $password = "";

    /** The HTTP basic authentication authorization string. */
    private $authorization = "";

    /** Whether to ask for flattened XML (recommended for efficiency). */
    protected $flatXML = true;

    /**
     * Create an IbisClientConnection to the Lookup/Ibis web service API at
     * {@link https://www.lookup.cam.ac.uk/}.
     *
     * The connection is initially anonymous, but this may be changed using
     * {@link setUsername()} and {@link setPassword()}.
     *
     * @return IbisClientConnection the connection to the Lookup/Ibis server.
     */
    public static function createConnection()
    {
        return new IbisClientConnection("https://www.lookup.cam.ac.uk/", true);
    }

    /**
     * Create an IbisClientConnection to the Lookup/Ibis test web service API
     * at {@link https://lookup-test.csx.cam.ac.uk/}.
     *
     * The connection is initially anonymous, but this may be changed using
     * {@link setUsername()} and {@link setPassword()}.
     *
     * NOTE: This test server is not guaranteed to always be available, and
     * the data in it may be out of sync with the data on the live system.
     *
     * @return IbisClientConnection the connection to the Lookup/Ibis test
     * server.
     */
    public static function createTestConnection()
    {
        return new IbisClientConnection("https://lookup-test.csx.cam.ac.uk/", true);
    }

    /**
     * Create an IbisClientConnection to a Lookup/Ibis web service API
     * running locally on {@link https://localhost:8443/ibis/}.
     *
     * The connection is initially anonymous, but this may be changed using
     * {@link setUsername()} and {@link setPassword()}.
     *
     * This is intended for testing during development. The local server is
     * assumed to be using self-signed certificates, which will not be
     * checked.
     *
     * @return IbisClientConnection the connection to a local Lookup/Ibis
     * server.
     */
    public static function createLocalConnection()
    {
        return new IbisClientConnection("https://localhost:8443/ibis/", false);
    }

    /**
     * Create a new IbisClientConnection using the specified URL base, which
     * should be something like {@link https://www.lookup.cam.ac.uk/}.
     * It is strongly recommended that certificate checking be enabled.
     *
     * The connection is initially anonymous, but this may be changed using
     * {@link setUsername()} and {@link setPassword()}.
     *
     * @param string $urlBase The base URL to the Lookup/Ibis web service
     * API.
     * @param boolean $checkCertificates If this is ``true`` the server's
     * certificates will be checked. Otherwise, the they will not, and the
     * connection may be insecure.
     * @see createConnection()
     * @see createTestConnection()
     */
    public function __construct($urlBase, $checkCertificates)
    {
        $this->urlBase = $urlBase;
        $this->allowSelfSigned = !$checkCertificates;

        // Initially use anonymous authentication
        $this->setUsername("anonymous");
        $this->setPassword("");
    }

    /*
     * Update the authorization string for HTTP basic authentication, in
     * response to a change in the username or password. 
     */
    private function updateAuthorization()
    {
        $credentials = $this->username . ":" . $this->password;
        $auth = base64_encode($credentials);
        $this->authorization = "Authorization: Basic " . $auth;
    }

    /* @see ClientConnection::setUsername(string) */
    public function setUsername($username)
    {
        $this->username = $username;
        $this->updateAuthorization();
    }

    /* @see ClientConnection::setPassword(string) */
    public function setPassword($password)
    {
        $this->password = $password;
        $this->updateAuthorization();
    }

    /*
     * Convert an arbitrary value to a string for use as a parameter to be
     * sent to the server.
     */
    private function valueToString($value)
    {
        if (is_bool($value))
            return $value ? "true" : "false";
        if ($value instanceof DateTime)
            return $value->format("d M Y");
        if ($value instanceof IbisAttribute)
            return $value->encodedString();
        return (string )$value;
    }

    /**
     * Build the full URL needed to invoke a method in the web service API.
     *
     * The path may contain standard Java format specifiers, which will be
     * substituted from the path parameters (suitably URL-encoded). Thus
     * for example, given the following arguments:
     *
     *  * path = "api/v1/person/%1$s/%2$s"
     *  * pathParams = ["crsid", "dar17"]
     *  * queryParams = ["fetch" => "email,title"]
     *
     * this method will create a URL like
     * https://www.lookup.cam.ac.uk/api/v1/person/crsid/dar17?fetch=email%2Ctitle.
     *
     * Note that all parameter values are automatically URL-encoded.
     *
     * @param string $path The basic path to the method, relative to the URL
     * base.
     * @param string[] $pathParams Any path parameters that should be inserted
     * into the path in place of any format specifiers.
     * @param array $queryParams Any query parameters to add as part of the
     * URL's query string.
     * @return string The complete URL.
     */
    protected function buildURL($path, $pathParams, $queryParams)
    {
        $url = $this->urlBase;
        if (strcasecmp(substr($url, 0, 5), "https") != 0)
            throw new Exception("Illegal URL protocol - must use HTTPS");

        $haveQueryParams = false;
        $haveFlattenParam = false;

        // Substitute any path parameters
        $path = is_null($path) ? "" : $path;
        if (isset($pathParams))
        {
            $encodedPathParams = array();
            foreach ($pathParams as $pathParam)
                $encodedPathParams[] = urlencode($pathParam);
            $path = vsprintf($path, $encodedPathParams);
        }

        // Add the path to the common URL base
        if (substr($url, -1) !== "/")
            $url .= "/";

        while (substr($path, 0, 1) === "/")
            $path = substr($path, 1);
        while (substr($path, -1) === "/")
            $path = substr($path, 0, -1);

        if (isset($path))
            $url .= $path;

        // Add any query parameters
        if (isset($queryParams))
        {
            foreach ($queryParams as $queryParam => $value)
            {
                if (isset($queryParam) && isset($value))
                {
                    $name = (string )$queryParam;
                    $val = $this->valueToString($value);

                    $url .= $haveQueryParams ? "&" : "?";
                    $url .= urlencode($name);
                    $url .= "=";
                    $url .= urlencode($val);
                    $haveQueryParams = true;
                    if ($queryParam === "flatten")
                        $haveFlattenParam = true;
                }
            }
        }

        // If the flattened XML representation is being used, add the
        // "flatten" parameter, unless it has already been specified
        if ($this->flatXML && !$haveFlattenParam)
        {
            $url .= $haveQueryParams ? "&" : "?";
            $url .= "flatten=true";
        }

        return $url;
    }

    /* @see ClientConnection::invokeGetMethod(string, string[], array) */
    public function invokeGetMethod($path, $pathParams, $queryParams)
    {
        return $this->invokeMethod("GET", $path, $pathParams, $queryParams);
    }

    /* @see ClientConnection::setUsername(string, string, string[], array, array) */
    public function invokeMethod($method, $path, $pathParams,
                                 $queryParams, $formParams=null)
    {
        // Build the URL
        $headers = array($this->authorization, "Accept: application/xml");
        $url = $this->buildURL($path, $pathParams, $queryParams);
        $content = "";

        // Build any content to send from any form parameters
        if (isset($formParams) && !empty($formParams))
        {
            $strFormParams = array();
            foreach ($formParams as $formParam => $value)
            {
                $name = (string )$formParam;
                $val = $this->valueToString($value);
                $strFormParams[$name] = $val;
            }
            $content = http_build_query($strFormParams);
            $headers[] = "Content-type: application/x-www-form-urlencoded";
        }

        // ---------------------------------------------------------------
        // Experimental code using Curl to talk to the server. This has
        // the advantage of being able to force the use of the TLSv1
        // protocol, but suffers from not having a mechanism for decent
        // error checking, so this is disabled for now.
        //
        // Use Curl for the request
        //$ch = curl_init();
        //
        //curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        //curl_setopt($ch, CURLOPT_SSLVERSION, 1 /*CURL_SSLVERSION_TLSv1*/);
        //curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cacerts.txt");
        //
        //if ($content)
        //{
        //    curl_setopt($ch, CURLOPT_POST, true);
        //    curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        //}
        //
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //
        // TODO: Proper error checking of result status, etc...
        //$xml = curl_exec($ch);
        //curl_close($ch);
        //
        // Parse the XML result into an IbisResult object
        //$parser = new IbisResultParser();
        //$result = $parser->parseXml($xml);
        //
        // ---------------------------------------------------------------

        // Set up the HTTPS request headers
        $http_options = array("method" => $method,
                              "header" => $headers,
                              "content" => $content,
                              "ignore_errors" => true);

        $ssl_options = array("verify_peer" => true,
                             "cafile" => dirname(__FILE__) . "/cacerts.txt",
                             "allow_self_signed" => $this->allowSelfSigned);

        $ctx_params = array("http" => $http_options,
                            "ssl" => $ssl_options);

        // Send the request and check if we got XML back
        $ctx = stream_context_create($ctx_params);
        $file = fopen($url, "r", false, $ctx);
        $status = "200";
        $code = "OK";
        $gotXml = false;

        foreach ($http_response_header as $header)
        {
            if (stripos($header, "http") === 0)
            {
                $a = explode(" ", $header);
                $status = $a[1];
                $code = $a[2];
            }
            if (stripos($header, "content-type: application/xml") !== false)
                $gotXml = true;
        }

        if (!$gotXml)
        {
            // We didn't get XML back so create an IbisResult containing a
            // suitable IbisError
            $error = new IbisError(array("status" => $status,
                                         "code" => $code));
            $error->message = "Unexpected result from server";
            $error->details = fread($file, 1000000);
            fclose($file);

            $result = new IbisResult();
            $result->error = $error;

            return $result;
        }

        // Parse the XML result into an IbisResult object
        $parser = new IbisResultParser();
        $result = $parser->parseXmlFile($file);
        fclose($file);

        return $result;
    }
}
