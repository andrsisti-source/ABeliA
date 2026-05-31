# Invio automatico del modulo di contatto

Il form della home (`index.html`) invia i dati a `invia.php`, che spedisce
l'email a **info@abelia.it** senza aprire il programma di posta del visitatore.

## Messa online (3 passi)
1. Carica `index.html`, `chi-siamo.html`, `style.css`, `logo.jpg` **e `invia.php`**
   nella stessa cartella sul tuo hosting (deve supportare PHP — Aruba,
   Register.it, SiteGround, ecc.).
2. Assicurati che la casella **info@abelia.it** esista sul dominio.
3. Fatto. Apri il sito, compila il modulo e premi "Invia il messaggio":
   il messaggio arriva direttamente in casella.

> Il modulo **non funziona in anteprima locale** perché serve un server PHP:
> va provato una volta caricato sull'hosting.

## Se le email non arrivano (passaggio a SMTP)
Su alcuni hosting la funzione `mail()` di PHP è disattivata o finisce in spam.
In quel caso usa l'invio autenticato SMTP:

1. Scarica [PHPMailer](https://github.com/PHPMailer/PHPMailer) e carica la
   cartella `PHPMailer/` accanto a `invia.php`.
2. In cima a `invia.php` imposta `$USA_SMTP = true;`. Il blocco `$SMTP` è già
   precompilato per **TopHost**:
   - host: `mail.tophost.it`
   - porta: `587`
   - secure: `tls` (STARTTLS)
   - **user: il nome del dominio** (es. `abelia.it`), NON l'email completa —
     lo trovi nel cpanel TopHost in "Gestione avanzata email".
   - pass: la password della casella `info@abelia.it`.
3. Salva e ricarica `invia.php`.

> ⚠️ Per evitare che le email finiscano in spam, controlla nel pannello TopHost
> che il record **SPF** del dominio sia attivo/corretto.

## Anti-spam
È già attivo un campo nascosto "honeypot": i bot che lo compilano vengono
ignorati automaticamente, senza fastidi per gli utenti reali.
