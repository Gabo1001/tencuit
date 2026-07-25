
define('ADMIN_PASS',    'schimba_parola_123');
define('SESSION_HOURS', 4);
define('CONFIG_FILE',   __DIR__ . '/calculator_config.json');
define('CERERI_FILE',   __DIR__ . '/cereri.json');

session_start();
$eroare = '';
$succes = '';

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
        $_SESSION['admin_ok']   = true;
        $_SESSION['login_time'] = time();
        $logat = true;
    } else {
        $eroare = 'Parolă incorectă!';
    }
}

// ── Marchează mesaj ca citit ──────────────
if ($logat && isset($_GET['citit'])) {
    $cereri = file_exists(CERERI_FILE) ? (json_decode(file_get_contents(CERERI_FILE), true) ?? []) : [];
    foreach ($cereri as &$c) {
        if ($c['id'] === $_GET['citit']) $c['citit'] = true;
    }
    file_put_contents(CERERI_FILE, json_encode($cereri, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: admin.php?tab=mesaje');
    exit;
}

// ── Șterge mesaj ─────────────────────────
if ($logat && isset($_GET['sterge'])) {
    $cereri = file_exists(CERERI_FILE) ? (json_decode(file_get_contents(CERERI_FILE), true) ?? []) : [];
    $cereri = array_values(array_filter($cereri, fn($c) => $c['id'] !== $_GET['sterge']));
    file_put_contents(CERERI_FILE, json_encode($cereri, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: admin.php?tab=mesaje');
    exit;
}

// ── Salvare configurație ──────────────────
if ($logat && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune']) && $_POST['actiune'] === 'salveaza') {
    $opt1 = array_values(array_filter(array_map('trim', explode("\n", $_POST['optiuni_t1'] ?? ''))));
    $opt2 = array_values(array_filter(array_map('trim', explode("\n", $_POST['optiuni_t2'] ?? ''))));

    function parseCoef($raw, $opts) {
        $map = [];
        foreach (array_filter(array_map('trim', explode("\n", $raw))) as $l) {
            if (strpos($l, '=') !== false) {
                [$k, $v] = explode('=', $l, 2);
                $map[trim($k)] = floatval(trim($v));
            }
        }
        foreach ($opts as $opt) { if (!isset($map[$opt])) $map[$opt] = 1.0; }
        return $map;
    }

    $config = [
        'lbl_t1'     => htmlspecialchars(trim($_POST['lbl_t1'] ?? 'Selectează Serviciu 1:'), ENT_QUOTES, 'UTF-8'),
        'lbl_t2'     => htmlspecialchars(trim($_POST['lbl_t2'] ?? 'Selectează Serviciu 2:'), ENT_QUOTES, 'UTF-8'),
        'lbl_t3'     => htmlspecialchars(trim($_POST['lbl_t3'] ?? 'Valoare Manuală 1:'), ENT_QUOTES, 'UTF-8'),
        'lbl_t4'     => htmlspecialchars(trim($_POST['lbl_t4'] ?? 'Valoare Manuală 2:'), ENT_QUOTES, 'UTF-8'),
        'optiuni_t1' => $opt1,
        'optiuni_t2' => $opt2,
        'pret_baza'  => floatval($_POST['pret_baza'] ?? 120),
        'coef_t1'    => parseCoef($_POST['coef_t1_raw'] ?? '', $opt1),
        'coef_t2'    => parseCoef($_POST['coef_t2_raw'] ?? '', $opt2),
        'coef_t3'    => floatval($_POST['coef_t3'] ?? 0.5),
        'coef_t4'    => floatval($_POST['coef_t4'] ?? 2.0),
        'moneda'     => htmlspecialchars(trim($_POST['moneda'] ?? 'MDL'), ENT_QUOTES, 'UTF-8'),
        '_salvat_la' => date('d.m.Y H:i:s'),
    ];

    if (file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $succes = '✅ Configurația a fost salvată! Site-ul se actualizează automat.';
    } else {
        $eroare = '❌ Nu am putut salva fișierul. Verifică permisiunile folderului (chmod 755).';
    }
}

// ── Citire date ───────────────────────────
$cfg = [];
if (file_exists(CONFIG_FILE)) $cfg = json_decode(file_get_contents(CONFIG_FILE), true) ?? [];
$d = fn($key, $default) => $cfg[$key] ?? $default;

$coef1_raw = ''; foreach (($cfg['coef_t1'] ?? []) as $k => $v) $coef1_raw .= "$k=$v\n";
$coef2_raw = ''; foreach (($cfg['coef_t2'] ?? []) as $k => $v) $coef2_raw .= "$k=$v\n";

$cereri = [];
if (file_exists(CERERI_FILE)) $cereri = array_reverse(json_decode(file_get_contents(CERERI_FILE), true) ?? []);
$necitite = count(array_filter($cereri, fn($c) => !$c['citit']));

$tab = $_GET['tab'] ?? 'calculator';
?><!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Tencuit.md</title>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --blue: #6599FF;
    --dark: #030f27;
    --yellow: #f7b205;
    --card-shadow: 0 4px 20px rgba(0,0,0,.10);
  }
  * { box-sizing: border-box; }
  body { background: #f0f3fa; font-family: 'Poppins', sans-serif; color: #444; margin: 0; }

  /* ── Sidebar ── */
  .sidebar {
    position: fixed; top: 0; left: 0; height: 100vh; width: 240px;
    background: var(--dark); display: flex; flex-direction: column;
    z-index: 100; box-shadow: 4px 0 20px rgba(0,0,0,.3);
  }
  .sidebar-logo {
    padding: 28px 24px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
  }
  .sidebar-logo img { height: 36px; }
  .sidebar-logo span { display: block; color: rgba(255,255,255,.5); font-size: 11px; margin-top: 4px; letter-spacing: 1px; text-transform: uppercase; }
  .sidebar-nav { padding: 20px 0; flex: 1; }
  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 24px; color: rgba(255,255,255,.65);
    text-decoration: none; font-size: 14px; font-weight: 500;
    transition: all .2s; cursor: pointer; border: none; background: none; width: 100%;
  }
  .nav-item:hover { color: #fff; background: rgba(255,255,255,.06); text-decoration: none; }
  .nav-item.active { color: #fff; background: var(--blue); }
  .nav-item i { width: 18px; text-align: center; font-size: 15px; }
  .badge-count {
    margin-left: auto; background: #e74c3c; color: #fff;
    border-radius: 20px; padding: 2px 8px; font-size: 11px; font-weight: 700;
  }
  .sidebar-footer {
    padding: 16px 24px; border-top: 1px solid rgba(255,255,255,.08);
  }
  .btn-logout {
    display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,.5);
    font-size: 13px; text-decoration: none; transition: color .2s;
  }
  .btn-logout:hover { color: #fff; text-decoration: none; }

  /* ── Main content ── */
  .main { margin-left: 240px; min-height: 100vh; }
  .topbar {
    background: #fff; padding: 18px 32px; border-bottom: 1px solid #e8ecf5;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
  }
  .topbar h1 { margin: 0; font-size: 20px; font-weight: 700; color: var(--dark); }
  .topbar small { color: #aaa; font-size: 12px; }
  .badge-live { background: #28a745; color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 20px; font-weight: 600; letter-spacing: .5px; }
  .content { padding: 32px; }

  /* ── Cards ── */
  .card {
    border: none; border-radius: 12px; box-shadow: var(--card-shadow);
    margin-bottom: 24px; overflow: hidden;
  }
  .card-header {
    background: #fff; border-bottom: 2px solid var(--blue);
    font-weight: 600; color: var(--dark); padding: 16px 20px;
    display: flex; align-items: center; gap: 10px; font-size: 14px;
  }
  .card-header i { color: var(--blue); }
  .card-body { padding: 24px; background: #fff; }

  /* ── Form elements ── */
  .section-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--blue); margin-bottom: 6px;
  }
  .form-control {
    border: 1.5px solid #e0e6f0; border-radius: 8px;
    font-size: 13px; transition: border-color .2s;
  }
  .form-control:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(101,153,255,.15); }
  textarea.form-control { font-family: 'Courier New', monospace; font-size: 12px; }
  .preview-box {
    background: #f5f8ff; border: 1px solid #d0dcff; border-radius: 8px;
    padding: 12px; margin-top: 8px;
  }
  .preview-box .tag {
    display: inline-block; background: var(--blue); color: #fff;
    border-radius: 5px; padding: 3px 10px; margin: 2px; font-size: 12px;
  }
  .formula-box {
    background: #f0f5ff; border: 1.5px solid var(--blue); border-radius: 8px;
    padding: 16px; margin-top: 8px; font-size: 13px; color: #333;
  }
  .formula-box code { background: rgba(101,153,255,.15); padding: 2px 6px; border-radius: 4px; }
  .salvat-la { font-size: 12px; color: #bbb; text-align: right; margin-top: -16px; margin-bottom: 20px; }

  /* ── Buttons ── */
  .btn-save {
    background: var(--blue); border: none; color: #fff;
    font-weight: 700; padding: 13px 48px; border-radius: 8px;
    font-size: 15px; transition: background .2s; letter-spacing: .5px;
  }
  .btn-save:hover { background: #4a7df0; color: #fff; }
  .btn-view { background: var(--dark); border: none; color: #fff; font-weight: 600; padding: 12px 28px; border-radius: 8px; font-size: 14px; }
  .btn-view:hover { background: #0a1f4a; color: #fff; }

  /* ── Inbox ── */
  .msg-card {
    background: #fff; border-radius: 12px; padding: 20px 24px;
    margin-bottom: 14px; box-shadow: var(--card-shadow);
    border-left: 4px solid var(--blue); transition: transform .15s;
  }
  .msg-card.unread { border-left-color: var(--yellow); background: #fffcf0; }
  .msg-card:hover { transform: translateY(-1px); }
  .msg-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
  .msg-email { font-weight: 700; color: var(--dark); font-size: 15px; }
  .msg-date { color: #aaa; font-size: 12px; }
  .msg-sup { background: var(--blue); color: #fff; border-radius: 20px; padding: 3px 12px; font-size: 12px; font-weight: 600; }
  .msg-badge-new { background: var(--yellow); color: #fff; border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 700; }
  .msg-body { color: #555; font-size: 13px; line-height: 1.6; white-space: pre-wrap; background: #f7f9ff; border-radius: 8px; padding: 12px 14px; }
  .msg-actions { margin-top: 12px; display: flex; gap: 8px; }
  .btn-read { background: #e8f0ff; color: var(--blue); border: none; border-radius: 6px; padding: 6px 16px; font-size: 12px; font-weight: 600; cursor: pointer; }
  .btn-read:hover { background: var(--blue); color: #fff; }
  .btn-del { background: #ffeaea; color: #e74c3c; border: none; border-radius: 6px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; }
  .btn-del:hover { background: #e74c3c; color: #fff; }
  .btn-reply { background: #e8ffe8; color: #28a745; border: none; border-radius: 6px; padding: 6px 16px; font-size: 12px; font-weight: 600; }
  .btn-reply:hover { background: #28a745; color: #fff; }
  .empty-state { text-align: center; padding: 60px 20px; color: #bbb; }
  .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }

  /* ── Login ── */
  .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--dark); }
  .login-card { background: #fff; border-radius: 16px; padding: 48px 40px; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
  .login-card h2 { color: var(--dark); font-weight: 700; margin-bottom: 4px; }
  .login-card p { color: #aaa; font-size: 13px; }
  .login-logo { text-align: center; margin-bottom: 28px; }
  .login-logo .logo-circle { width: 64px; height: 64px; background: var(--blue); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; }

  /* ── Alert ── */
  .alert-success { background: #edfaf1; border: 1px solid #27ae60; color: #27ae60; border-radius: 8px; padding: 14px 18px; }
  .alert-danger  { background: #fdf3f2; border: 1px solid #e74c3c; color: #c0392b; border-radius: 8px; padding: 14px 18px; }

  @media(max-width:768px) {
    .sidebar { transform: translateX(-100%); }
    .main { margin-left: 0; }
  }
</style>
</head>
<body>

<?php if (!$logat): ?>
<!-- ══════════════════════ LOGIN ══════════════════════ -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle"><i class="fas fa-lock"></i></div>
    </div>
    <h2 class="text-center">Panou Admin</h2>
    <p class="text-center">Tencuit.md — Acces restricționat</p>
    <?php if ($eroare): ?>
      <div class="alert-danger mb-3"><?= $eroare ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label style="font-weight:600;color:#444;font-size:13px;">Parolă de acces</label>
        <input type="password" name="parola" class="form-control" placeholder="••••••••" autofocus required style="padding:12px 14px;font-size:15px;">
      </div>
      <button type="submit" class="btn btn-save btn-block mt-3" style="padding:14px;">
        <i class="fas fa-sign-in-alt mr-2"></i> Intră în panou
      </button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════ DASHBOARD ══════════════════════ -->

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo">
    <img src="img/logo-white.png" alt="Tencuit.md" onerror="this.style.display='none'">
    <span>Panou Administrare</span>
  </div>
  <nav class="sidebar-nav">
    <a href="admin.php?tab=calculator" class="nav-item <?= $tab === 'calculator' ? 'active' : '' ?>">
      <i class="fas fa-calculator"></i> Calculator Prețuri
    </a>
    <a href="admin.php?tab=mesaje" class="nav-item <?= $tab === 'mesaje' ? 'active' : '' ?>">
      <i class="fas fa-envelope"></i> Mesaje
      <?php if ($necitite > 0): ?><span class="badge-count"><?= $necitite ?></span><?php endif; ?>
    </a>
    <a href="index.html" target="_blank" class="nav-item">
      <i class="fas fa-external-link-alt"></i> Vezi site-ul
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="admin.php?logout=1" class="btn-logout">
      <i class="fas fa-sign-out-alt"></i> Deconectare
    </a>
  </div>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div>
      <h1>
        <?= $tab === 'mesaje' ? '<i class="fas fa-envelope mr-2" style="color:var(--blue)"></i> Inbox Mesaje' : '<i class="fas fa-calculator mr-2" style="color:var(--blue)"></i> Calculator Prețuri' ?>
      </h1>
      <?php if ($tab === 'calculator'): ?>
        <small>Modificările se aplică live pe site <span class="badge-live">LIVE</span></small>
      <?php else: ?>
        <small><?= count($cereri) ?> mesaje totale, <?= $necitite ?> necitite</small>
      <?php endif; ?>
    </div>
    <?php if (!empty($cfg['_salvat_la']) && $tab === 'calculator'): ?>
      <small style="color:#bbb;"><i class="fas fa-clock mr-1"></i> Salvat: <?= $cfg['_salvat_la'] ?></small>
    <?php endif; ?>
  </div>

  <div class="content">

    <?php if ($succes): ?><div class="alert-success mb-4"><?= $succes ?></div><?php endif; ?>
    <?php if ($eroare): ?><div class="alert-danger mb-4"><?= $eroare ?></div><?php endif; ?>

    <?php if ($tab === 'calculator'): ?>
    <!-- ══ TAB CALCULATOR ══ -->
    <form method="POST">
      <input type="hidden" name="actiune" value="salveaza">

      <!-- Card: Etichete -->
      <div class="card">
        <div class="card-header"><i class="fas fa-tag"></i> Etichete câmpuri calculator</div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 form-group">
              <div class="section-label">Etichetă Select 1</div>
              <input type="text" name="lbl_t1" class="form-control" value="<?= htmlspecialchars($d('lbl_t1','Selectează Serviciu 1:')) ?>">
            </div>
            <div class="col-md-6 form-group">
              <div class="section-label">Etichetă Select 2</div>
              <input type="text" name="lbl_t2" class="form-control" value="<?= htmlspecialchars($d('lbl_t2','Selectează Serviciu 2:')) ?>">
            </div>
            <div class="col-md-6 form-group">
              <div class="section-label">Etichetă Câmp Manual 1</div>
              <input type="text" name="lbl_t3" class="form-control" value="<?= htmlspecialchars($d('lbl_t3','Grosime strat (mm):')) ?>">
            </div>
            <div class="col-md-6 form-group">
              <div class="section-label">Etichetă Câmp Manual 2</div>
              <input type="text" name="lbl_t4" class="form-control" value="<?= htmlspecialchars($d('lbl_t4','Distanță transport (km):')) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Opțiuni -->
      <div class="card">
        <div class="card-header"><i class="fas fa-list"></i> Opțiuni meniuri selectabile</div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 form-group">
              <div class="section-label">Opțiuni Select 1 (una pe linie)</div>
              <textarea name="optiuni_t1" class="form-control" rows="5" id="opt1-ta"><?= htmlspecialchars(implode("\n", $d('optiuni_t1',['Interior','Exterior']))) ?></textarea>
              <div class="preview-box" id="prev1">
                <small style="color:#aaa;font-size:11px;">PREVIZUALIZARE</small><br>
                <?php foreach ($d('optiuni_t1',['Interior','Exterior']) as $o): ?><span class="tag"><?= htmlspecialchars($o) ?></span><?php endforeach; ?>
              </div>
            </div>
            <div class="col-md-6 form-group">
              <div class="section-label">Opțiuni Select 2 (una pe linie)</div>
              <textarea name="optiuni_t2" class="form-control" rows="5" id="opt2-ta"><?= htmlspecialchars(implode("\n", $d('optiuni_t2',['Sub 100 m²','100 - 300 m²','Peste 300 m²']))) ?></textarea>
              <div class="preview-box" id="prev2">
                <small style="color:#aaa;font-size:11px;">PREVIZUALIZARE</small><br>
                <?php foreach ($d('optiuni_t2',['Sub 100 m²','100 - 300 m²','Peste 300 m²']) as $o): ?><span class="tag"><?= htmlspecialchars($o) ?></span><?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Prețuri -->
      <div class="card">
        <div class="card-header"><i class="fas fa-coins"></i> Prețuri & Coeficienți</div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 form-group">
              <div class="section-label">Preț de bază (MDL/m²)</div>
              <input type="number" name="pret_baza" class="form-control" step="0.01" value="<?= $d('pret_baza',120) ?>" style="font-size:18px;font-weight:700;color:var(--dark);">
              <small class="text-muted">Prețul de pornire al calculului</small>
            </div>
            <div class="col-md-4 form-group">
              <div class="section-label">Coeficient câmp manual 1</div>
              <input type="number" name="coef_t3" class="form-control" step="0.01" value="<?= $d('coef_t3',0.5) ?>">
            </div>
            <div class="col-md-4 form-group">
              <div class="section-label">Coeficient câmp manual 2</div>
              <input type="number" name="coef_t4" class="form-control" step="0.01" value="<?= $d('coef_t4',2.0) ?>">
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-md-6 form-group">
              <div class="section-label">Coeficienți Select 1 (format: Opțiune=valoare)</div>
              <textarea name="coef_t1_raw" class="form-control" rows="5"><?= htmlspecialchars(trim($coef1_raw)) ?></textarea>
              <small class="text-muted">Ex: <code>Interior=1.0</code> / <code>Exterior=1.2</code></small>
            </div>
            <div class="col-md-6 form-group">
              <div class="section-label">Coeficienți Select 2 (format: Opțiune=valoare)</div>
              <textarea name="coef_t2_raw" class="form-control" rows="5"><?= htmlspecialchars(trim($coef2_raw)) ?></textarea>
              <small class="text-muted">Ex: <code>Sub 100 m²=1.3</code> / <code>Peste 300 m²=1.0</code></small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-3 form-group">
              <div class="section-label">Monedă</div>
              <input type="text" name="moneda" class="form-control" value="<?= htmlspecialchars($d('moneda','MDL')) ?>" maxlength="5">
            </div>
          </div>

          <div class="formula-box">
            <strong>Formula de calcul (adunare simplă):</strong><br>
            <code>Total = Preț_bază + Coef_Select1 + Coef_Select2 + CâmpManual1 + CâmpManual2</code>
          </div>
        </div>
      </div>

      <div class="text-center mt-2 mb-4">
        <button type="submit" class="btn btn-save">
          <i class="fas fa-save mr-2"></i> SALVEAZĂ CONFIGURAȚIA
        </button>
      </div>
    </form>

    <?php else: ?>
    <!-- ══ TAB MESAJE ══ -->
    <?php if (empty($cereri)): ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        Niciun mesaj primit încă.<br>
        <small>Mesajele trimise prin formularul de contact vor apărea aici.</small>
      </div>
    <?php else: ?>
      <?php foreach ($cereri as $msg): ?>
        <div class="msg-card <?= !$msg['citit'] ? 'unread' : '' ?>">
          <div class="msg-meta">
            <span class="msg-email"><i class="fas fa-user-circle mr-1" style="color:var(--blue)"></i> <?= htmlspecialchars($msg['email']) ?></span>
            <?php if (!$msg['citit']): ?><span class="msg-badge-new">NOU</span><?php endif; ?>
            <span class="msg-sup"><i class="fas fa-ruler-combined mr-1"></i> <?= $msg['suprafata'] ?> m²</span>
            <span class="msg-date"><i class="fas fa-clock mr-1"></i> <?= htmlspecialchars($msg['data']) ?></span>
          </div>
          <div class="msg-body"><?= htmlspecialchars($msg['detalii']) ?></div>
          <div class="msg-actions">
            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="btn-reply">
              <i class="fas fa-reply mr-1"></i> Răspunde
            </a>
            <?php if (!$msg['citit']): ?>
              <a href="admin.php?citit=<?= $msg['id'] ?>" class="btn-read">
                <i class="fas fa-check mr-1"></i> Marchează citit
              </a>
            <?php endif; ?>
            <a href="admin.php?sterge=<?= $msg['id'] ?>" class="btn-del"
               onclick="return confirm('Ștergi acest mesaj?')">
              <i class="fas fa-trash mr-1"></i> Șterge
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; ?>