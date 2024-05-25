<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Party Manager</title>
    <style>
        * {
            user-select: none;
            -webkit-user-drag: none;
            font-family: Arial, sans-serif;
        }

        td {
            white-space: nowrap;
            overflow: hidden !important;
            text-overflow: ellipsis;
        }

        @keyframes blinker {
            from { visibility: visible; }
            to { visibility: hidden; }
        }

        blink {
            animation: blinker .5s steps(5, start) infinite;
        }
    </style>
</head>
<body style="margin: 0; overflow: hidden;">
    <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
        <thead>
            <tr style="font-weight: bold; color: white; background-color: #111; border-bottom: 2px solid #333;">
                <td style="width: 128px; border-right: 1px solid white;">ID</td>
                <td style="width: 20%; border-right: 1px solid white;">Nom</td>
                <td style="width: 55%; border-right: 1px solid white;">Motif</td>
                <td style="width: 96px; border-right: 1px solid white;">Montant</td>
                <td style="width: 96px; border-right: 1px solid white;">Nv solde</td>
                <td style="width: 96px; border-right: 1px solid white;">Heure</td>
                <td style="width: 32px;">+</td>
            </tr>
        </thead>
        <tbody id="entries"></tbody>
    </table>

    <script>
        <?php $types = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/includes/reasons.json"), true); ?>
        window.transactionTypes = JSON.parse(`<?= json_encode($types) ?>`);
        let knownIDs = [];

        async function update() {
            let entries = await (await fetch("/api/inspect.php")).json();
            let lastKnown = true;

            document.getElementById("entries").innerHTML = entries.map(i => {
                let html = `
                <tr style="font-weight: normal; color: white; background-color: ${knownIDs.includes(i.id) ? i['color0'] : i['color1']}; border-bottom: 1px solid white;">
                    <td style="border-right: 1px solid white;">${i.id}</td>
                    <td style="border-right: 1px solid white;">${i.name}</td>
                    <td style="border-right: 1px solid white;">${(window.transactionTypes[i['reason'][0]] ?? [i['reason'][0]])[0]}${i['reason'][1] ? " : <i>" + i['reason'][1] + "</i>" : ""}</td>
                    <td style="border-right: 1px solid white;">${i.amount} pts</td>
                    <td style="border-right: 1px solid white;">${i['balance'] !== null ? (i['balance'] < 0 ? "<b><blink>" + i['balance'] + " pts</blink></b>" : i['balance'] + " pts") : ""}</td>
                    <td style="border-right: 1px solid white;">${new Date(i['date']).toLocaleTimeString()}</td>
                    <td></td>
                </tr>
                `;

                if (!knownIDs.includes(i.id)) {
                    knownIDs.push(i.id);
                }

                return html;
            }).join("");
        }

        update();

        window.updateInterval = setInterval(async () => {
            await update();
        }, 2000);
    </script>
</body>
</html>
