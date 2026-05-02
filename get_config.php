<?php
/**
 * get_config.php
 * Servește configurația calculatorului ca JSON.
 * Citit de index.html la fiecare 30 secunde pentru update live.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$fisier = __DIR__ . '/calculator_config.json';

if (file_exists($fisier)) {
    echo file_get_contents($fisier);
} else {
    // Config default dacă admin-ul nu a salvat nimic încă
    echo json_encode([
        "lbl_t1"     => "Selectează Serviciu 1:",
        "lbl_t2"     => "Selectează Serviciu 2:",
        "lbl_t3"     => "Grosime strat (mm):",
        "lbl_t4"     => "Distanță transport (km):",
        "optiuni_t1" => ["Interior", "Exterior"],
        "optiuni_t2" => ["Sub 100 m²", "100 - 300 m²", "Peste 300 m²"],
        "pret_baza"  => 120,
        "coef_t1"    => ["Interior" => 1.0, "Exterior" => 1.2],
        "coef_t2"    => ["Sub 100 m²" => 1.3, "100 - 300 m²" => 1.1, "Peste 300 m²" => 1.0],
        "coef_t3"    => 0.5,
        "coef_t4"    => 2.0,
        "moneda"     => "MDL"
    ]);
}