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
<button id="emulator" onclick="qrEmu();" style="display: none; position: fixed; top: 0; left: 0;">QREmu</button>
<div class="container">
    <a href="/panes/home.php">← Changer de mode</a>
    <hr>
    <h1>Arrivées et départs</h1>

    <div id="wait">Veuillez présenter une carte.</div>
    <div id="qr" style="display: none;">Scannez le code QR présent sur l'invitation :<video id="preview" style="max-width: 90vw; max-height: 70vh; margin-left: auto; margin-right: auto; margin-top: 10px; display: block;"></video></div>
    <div id="already" style="display: none;">Cette carte a déjà été associée à <span id="name">-</span>. Par mesure de sécurité, il est nécessaire d'utiliser le Client HYPERPLANNING pour la dissocier en raison d'une perte, d'une exclusion ou d'un départ.</div>
    <div id="already2" style="display: none;">Ce code QR correspond à <span id="name3">-</span>, qui dispose déjà d'une carte à son nom. Par mesure de sécurité, il est nécessaire d'utiliser le Client HYPERPLANNING pour la dissocier en raison d'une perte, d'une exclusion ou d'un départ.</div>
    <div id="notfound" style="display: none;">Ce code QR ne correspond à aucun invité connu. Contactez votre administrateur.</div>
    <div id="processing" style="display: none;">Lecture des données...</div>
    <div id="linking" style="display: none;">Association de la carte avec <span id="name2">-</span>...</div>

    <script>
        window.qrIsEnabled = false;

        function qrEmu() {
            let data = prompt("QREmu: Enter data to emulate:");
            processQr(data);
        }

        window.onnfcupdate = (data) => {
            window.currentGuestData = data;

            if (data) {
                if (data['linked']) {
                    document.getElementById("wait").style.display = "none";
                    document.getElementById("qr").style.display = "none";
                    document.getElementById("already").style.display = "";
                    document.getElementById("already2").style.display = "none";
                    document.getElementById("notfound").style.display = "none";
                    document.getElementById("processing").style.display = "none";
                    document.getElementById("linking").style.display = "none";
                    document.getElementById("name").innerText = data.name;
                    window.qrIsEnabled = false;
                    qrScanner.pause();
                    if (window.invalidQR) document.getElementById("emulator").style.display = "none";
                } else {
                    document.getElementById("wait").style.display = "none";
                    document.getElementById("qr").style.display = "";
                    document.getElementById("already").style.display = "none";
                    document.getElementById("already2").style.display = "none";
                    document.getElementById("notfound").style.display = "none";
                    document.getElementById("processing").style.display = "none";
                    document.getElementById("linking").style.display = "none";
                    window.qrIsEnabled = true;
                    qrScanner.start();
                    if (window.invalidQR) document.getElementById("emulator").style.display = "";
                }
            } else {
                document.getElementById("wait").style.display = "";
                document.getElementById("qr").style.display = "none";
                document.getElementById("already").style.display = "none";
                document.getElementById("already2").style.display = "none";
                document.getElementById("notfound").style.display = "none";
                document.getElementById("processing").style.display = "none";
                document.getElementById("linking").style.display = "none";
                window.qrIsEnabled = false;
                qrScanner.pause();
                if (window.invalidQR) document.getElementById("emulator").style.display = "none";
            }
        }

        async function processQr(id) {
            if (!window.qrIsEnabled) return;
            window.qrIsEnabled = false;
            if (window.invalidQR) document.getElementById("emulator").style.display = "none";
            qrScanner.pause();
            document.getElementById("wait").style.display = "none";
            document.getElementById("qr").style.display = "none";
            document.getElementById("already").style.display = "none";
            document.getElementById("already2").style.display = "none";
            document.getElementById("notfound").style.display = "none";
            document.getElementById("processing").style.display = "";
            document.getElementById("linking").style.display = "none";

            window.qrId = id;
            let data = window.qrServerData = await (await fetch("/api/fetch.php?id=" + encodeURIComponent(id))).json();

            if (window.qrServerData['found']) {
                if (window.qrServerData['free']) {
                    document.getElementById("wait").style.display = "none";
                    document.getElementById("qr").style.display = "none";
                    document.getElementById("already").style.display = "none";
                    document.getElementById("already2").style.display = "none";
                    document.getElementById("notfound").style.display = "none";
                    document.getElementById("processing").style.display = "none";
                    document.getElementById("linking").style.display = "";
                    document.getElementById("name2").innerText = data.name;

                    window.qrServerData = await (await fetch("/api/register.php?qr=" + encodeURIComponent(id) + "&id=" + encodeURIComponent(window.currentGuestData["_query"]))).json();
                    window.parent.reloadNfc();
                } else {
                    document.getElementById("wait").style.display = "none";
                    document.getElementById("qr").style.display = "none";
                    document.getElementById("already").style.display = "none";
                    document.getElementById("already2").style.display = "";
                    document.getElementById("notfound").style.display = "none";
                    document.getElementById("processing").style.display = "none";
                    document.getElementById("linking").style.display = "none";
                    document.getElementById("name3").innerText = data.name;
                }
            } else {
                document.getElementById("wait").style.display = "none";
                document.getElementById("qr").style.display = "none";
                document.getElementById("already").style.display = "none";
                document.getElementById("already2").style.display = "none";
                document.getElementById("notfound").style.display = "";
                document.getElementById("processing").style.display = "none";
                document.getElementById("linking").style.display = "none";
            }
        }
    </script>

    <script type="module">
        import QrScanner from '/qr-scanner.min.js';

        try {
            window.invalidQR = false;
            window.lastResult = null;

            window.qrScanner = new QrScanner(
                document.getElementById("preview"),
                (result) => {
                    if (result.data !== lastResult) {
                        window.lastResult = result.data;
                        console.log(result.data);
                        processQr(result.data);
                    }
                },
                {},
            );
        } catch (e) {
            window.invalidQR = true;
        }
    </script>

</div>
</body>
</html>
