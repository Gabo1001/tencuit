<?php
/**
 * procesare_contact.php
 * Primește datele din formularul de contact și trimite email la deținătorul site-ului.
 * Compatibil cPanel / PHP mail() sau PHPMailer dacă e configurat.
 *
 * Pune acest fișier în rădăcina site-ului, lângă index.html
 */

header('Content-Type: application/json; charset=utf-8');

// ─────────────────────────────────────────
//  CONFIGURARE — schimbă doar aceste valori
// ─────────────────────────────────────────
define('DESTINATAR_EMAIL', 'info@tencuit.md');          // ← emailul deținătorului
define('DESTINATAR_NUME',  'Mixokret RR Grup');
define('SITE_NUME',        'Tencuit.md');
define('REPLY_TO_DEFAUT',  'noreply@tencuit.md');
// ─────────────────────────────────────────

// Acceptăm doar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodă nepermisă.']);
    exit;
}

// ── Colectare & validare câmpuri ──────────
$email     = filter_var(trim($_POST['email']    ?? ''), FILTER_VALIDATE_EMAIL);
$suprafata = intval($_POST['suprafata'] ?? 0);
$detalii   = trim($_POST['detalii']    ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Adresa de email nu este validă.']);
    exit;
}
if ($suprafata <= 0) {
    echo json_encode(['success' => false, 'message' => 'Suprafața trebuie să fie mai mare ca 0.']);
    exit;
}
if (strlen($detalii) < 5) {
    echo json_encode(['success' => false, 'message' => 'Vă rugăm adăugați o descriere mai detaliată.']);
    exit;
}

// ── Protecție anti-spam basic ─────────────
$detalii   = htmlspecialchars($detalii, ENT_QUOTES, 'UTF-8');
$suprafata = (int)$suprafata;

// ── Construire email ──────────────────────
$data_ora = date('d.m.Y H:i:s');

$subiect = "=?UTF-8?B?" . base64_encode("Cerere nouă de ofertă — " . SITE_NUME) . "?=";

$mesaj_text = <<<TXT
Ați primit o cerere nouă de ofertă prin site-ul {$_SERVER['HTTP_HOST']}.

─────────────────────────────
 DATA / ORA : {$data_ora}
 EMAIL CLIENT: {$email}
 SUPRAFAȚĂ   : {$suprafata} m²
─────────────────────────────
 DESCRIERE LUCRARE:
{$detalii}
─────────────────────────────

Răspundeți direct la acest email sau sunați clientul.

---
Mesaj generat automat de {$_SERVER['HTTP_HOST']}
TXT;

$mesaj_html = <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8"><style>
  body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
  .wrap { max-width:580px; margin:30px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.15); }
  .hdr  { background:#f7b205; padding:24px 30px; }
  .hdr h1 { color:#fff; margin:0; font-size:22px; }
  .body { padding:28px 30px; }
  .row  { display:flex; margin-bottom:14px; border-bottom:1px solid #eee; padding-bottom:14px; }
  .lbl  { width:140px; font-weight:bold; color:#555; font-size:14px; flex-shrink:0; }
  .val  { color:#222; font-size:14px; word-break:break-word; }
  .desc { background:#f9f9f9; border-left:4px solid #f7b205; padding:14px 16px; border-radius:4px; margin-top:10px; font-size:14px; color:#333; white-space:pre-wrap; }
  .ftr  { background:#222; padding:14px 30px; color:#aaa; font-size:12px; text-align:center; }
</style></head>
<body>
<div class="wrap">
  <div class="hdr"><h1>📋 Cerere nouă de ofertă</h1></div>
  <div class="body">
    <p style="color:#666;font-size:14px;margin-top:0;">Ați primit o solicitare nouă prin site-ul <strong>{$_SERVER['HTTP_HOST']}</strong> pe data de <strong>{$data_ora}</strong>.</p>

    <div class="row"><span class="lbl">Email client:</span><span class="val"><a href="mailto:{$2020gabrielghe@gmail.com}">{$2020gabrielghe@gmail.com}</a></span></div>
    <div class="row" style="border:none"><span class="lbl">Suprafață:</span><span class="val">{$suprafata} m²</span></div>

    <p style="font-weight:bold;color:#555;margin-bottom:6px;font-size:14px;">Descriere lucrare:</p>
    <div class="desc">{$detalii}</div>

    <p style="margin-top:24px;font-size:13px;color:#888;">Răspundeți la <a href="mailto:{$2020gabrielghe@gmail.com}">{$2020gabrielghe@gmail.com}</a> sau contactați direct clientul.</p>
  </div>
  <div class="ftr">&copy; Mesaj automat &mdash; {$_SERVER['HTTP_HOST']}</div>
</div>
</body></html>
HTML;

// ── Anteturi email ────────────────────────
$boundary = md5(uniqid(rand(), true));

$headers  = "From: " . SITE_NUME . " <" . REPLY_TO_DEFAUT . ">\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$corp  = "--{$boundary}\r\n";
$corp .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$corp .= $mesaj_text . "\r\n";
$corp .= "--{$boundary}\r\n";
$corp .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
$corp .= $mesaj_html . "\r\n";
$corp .= "--{$boundary}--";

// ── Trimitere ─────────────────────────────
$trimis = mail(DESTINATAR_EMAIL, $subiect, $corp, $headers);

if ($trimis) {
    // Log opțional în fișier (decomentează dacă vrei)
    file_put_contents('cereri_log.txt', "[{$data_ora}] {$email} | {$suprafata}m2\n", FILE_APPEND);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la trimiterea emailului. Contactați-ne telefonic.']);
}