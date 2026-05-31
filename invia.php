<?php
/* =====================================================================
   Studio ABeliA — Endpoint invio modulo di contatto
   ---------------------------------------------------------------------
   Riceve i dati del form (POST) e invia l'email a info@abelia.it.
   Risponde in JSON: { "ok": true } oppure { "ok": false, "error": "..." }

   COME USARLO
   1. Carica questo file nella cartella principale del sito (stessa di
      index.html) sul tuo hosting (deve supportare PHP — Aruba, Register.it,
      SiteGround, ecc. lo supportano).
   2. Verifica che la casella info@abelia.it esista sul dominio.
   3. Non serve altro: il form punta gia' a "invia.php".

   NOTA SMTP (opzionale ma consigliato)
   Se le email non arrivano con mail() — capita su alcuni hosting — puoi
   passare all'invio via SMTP con PHPMailer: vedi il blocco commentato in
   fondo al file e le istruzioni nel README.
   ===================================================================== */

/* ----------------------------- CONFIG ------------------------------ */
$DESTINATARIO   = 'info@abelia.it';                 // dove ricevi i messaggi
$MITTENTE       = 'info@abelia.it';                 // deve essere una casella DEL dominio
$NOME_MITTENTE  = 'Sito Studio ABeliA';
$OGGETTO_PREFIX = 'Nuovo messaggio dal sito';

/* Imposta a true SOLO se vuoi usare SMTP via PHPMailer (vedi sotto).
   Su TopHost la funzione mail() di norma funziona gia' senza SMTP:
   prova prima con $USA_SMTP = false. Se le email non arrivano, passa a true. */
$USA_SMTP = false;
$SMTP = [
    'host'   => 'mail.tophost.it',  // server SMTP TopHost
    'port'   => 587,                // TopHost usa la 587
    'secure' => 'tls',              // STARTTLS su TopHost
    // ATTENZIONE TopHost: l'utente NON e' l'email completa ma il nome del dominio
    // (lo trovi in cpanel > Gestione avanzata email; es. "abelia.it" o "abelia.it41234")
    'user'   => 'abelia.it',
    'pass'   => 'LA_PASSWORD_DELLA_CASELLA',  // password della casella info@abelia.it
];
/* ------------------------------------------------------------------- */

header('Content-Type: application/json; charset=utf-8');

// Risponde solo a richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo non consentito']);
    exit;
}

// Helper: legge e ripulisce un campo
function campo($nome) {
    return isset($_POST[$nome]) ? trim($_POST[$nome]) : '';
}

// --- Anti-spam: honeypot. I bot compilano il campo nascosto "website" ---
if (campo('website') !== '') {
    // Fingiamo successo per non insospettire il bot
    echo json_encode(['ok' => true]);
    exit;
}

// --- Raccolta dati ---
$nome      = campo('nome');
$email     = campo('email');
$telefono  = campo('telefono');
$messaggio = campo('messaggio');

// --- Validazione ---
$errori = [];
if ($nome === '')                                  $errori[] = 'nome';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errori[] = 'email';
if ($messaggio === '')                             $errori[] = 'messaggio';

if (!empty($errori)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Campi mancanti o non validi: ' . implode(', ', $errori)]);
    exit;
}

// Difesa contro header injection
foreach ([$nome, $email] as $v) {
    if (preg_match('/[\r\n]/', $v)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Input non valido']);
        exit;
    }
}

// --- Composizione messaggio ---
$oggetto = $OGGETTO_PREFIX . ' — ' . $nome;
$corpo =
    "Hai ricevuto un nuovo messaggio dal sito web.\n\n" .
    "Nome e Cognome: {$nome}\n" .
    "Email: {$email}\n" .
    "Telefono: " . ($telefono !== '' ? $telefono : '—') . "\n\n" .
    "Messaggio:\n{$messaggio}\n\n" .
    "----\nInviato da: " . ($_SERVER['HTTP_HOST'] ?? 'sito') . " il " . date('d/m/Y H:i');

/* =====================================================================
   INVIO
   ===================================================================== */
$inviato = false;

if ($USA_SMTP) {
    // ---- Invio via SMTP con PHPMailer (richiede la libreria, vedi README) ----
    $autoload = __DIR__ . '/PHPMailer/src/PHPMailer.php';
    if (file_exists($autoload)) {
        require __DIR__ . '/PHPMailer/src/Exception.php';
        require __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require __DIR__ . '/PHPMailer/src/SMTP.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $SMTP['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $SMTP['user'];
            $mail->Password   = $SMTP['pass'];
            $mail->SMTPSecure = $SMTP['secure'];
            $mail->Port       = $SMTP['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($MITTENTE, $NOME_MITTENTE);
            $mail->addAddress($DESTINATARIO);
            $mail->addReplyTo($email, $nome);
            $mail->Subject = $oggetto;
            $mail->Body    = $corpo;
            $mail->send();
            $inviato = true;
        } catch (Exception $e) {
            $inviato = false;
        }
    }
} else {
    // ---- Invio con la funzione mail() di PHP ----
    $headers  = 'From: ' . $NOME_MITTENTE . ' <' . $MITTENTE . ">\r\n";
    $headers .= 'Reply-To: ' . $email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $oggetto_enc = '=?UTF-8?B?' . base64_encode($oggetto) . '?=';
    $inviato = @mail($DESTINATARIO, $oggetto_enc, $corpo, $headers, '-f' . $MITTENTE);
}

if ($inviato) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Invio non riuscito']);
}
