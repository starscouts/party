<?php

$credentials = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/credentials.json"), true);
$WSDL = "http://10.10.3.12/api/wsdl/RpcEncoded";
$LOGIN = $credentials["login"];
$PASS = $credentials["password"];

$data = [
    'success' => false
];

try {
    $client = new SoapClient($WSDL, array('login'=> $LOGIN,'password'=> $PASS));

    $data["software"] = $client->Version();
    $data["start"] = $client->DatePremierJourBase();
    $data["users"] = $client->NombreUtilisateurs();

    $data["success"] = true;
} catch (Exception $e) {
    $data["success"] = false;
}

header("Content-Type: application/json");
die(json_encode($data, JSON_PRETTY_PRINT));
