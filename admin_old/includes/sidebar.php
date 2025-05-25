<aside class="bg-dark text-white p-4 sidebar">
  <navv>

<!-- Top-Navigation -->
<div class="d-flex justify-content-between align-items-center mb-3">

  <!-- Navigation Buttons -->
  <div class="d-flex gap-2">
    <a href="?page=dashboard" class="btn btn-primary btn-sm text-center d-block <?= ($_GET['page'] ?? '') === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-house-door"></i> Dashboard
    </a>
    <a href="?page=settings" class="btn btn-primary btn-sm <?= ($_GET['page'] ?? '') === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-tools"></i> Einstellungen
    </a>
  </div>

  <!-- Login/Logout rechts -->
  <div style="margin-left:7px;">
    <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
      <a href="?page=logout" class="btn btn-primary btn-sm" title="Abmelden">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    <?php else: ?>
      <a href="?page=login" class="btn btn-primary btn-sm" title="Anmelden">
        <i class="bi bi-box-arrow-in-right"></i> Login
      </a>
    <?php endif; ?>
  </div>

</div>



      <!-- Tabellen anzeigen -->
      <?php
        // Überprüfen, ob der Benutzer eingeloggt ist
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {

          // Datenbankpfad aus den Einstellungen laden
          require_once __DIR__ . '/../includes/load-settings.php';
          $settings = get_settings();
          $database_path = $settings['database_path'] ?? '';

          // Funktion zum Laden der Tabellen einbinden
          require_once __DIR__ . '/../includes/load_tables.php';
          $tables = load_tables($database_path);

          echo '<div class="flex-column left-tables">';


          // Tabellen anzeigen
          echo '<div class="tablehead"><strong>Tabellen</strong></div>';

          echo '<div class="mb-3">
          <input type="text" id="tableFilter" class="form-control form-control-sm" placeholder="🔍 Tabelle filtern...">
          </div>';
        
          echo '<div class="left-tables-inner" id="table-list">';


          foreach ($tables as $table) {
            $tableName = pathinfo($table, PATHINFO_FILENAME); // Entfernt die .json-Erweiterung

            // Überprüfen, ob es eine zugehörige Systemdatei gibt (z.B. table.system.json)
            $systemFile = $database_path . '/' . $tableName . '.system.json';
            $systemFilename = $tableName . '.system.json';

            $isSystemTable = file_exists($systemFile); // Prüft, ob die Systemdatei existiert

            $isActive = ($_GET['page'] ?? '') === 'view_table' && ($_GET['table'] ?? '') === $table;
            $activeClass = $isActive ? ' active-table' : '';

            echo '<div class="nav-itemm' . $activeClass . '">';


            
            echo '<a href="?page=view_table&table=' . urlencode($table) . '" class="nav-links text-white py-2 rounded">';            

            // echo '<a href="?page=view_table&table=' . urlencode($table) . '" class="nav-links text-white py-2 rounded">';
            
            
            echo "<i class='bi bi-table'></i> " . htmlspecialchars($table);

            // Zeige das passende Icon an
            if ($isSystemTable) {
              // Zeige das goldene System-Icon
              echo '<img src="includes/assets/images/gear_system.svg" title="Systemtabelle: '.$systemFilename.'"  style="height: 24px; margin-left: 8px;">';
            } else {
              // Zeige das graue System-Icon
              echo '<img src="includes/assets/images/gear_system_gray.svg" title="Keine Systemtabelle" style="height: 24px; margin-left: 8px;">';
            }

            echo '</a>';
            echo '</div>';
          }
          
          echo '</div></div>';          

        } else {
          echo '<img class="nav-logo" src="includes/assets/images/nav_team.webp" alt="">';            
          // Falls nicht eingeloggt, zeige Login oder Fehlermeldung
      //    echo '<div class="nav-item"><a href="?page=login" class="nav-link text-white py-2 rounded">🔑 Login</a></div>';
        } 
      ?>



  </navv>

  
  <script>
document.addEventListener('DOMContentLoaded', function () {
  const filterInput = document.getElementById('tableFilter');
  const tableList = document.getElementById('table-list');
  const items = tableList.querySelectorAll('.nav-itemm');

  filterInput.addEventListener('input', function () {
    const search = this.value.toLowerCase();
    items.forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(search) ? '' : 'none';
    });
  });
});
</script>

  <!-- Footer -->
  <footer class="text-white-50 mt-5 small">&copy; 2025</footer>
</aside>
