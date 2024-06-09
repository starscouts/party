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
    <h1>Gestion des points générale</h1>

    <div id="wait">Veuillez présenter une carte.</div>
    <div id="unlinked">Carte non associée.</div>
    <div id="data" style="display: none;">
        <?php $types = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/reasons.json"), true); ?>

        <select onchange="fillDefaultValue();" id="reason" class="form-select mb-3">
            <?php $defaulted = false; foreach ($types as $item => $value): ?>
                <option <?= !$defaulted ? 'id="reason-default"' : "" ?> value="<?= $item ?>"><?= $value[0] ?></option>
            <?php $defaulted = true; endforeach; ?>
        </select>

        <div class="input-group mb-3">
            <input type="number" class="form-control" placeholder="Changement à effectuer" id="score">
            <span class="input-group-text" id="score-description">point·s</span>
        </div>

        <p>
            <span id="confirm" class="btn btn-primary" onclick="confirmTransaction();">Confirmer la transaction</span>
        </p>
        <span class="small text-muted">Toutes les transactions sont enregistrées de façon permanente et pourront être relues à l'avenir. Toute tentative de fraude sera sévèrement punie.</span>

        <hr>
        <div id="history-load">Chargement de l'historique des transactions...</div>
        <div id="history-data" class="list-group" style="display: none;"></div>
    </div>

    <script>
        window.transactionTypes = JSON.parse(`<?= json_encode($types) ?>`);
        window.previousAmount = "";

        function fillDefaultValue() {
            if (window.transactionTypes[document.getElementById("reason").value][1]) {
                if (!document.getElementById("score").disabled) {
                    document.getElementById("score").disabled = true;
                    window.previousAmount = document.getElementById("score").value;
                }

                document.getElementById("score").value = window.transactionTypes[document.getElementById("reason").value][1];
            } else if (document.getElementById("score").disabled) {
                document.getElementById("score").disabled = false;
                document.getElementById("score").value = window.previousAmount;
            }
        }

        fillDefaultValue();

        window.onnfcupdate = (data) => {
            window.currentGuestData = data;

            if (data) {
                if (data['linked']) {
                    document.getElementById("wait").style.display = "none";
                    document.getElementById("data").style.display = "";
                    document.getElementById("unlinked").style.display = "none";
                    document.getElementById("history-load").style.display = "";
                    document.getElementById("history-data").style.display = "none";

                    (async () => {
                        let history = (await (await fetch("/api/history.php?id=" + window.currentGuestData['transactional'])).json()).reverse();
                        document.getElementById("history-data").innerHTML = history.map(i => `
                        <div class="list-group-item">
                            <b>${i.amount > 0 ? "+" : ""}${i.amount} pts</b> · ${(transactionTypes[i.reason] ?? [i.reason, null])[0]}<br>
                            Confirmé à ${new Date(i.date).toLocaleString("fr-FR", { hour: "2-digit", minute: "2-digit", second: "2-digit" })}
                            ${i.detail.trim() !== "" ? "<br><i>" + i.detail.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;") + "</i>" : ""}
                        </div>
                        `).join("");

                        document.getElementById("history-load").style.display = "none";
                        document.getElementById("history-data").style.display = "";
                    })();
                } else {
                    document.getElementById("wait").style.display = "none";
                    document.getElementById("data").style.display = "none";
                    document.getElementById("unlinked").style.display = "";
                }
            } else {
                document.getElementById("wait").style.display = "";
                document.getElementById("data").style.display = "none";
                document.getElementById("unlinked").style.display = "none";
                document.getElementById("score").disabled = false;
                document.getElementById("confirm").classList.remove("disabled");
                document.getElementById("reason").disabled = false;
                document.getElementById("reason").value = document.getElementById("reason-default").value;
                document.getElementById("score").value = "";
            }
        }

        async function confirmTransaction() {
            if (isNaN(parseInt(document.getElementById("score").value)) || parseInt(document.getElementById("score").value) === 0) {
                alert("Aucune transaction n'est à effectuer.");
            } else if (Math.abs(parseInt(document.getElementById("score").value)) > 3000) {
                alert("Une unique transaction ne peut pas ajouter ou retirer plus de 3000 points.");
            } else if (Math.abs(parseInt(document.getElementById("score").value)) <= 10) {
                alert("Une unique transaction ne peut pas ajouter ou retirer moins de 10 points.");
            } else {
                let value = parseInt(document.getElementById("score").value);
                if (confirm("Voulez-vous vraiment " + (value < 0 ? "prélever" : "créditer") + " " + Math.abs(value) + " points au compte de " + window.currentGuestData.name + " ?")) {
                    document.getElementById("score").disabled = true;
                    document.getElementById("confirm").classList.add("disabled");
                    document.getElementById("reason").disabled = true;
                    await fetch("/api/transaction.php?id=" + window.currentGuestData['transactional'] + "&amount=" + value + "&reason=" + document.getElementById("reason").value + "&detail=");
                    await window.parent.reloadNfc();
                }
            }
        }
    </script>
</div>
</body>
</html>
