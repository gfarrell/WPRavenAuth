<?php

require_once "ibisclient/client/IbisClientConnection.php";
require_once "ibisclient/methods/InstitutionMethods.php";

$conn = IbisClientConnection::createTestConnection();
$im = new InstitutionMethods($conn);

$people = $im->getMembers("CS");

print("Members of the Computing Service:\n");
foreach ($people as $person)
{
    print("\n");
    print_r($person);
}

?>
