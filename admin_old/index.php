<?php
// Fehlerbericht aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FancyDumpVar für Debugging einbinden
require_once __DIR__ . '/../demos/includes/tools/fdv/FancyDumpVar.php';
use FancyDumpVar\FancyDumpVar;
$debugger = new FancyDumpVar();

// Login-Prüfung durchführen
session_start();

// Aktuelle Seite setzen, Standard: Dashboard
$page = $_GET['page'] ?? 'dashboard';
// echo $page;

// Relativer Basis-Pfad
$basePath = dirname($_SERVER['PHP_SELF']); // relativ zur aktuellen Datei

// Absolute Basis-URL für die Anwendung
$baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/JsonSQL/admin"; // Absolut, angepasst

// Optional: Wenn du in einer lokalen Entwicklungsumgebung arbeitest, könntest du den Protokoll-Teil dynamisch setzen
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/JsonSQL/admin";

// JsonSQL-Datei einbinden
$JsonSQLpath = __DIR__ . '/../src/JsonSQL.php';
if (!file_exists($JsonSQLpath)) {
    die("❌ Datei nicht gefunden: '$JsonSQLpath' !");
}
require_once $JsonSQLpath;
use Src\JsonSQL;

// Konfigurationsdatei laden
require_once __DIR__ . '/includes/load-settings.php'; // Konfigurationsdatei einbinden
$settings = get_settings(); // Funktion zum Abrufen der Einstellungen


// Den Datenbankpfad setzen
$database_path = $settings['database_path'] ?? ''; // Standardwert leer, wenn nicht gesetzt


// Überprüfen, ob der Benutzer eingeloggt ist
// Nur weiterleiten, wenn NICHT eingeloggt und NICHT bereits auf der Login-Seite
if ($page !== 'login' && (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true)) {
  $redirect_target = urlencode($page); // Sicheres Ziel
  header("Location: ?page=login&redirect=$redirect_target");
  exit;
}




// Header und Sidebar einbinden
include 'includes/header.php';
include 'includes/sidebar.php';

?>

<!-- Start: Hauptinhalt rechts von der Sidebar -->
<div class="flex-grow-1 d-flex flex-column">

  <!-- Header-Bereich mit Logo -->
<!--  <header class="bg-white border-bottom p-3 shadow-sm d-flex align-items-center"> -->
  <header class="align-items-center">  
    <img class="admin-logo me-3" src="includes/assets/images/JsonSQL-Admin-Logo.svg" alt="JsonSQL Admin">
    <hr class="top-separator my-4">
<!--    <h1 class="h5 m-0">JsonSQL Admin Interface</h1> -->
  </header>

  <!-- Seiteninhalt -->
  <main class="container py-4">
    <?php
    $pagePath = "pages/$page.php";
    if (file_exists($pagePath)) {
        include $pagePath;
    } else {
        echo "<div class='alert alert-danger'>Seite <code>$page</code> nicht gefunden.</div>";
    }
    ?>
  </main>

</div>

<?php include 'includes/footer.php'; ?>
