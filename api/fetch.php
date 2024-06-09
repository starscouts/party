<?php

$credentials = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/credentials.json"), true);
$WSDL = "http://10.10.3.12/api/wsdl/RpcEncoded";
$LOGIN = $credentials["login"];
$PASS = $credentials["password"];

$data = [
    'success' => false,
    'found' => false,
    'free' => false
];

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

try {
    $client = new SoapClient($WSDL, array('login'=> $LOGIN,'password'=> $PASS));
    $links = array_filter(array_map(function ($i) use ($client) {
        return [
            $i,
            $client->NumeroINEEtudiant($i) . "-" . $client->CodeFiscalEtudiant($i)
        ];
    }, $client->TousLesEtudiants()), function ($i) {
        return $i[1] !== "";
    });

    if (in_array($_GET["id"], array_map(function ($i) {
        return $i[1];
    }, $links))) {
        $id = $links[array_search($_GET["id"], array_map(function ($i) {
            return $i[1];
        }, $links))][0];

        $data["found"] = true;
        $data["name"] = $client->CiviliteEtudiant($id) . " " . $client->NomEtudiant($id) . " " . $client->PrenomEtudiant($id);

        if ($client->NumeroSecuriteSocialeEtudiant($id) !== "") {
            $data["free"] = false;
        } else {
            $data["free"] = true;
        }
    } else {
        $data["found"] = false;
        $data["free"] = false;
    }

    $data["success"] = true;
} catch (Exception $e) {
    $data["success"] = false;
}

header("Content-Type: application/json");
die(json_encode($data, JSON_PRETTY_PRINT));
