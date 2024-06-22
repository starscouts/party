<?php

$credentials = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/credentials.json"), true);
$WSDL = "http://10.10.3.12/api/wsdl/RpcEncoded";
$LOGIN = $credentials["login"];
$PASS = $credentials["password"];

$data = [
    'success' => false,
    'linked' => false
];

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$data["_query"] = $_GET["id"];

try {
    $client = new SoapClient($WSDL, array('login'=> $LOGIN,'password'=> $PASS));
    $links = array_filter(array_map(function ($i) use ($client) {
        return [
            $i,
            $client->NumeroSecuriteSocialeEtudiant($i)
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

        $data["linked"] = true;

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $id . ".json")) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $id . ".json", "{\"balance\": 100, \"history\": []}");
        }

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/includes/orders/" . $id . ".json")) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/orders/" . $id . ".json", "{\"foods\": [], \"drinks\": []}");
        }

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/includes/receipts/" . $id . ".json")) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/receipts/" . $id . ".json", "[]");
        }

        $data["id"] = $client->NumeroINEEtudiant($id);
        $data["transactional"] = $id;
        $data["name"] = $client->CiviliteEtudiant($id) . " " . $client->NomEtudiant($id) . " " . $client->PrenomEtudiant($id);
        $data["birthday"] = $client->DateDeNaissanceEtudiant($id);
        $data["phone"] = $client->AutorisationReceptionSMSEtudiant($id) ? $client->TelephonePortableEtudiant($id) : null;
        $data["email"] = $client->EMailEtudiant($id);
        $data["groups"] = array_map(function ($i) use ($client) {
            return $client->CodePromotion($i);
        }, $client->PromotionsEtudiant($id));
        $data["balance"] = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $id . ".json"), true)["balance"];

        $photo = $client->PhotoEtudiant($id, "png");
        $data["photo"] = isset($photo) ? base64_encode($photo) : null;

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/profiles/" . $id . ".json", json_encode($data, JSON_PRETTY_PRINT));
    } else {
        $data["linked"] = false;
    }

    $data["success"] = true;
} catch (Exception $e) {
    $data["success"] = false;
}

header("Content-Type: application/json");
die(json_encode($data, JSON_PRETTY_PRINT));
