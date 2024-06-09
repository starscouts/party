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
<body style="overflow: hidden;">
    <div id="cta" class="bg-primary text-white" style="display: none; inset: 0; z-index: 999; position: fixed; align-items: center; justify-content: center;" onclick="start();">
        Appuyez ici pour commencer
    </div>

    <button id="emulator" style="display: none; position: fixed; top: 0; left: 0;" onclick="nfcEmu();">NFCEmu</button>

    <div onclick="clearNfc();" style="border-bottom: 1px solid rgba(0, 0, 0, .25); height: 200px;">
        <div id="data" style="display: none;">
            <div class="container" style="display: grid; grid-template-columns: 156px 1fr; grid-gap: 20px;">
                <div>
                    <img src="" id="image" class="bg-light" alt="" style="aspect-ratio: 156/200; width: 156px; height: 200px;">
                </div>
                <div style="display: flex; align-items: center;">
                    <div>
                        <p><b><span id="name"></span><br><span id="id"></span></b></p>
                        <p>Né·e le <span id="birth"></span><br><span id="phone"></span><br><span id="email"></span></p>
                        <p><span id="groups"></span><br><span id="score">-</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div id="message" style="height: 100%; display: flex; align-items: center; justify-content: center; margin-left: 2%; margin-right: 2%; text-align: center;">
            Connexion au serveur...
        </div>
    </div>
    <iframe id="frame" src="about:blank" style="width: 100%; height: calc(100vh - 200px);"></iframe>

    <script>
        async function start() {
            document.getElementById("cta").style.display = "none";

            window.onhashchange = loadHash = () => {
                if (location.hash === "#" || location.hash === "") {
                    location.hash = "#/home";
                    return;
                }

                let requested = "/panes/" + location.hash.substring(2) + ".php";
                if (document.getElementById("frame").contentWindow.location.pathname === requested) return;
                document.getElementById("frame").src = requested;
            }

            window.nfcEmu = () => {
                let data = prompt("NFCEmu: Enter data to emulate:");
                processNfc(data);
            }

            window.processNfc = async (id) => {
                localStorage.setItem("last-nfc-data", id);

                window.currentGuestData = null;
                if (document.getElementById("frame").contentWindow.onnfcupdate) {
                    document.getElementById("frame").contentWindow.onnfcupdate(null);
                }

                document.getElementById("data").style.display = "none";
                document.getElementById("message").style.display = "flex";
                document.getElementById("message").innerText = "Lecture de la carte...";
                window.currentGuestData = await (await fetch("/api/link.php?id=" + encodeURIComponent(id))).json();

                if (window.currentGuestData['linked']) {
                    document.getElementById("data").style.display = "";
                    document.getElementById("message").style.display = "none";

                    document.getElementById("image").src = "data:image/jpeg;base64," + window.currentGuestData['photo'];
                    document.getElementById("name").innerText = window.currentGuestData["name"];
                    document.getElementById("id").innerText = window.currentGuestData["id"];
                    document.getElementById("phone").innerText = window.currentGuestData["phone"] ?? "-";
                    document.getElementById("email").innerText = window.currentGuestData["email"] ?? "-";
                    document.getElementById("groups").innerText = window.currentGuestData["groups"].join(", ");
                    document.getElementById("birth").innerText = window.currentGuestData["birthday"];
                    document.getElementById("score").innerText = window.currentGuestData["balance"] + " point" + (window.currentGuestData['balance'] > 1 ? "s" : "");

                    if (document.getElementById("frame").contentWindow.onnfcupdate) {
                        document.getElementById("frame").contentWindow.onnfcupdate(window.currentGuestData);
                    }
                } else {
                    document.getElementById("data").style.display = "none";
                    document.getElementById("message").style.display = "flex";
                    document.getElementById("message").innerText = "Carte non associée, ou l'invité correspondant est exclu ou parti. Utilisez l'option \"Arrivées et départs\" pour associer cette carte à un invité.";

                    if (document.getElementById("frame").contentWindow.onnfcupdate) {
                        document.getElementById("frame").contentWindow.onnfcupdate(window.currentGuestData);
                    }
                }
            }

            document.getElementById("frame").onload = () => {
                if (document.getElementById("frame").contentWindow.location.pathname.endsWith(".php")) {
                    let parts = document.getElementById("frame").contentWindow.location.pathname.split("/");
                    location.hash = "#/" + parts[parts.length - 1].split(".php")[0];
                }

                if (document.getElementById("frame").contentWindow.onnfcupdate) {
                    document.getElementById("frame").contentWindow.onnfcupdate(window.currentGuestData);
                }
            }

            window.clearNfc = () => {
                localStorage.removeItem("last-nfc-data");
                window.currentGuestData = null;

                if (document.getElementById("frame").contentWindow.onnfcupdate) {
                    document.getElementById("frame").contentWindow.onnfcupdate(null);
                }

                document.getElementById("data").style.display = "none";
                document.getElementById("message").style.display = "flex";
                document.getElementById("message").innerText = "Passez une carte devant le lecteur pour commencer.";
            }

            window.reloadNfc = async () => {
                await processNfc(window.currentGuestData['_query']);
            }

            window.nfcFail = () => {
                alert("Cannot open NFC reader, please use NFC emulation. Check for permission.");
                document.getElementById("emulator").style.display = "";
            }

            if (!(NDEFReader in window)) {
                const ndef = new NDEFReader();

                function readNfc() {
                    ndef.scan().then(() => {
                        ndef.onreadingerror = () => null;
                        ndef.onreading = (e) => {
                            let decoder = new TextDecoder();
                            let data = toCardID(e.serialNumber) + "-" + decoder.decode(e.message.records[1].data).toUpperCase();
                            processNfc(data);
                        };
                    }).catch((e) => {
                        console.error(e);
                        nfcFail();
                    });
                }
            }

            window.toCardID = (serial) => {
                let t = (parseInt(serial.replaceAll(":", ""), 16).toString(36) + parseInt(serial.split("").reverse().join("").replaceAll(":", ""), 16).toString(36)).toUpperCase();
                return "00000000000000000000".substring(0, 20 - t.length) + t.substring(0, 20);
            }

            readNfc();

            window.systemInfo = await (await fetch("/api/check.php")).json();

            if (window.systemInfo.success) {
                document.getElementById("data").style.display = "none";
                document.getElementById("message").style.display = "flex";
                document.getElementById("message").innerText = "Passez une carte devant le lecteur pour commencer.";
                loadHash();

                if (localStorage.getItem("last-nfc-data")) {
                    await processNfc(localStorage.getItem("last-nfc-data"));
                }
            } else {
                document.getElementById("data").style.display = "none";
                document.getElementById("message").style.display = "flex";
                document.getElementById("message").innerText = "Connexion au serveur impossible, contactez votre administrateur.";
            }
        }

        (async () => {
            try {
                const ndef = new NDEFReader();
                await ndef.scan();
                start();
            } catch (e) {
                document.getElementById("cta").style.display = "flex";
            }
        })();
    </script>
</body>
</html>
