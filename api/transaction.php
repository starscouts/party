<?php

$data = [
    'success' => false
];

if (!isset($_GET["id"])) $_GET["id"] = "-1";
if (!isset($_GET["amount"])) $_GET["amount"] = "0";
if (!isset($_GET["reason"])) $_GET["reason"] = "UNSORTED";
if (!isset($_GET["detail"])) $_GET["detail"] = "";
if (!isset($_GET["receipt"])) $_GET["receipt"] = "";

$receipts = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/receipts/" . $_GET["id"] . ".json"), true);

if (is_numeric($_GET["id"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $_GET["id"] . ".json")) {
    $d = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $_GET["id"] . ".json"), true);
    $d["balance"] += (int)($_GET["amount"]);
    $d["history"][] = [
        "date" => date('c'),
        "amount" => (int)($_GET["amount"]),
        "reason" => substr($_GET["reason"], 0, 64),
        "detail" => substr($_GET["detail"], 0, 256)
    ];
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $_GET["id"] . ".json", json_encode($d));

    if (trim($_GET["receipt"]) !== "") {
        $receipts[] = rawurldecode(base64_decode($_GET["receipt"]));
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/receipts/" . $_GET["id"] . ".json", json_encode($receipts));
    }

    $d["success"] = true;
}

header("Content-Type: application/json");
die(json_encode($data, JSON_PRETTY_PRINT));
