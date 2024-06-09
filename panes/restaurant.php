<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>Party Manager</title>
    <style>
        * {
            user-select: none;
            -webkit-user-drag: none;
        }
    </style>
</head>
<body style="padding-top: 30px;">
<div class="container">
    <a href="/panes/home.php">← Changer de mode</a>
    <hr>
    <h1>Restauration et commandes</h1>

    <div id="wait">Veuillez présenter une carte.</div>
    <div id="unlinked">Carte non associée.</div>
    <div id="data" style="display: none;">
        <div style="display: grid; grid-template-columns: 1fr max-content; grid-gap: 10px;">
            <div style="display: flex; align-items: center;" id="status">
                0 articles · 0 points
            </div>
            <div>
                <span onclick="confirmOrder();" class="btn btn-primary">Valider la commande</span>
                <span onclick="cancelOrder();" class="btn btn-outline-primary">Annuler</span>
            </div>
        </div>
        <hr>
        <div class="list-group">
            <?php

            $types = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/reasons.json"), true);
            $foods = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/foods.json"), true);
            $id = 0;

            foreach ($foods as $name => $items): if (str_starts_with($name, "_")) continue; ?>
                <?php foreach ($items as $item): ?>
                    <a onclick="addItem(<?= $id ?>);" id="item-<?= $id ?>" style="display: grid; grid-template-columns: max-content 1fr; cursor: pointer; grid-gap: 10px;" class="<?= match ($name) {
                        "alcohol" => "tax-40",
                        default => "tax-20"
                    } ?> item list-group-item list-group-item-action <?= match ($name) {
                        "alcohol" => "list-group-item-info",
                        "drinks" => "list-group-item-primary",
                        "starters" => "list-group-item-success",
                        "mains" => "list-group-item-danger",
                        "desserts" => "list-group-item-warning",
                        default => ""
                    } ?>" data-item-category="<?= $name ?>" data-item-title="<?= $item ?>" data-item-value="<?= match ($name) {
                        "alcohol" => $types["MENU_ALCOHOL"][1],
                        "drinks" => $types["MENU_DRINKS"][1],
                        "starters" => $types["MENU_STARTER"][1],
                        "mains" => $types["MENU_MAIN"][1],
                        "desserts" => $types["MENU_DESSERT"][1],
                        default => ""
                    } ?>">
                        <div style="display: flex; align-items: center;">
                            <span class="badge <?= match ($name) {
                                "alcohol" => "bg-info",
                                "drinks" => "bg-primary",
                                "starters" => "bg-success",
                                "mains" => "bg-danger",
                                "desserts" => "bg-warning",
                                default => ""
                            } ?> <?= match ($name) {
                                "desserts", "alcohol" => "text-black",
                                default => "text-white"
                            } ?> rounded-pill opacity-50">0</span>
                        </div>
                        <div style="display: flex; align-items: center;"><?= $item ?></div>
                    </a>
                <?php $id++; endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="receipt" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" style="width: max-content;">
        <div class="modal-content" style="width: max-content;">
            <div class="modal-body" style="width: max-content;">
                <pre id="receipt-text" style="width: 42ch;"></pre>
                <hr>
                <span id="credit-order-btn" class="btn btn-primary" onclick="creditOrder();">Confirmer et créditer</span>
                <span id="cancel-order-btn" class="btn btn-outline-primary" data-bs-dismiss="modal">Annuler</span>
            </div>
        </div>
    </div>
</div>

<script>
    let order = {};
    let receiptModal = new bootstrap.Modal(document.getElementById("receipt"));
    let substitutions = JSON.parse(`<?= json_encode($foods["_substitutions"] ?? []) ?>`);

    window.onnfcupdate = (data) => {
        window.currentGuestData = data;

        if (data) {
            if (data['linked']) {
                document.getElementById("wait").style.display = "none";
                document.getElementById("data").style.display = "";
                document.getElementById("unlinked").style.display = "none";
            } else {
                document.getElementById("wait").style.display = "none";
                document.getElementById("data").style.display = "none";
                document.getElementById("unlinked").style.display = "";
            }
        } else {
            document.getElementById("wait").style.display = "";
            document.getElementById("data").style.display = "none";
            document.getElementById("unlinked").style.display = "none";
        }
    }

    function addItem(id) {
        let item = document.getElementById("item-" + id);
        let badge = item.getElementsByClassName("badge")[0];
        if (!order[id]) order[id] = 0;
        order[id]++;
        if (order[id] > 99) order[id] = 99;
        badge.classList.remove("opacity-50");
        badge.innerText = order[id];
        updateDisplay();
    }

    function cancelOrder() {
        order = {};

        for (let item of Array.from(document.getElementsByClassName("item"))) {
            let badge = item.getElementsByClassName("badge")[0];
            badge.classList.add("opacity-50");
            badge.innerText = "0";
        }

        updateDisplay();
    }

    function updateDisplay() {
        let value = 0;

        for (let item of Object.entries(order)) {
            let id = item[0];
            let quantity = item[1];
            value += parseInt(document.getElementById("item-" + id).getAttribute("data-item-value")) * quantity;
        }

        document.getElementById("status").innerText = Object.values(order).reduce((a, b) => a + b, 0) + " articles · " + value + " points HT";
    }

    function generateReceipt() {
        let subTotal = Object.entries(order).map(i => parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]).reduce((a, b) => a + b);
        let subTotal40 = Object.entries(order).filter(i => document.getElementById("item-" + i[0]).classList.contains("tax-40")).map(i => parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]).reduce((a, b) => a + b, 0);
        let subTotal20 = Object.entries(order).filter(i => document.getElementById("item-" + i[0]).classList.contains("tax-20")).map(i => parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]).reduce((a, b) => a + b, 0);
        let total = subTotal - ((subTotal20 * 1.2) - subTotal20) - ((subTotal40 * 1.4) - subTotal40);

        return "" +
`         FÊTE DE FIN D'ANNÉE 2024
            POINT RESTAURATION

  Date : ${new Date().toLocaleDateString('fr-FR', {month: 'numeric',day: 'numeric',year: 'numeric'})}  -  Heure : ${new Date().toLocaleTimeString('fr-FR', {
            hour: 'numeric',minute: 'numeric',second: 'numeric'})}

 ----------- VENTE IMMEDIATE -----------
${Object.entries(order).map(i => {
    let title = document.getElementById("item-" + i[0]).getAttribute("data-item-title");

    for (let substitution of Object.entries(substitutions)) {
        title = title.replaceAll(substitution[0], substitution[1]);
    }

    let j = `${i[1].toString() + "  ".substring(0, 2 - i[1].toString().length)}  ${title.toUpperCase().substring(0, 12)}`;
    let k = `${"   ".substring(0, 3 - document.getElementById("item-" + i[0]).getAttribute("data-item-value").length) + document.getElementById("item-" + i[0]).getAttribute("data-item-value")},00 P  x${i[1]}`;
    let l = `${parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]},00 P`;
    return j + " ".repeat(18 - j.length) + k + " ".repeat(24 - k.length - l.length) + l;
}).join("\n")}

 ---------------------------------------
SOUS-TOTAL ${" ".repeat(26 - subTotal.toString().length)}${subTotal},00 P
${Object.values(order).reduce((a, b) => a + b)} ARTICLES

 ---------------------------------------
TAXE 20% ${" ".repeat(27 - ((subTotal20 * 1.2) - subTotal20).toString().length)}-${(subTotal20 * 1.2) - subTotal20},00 P
TAXE 40% ${" ".repeat(27 - ((subTotal40 * 1.4) - subTotal40).toString().length)}-${(subTotal40 * 1.4) - subTotal40},00 P

TOTAL TTC ${" ".repeat(27 - total.toString().length)}${total.toString()},00 P

 ---------------------------------------
 Merci pour votre commande. Nous espérons
       que vous allez vous régaler.

             Revenez vite !

         (c) 2024 Equestria.dev
`
        ;
    }

    function confirmOrder() {
        if (Object.values(order).reduce((a, b) => a + b, 0) === 0) return;

        document.getElementById("receipt-text").innerText = generateReceipt();
        receiptModal.show();
    }

    async function creditOrder() {
        document.getElementById("credit-order-btn").classList.add("disabled");
        document.getElementById("cancel-order-btn").classList.add("disabled");

        let subTotal = Object.entries(order).map(i => parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]).reduce((a, b) => a + b);
        let subTotal40 = Object.entries(order).filter(i => document.getElementById("item-" + i[0]).classList.contains("tax-40")).map(i => parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]).reduce((a, b) => a + b, 0);
        let subTotal20 = Object.entries(order).filter(i => document.getElementById("item-" + i[0]).classList.contains("tax-20")).map(i => parseInt(document.getElementById("item-" + i[0]).getAttribute("data-item-value")) * i[1]).reduce((a, b) => a + b, 0);
        let total = subTotal - ((subTotal20 * 1.2) - subTotal20) - ((subTotal40 * 1.4) - subTotal40);

        await fetch("/api/transaction.php?id=" + currentGuestData['transactional'] + "&amount=" + total + "&reason=MENU_GENERIC&detail=" + Object.keys(order).map(i => document.getElementById("item-" + i).getAttribute("data-item-title")).join(", ") + "&receipt=" + btoa(encodeURIComponent(generateReceipt())));

        document.getElementById("credit-order-btn").classList.remove("disabled");
        document.getElementById("cancel-order-btn").classList.remove("disabled");
        window.parent.reloadNfc();
        receiptModal.hide();
        cancelOrder();
    }
</script>
</body>
</html>
