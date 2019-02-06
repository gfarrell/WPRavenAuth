#!/usr/bin/php
<?php

require_once "ibisclient/client/IbisClientConnection.php";
require_once "ibisclient/methods/InstitutionMethods.php";

$conn = IbisClientConnection::createTestConnection();
$im = new InstitutionMethods($conn);

$people = $im->getMembers("UIS");

print("Members of University Information Services:\n");
foreach ($people as $person)
{
    print("\n");
    print_r($person);
}

?>
