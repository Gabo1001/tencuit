<?php
/**
 * admin.php
 * Panou de administrare pentru calculatorul de prețuri.
 * Protejat cu parolă. Salvează config în calculator_config.json.
 *
 * IMPORTANT: Schimbă parola ADMIN_PASS înainte de a urca pe server!
 */

// ─── CONFIGURARE ADMIN ───────────────────
define('ADMIN_PASS',    'schimba_parola_123');   // ← SCHIMBĂ ACEASTA!
define('SESSION_HOURS', 4);                      // sesiune validă ore
// ────────────────────────────────────────

session_start();
$config_file = __DIR__ . '/calculator_config.json';
$eroare  = '';
$succes  = '';

// ── Logout ────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Verifică sesiune ──────────────────────
$logat = isset($_SESSION['admin_ok']) && $_SESSION['admin_ok'] === true
         && (time() - ($_SESSION['login_time'] ?? 0)) < SESSION_HOURS * 3600;

// ── Login ─────────────────────────────────
if (!$logat && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parola'])) {
    if ($_POST['parola'] === ADMIN_PASS) {
        $_SESSION['admin_ok']    = true;
        $_SESSION['login_time']  = time();
        $logat = true;
    } else {
        $eroare = 'Parolă incorectă!';
    }
}

// ── Salvare configurație ──────────────────
if ($logat && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune']) && $_POST['actiune'] === 'salveaza') {

    // Procesăm opțiunile selectabile (una pe linie)
    $opt1_raw = array_filter(array_map('trim', explode("\n", $_POST['optiuni_t1'] ?? '')));
    $opt2_raw = array_filter(array_map('trim', explode("\n", $_POST['optiuni_t2'] ?? '')));
    $opt1 = array_values($opt1_raw);
    $opt2 = array_values($opt2_raw);

    // Coeficienți pentru fiecare opțiune (separați prin linie nouă, format: Opțiune=valoare)
    function parseCoef($raw, $opts) {
        $map = [];
        $linii = array_filter(array_map('trim', explode("\n", $raw)));
        foreach ($linii as $l) {
            if (strpos($l, '=') !== false) {
                [$k, $v] = explode('=', $l, 2);
                $map[trim($k)] = floatval(trim($v));
            }
        }
        // Completează automat valorile lipsă cu 1.0
        foreach ($opts as $opt) {
            if (!isset($map[$opt])) $map[$opt] = 1.0;
        }
        return $map;
    }

    $coef1 = parseCoef($_POST['coef_t1_raw'] ?? '', $opt1);
    $coef2 = parseCoef($_POST['coef_t2_raw'] ?? '', $opt2);

    $config = [
        'lbl_t1'     => htmlspecialchars(trim($_POST['lbl_t1'] ?? 'Selectează Serviciu 1:'), ENT_QUOTES, 'UTF-8'),
        'lbl_t2'     => htmlspecialchars(trim($_POST['lbl_t2'] ?? 'Selectează Serviciu 2:'), ENT_QUOTES, 'UTF-8'),
        'lbl_t3'     => htmlspecialchars(trim($_POST['lbl_t3'] ?? 'Valoare Manuală 1:'), ENT_QUOTES, 'UTF-8'),
        'lbl_t4'     => htmlspecialchars(trim($_POST['lbl_t4'] ?? 'Valoare Manuală 2:'), ENT_QUOTES, 'UTF-8'),
        'optiuni_t1' => $opt1,
        'optiuni_t2' => $opt2,
        'pret_baza'  => floatval($_POST['pret_baza'] ?? 120),
        'coef_t1'    => $coef1,
        'coef_t2'    => $coef2,
        'coef_t3'    => floatval($_POST['coef_t3'] ?? 0.5),
        'coef_t4'    => floatval($_POST['coef_t4'] ?? 2.0),
        'moneda'     => htmlspecialchars(trim($_POST['moneda'] ?? 'MDL'), ENT_QUOTES, 'UTF-8'),
        '_salvat_la' => date('d.m.Y H:i:s'),
    ];

    if (file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $succes = '✅ Configurația a fost salvată! Site-ul se va actualiza automat în max. 30 secunde.';
    } else {
        $eroare = '❌ Nu am putut salva fișierul. Verifică permisiunile folderului (chmod 755).';
    }
}

// ── Citire config curentă ─────────────────
$cfg = [];
if (file_exists($config_file)) {
    $cfg = json_decode(file_get_contents($config_file), true) ?? [];
}

// Valori default pentru form
$d = function($key, $default) use ($cfg) { return $cfg[$key] ?? $default; };

$coef1_raw = '';
foreach (($cfg['coef_t1'] ?? []) as $k => $v) $coef1_raw .= "$k=$v\n";
$coef2_raw = '';
foreach (($cfg['coef_t2'] ?? []) as $k => $v) $coef2_raw .= "$k=$v\n";

?><!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Calculator Prețuri | Tencuit.md</title>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
  .admin-wrap { max-width: 900px; margin: 40px auto; padding: 0 15px 60px; }
  .admin-header { background: linear-gradient(135deg,#f7b205,#e8940a); color:#fff; border-radius: 10px; padding: 24px 30px; margin-bottom: 28px; display:flex; align-items:center; justify-content:space-between; }
  .admin-header h1 { margin:0; font-size:22px; }
  .admin-header small { opacity:.85; font-size:13px; }
  .card { border:none; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.09); margin-bottom:24px; }
  .card-header { background:#fff; border-bottom:2px solid #f7b205; font-weight:700; color:#333; border-radius:10px 10px 0 0 !important; }
  .section-title { color:#f7b205; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
  .btn-salveaza { background:#f7b205; border:none; color:#fff; font-weight:700; padding:12px 40px; border-radius:6px; font-size:16px; }
  .btn-salveaza:hover { background:#d9980a; color:#fff; }
  .badge-live { background:#28a745; color:#fff; font-size:11px; padding:3px 8px; border-radius:20px; }
  .login-box { max-width:380px; margin:80px auto; }
  .login-box .card { border-radius:12px; }
  .coef-help { font-size:12px; color:#888; margin-top:4px; }
  textarea.form-control { font-family: monospace; font-size: 13px; }
  .preview-box { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:12px; margin-top:10px; font-size:13px; color:#555; }
  .preview-box span { background:#f7b205; color:#fff; border-radius:4px; padding:2px 8px; margin:2px; display:inline-block; font-size:12px; }
  #formula-preview { font-size:14px; color:#333; margin-top:10px; background:#fffbf0; border:1px solid #f7b205; border-radius:6px; padding:14px; }
  .salvat-la { font-size:12px; color:#aaa; text-align:right; margin-top:-16px; margin-bottom:16px; }
</style>
</head>
<body>

<?php if (!$logat): ?>
<!-- ══════════════════════ LOGIN ══════════════════════ -->
<div class="login-box">
  <div class="card shadow">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <i class="fas fa-lock fa-2x" style="color:#f7b205"></i>
        <h4 class="mt-2">Admin Panou</h4>
        <p class="text-muted small">Calculator Prețuri — Tencuit.md</p>
      </div>
      <?php if ($eroare): ?>
        <div class="alert alert-danger"><?= $eroare ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label>Parolă</label>
          <input type="password" name="parola" class="form-control" autofocus required>
        </div>
        <button type="submit" class="btn btn-block btn-salveaza">
          <i class="fas fa-sign-in-alt mr-2"></i> Intră în admin
        </button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════ DASHBOARD ══════════════════════ -->
<div class="admin-wrap">

  <div class="admin-header">
    <div>
      <h1><i class="fas fa-calculator mr-2"></i> Admin Calculator Prețuri</h1>
      <small>Modificările se aplică live pe site în max. 30 secunde <span class="badge-live">LIVE</span></small>
    </div>
    <a href="admin.php?logout=1" class="btn btn-sm btn-outline-light">
      <i class="fas fa-sign-out-alt mr-1"></i> Ieși
    </a>
  </div>

  <?php if ($succes): ?>
    <div class="alert alert-success"><?= $succes ?></div>
  <?php endif; ?>
  <?php if ($eroare): ?>
    <div class="alert alert-danger"><?= $eroare ?></div>
  <?php endif; ?>

  <?php if (!empty($cfg['_salvat_la'])): ?>
    <p class="salvat-la"><i class="fas fa-clock mr-1"></i> Ultima salvare: <?= $cfg['_salvat_la'] ?></p>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="actiune" value="salveaza">

    <!-- ── Card 1: Etichete ── -->
    <div class="card">
      <div class="card-header p-3">
        <i class="fas fa-tag mr-2"></i> Etichete câmpuri calculator
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 form-group">
            <div class="section-title">Etichetă Select 1</div>
            <input type="text" name="lbl_t1" class="form-control" value="<?= htmlspecialchars($d('lbl_t1','Selectează Serviciu 1:')) ?>">
          </div>
          <div class="col-md-6 form-group">
            <div class="section-title">Etichetă Select 2</div>
            <input type="text" name="lbl_t2" class="form-control" value="<?= htmlspecialchars($d('lbl_t2','Selectează Serviciu 2:')) ?>">
          </div>
          <div class="col-md-6 form-group">
            <div class="section-title">Etichetă Câmp Manual 1</div>
            <input type="text" name="lbl_t3" class="form-control" value="<?= htmlspecialchars($d('lbl_t3','Grosime strat (mm):')) ?>">
          </div>
          <div class="col-md-6 form-group">
            <div class="section-title">Etichetă Câmp Manual 2</div>
            <input type="text" name="lbl_t4" class="form-control" value="<?= htmlspecialchars($d('lbl_t4','Distanță transport (km):')) ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── Card 2: Opțiuni selectabile ── -->
    <div class="card">
      <div class="card-header p-3">
        <i class="fas fa-list mr-2"></i> Opțiuni meniuri selectabile
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 form-group">
            <div class="section-title">Opțiuni Select 1 (una pe linie)</div>
            <textarea name="optiuni_t1" class="form-control" rows="5" id="opt1-ta"><?= htmlspecialchars(implode("\n", $d('optiuni_t1',['Interior','Exterior']))) ?></textarea>
            <div class="preview-box" id="prev1">
              <strong>Previzualizare:</strong><br>
              <?php foreach ($d('optiuni_t1',['Interior','Exterior']) as $o): ?>
                <span><?= htmlspecialchars($o) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-md-6 form-group">
            <div class="section-title">Opțiuni Select 2 (una pe linie)</div>
            <textarea name="optiuni_t2" class="form-control" rows="5" id="opt2-ta"><?= htmlspecialchars(implode("\n", $d('optiuni_t2',['Sub 100 m²','100 - 300 m²','Peste 300 m²']))) ?></textarea>
            <div class="preview-box" id="prev2">
              <strong>Previzualizare:</strong><br>
              <?php foreach ($d('optiuni_t2',['Sub 100 m²','100 - 300 m²','Peste 300 m²']) as $o): ?>
                <span><?= htmlspecialchars($o) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Card 3: Prețuri & Coeficienți ── -->
    <div class="card">
      <div class="card-header p-3">
        <i class="fas fa-coins mr-2"></i> Prețuri & Coeficienți
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 form-group">
            <div class="section-title">Preț de bază (MDL/m²)</div>
            <input type="number" name="pret_baza" class="form-control" step="0.01" id="pret-baza" value="<?= $d('pret_baza',120) ?>">
            <small class="text-muted">Prețul de pornire al calculului</small>
          </div>
          <div class="col-md-4 form-group">
            <div class="section-title">Coeficient câmp manual 1</div>
            <input type="number" name="coef_t3" class="form-control" step="0.01" value="<?= $d('coef_t3',0.5) ?>">
            <small class="text-muted">Valoare × coef = adaos la total</small>
          </div>
          <div class="col-md-4 form-group">
            <div class="section-title">Coeficient câmp manual 2</div>
            <input type="number" name="coef_t4" class="form-control" step="0.01" value="<?= $d('coef_t4',2.0) ?>">
            <small class="text-muted">Valoare × coef = adaos la total</small>
          </div>
        </div>

        <div class="row mt-2">
          <div class="col-md-6 form-group">
            <div class="section-title">Coeficienți Select 1 (format: Opțiune=valoare)</div>
            <textarea name="coef_t1_raw" class="form-control" rows="5"><?= htmlspecialchars(trim($coef1_raw)) ?></textarea>
            <p class="coef-help">Ex: <code>Interior=1.0</code><br><code>Exterior=1.2</code><br>Prețul de bază se înmulțește cu acest coef.</p>
          </div>
          <div class="col-md-6 form-group">
            <div class="section-title">Coeficienți Select 2 (format: Opțiune=valoare)</div>
            <textarea name="coef_t2_raw" class="form-control" rows="5"><?= htmlspecialchars(trim($coef2_raw)) ?></textarea>
            <p class="coef-help">Ex: <code>Sub 100 m²=1.3</code><br><code>100 - 300 m²=1.1</code></p>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 form-group">
            <div class="section-title">Monedă</div>
            <input type="text" name="moneda" class="form-control" value="<?= htmlspecialchars($d('moneda','MDL')) ?>" maxlength="5">
          </div>
        </div>

        <!-- Formula vizualizată -->
        <div id="formula-preview">
          <strong>Formula de calcul:</strong><br>
          <code>Total = Preț_bază × Coef_Select1 × Coef_Select2 + (CâmpManual1 × CoefM1) + (CâmpManual2 × CoefM2)</code>
        </div>
      </div>
    </div>

    <div class="text-center mt-3">
      <button type="submit" class="btn btn-salveaza">
        <i class="fas fa-save mr-2"></i> SALVEAZĂ CONFIGURAȚIA
      </button>
      <a href="index.html" target="_blank" class="btn btn-outline-secondary ml-3">
        <i class="fas fa-eye mr-1"></i> Vezi site-ul
      </a>
    </div>

  </form>
</div>

<script>
// Preview live pentru opțiunile din textarea
function updatePreview(taId, prevId) {
  const ta = document.getElementById(taId);
  const prev = document.getElementById(prevId);
  ta.addEventListener('input', function() {
    const linii = this.value.split('\n').map(l => l.trim()).filter(Boolean);
    prev.innerHTML = '<strong>Previzualizare:</strong><br>' +
      linii.map(l => `<span>${l}</span>`).join('');
  });
}
updatePreview('opt1-ta','prev1');
updatePreview('opt2-ta','prev2');
</script>

<?php endif; ?>

</body>
</html>