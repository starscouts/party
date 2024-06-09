<?php

header("Content-Type: application/json");
$data = [];

foreach (array_filter(scandir($_SERVER['DOCUMENT_ROOT'] . "/includes/balances"), function ($i) { return !str_starts_with($i, "."); }) as $file) {
    $json = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/balances/$file"), true);
    $profile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/profiles/$file"), true);
    $userList = [];

    foreach ($json["history"] as $id => $transaction) {
        $userList[] = [
            "color0" => $id == count($json["history"]) - 1 || abs($transaction["amount"]) >= 500 ? ($transaction["amount"] > 0 ? "darkgreen" : "darkred") : "darkgray",
            "color1" => "darkorange",
            "id" => $profile["id"] . "-" . sprintf("%03d", $id + 1),
            "name" => $profile["name"],
            "amount" => $transaction["amount"],
            "balance" => $id == count($json["history"]) - 1 ? $json["balance"] : null,
            "date" => $transaction["date"],
            "reason" => [$transaction["reason"], $transaction["detail"]]
        ];
    }

    array_push($data, ...$userList);
}

usort($data, function ($a, $b) {
    return strtotime($b["date"]) - strtotime($a["date"]);
});

die(json_encode($data, JSON_PRETTY_PRINT));
