<?php

if (!isset($_GET["id"])) $_GET["id"] = "-1";

if (is_numeric($_GET["id"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $_GET["id"] . ".json")) {
    $data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/" . $_GET["id"] . ".json"), true);
    header("Content-Type: application/json");
    die(json_encode($data["history"], JSON_PRETTY_PRINT));
}

header("Content-Type: application/json");
die(json_encode(null, JSON_PRETTY_PRINT));
