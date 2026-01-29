<?php
/**
 * PHPLiteAdmin v5.0 - Modern UI Edition
 * SQLite database management with a modern and responsive interface
 * Integrated and built with AI Preplexity and DeepSeek features derived as an idea from the old and non-responsive program phpliteadmin.php
 * Autor: Daniele Deplano - email: deplano.d@gmail.com
 * Autor: Roberto Viola - vroby65
 */

// ============ CUSTOMIZABLE COLOR CONFIGURATIONS ============
$APP_NAME = 'PHPLiteAdmin v5.0 - Modern UI Edition';
$ADMIN_PASS = 'admin123';  // CHANGE THIS PASSWORD!

// Colori del tema (personalizzabili)
$PRIMARY_COLOR = '#2563eb';         // Blu principale
$PRIMARY_DARK = '#1d4ed8';          // Dark blue
$SECONDARY_COLOR = '#64748b';       // Secondary grey
$SUCCESS_COLOR = '#059669';         // Green success
$WARNING_COLOR = '#d97706';         // Orange warning
$DANGER_COLOR = '#dc2626';          // Danger red
$BACKGROUND_COLOR = '#f8fafc';      // Main background
$SIDEBAR_BG = '#1e293b';            // background sidebar
$SIDEBAR_TEXT = '#e2e8f0';          // Text sidebar
$SIDEBAR_HOVER = '#334155';         // Hover sidebar
$SIDEBAR_ACTIVE = '#3b82f6';        // Active sidebar
$CARD_BG = '#ffffff';               // background card
$BORDER_COLOR = '#e5e7eb';          // Borders

// Database configurations
$DATABASES_PATH = './databases/';
if (!file_exists($DATABASES_PATH)) {
    mkdir($DATABASES_PATH, 0755, true);
}

// ============ USEFUL FUNCTIONS ============
session_start();

function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function login($password) {
    global $ADMIN_PASS;
    if ($password === $ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
}

function list_databases() {
    global $DATABASES_PATH;
    $databases = [];
    foreach (glob($DATABASES_PATH . "*") as $file) {
        $databases[] = basename($file);
    }
    return $databases;
}

function get_database_info($dbfile) {
    global $DATABASES_PATH;
    $path = $DATABASES_PATH . $dbfile;
    if (!file_exists($path)) return null;

    return [
        'db_name' => $dbfile,
        'path' => $path,
        'size' => formatBytes(filesize($path)),
        'last_modified' => date('Y-m-d H:i:s', filemtime($path)),
        'sqlite_version' => SQLite3::version()['versionString'],
        'sqlite_extension' => extension_loaded('sqlite3') ? 'sqlite3' : 'pdo_sqlite',
        'php_version' => PHP_VERSION,
        'program_version' => '5.0 Modern UI Edition'
    ];
}

function formatBytes($size, $precision = 2) {
    if ($size <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

// Type options for creating columns
function type_options_html($selected = "") {
    $types = ['INTEGER', 'TEXT', 'REAL', 'BLOB', 'NUMERIC', 'BOOLEAN', 'DATETIME'];
    $html = "";
    foreach ($types as $t) {
        $sel = (strcasecmp($selected, $t) === 0) ? 'selected' : '';
        $html .= "<option value=\"$t\" $sel>$t</option>";
    }
    return $html;
}

// Function to empty a specified table
function dropTableData($table, $pdo) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table); // Simple table name sanitization
    if (!$table) {
        throw new Exception("Nome tabella non valido.");
    }
    // Runs "DELETE FROM table" to empty the table without deleting it
    $pdo->exec("DELETE FROM \"$table\""); 
}

// Function to get database structure
function get_database_structure($pdo) {
    if (!$pdo) return null;
    
    $structure = [
        'tables' => [],
        'indexes' => [],
        'triggers' => [],
        'views' => []
    ];
    
    try {
        // Get all tables
        $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tables as $table) {
            $table_name = $table['name'];
            
            // Get table structure
            $columns = $pdo->query("PRAGMA table_info(`$table_name`)")->fetchAll(PDO::FETCH_ASSOC);
            
            // Get indexes for this table
            $indexes = $pdo->query("SELECT * FROM sqlite_master WHERE type='index' AND tbl_name='$table_name'")->fetchAll(PDO::FETCH_ASSOC);
            
            $structure['tables'][$table_name] = [
                'sql' => $table['sql'],
                'columns' => $columns,
                'indexes' => $indexes
            ];
        }
        
        // Get views
        $structure['views'] = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get triggers
        $structure['triggers'] = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='trigger'")->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return null;
    }
    
    return $structure;
}

// Function to generate printable structure
function generate_print_structure($structure, $database_name) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Structure: ' . htmlspecialchars($database_name) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .table-structure { margin-bottom: 30px; border: 1px solid #ccc; padding: 15px; }
            .table-name { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .column { margin-left: 20px; margin-bottom: 5px; }
            .index { margin-left: 20px; color: #666; font-style: italic; }
            @media print {
                .no-print { display: none; }
                body { margin: 10px; }
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <button onclick="window.print()">Print</button>
            <button onclick="window.close()">Close</button>
            <hr>
        </div>
        <h1>Database Structure: ' . htmlspecialchars($database_name) . '</h1>';
    
    foreach ($structure['tables'] as $table_name => $table_data) {
        $html .= '<div class="table-structure">
            <div class="table-name">Table: ' . htmlspecialchars($table_name) . '</div>
            <div class="sql">SQL: <code>' . htmlspecialchars($table_data['sql']) . '</code></div>
            <div class="columns">';
        
        foreach ($table_data['columns'] as $column) {
            $html .= '<div class="column">' . htmlspecialchars($column['name']) . ' ' . 
                    htmlspecialchars($column['type']) . 
                    ($column['pk'] ? ' PRIMARY KEY' : '') . 
                    ($column['notnull'] ? ' NOT NULL' : '') . 
                    ($column['dflt_value'] ? ' DEFAULT ' . htmlspecialchars($column['dflt_value']) : '') . '</div>';
        }
        
        foreach ($table_data['indexes'] as $index) {
            $html .= '<div class="index">Index: ' . htmlspecialchars($index['name']) . ' (' . htmlspecialchars($index['sql']) . ')</div>';
        }
        
        $html .= '</div></div>';
    }
    
    if (!empty($structure['views'])) {
        $html .= '<h2>Views</h2>';
        foreach ($structure['views'] as $view) {
            $html .= '<div class="table-structure">
                <div class="table-name">View: ' . htmlspecialchars($view['name']) . '</div>
                <div class="sql">' . htmlspecialchars($view['sql']) . '</div>
            </div>';
        }
    }
    
    if (!empty($structure['triggers'])) {
        $html .= '<h2>Triggers</h2>';
        foreach ($structure['triggers'] as $trigger) {
            $html .= '<div class="table-structure">
                <div class="table-name">Trigger: ' . htmlspecialchars($trigger['name']) . '</div>
                <div class="sql">' . htmlspecialchars($trigger['sql']) . '</div>
            </div>';
        }
    }
    
    $html .= '</body></html>';
    return $html;
}

// ============ REQUEST MANAGEMENT ============
$page = $_GET['page'] ?? 'dashboard';
$dbfile = $_GET['db'] ?? '';
$pdo = null;
$import_error = '';
$import_success = '';
$query_result = '';
$browse_error = '';
$error_structure = '';
$browse_data = [];
$page_num = 1;
$per_page = 100;
$total_records = 0;
$total_pages = 1;

// Login/Logout
if (isset($_POST['login'])) {
    if (login($_POST['pwd'])) {
        header('Location: ?page=dashboard');
        exit;
    } else {
        $login_error = 'Password non corretta';
    }
}

if (isset($_GET['logout'])) {
    logout();
    header('Location: ?');
    exit;
}

// Database connection
if ($dbfile && is_logged_in()) {
    try {
        $pdo = new PDO('sqlite:' . $DATABASES_PATH . $dbfile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $error = "Connection error: " . $e->getMessage();
    }
}

// Creation of new database
if (isset($_POST['newdb']) && is_logged_in()) {
    $newdb = trim($_POST['newdb']);
    if ($newdb) {
        $newdb_path = $DATABASES_PATH . $newdb;
        try {
            $temp_pdo = new PDO('sqlite:' . $newdb_path);
            $temp_pdo = null;
            header('Location: ?db=' . urlencode($newdb ) . '&page=dashboard');
            exit;
        } catch (PDOException $e) {
            $error = "Database creation error: " . $e->getMessage();
        }
    }
}

// Database deletion
if (isset($_GET['deldb']) && is_logged_in()) {
    $del_db = $_GET['deldb'];
    $del_path = $DATABASES_PATH . $del_db;
    if (file_exists($del_path)) {
        unlink($del_path);
        header('Location: ?page=dashboard');
        exit;
    }
}

// Rename database
if (isset($_POST['rename_submit']) && is_logged_in()) {
    $old_name = $_POST['db'];
    $new_name = trim($_POST['renamedb']);
    if ($new_name && $old_name !== $new_name) {
        $old_path = $DATABASES_PATH . $old_name;
        $new_path = $DATABASES_PATH . $new_name;
        if (file_exists($old_path) && !file_exists($new_path)) {
            if (rename($old_path, $new_path)) {
                header('Location: ?db=' . urlencode($new_name) . '&page=dashboard');
                exit;
            }
        }
    }
}

// VACUUM database
if (isset($_GET['vacuum']) && $pdo && is_logged_in()) {
    try {
        $pdo->exec('VACUUM');
        $success = 'Database vacuum eseguito con successo';
    } catch (PDOException $e) {
        $error = 'Errore vacuum: ' . $e->getMessage();
    }
}

// Export SQL or CSV data
if (isset($_GET['export_format']) && $pdo && $dbfile && is_logged_in()) {
    $fmt = $_GET['export_format'];
    // If SQL, complete dump schema + data + indexes + views + triggers
    if ($fmt === 'sql') {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="'.basename($dbfile, '.db').'.sql"');
        
        $out = "-- PHPLiteAdmin SQL Export\n";
        $out .= "-- Database: " . $dbfile . "\n";
        $out .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- SQLite version: " . SQLite3::version()['versionString'] . "\n\n";
        $out .= "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n";
        
        // Export tables
        $out .= "-- ========== TABLES ==========\n";
        $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $t) {
            if ($t['sql']) {
                $out .= "-- Table: " . $t['name'] . "\n";
                $out .= $t['sql'] . ";\n\n";
            }
        }
        
        // Export indexes
        $indexes = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($indexes)) {
            $out .= "-- ========== INDEXES ==========\n";
            foreach ($indexes as $idx) {
                $out .= $idx['sql'] . ";\n";
            }
            $out .= "\n";
        }
        
        // Export views
        $views = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='view' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($views)) {
            $out .= "-- ========== VIEWS ==========\n";
            foreach ($views as $v) {
                if ($v['sql']) {
                    $out .= "-- View: " . $v['name'] . "\n";
                    $out .= $v['sql'] . ";\n\n";
                }
            }
        }
        
        // Export triggers
        $triggers = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='trigger' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($triggers)) {
            $out .= "-- ========== TRIGGERS ==========\n";
            foreach ($triggers as $tr) {
                if ($tr['sql']) {
                    $out .= "-- Trigger: " . $tr['name'] . "\n";
                    $out .= $tr['sql'] . ";\n\n";
                }
            }
        }
        
        // Export data
        $out .= "-- ========== DATA ==========\n";
        foreach ($tables as $t) {
            $rows = $pdo->query("SELECT * FROM `{$t['name']}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $out .= "-- Data for table: " . $t['name'] . "\n";
                foreach ($rows as $r) {
                    $cols = array_keys($r);
                    $vals = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($r));
                    $out .= "INSERT INTO `{$t['name']}` (`".implode('`,`',$cols)."`) VALUES(".implode(',',$vals).");\n";
                }
                $out .= "\n";
            }
        }
        
        $out .= "COMMIT;\n";
        echo $out;
        exit;
    }
    // If CSV, export data from a single table
    if ($fmt === 'csv') {
        $table = $_GET['table'] ?? '';
        if (!$table) {
            echo "Errore: nessuna tabella selezionata.";
            exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.basename($table).'.csv"');
        echo "\xEF\xBB\xBF"; // BOM for Excel UTF-8
        $out = fopen('php://output', 'w');
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            // Column headings
            fputcsv($out, array_keys($rows[0]));
            // Data rows
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
        }
        fclose($out);
        exit;
    }
}

// Import CSV or SQL data
if (isset($_POST['import_submit']) && $pdo && is_logged_in()) {
    $fmt = $_POST['import_format'] ?? '';
    $tmp = $_FILES['import_file']['tmp_name'] ?? '';
    if (!$tmp || !in_array($fmt, ['sql','csv'])) {
        $import_error = "File o formato non valido";
    } else {
        if ($fmt === 'sql') {
            // Import SQL
            $sql = file_get_contents($tmp);
            try {
                $pdo->exec($sql);
                $import_success = "SQL import completato";
            } catch (Exception $e) {
                $import_error = "Errore SQL import: " . $e->getMessage();
            }
        } else {
            // Import CSV
            // Read options
            $delim = $_POST['csv_term']  ?? ',';
            $enc   = $_POST['csv_encl']  ?? '"';
            $esc   = $_POST['csv_esc']   ?? '\\';
            $null  = $_POST['csv_null']  ?? '';
            $hdr   = isset($_POST['csv_header']);
            // Target table (pass it via GET or set variable)
            $browse_table = $_GET['table'] ?? '';
            if (!$browse_table) {
                $import_error = "Select a table for CSV import";
            } else {
                $f = fopen($tmp, 'r');
                if (!$f) {
                    $import_error = "Unable to open CSV file";
                } else {
                    // Get table metadata
                    $table_info = $pdo->query("PRAGMA table_info(`$browse_table`)")->fetchAll(PDO::FETCH_ASSOC);
                    $columns = [];
                    if ($hdr) {
                        $columns = fgetcsv($f, 0, $delim, $enc, $esc);
                    } else {
                        foreach ($table_info as $col) {
                            if ($col['name'] !== 'rowid') {
                                $columns[] = $col['name'];
                            }
                        }
                    }
                    if (!$columns) {
                        $import_error = "Unable to determine columns";
                        fclose($f);
                    } else {
                        // Prepare INSERT
                        $placeholders = implode(',', array_fill(0, count($columns), '?'));
                        $col_list = implode('`,`', $columns);
                        $stmt = $pdo->prepare("INSERT INTO `$browse_table` (`$col_list`) VALUES ($placeholders)");
                        $error_occurred = false;
                        while (($row = fgetcsv($f, 0, $delim, $enc, $esc)) !== false) {
                            // Replace empty strings with NULL if required
                            foreach ($row as &$v) {
                                if ($v === '' && $null !== '') $v = $null;
                            }
                            try {
                                $stmt->execute($row);
                            } catch (Exception $e) {
                                $import_error = "CSV import error: " . $e->getMessage();
                                $error_occurred = true;
                                break;
                            }
                        }
                        fclose($f);
                        if (!$error_occurred) {
                            $import_success = "CSV import completed";
                        }
                    }
                }
            }
        }
    }
}

// Blob download management (preview / download)
if (isset($_GET['download_blob']) && $pdo && isset($_GET['table']) && isset($_GET['col']) && isset($_GET['rowid']) && is_logged_in()) {
    $dl_table = $_GET['table'];
    $dl_col = $_GET['col'];
    $dl_rowid = intval($_GET['rowid']);
    // table name validation
    $valid_tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($dl_table, $valid_tables)) { http_response_code(400); exit; }
    // check columns
    $ti = $pdo->query("PRAGMA table_info(`$dl_table`)")->fetchAll(PDO::FETCH_ASSOC);
    $colnames = array_column($ti, 'name');
    if (!in_array($dl_col, $colnames)) { http_response_code(400); exit; }
    $stmt = $pdo->prepare("SELECT `$dl_col` FROM `$dl_table` WHERE rowid = :rid LIMIT 1");
    $stmt->bindValue(':rid', $dl_rowid, PDO::PARAM_INT);
    $stmt->execute();
    $val = $stmt->fetchColumn();
    if ($val === false) { http_response_code(404); exit; }
    // detect mime for images
    $mime = 'application/octet-stream';
    $imginfo = @getimagesizefromstring($val);
    if ($imginfo && isset($imginfo['mime'])) $mime = $imginfo['mime'];
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="'.basename($dl_table).'_'.$dl_rowid.'_'.$dl_col.'"');
    echo $val;
    exit;
}

// Table creation
if ($pdo && $page === 'crea_tabella' && isset($_POST['newtable']) && $_POST['newtable'] && is_logged_in()) {
    $tablename = preg_replace("/[^a-zA-Z0-9_]/", "", $_POST['newtable']);
    $fields_sql = [];
    for($i=0; $i<count($_POST['col']); $i++) {
        $name = preg_replace("/[^a-zA-Z0-9_]/", "", $_POST['col'][$i]);
        if (!$name) continue;
        $type = $_POST['type'][$i];
        $sql = "`$name` $type";
        if (!empty($_POST['pk'][$i])) $sql .= " PRIMARY KEY";
        if (!empty($_POST['ai'][$i]) && $type == 'INTEGER' && !empty($_POST['pk'][$i])) $sql .= " AUTOINCREMENT";
        if (!empty($_POST['nn'][$i])) $sql .= " NOT NULL";
        if (!empty($_POST['unique'][$i])) $sql .= " UNIQUE";
        if (isset($_POST['default'][$i]) && $_POST['default'][$i] !== '') $sql .= " DEFAULT '".addslashes($_POST['default'][$i])."'";
        $fields_sql[] = $sql;
    }
    if (count($fields_sql) > 0) {
        $create_sql = "CREATE TABLE `$tablename` (" . implode(",",$fields_sql) . ");";
        $pdo->query($create_sql);
    }
    header("Location: ?db=$dbfile&page=tabelle"); exit;
}

// Edit table structure
if ($pdo && $page === 'structure' && isset($_GET['table']) && is_logged_in()) {
    $table = $_GET['table'];
    $error_structure = "";
    if (isset($_POST['save_structure'])) {
        $old_cols = $pdo->query("PRAGMA table_info('$table')")->fetchAll(PDO::FETCH_ASSOC);
        $old_col_names = array_column($old_cols, 'name');
        $new_cols = [];
        for ($i=0; $i<count($_POST['col_oldname']); $i++) {
            // Skip columns marked for deletion
            if (isset($_POST['delcol']) && in_array($i, $_POST['delcol'])) {
                continue;
            }
            $colname = trim($_POST['col_newname'][$i]);
            if ($colname === '') continue;
            $colname = preg_replace("/[^a-zA-Z0-9_]/", "", $colname);
            $coltype = $_POST['col_type'][$i];
            $sql = "`$colname` $coltype";
            if (!empty($_POST['pk'][$i])) $sql .= " PRIMARY KEY";
            if (!empty($_POST['ai'][$i]) && $coltype == 'INTEGER' && !empty($_POST['pk'][$i])) $sql .= " AUTOINCREMENT";
            if (!empty($_POST['nn'][$i])) $sql .= " NOT NULL";
            if (!empty($_POST['unique'][$i])) $sql .= " UNIQUE";
            if (isset($_POST['default'][$i]) && $_POST['default'][$i] !== '') $sql .= " DEFAULT '".addslashes($_POST['default'][$i])."'";
            $new_cols[] = [
                'sql' => $sql,
                'name' => $colname,
                'oldname' => $_POST['col_oldname'][$i]
            ];
        }
        if (count($new_cols) < 1) {
            $error_structure = "At least one column is needed to create the table.";
        } else {
            try {
                $pdo->beginTransaction();
                $tmp_table = $table."_old_".time();
                $pdo->exec("ALTER TABLE `$table` RENAME TO `$tmp_table`;");
                $cols_sql = array_column($new_cols, 'sql');
                $create_sql = "CREATE TABLE `$table` (" . implode(", ", $cols_sql) . ");";
                $pdo->exec($create_sql);
                $copy_cols_names = [];
                foreach ($new_cols as $nc) {
                    if (in_array($nc['oldname'], $old_col_names)) {
                        $copy_cols_names[] = "`" . $nc['oldname'] . "`";
                    }
                }
                if (count($copy_cols_names) > 0) {
                    $cols_list = implode(", ", $copy_cols_names);
                    $pdo->exec("INSERT INTO `$table` ($cols_list) SELECT $cols_list FROM `$tmp_table`;");
                }
                $pdo->exec("DROP TABLE `$tmp_table`;");
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_structure = "Error while editing structure: " . $e->getMessage();
            }
        }
    }
}

// SQL query execution
if ($pdo && $page === 'sql' && isset($_POST['sql']) && is_logged_in()) {
    try {
        $stm = $pdo->query($_POST['sql']);
        if ($stm) {
            $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
            $query_result = "<table class='table table-sm table-bordered mt-2'><tr>";
            if ($rows) foreach (array_keys($rows[0]) as $col) $query_result .= "<th>$col</th>";
            $query_result .= "</tr>";
            foreach ($rows as $row) {
                $query_result .= "<tr>";
                foreach ($row as $cell) $query_result .= "<td>".htmlspecialchars($cell)."</td>";
                $query_result .= "</tr>";
            }
            $query_result .= "</table>";
        } else $query_result = "Query OK.";
    } catch (Exception $e) {
        $query_result = "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    $query_result = "";
}

// Browse table
if ($pdo && $page === 'browse' && isset($_GET['table']) && is_logged_in()) {
    $browse_table = $_GET['table'];
    
    // Record deletion
    if (isset($_GET['delrow'])) {
        $delrow = (int)$_GET['delrow'];
        try {
            $pdo->exec("DELETE FROM `$browse_table` WHERE rowid = $delrow");
            header("Location: ?db=".urlencode($dbfile)."&page=browse&table=".urlencode($browse_table));
            exit;
        } catch (Exception $e) {
            $browse_error = "Error deleting records: " . $e->getMessage();
        }
    }
    
    // Inserting a new record
    if (isset($_POST['insert_record'])) {
        $table_info = $pdo->query("PRAGMA table_info(`$browse_table`)")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];
        $values = [];
        foreach ($table_info as $col) {
            if ($col['name'] === 'rowid') continue;
            $name = $col['name'];
            $fields[] = "`$name`";
            $raw = $_POST["new_$name"] ?? '';
            if ($raw === '') {
                $values[] = 'NULL';
            } else {
                // For numeric types, I leave it as is; for dates, I convert to ISO
                if (stripos($col['type'], 'INT') === 0 || stripos($col['type'], 'REAL') === 0) {
                    $values[] = $pdo->quote($raw);
                } elseif (stripos($col['type'], 'DATE') !== false) {
                    $d = date('Y-m-d', strtotime($raw));
                    $values[] = $pdo->quote($d);
                } else {
                    $values[] = $pdo->quote($raw);
                }
            }
        }
        $sql = "INSERT INTO `$browse_table` (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
        try {
            $pdo->exec($sql);
            header("Location: ?db=".urlencode($dbfile)."&page=browse&table=".urlencode($browse_table));
            exit;
        } catch (Exception $e) {
            $browse_error = "Record insertion error: " . $e->getMessage();
        }
    }
    
    // Saving changes
    if (isset($_POST['save_edits']) && isset($_POST['rowid'])) {
        try {
            $pdo->beginTransaction();
            $cols = json_decode($_POST['cols'], true);
            // Get metadata column types
            $table_info = $pdo->query("PRAGMA table_info(`$browse_table`)")->fetchAll(PDO::FETCH_ASSOC);
            $col_types = [];
            foreach ($table_info as $ci) $col_types[$ci['name']] = $ci['type'];
            for ($r=0; $r<count($_POST['rowid']); $r++) {
                $rowid = intval($_POST['rowid'][$r]);
                $sets = [];
                foreach ($cols as $col) {
                    if ($col == 'rowid') continue;
                    $ctype = $col_types[$col] ?? '';
                    if (stripos($ctype, 'BLOB') !== false) {
                        // File upload for BLOB? Field name: new_$col[]
                        if (isset($_FILES['new_'.$col]) && isset($_FILES['new_'.$col]['error'][$r]) && $_FILES['new_'.$col]['error'][$r] === UPLOAD_ERR_OK && $_FILES['new_'.$col]['size'][$r] > 0) {
                            $tmp = $_FILES['new_'.$col]['tmp_name'][$r];
                            $data = file_get_contents($tmp);
                            $stmt = $pdo->prepare("UPDATE `$browse_table` SET `$col` = :data WHERE rowid = :rid");
                            $stmt->bindValue(':data', $data, PDO::PARAM_LOB);
                            $stmt->bindValue(':rid', $rowid, PDO::PARAM_INT);
                            $stmt->execute();
                        }
                        // otherwise keep the current value
                    } else {
                        $val = isset($_POST[$col][$r]) ? $_POST[$col][$r] : '';
                        if ($val === '') $val_escaped = 'NULL';
                        else $val_escaped = $pdo->quote($val);
                        $sets[] = "`$col` = $val_escaped";
                    }
                }
                if (count($sets) > 0) {
                    $set_sql = implode(", ", $sets);
                    $sql = "UPDATE `$browse_table` SET $set_sql WHERE rowid = $rowid";
                    $pdo->exec($sql);
                }
            }
            $pdo->commit();
            header("Location: ?db=".urlencode($dbfile)."&page=browse&table=".urlencode($browse_table));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $browse_error = "Error saving changes: " . $e->getMessage();
        }
    }
    
    // Retrieve data for viewing with pagination
    $page_num = max(1, intval($_GET['p'] ?? 1));
    $per_page = max(10, min(500, intval($_GET['pp'] ?? 100)));
    $offset = ($page_num - 1) * $per_page;
    $total_records = $pdo->query("SELECT COUNT(*) FROM `$browse_table`")->fetchColumn();
    $total_pages = max(1, ceil($total_records / $per_page));
    $browse_data = $pdo->query("SELECT rowid, * FROM `$browse_table` LIMIT $per_page OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
}

// Table deletion
if ($pdo && $page === 'tabelle' && isset($_GET['deltable']) && is_logged_in()) {
    $toDel = preg_replace('/[^a-zA-Z0-9_]/','', $_GET['deltable']);
    try {
        $pdo->exec("DROP TABLE `$toDel`");
        header("Location: ?db=".urlencode($dbfile)."&page=tabelle");
        exit;
    } catch (Exception $e) {
        $delete_table_error = "Error deleting table: " . $e->getMessage();
    }
}

// Truncate table
if (isset($_GET['page']) && $_GET['page']==='truncate' && isset($_GET['table']) && $pdo && is_logged_in()) {
    $tbl = preg_replace('/[^a-zA-Z0-9_]/','', $_GET['table']);
    if (isset($_POST['confirm_truncate'])) {
        // DROP request management (table emptying)
        // Empty table
        $pdo->exec("DELETE FROM `$tbl`;");
        if (!empty($_POST['vacuum_after'])) {
            $pdo->exec("VACUUM;");
        }
        header("Location: ?db=".urlencode($dbfile)."&page=browse&table=".urlencode($tbl));
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $APP_NAME ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
        --primary: <?= $PRIMARY_COLOR ?>;
        --primary-dark: <?= $PRIMARY_DARK ?>;
        --secondary: <?= $SECONDARY_COLOR ?>;
        --success: <?= $SUCCESS_COLOR ?>;
        --warning: <?= $WARNING_COLOR ?>;
        --danger: <?= $DANGER_COLOR ?>;
        --background: <?= $BACKGROUND_COLOR ?>;
        --sidebar-bg: <?= $SIDEBAR_BG ?>;
        --sidebar-text: <?= $SIDEBAR_TEXT ?>;
        --sidebar-hover: <?= $SIDEBAR_HOVER ?>;
        --sidebar-active: <?= $SIDEBAR_ACTIVE ?>;
        --card-bg: <?= $CARD_BG ?>;
        --border: <?= $BORDER_COLOR ?>;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --radius: 0.5rem;
        --radius-lg: 1rem;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: var(--background);
        color: #1f2937;
        line-height: 1.6;
    }

    /* ============ MODERN HEADER ============ */
    .header {
        background: var(--card-bg);
        border-bottom: 1px solid var(--border);
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 1000;
        backdrop-filter: blur(10px);
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 2rem;
        max-width: 100%;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }

    .logo i {
        font-size: 1.5rem;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--secondary);
        cursor: pointer;
        padding: 0.5rem;
        border-radius: var(--radius);
        transition: all 0.2s;
    }

    .menu-toggle:hover {
        background: var(--background);
        color: var(--primary);
    }

    /* ============ MAIN LAYOUT ============ */
    .app-layout {
        display: flex;
        min-height: calc(100vh - 80px);
    }

    /* ============ SIDEBAR MODERNA ============ */
    .sidebar {
        width: 280px;
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        padding: 1.5rem;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
        position: relative;
        z-index: 900;
    }

    .sidebar-section {
        margin-bottom: 2rem;
    }

    .sidebar-title {
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 1rem;
        padding-left: 0.5rem;
    }

    .nav-menu {
        list-style: none;
    }

    .nav-item {
        margin-bottom: 0.25rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: var(--radius);
        text-decoration: none;
        color: var(--sidebar-text);
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }

    .nav-link:hover {
        background: var(--sidebar-hover);
        color: #ffffff;
        transform: translateX(4px);
    }

    .nav-link.active {
        background: var(--sidebar-active);
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .nav-link i {
        width: 1.25rem;
        text-align: center;
        flex-shrink: 0;
    }

    .nav-link-text {
        flex: 1;
        font-size: 0.875rem;
    }

    .nav-badge {
        background: var(--warning);
        color: white;
        font-size: 0.75rem;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-weight: 500;
    }

    /* Database Items */
.db-list {
    max-height: 200px;
    overflow-y: auto;
    -ms-overflow-style: none;  /* Hide scrollbar in IE e Edge */
    scrollbar-width: none;     /* Hide scrollbar in Firefox */
    border: 1px solid var(--sidebar-hover);
    border-radius: var(--radius);
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
}

/* Hide scrollbar in WebKit (Chrome, Safari) */
.db-list::-webkit-scrollbar {
    display: none;
}

    .db-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: calc(var(--radius) - 2px);
        text-decoration: none;
        color: var(--sidebar-text);
        font-size: 0.875rem;
        transition: all 0.2s;
        margin-bottom: 0.25rem;
    }

    .db-item:hover {
        background: var(--sidebar-hover);
        color: #ffffff;
    }

    .db-item.active {
        background: var(--sidebar-active);
        color: #ffffff;
    }

    .db-item i {
        width: 1rem;
        text-align: center;
    }

    /* Forms in the sidebar */
    .sidebar-form {
        margin-bottom: 1rem;
    }

	.sidebar input.form-input {
  		background: rgba(255, 255, 255, 0.1);
  	color: var(--sidebar-text);
	}

    .form-group {
        margin-bottom: 1rem;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--sidebar-hover);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.1);
//      color: var(--sidebar-text);
//		color: #b0abab;
		color: #373535;
		font-size: 0.875rem;
        box-sizing: border-box; /* including padding and borders in the width calculation */
		min-width: 0; /* avoid overflow */
    }

    .form-input::placeholder {
		color: #b0abab;
		opacity: 1;
   }

    .form-input:focus {
        outline: none;
        border-color: var(--sidebar-active);
        background: rgba(255, 255, 255, 0.15);
    }

.form-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--sidebar-hover);
    border-radius: var(--radius);
    background: #fff; /* or a different color to distinguish */
    color: #222;
    font-size: 0.875rem;
    appearance: none; /* to stylize without system defaults */
    background-image: url('data:image/svg+xml;utf8,<svg ...>'); /* optional custom arrow */
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1rem 1rem;
}

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: var(--radius);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: var(--shadow);
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #047857;
        transform: translateY(-1px);
    }

    .btn-warning {
        background: var(--warning);
        color: white;
    }

    .btn-warning:hover {
        background: #b45309;
        transform: translateY(-1px);
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .btn-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
    }

    .btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .btn-full {
        width: 100%;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid currentColor;
    }

    .btn-outline:hover {
        background: currentColor;
        color: white;
    }

    /* ============ MAIN CONTENT ============ */
    .main-content {
        flex: 1;
        padding: 2rem;
        overflow-x: auto;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        color: var(--secondary);
        font-size: 1rem;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
    }

    .card-body {
        padding: 1.5rem;
    }

/* Keep table-container as a horizontally scrollable wrapper */
.table-container {
    overflow-x: auto;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

/* Make sure the table takes up the full available width */
.table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card-bg);
    min-width: 600px;  /* minimum horizontal width to avoid excessive collapses */
}

/* Styles of the th */
.table th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid var(--border);
}

/* Styles of the td */
.table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
    color: #4b5563;
    white-space: nowrap;  /* prevents content from wrapping inside the cell */
}

.table td input.form-input {
    width: auto;             /* remove width:100% to not limit the width */
    min-width: 15rem;         /* minimum reasonable width for usability */
    max-width: 100%;         /* never leave the cell */
    box-sizing: border-box;  /* includes padding and border in width calculation */
}

/* Media queries for improvement on small screens */
@media (max-width: 600px) {
    .table td input.form-input {
        min-width: 6rem;     /* narrower but still readable */
        font-size: 0.9rem;
    }
}

/* Hover on the line */
.table tbody tr:hover {
    background: #f9fafb;
}

/* Media queries for small screens */
@media (max-width: 600px) {
    .table {
        min-width: auto; /* removes min-width to better fit the container */
    }

    /* Inline button in td with horizontal wrap */
    .table td > button {
        white-space: normal; /* allows you to start a new line */
        display: inline-block; /* force inline layout */
        margin-right: 0.25rem;
        margin-bottom: 0.25rem; /* margin to separate buttons if they go to a new line */
        vertical-align: middle;
    }

    /* Optional: Make the buttons smaller per piece of furniture */
    .table td > button {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
    }

	.form-input {
        font-size: 1rem;  /* slightly larger size for readability */
        padding: 0.5rem;
    }
}

    /* Alerts */
    .alert {
        padding: 1rem;
        border-radius: var(--radius);
        margin-bottom: 1rem;
        border: 1px solid;
    }

    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .alert-warning {
        background: #fffbeb;
        color: #92400e;
        border-color: #fed7aa;
    }

    .alert-info {
        background: #eff6ff;
        color: #1e40af;
        border-color: #bfdbfe;
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 768px) {
        .header-content {
            padding: 1rem;
        }

        .menu-toggle {
            display: block;
        }

        .sidebar {
            position: fixed;
            top: 80px;
            left: -280px;
            height: calc(100vh - 80px);
            transition: left 0.3s ease;
            z-index: 1100;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .main-content {
            padding: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        .table th,
        .table td {
            padding: 0.5rem;
        }
    }

    /* ============ ANIMATIONS ============ */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card {
        animation: slideIn 0.3s ease;
    }

    /* ============ UTILITIES ============ */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-muted { color: var(--secondary); }
    .mb-0 { margin-bottom: 0; }
    .mb-1 { margin-bottom: 0.5rem; }
    .mb-2 { margin-bottom: 1rem; }
    .mb-3 { margin-bottom: 1.5rem; }
    .mt-2 { margin-top: 1rem; }
    .flex { display: flex; }
    .items-center { align-items: center; }
    .justify-between { justify-content: space-between; }
    .gap-2 { gap: 1rem; }
    .w-full { width: 100%; }

    /* Special form login */
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 2rem;
        animation: slideIn 0.5s ease;
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-header i {
        font-size: 3rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .login-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }

    /* Tables list in sidebar */
    .tables-section {
        background: rgba(255, 255, 255, 0.03);
        border-radius: var(--radius);
        padding: 1rem;
        margin: 1rem 0;
    }

    .table-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: calc(var(--radius) - 2px);
        text-decoration: none;
        color: var(--sidebar-text);
        font-size: 0.8125rem;
        margin-bottom: 0.25rem;
        transition: all 0.2s;
    }

    .table-link:hover {
        background: var(--sidebar-hover);
        color: #ffffff;
    }

    .table-link.active {
        background: var(--sidebar-active);
        color: #ffffff;
    }

    .table-link i {
        font-size: 0.75rem;
        width: 1rem;
        text-align: center;
    }

    /* Form styles for table creation */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: center;
    }

    .form-row > * {
        flex: 1;
        min-width: 120px;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* BLOB image preview */
    .blob-preview {
        max-width: 100px;
        max-height: 100px;
        border-radius: var(--radius);
        border: 1px solid var(--border);
    }

    /* Query builder */
    .condition-row {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        align-items: center;
    }

    /* Export/Import forms */
    .csv-options {
        background: #f8fafc;
        padding: 1rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        margin-top: 1rem;
    }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <button class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <i class="fas fa-database"></i>
                <span><?= $APP_NAME ?></span>
            </div>
            <div class="header-actions">
                <?php if (is_logged_in()): ?>
                    <a href="?logout" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (!is_logged_in()): ?>
        <!-- Login Form -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-lock"></i>
                    <h2 class="login-title">Sign in <?= $APP_NAME ?></h2>
                </div>

                <?php if (isset($login_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= $login_error ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <input type="password" 
                               name="pwd" 
                               class="form-input" 
                               placeholder="Administrator password" 
                               required 
                               autocomplete="current-password">
                    </div>
                    <button type="submit" name="login" class="btn btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i>
                        Log in
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Main App Layout -->
        <div class="app-layout">
            <!-- Sidebar -->
            <nav class="sidebar" id="sidebar">
                <!-- Database Section -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-database"></i>
                        Database
                    </h3>

                    <div class="db-list">
                        <?php foreach (list_databases() as $db): ?>
                            <a href="?db=<?= urlencode($db) ?>&page=dashboard" 
                               class="db-item <?= ($dbfile == $db && $page == 'dashboard') ? 'active' : '' ?>">
                                <i class="fas fa-database"></i>
                                <?= htmlspecialchars($db) ?>
                            </a>
                        <?php endforeach; ?>

                        <?php if (empty(list_databases())): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-folder-open"></i><br>
                                <small>No database present</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Create New Database -->
                    <form method="post" class="sidebar-form">
                        <div class="form-group">
                            <input type="text" 
                                   name="newdb" 
                                   class="form-input" 
                                   placeholder="Nome nuovo database" 
                                   required>
                        </div>
                        <button type="submit" class="btn btn-success btn-full btn-sm">
                            <i class="fas fa-plus"></i>
                            Create Database
                        </button>
                    </form>
                </div>

                <?php if ($dbfile): ?>
                    <!-- Rename Database -->
                    <div class="sidebar-section">
                        <form method="post" class="sidebar-form">
                            <input type="hidden" name="db" value="<?= urlencode($dbfile) ?>">
                            <div class="form-group">
                                <input type="text" 
                                       name="renamedb" 
                                       value="<?= htmlspecialchars($dbfile) ?>" 
                                       class="form-input" 
                                       placeholder="Rename database" 
                                       required>
                            </div>
                            <button type="submit" 
                                    name="rename_submit" 
                                    class="btn btn-warning btn-full btn-sm">
                                <i class="fas fa-edit"></i>
                                Rename
                            </button>
                        </form>
                    </div>

                    <!-- Main Navigation -->
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-tools"></i>
                            Instruments
                        </h3>

                        <ul class="nav-menu">
                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=tabelle" 
                                   class="nav-link <?= ($page == 'tabelle') ? 'active' : '' ?>">
                                    <i class="fas fa-table"></i>
                                    <span class="nav-link-text">Tables</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=crea_tabella" 
                                   class="nav-link <?= ($page == 'crea_tabella') ? 'active' : '' ?>">
                                    <i class="fas fa-plus-square"></i>
                                    <span class="nav-link-text">New Table</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=sql" 
                                   class="nav-link <?= ($page == 'sql') ? 'active' : '' ?>">
                                    <i class="fas fa-terminal"></i>
                                    <span class="nav-link-text">Query SQL</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=views" 
                                   class="nav-link <?= ($page == 'views') ? 'active' : '' ?>">
                                    <i class="fas fa-eye"></i>
                                    <span class="nav-link-text">Views</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=triggers" 
                                   class="nav-link <?= ($page == 'triggers') ? 'active' : '' ?>">
                                    <i class="fas fa-bolt"></i>
                                    <span class="nav-link-text">Triggers</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=info" 
                                   class="nav-link <?= ($page == 'info') ? 'active' : '' ?>">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="nav-link-text">Database Info</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&page=print_structure" 
                                   class="nav-link <?= ($page == 'print_structure') ? 'active' : '' ?>">
                                    <i class="fas fa-print"></i>
                                    <span class="nav-link-text">Print Structure</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <?php if ($pdo): ?>
                        <!-- Tables List -->
                        <?php 
                        $current_tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                        if (!empty($current_tables)):
                        ?>
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-list"></i>
                                Tabelle (<?= count($current_tables) ?>)
                            </h3>

                            <div class="tables-section">
                                <?php foreach ($current_tables as $table_name): ?>
                                    <?php $is_active = ($page == 'browse' && isset($_GET['table']) && $_GET['table'] == $table_name); ?>
                                    <a href="?db=<?= urlencode($dbfile) ?>&page=browse&table=<?= urlencode($table_name) ?>" 
                                       class="table-link <?= $is_active ? 'active' : '' ?>">
                                        <i class="fas fa-table"></i>
                                        <?= htmlspecialchars($table_name) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Database Actions -->
                    <div class="sidebar-section">
                        <h3 class="sidebar-title">
                            <i class="fas fa-cog"></i>
                            Database Actions
                        </h3>

                        <ul class="nav-menu">
                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&vacuum=1&page=<?= $page ?>" 
                                   class="nav-link">
                                    <i class="fas fa-broom"></i>
                                    <span class="nav-link-text">Vacuum</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <?php
                                  // At the top of the sidebar, right after setting $pdo:
                                  // Retrieve the list of tables
                                  $currenttables = $pdo
                                    ->query(
                                      "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
                                    )
                                    ->fetchAll(PDO::FETCH_COLUMN);
                                ?>

                                <form method="get" class="sidebar-form">
                                    <input type="hidden" name="db" value="<?= urlencode($dbfile) ?>">
                                    <input type="hidden" name="page" value="dashboard">
                                    <input type="hidden" name="db" value="<?=urlencode($dbfile)?>">
                                    <input type="hidden" name="page" value="<?=htmlspecialchars($page)?>">
                                     <div class="form-group">
                                        <select name="export_format" class="form-select form-input">
                                            <option value="sql">SQL</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                     <!-- Aggiungere qui: selettore tabella visibile solo se CSV -->
                                      <div id="csv-table-selector" class="form-group" style="display:none;">
                                        <label>Table for CSV:</label>
                                        <select name="table" class="form-select form-input">
                                          <?php foreach($currenttables as $t): ?>
                                            <option value="<?=htmlspecialchars($t)?>"><?=htmlspecialchars($t)?></option>
                                          <?php endforeach; ?>
                                        </select>
                                      </div>
                                     <button type="submit" class="btn btn-warning btn-full btn-sm">
                                        <i class="fas fa-download"></i>
                                        Export
                                    </button>
                                </form>
                                    <script>
                                      document.querySelector('select[name="export_format"]').addEventListener('change', function(e) {
                                        document.getElementById('csv-table-selector').style.display =
                                          e.target.value === 'csv' ? 'block' : 'none';
                                      });
                                    </script>
                            </li>

                            <li class="nav-item">
                                <form method="post" enctype="multipart/form-data" class="sidebar-form">
                                    <div class="form-group">
                                        <input type="file" name="import_file" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <select name="import_format" class="form-select form-input">
                                            <option value="sql">SQL</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="import_submit" class="btn btn-primary btn-full btn-sm">
                                        <i class="fas fa-upload"></i>
                                        Import
                                    </button>
                                    <!-- CSV Options (hidden by default) -->
                                    <div id="csv_options" style="display:none; margin-top:.5rem;">
                                        <div class="form-group">
                                            <input name="csv_term" class="form-input" placeholder="Terminated by" value=",">
                                        </div>
                                        <div class="form-group">
                                            <input name="csv_encl" class="form-input" placeholder="Enclosed by" value="&quot;">
                                        </div>
                                        <div class="form-group">
                                            <input name="csv_esc"  class="form-input" placeholder="Escaped by" value="\\">
                                        </div>
                                        <div class="form-group">
                                            <input name="csv_null" class="form-input" placeholder="Replace NULL by" value="NULL">
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="csv_header" name="csv_header" checked>
                                            <label class="form-check-label" for="csv_header">Header row</label>
                                        </div>
                                    </div>
                                </form>
                            </li>

                            <li class="nav-item">
                                <a href="?db=<?= urlencode($dbfile) ?>&deldb=<?= urlencode($dbfile) ?>&page=dashboard" 
                                   class="nav-link"
                                   onclick="return confirm('Permanently delete the database?')">
                                    <i class="fas fa-trash"></i>
                                    <span class="nav-link-text">Delete Database</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </nav>

            <!-- Sidebar Overlay for mobile -->
            <div class="sidebar-overlay" id="sidebar-overlay"></div>

            <!-- Main Content -->
            <main class="main-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($import_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= $import_error ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($import_success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $import_success ?>
                    </div>
                <?php endif; ?>

                <?php
                // ============ ROUTING DELLE PAGINE ============
                switch ($page):
                    case 'dashboard':
                    default:
                        include_page_dashboard();
                        break;
                    case 'tabelle':
                        include_page_tabelle();
                        break;
                    case 'crea_tabella':
                        include_page_crea_tabella();
                        break;
                    case 'sql':
                        include_page_sql();
                        break;
                    case 'info':
                        include_page_info();
                        break;
                    case 'browse':
                        include_page_browse();
                        break;
                    case 'structure':
                        include_page_structure();
                        break;
                    case 'indexes':
                        include_page_indexes();
                        break;
                    case 'views':
                        include_page_views();
                        break;
                    case 'triggers':
                        include_page_triggers();
                        break;
                    case 'truncate':
                        include_page_truncate();
                        break;
                    case 'print_structure':
                        include_page_print_structure();
                        break;
                    case 'print_output':
                        include_page_print_output();
                        break;
                endswitch;
                ?>
            </main>
        </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('show');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
            });
        }

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0.8';
                });
            }, 5000);
        });

        // Smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });

        // Toggle CSV options
        const importFormat = document.querySelector('select[name="import_format"]');
        const csvOpts = document.getElementById('csv_options');
        if (importFormat && csvOpts) {
            importFormat.addEventListener('change', e => {
                csvOpts.style.display = (e.target.value==='csv') ? 'block' : 'none';
            });
        }

        // Aggiungi riga colonna in modifica struttura
        function addColumnRow() {
            const tbody = document.getElementById('cols-body');
            const i = tbody.rows.length;
            const types = ['INTEGER','TEXT','REAL','BLOB','NUMERIC','BOOLEAN','DATETIME'];
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="col_oldname[]" value="">
                    <input type="text" name="col_newname[]" class="form-control form-control-sm" required>
                </td>
                <td>
                    <select name="col_type[]" class="form-select form-select-sm" required>
                        ${types.map(t => `<option value="${t}">${t}</option>`).join('')}
                    </select>
                </td>
                <td><input type="checkbox" name="pk[${i}]" value="1"></td>
                <td><input type="checkbox" name="ai[${i}]" value="1"></td>
                <td><input type="checkbox" name="nn[${i}]" value="1"></td>
                <td><input type="checkbox" name="unique[${i}]" value="1"></td>
                <td><input type="text" name="default[]" class="form-control form-control-sm"></td>
                <td></td>
            `;
            tbody.appendChild(tr);
        }

        // Aggiungi condizione in query builder
        document.getElementById('addConditionBtn')?.addEventListener('click', function() {
            const container = document.getElementById('conditionsContainer');
            const index = container.children.length;
            const columnsOptions = `<?php foreach ($columns as $col): ?><option value="<?=htmlspecialchars($col)?>"><?=htmlspecialchars($col)?></option><?php endforeach; ?>`;
            const operatorsOptions = `
                <option value="=">equal</option>
                <option value="!=">Different</option>
                <option value="<">Minor</option>
                <option value="<=">Less than or equal</option>
                <option value=">">Greater</option>
                <option value=">=">Greater than or equal</option>
                <option value="LIKE">Contains</option>`;
            const div = document.createElement('div');
            div.className = 'row g-2 mb-2 condition-row';
            div.innerHTML = `
                <div class="col-md-4">
                    <select name="where[${index}][column]" class="form-select">
                        <option value="">Select field</option>
                        ${columnsOptions}
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="where[${index}][operator]" class="form-select">
                        ${operatorsOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="where[${index}][value]" class="form-control" placeholder="Valore">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>
                </div>
            `;
            container.appendChild(div);
        });

        // Remove condition in query builder
        document.getElementById('conditionsContainer')?.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-condition')) {
                e.target.closest('.condition-row').remove();
            }
        });

        // SQL Editor improvements
        document.addEventListener('DOMContentLoaded', function() {
            const sqlEditor = document.getElementById('sql-editor');
            
            if (sqlEditor) {
                // Auto-indentazione base
                sqlEditor.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        
                        // Inserisci 4 spazi
                        this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                        this.selectionStart = this.selectionEnd = start + 4;
                    }
                    
                    // Ctrl+Enter per eseguire
                    if (e.ctrlKey && e.key === 'Enter') {
                        this.form.querySelector('button[type="submit"]').click();
                    }
                });
                
                // Sintassi SQL di base nel placeholder
                const examples = [
                    "SELECT * FROM table_name WHERE condition;",
                    "INSERT INTO table_name (col1, col2) VALUES (val1, val2);",
                    "UPDATE table_name SET col1 = val1 WHERE condition;",
                    "DELETE FROM table_name WHERE condition;",
                    "CREATE TABLE new_table (id INTEGER PRIMARY KEY, name TEXT);",
                    "ALTER TABLE table_name ADD COLUMN new_column TEXT;",
                    "CREATE INDEX idx_name ON table_name(column_name);",
                    "PRAGMA table_info(table_name);"
                ];
                
                let exampleIndex = 0;
                sqlEditor.addEventListener('focus', function() {
                    if (this.value === '' || this.value === this.placeholder) {
                        this.value = examples[exampleIndex];
                        exampleIndex = (exampleIndex + 1) % examples.length;
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php

// ============ PAGE FUNCTIONS ============

function include_page_dashboard() {
    global $dbfile, $pdo, $DATABASES_PATH, $APP_NAME;
    ?>
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </h1>
        <p class="page-subtitle">
            General overview of databases and statistics
        </p>
    </div>

    <?php if (!$dbfile): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-database"></i>
                    Benvenuto in  <?=$APP_NAME;?>
                </h3>
            </div>
            <div class="card-body">
                <p>Select a database from the sidebar or create a new one to get started.</p>
                <div class="mt-2">
                    <strong>Databases available:</strong> <?= count(list_databases()) ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php 
        $info = get_database_info($dbfile);
        $tables_count = 0;
        $total_records = 0;

        if ($pdo) {
            try {
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
                $tables_count = count($tables);

                foreach ($tables as $table) {
                    $count = $pdo->query("SELECT COUNT(*) FROM `" . str_replace('`', '``', $table) . "`")->fetchColumn();
                    $total_records += $count;
                }
            } catch (Exception $e) {
                // Ignore errors
            }
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-database"></i>
                    <?= htmlspecialchars($dbfile) ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if ($info): ?>
                    <div class="table-container">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><strong>Database Name</strong></td>
                                    <td><?= htmlspecialchars($info['db_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Path</strong></td>
                                    <td><?= htmlspecialchars($info['path']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Size</strong></td>
                                    <td><?= htmlspecialchars($info['size']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Edit</strong></td>
                                    <td><?= htmlspecialchars($info['last_modified']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SQLite Version</strong></td>
                                    <td><?= htmlspecialchars($info['sqlite_version']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SQLite Extension</strong></td>
                                    <td><?= htmlspecialchars($info['sqlite_extension']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>PHP Version</strong></td>
                                    <td><?= htmlspecialchars($info['php_version']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Program Version</strong></td>
                                    <td><?= htmlspecialchars($info['program_version']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistics
                </h3>
            </div>
            <div class="card-body">
                <div class="flex items-center justify-between mb-2">
                    <span><strong>Number of Tables:</strong></span>
                    <span class="nav-badge"><?= $tables_count ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span><strong>Total Records:</strong></span>
                    <span class="nav-badge"><?= number_format($total_records) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php
}

function include_page_tabelle() {
    global $dbfile, $pdo, $delete_table_error;
    ?>
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-table"></i>
            Tables
        </h1>
        <p class="page-subtitle">
            Manage database tables <?= htmlspecialchars($dbfile) ?>
        </p>
    </div>

    <?php if (isset($delete_table_error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $delete_table_error ?>
        </div>
    <?php endif; ?>

    <?php if ($pdo): ?>
        <?php
        try {
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tables = [];
        }
        ?>

        <?php if (empty($tables)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-table" style="font-size: 3rem; color: var(--secondary); margin-bottom: 1rem;"></i>
                    <h3>No table present</h3>
                    <p class="text-muted">This database does not yet contain any tables.</p>
                    <a href="?db=<?= urlencode($dbfile) ?>&page=crea_tabella" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create the first table
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        List of Tables (<?= count($tables) ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Record</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                    <?php
                                    try {
                                        $count = $pdo->query("SELECT COUNT(*) FROM `" . str_replace('`', '``', $table['name']) . "`")->fetchColumn();
                                    } catch (Exception $e) {
                                        $count = 'N/A';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-table text-muted"></i>
                                            <strong><?= htmlspecialchars($table['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="nav-badge"><?= is_numeric($count) ? number_format($count) : $count ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="?db=<?= urlencode($dbfile) ?>&page=browse&table=<?= urlencode($table['name']) ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                                Browse
                                            </a>
                                            <a href="?db=<?= urlencode($dbfile) ?>&page=structure&table=<?= urlencode($table['name']) ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-cogs"></i>
                                                Structure
                                            </a>
                                            <a href="?db=<?= urlencode($dbfile) ?>&page=indexes&table=<?= urlencode($table['name']) ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-list-ol"></i>
                                                Indexes
                                            </a>
                                            <a href="?db=<?=urlencode($dbfile)?>&amp;page=truncate&amp;table=<?=urlencode($table['name'])?>" 
                                               class="btn btn-danger btn-sm">
                                               <i class="fas fa-eraser"></i>
                                                Empty
                                            </a>
                                            <a href="?db=<?= urlencode($dbfile) ?>&page=tabelle&deltable=<?= urlencode($table['name']) ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Sei sicuro di voler eliminare la tabella <?= htmlspecialchars($table['name']) ?>?')">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            Unable to connect to database.
        </div>
    <?php endif; ?>
    <?php
}

function include_page_crea_tabella() {
    global $dbfile, $pdo;
    ?>
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-plus-square"></i>
            Create New Table
        </h1>
        <p class="page-subtitle">
            Add a new table to the database <?= htmlspecialchars($dbfile) ?>
        </p>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Table Configuration</h3>
        </div>
        <div class="card-body">
            <form method="post" class="mb-3">
                <div class="form-group mb-3">
                    <label for="newtable" class="form-label">Table Name</label>
                    <input type="text" class="form-input" id="newtable" name="newtable" required>
                </div>

                <h4 class="mb-3">Columns</h4>
                
                <div class="table-container mb-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>PK</th>
                                <th>AI</th>
                                <th>Not Null</th>
                                <th>Unique</th>
                                <th>Default</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i=0; $i<5; $i++): ?>
                            <tr>
                                <td>
                                    <input type="text" name="col[]" class="form-input" placeholder="Nome colonna">
                                </td>
                                <td>
                                    <select name="type[]" class="form-input">
                                        <?= type_options_html() ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="pk[<?=$i?>]" value="1">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="ai[<?=$i?>]" value="1">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="nn[<?=$i?>]" value="1">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="unique[<?=$i?>]" value="1">
                                </td>
                                <td>
                                    <input type="text" name="default[]" class="form-input" placeholder="Valore default">
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    Create Table
                </button>
            </form>
        </div>
    </div>
    <?php
}

function include_page_sql() {
    global $dbfile, $pdo, $query_result;
    $result = null;
    $query_error = null;
    
    // Retrieve table lists from PDO database
    $tables = [];
    if ($pdo) {
        $resTables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = $resTables->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Function to get the names of the columns of a table
    function getTableColumns($table, $pdo) {
        $columns = [];
        if ($table && $pdo) {
            $res = $pdo->query("PRAGMA table_info(" . $pdo->quote($table) . ")");
            $columns = $res->fetchAll(PDO::FETCH_COLUMN, 1);
        }
        return $columns;
    }
    
    $table = $_POST['table'] ?? null;
    $columns = $table ? getTableColumns($table, $pdo) : [];
    $built_query = '';
    $queryType = $_POST['querytype'] ?? 'SELECT';
    
    // Builds the query if requested (but does not execute it)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['build_query'])) {
        switch($queryType) {
            case 'SELECT':
                $selectedFields = $_POST['fields'] ?? [];
                if (empty($selectedFields)) {
                    $selectedFields = ['*'];
                }
                $built_query = "SELECT " . implode(', ', $selectedFields) . " FROM `" . $table . "`";
                
                // WHERE
                $whereClauses = $_POST['where'] ?? [];
                if (!empty($whereClauses)) {
                    $conditions = [];
                    foreach ($whereClauses as $clause) {
                        $col = $clause['column'] ?? '';
                        $op = $clause['operator'] ?? '=';
                        $val = $clause['value'] ?? '';
                        if ($col && $op && $val !== '') {
                            if (strtoupper($op) === 'LIKE') {
                                $conditions[] = "`$col` $op " . $pdo->quote("%$val%");
                            } else {
                                $conditions[] = "`$col` $op " . $pdo->quote($val);
                            }
                        }
                    }
                    if ($conditions) {
                        $built_query .= " WHERE " . implode(' AND ', $conditions);
                    }
                }
                
                // GROUP BY
                if (!empty($_POST['groupby'])) {
                    $groupCols = array_map(function($col) {
                        return "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . "`";
                    }, (array)$_POST['groupby']);
                    $built_query .= ' GROUP BY ' . implode(', ', $groupCols);
                }
                
                // ORDER BY
                if (!empty($_POST['orderby'])) {
                    $col = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['orderby']);
                    $dir = ($_POST['orderdir'] === 'DESC') ? 'DESC' : 'ASC';
                    $built_query .= " ORDER BY `$col` $dir";
                }
                
                // LIMIT
                if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                    $built_query .= ' LIMIT ' . intval($_POST['limit']);
                }
                break;
                
            case 'INSERT':
                $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['insert_table'] ?? '');
                $cols = $_POST['insert_cols'] ?? [];
                $vals = $_POST['insert_vals'] ?? [];
                $filteredCols = [];
                $filteredVals = [];
                
                foreach ($cols as $i => $col) {
                    if (!empty($col) && isset($vals[$i]) && $vals[$i] !== '') {
                        $filteredCols[] = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . "`";
                        $filteredVals[] = $pdo->quote($vals[$i]);
                    }
                }
                
                if ($tbl && !empty($filteredCols)) {
                    $built_query = "INSERT INTO `$tbl` (" . implode(', ', $filteredCols) . ") VALUES (" . implode(', ', $filteredVals) . ")";
                }
                break;
                
            case 'UPDATE':
                $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['update_table'] ?? '');
                $sets = [];
                
                if (!empty($_POST['update_set'])) {
                    foreach ($_POST['update_set'] as $sv) {
                        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $sv['col'] ?? '');
                        $val = $sv['val'] ?? '';
                        if ($col && $val !== '') {
                            $sets[] = "`$col` = " . $pdo->quote($val);
                        }
                    }
                }
                
                if ($tbl && !empty($sets)) {
                    $built_query = "UPDATE `$tbl` SET " . implode(', ', $sets);
                    
                    // WHERE per UPDATE
                    $whereClauses = $_POST['where'] ?? [];
                    if (!empty($whereClauses)) {
                        $conditions = [];
                        foreach ($whereClauses as $clause) {
                            $col = $clause['column'] ?? '';
                            $op = $clause['operator'] ?? '=';
                            $val = $clause['value'] ?? '';
                            if ($col && $op && $val !== '') {
                                $conditions[] = "`$col` $op " . $pdo->quote($val);
                            }
                        }
                        if ($conditions) {
                            $built_query .= " WHERE " . implode(' AND ', $conditions);
                        }
                    }
                }
                break;
                
            case 'DELETE':
                $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['delete_table'] ?? '');
                if ($tbl) {
                    $built_query = "DELETE FROM `$tbl`";
                    
                    // WHERE per DELETE
                    $whereClauses = $_POST['where'] ?? [];
                    if (!empty($whereClauses)) {
                        $conditions = [];
                        foreach ($whereClauses as $clause) {
                            $col = $clause['column'] ?? '';
                            $op = $clause['operator'] ?? '=';
                            $val = $clause['value'] ?? '';
                            if ($col && $op && $val !== '') {
                                $conditions[] = "`$col` $op " . $pdo->quote($val);
                            }
                        }
                        if ($conditions) {
                            $built_query .= " WHERE " . implode(' AND ', $conditions);
                        }
                    }
                }
                break;
                
            case 'CREATE':
                $tbl = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['create_table'] ?? '');
                $defs = [];
                
                if (!empty($_POST['create_fields'])) {
                    foreach ($_POST['create_fields'] as $f) {
                        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $f['name'] ?? '');
                        $type = in_array($f['type'] ?? '', ['INTEGER', 'TEXT', 'REAL', 'BLOB'], true) ? $f['type'] : 'TEXT';
                        $constraints = trim($f['constraints'] ?? '');
                        
                        if ($name) {
                            $fieldDef = "`$name` $type";
                            if ($constraints) {
                                $fieldDef .= " $constraints";
                            }
                            $defs[] = $fieldDef;
                        }
                    }
                }
                
                if ($tbl && !empty($defs)) {
                    $built_query = "CREATE TABLE IF NOT EXISTS `$tbl` (" . implode(', ', $defs) . ")";
                }
                break;
        }
    }
    
    // Run ONLY if a query has been submitted manually
    if (isset($_POST['sql']) && $pdo) {
        $sql = trim($_POST['sql']);
        if ($sql) {
            try {
                if (stripos($sql, 'SELECT') === 0) {
                    $stmt = $pdo->query($sql);
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $affected = $pdo->exec($sql);
                    $result = "The query was successful. Affected rows: " . $affected;
                }
            } catch (PDOException $e) {
                $query_error = $e->getMessage();
            }
        }
    }
?>
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-terminal"></i>
            Query SQL
        </h1>
        <p class="page-subtitle">
            Run custom SQL queries on <?= htmlspecialchars($dbfile) ?>
        </p>
    </div>
    
    <!-- Query Builder -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-cogs"></i>
                Query Builder
            </h3>
        </div>
        <div class="card-body">
            <form method="post" class="mb-3" id="queryBuilderForm">
                <!-- Query type selector -->
                <div class="form-group mb-3">
                    <label for="queryType" class="form-label">Query Type</label>
                    <select id="queryType" name="querytype" class="form-input" onchange="this.form.submit()">
                        <option value="SELECT" <?= $queryType === 'SELECT' ? 'selected' : '' ?>>SELECT</option>
                        <option value="INSERT" <?= $queryType === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                        <option value="UPDATE" <?= $queryType === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                        <option value="DELETE" <?= $queryType === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                        <option value="CREATE" <?= $queryType === 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                    </select>
                </div>
                
                <?php if ($queryType === 'SELECT'): ?>
                    <div class="form-group mb-3">
                        <label for="tableSelect" class="form-label">Select Table</label>
                        <select id="tableSelect" name="table" class="form-input" onchange="this.form.submit()" required>
                            <option value="">-- Choose a table --</option>
                            <?php foreach ($tables as $tbl): ?>
                                <option value="<?= htmlspecialchars($tbl) ?>" <?= ($tbl === $table) ? 'selected' : '' ?>><?= htmlspecialchars($tbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($table): ?>
                        <div class="form-group mb-3">
                            <label for="fieldsSelect" class="form-label">Select Fields</label>
                            <select id="fieldsSelect" name="fields[]" class="form-input" multiple size="5">
                                <?php foreach ($columns as $col): ?>
                                    <option value="<?= htmlspecialchars($col) ?>" <?php if (in_array($col, $_POST['fields'] ?? [])) echo 'selected' ?>><?= htmlspecialchars($col) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="text-muted">Hold down Ctrl/Cmd to select multiple fields. Leave blank for SELECT *</div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Conditions (WHERE)</label>
                            <div id="conditionsContainer">
                                <?php
                                $conditions = $_POST['where'] ?? [[]];
                                foreach ($conditions as $index => $cond):
                                ?>
                                <div class="condition-row">
                                    <select name="where[<?=$index?>][column]" class="form-input">
                                        <option value="">Select field</option>
                                        <?php foreach ($columns as $col): ?>
                                            <option value="<?= htmlspecialchars($col) ?>" <?php if (($cond['column']??'') === $col) echo 'selected' ?>><?= htmlspecialchars($col) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="where[<?=$index?>][operator]" class="form-input">
                                        <?php
                                        $ops = ['='=>'Uguale', '!='=>'Diverso', '<'=>'Minore', '<='=>'Minore o uguale', '>'=>'Maggiore', '>='=>'Maggiore o uguale', 'LIKE'=>'Contiene'];
                                        foreach ($ops as $op => $label):
                                        ?>
                                        <option value="<?=$op?>" <?php if (($cond['operator']??'') === $op) echo 'selected' ?>><?=$label?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="where[<?=$index?>][value]" class="form-input" placeholder="Value" value="<?= htmlspecialchars($cond['value'] ?? '') ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="addConditionBtn" class="btn btn-outline btn-sm mt-2" onclick="addCondition()">
                                <i class="fas fa-plus"></i>
                                Add condition
                            </button>
                        </div>
                        
                        <!-- GROUP BY -->
                        <div class="form-group mb-3">
                            <label for="groupBySelect" class="form-label">GROUP BY</label>
                            <select id="groupBySelect" name="groupby[]" class="form-input" multiple size="3">
                                <?php foreach ($columns as $col): ?>
                                    <option value="<?= htmlspecialchars($col) ?>"
                                        <?= in_array($col, $_POST['groupby'] ?? [], true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($col) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <small class="text-muted">Hold down Ctrl/Cmd for multiple selections.</small>
                        </div>
                        
                        <!-- ORDER BY -->
                        <div class="form-group mb-3">
                            <label for="orderBySelect" class="form-label">ORDER BY</label>
                            <select id="orderBySelect" name="orderby" class="form-input">
                                <option value="">Nobody</option>
                                <?php foreach ($columns as $col): ?>
                                    <option value="<?= htmlspecialchars($col) ?>"
                                        <?= ($_POST['orderby'] ?? '') === $col ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($col) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <select name="orderdir" class="form-input ms-2">
                                <option value="ASC" <?= ($_POST['orderdir'] ?? 'ASC') === 'ASC' ? 'selected' : '' ?>>ASC</option>
                                <option value="DESC" <?= ($_POST['orderdir'] ?? '') === 'DESC' ? 'selected' : '' ?>>DESC</option>
                            </select>
                        </div>
                        
                        <!-- LIMIT -->
                        <div class="form-group mb-3">
                            <label for="limitInput" class="form-label">LIMIT</label>
                            <input type="number" id="limitInput" name="limit" class="form-input" min="1"
                                   value="<?= htmlspecialchars($_POST['limit'] ?? '') ?>" placeholder="Number of lines">
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($queryType === 'INSERT'): ?>
                    <div class="form-group mb-3">
                        <label for="insertTable" class="form-label">Table</label>
                        <select id="insertTable" name="insert_table" class="form-input" required>
                            <option value="">-- Choose a table --</option>
                            <?php foreach ($tables as $tbl): ?>
                                <option value="<?= htmlspecialchars($tbl) ?>" <?= ($_POST['insert_table'] ?? '') === $tbl ? 'selected' : '' ?>><?= htmlspecialchars($tbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Columns and Values</label>
                        <div id="insertCols">
                            <?php
                            $cols = $_POST['insert_cols'] ?? [''];
                            $vals = $_POST['insert_vals'] ?? [''];
                            foreach ($cols as $i => $col):
                            ?>
                            <div class="d-flex mb-2">
                                <input name="insert_cols[]" class="form-input me-2" placeholder="Column" value="<?= htmlspecialchars($col) ?>">
                                <input name="insert_vals[]" class="form-input" placeholder="Value" value="<?= htmlspecialchars($vals[$i] ?? '') ?>">
                                <button type="button" class="btn btn-danger btn-sm ms-2" onclick="this.parentNode.remove()">&times;</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addInsertField()">+ Add pair</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($queryType === 'UPDATE'): ?>
                    <div class="form-group mb-3">
                        <label for="updateTable" class="form-label">Table</label>
                        <select id="updateTable" name="update_table" class="form-input" onchange="this.form.submit()" required>
                            <option value="">-- Choose a table --</option>
                            <?php foreach ($tables as $tbl): ?>
                                <option value="<?= htmlspecialchars($tbl) ?>" <?= ($_POST['update_table'] ?? '') === $tbl ? 'selected' : '' ?>><?= htmlspecialchars($tbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php
                    $updateTable = $_POST['update_table'] ?? null;
                    $updateColumns = $updateTable ? getTableColumns($updateTable, $pdo) : [];
                    ?>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">SET</label>
                        <div id="updateSets">
                            <?php
                            $sets = $_POST['update_set'] ?? [['col' => '', 'val' => '']];
                            foreach ($sets as $i => $sv):
                            ?>
                            <div class="d-flex mb-2">
                                <select name="update_set[<?= $i ?>][col]" class="form-input me-2">
                                    <option value="">Select column</option>
                                    <?php foreach ($updateColumns as $col): ?>
                                        <option value="<?= htmlspecialchars($col) ?>" <?= $sv['col'] === $col ? 'selected' : '' ?>><?= htmlspecialchars($col) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="update_set[<?= $i ?>][val]" class="form-input" placeholder="New value" value="<?= htmlspecialchars($sv['val']) ?>">
                                <button type="button" class="btn btn-danger btn-sm ms-2" onclick="this.parentNode.remove()">&times;</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addUpdateField()">+ Add</button>
                    </div>
                    
                    <?php if ($updateTable): ?>
                        <div class="form-group mb-3">
                            <label class="form-label">Conditions (WHERE)</label>
                            <div id="updateConditionsContainer">
                                <?php
                                $conditions = $_POST['where'] ?? [[]];
                                foreach ($conditions as $index => $cond):
                                ?>
                                <div class="condition-row">
                                    <select name="where[<?=$index?>][column]" class="form-input">
                                        <option value="">Select field</option>
                                        <?php foreach ($updateColumns as $col): ?>
                                            <option value="<?= htmlspecialchars($col) ?>" <?php if (($cond['column']??'') === $col) echo 'selected' ?>><?= htmlspecialchars($col) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="where[<?=$index?>][operator]" class="form-input">
                                        <?php
                                        $ops = ['='=>'Uguale', '!='=>'Diverso', '<'=>'Minore', '<='=>'Minore o uguale', '>'=>'Maggiore', '>='=>'Maggiore o uguale', 'LIKE'=>'Contiene'];
                                        foreach ($ops as $op => $label):
                                        ?>
                                        <option value="<?=$op?>" <?php if (($cond['operator']??'') === $op) echo 'selected' ?>><?=$label?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="where[<?=$index?>][value]" class="form-input" placeholder="Valore" value="<?= htmlspecialchars($cond['value'] ?? '') ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm mt-2" onclick="addUpdateCondition()">
                                <i class="fas fa-plus"></i>
                                Add condition
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($queryType === 'DELETE'): ?>
                    <div class="form-group mb-3">
                        <label for="deleteTable" class="form-label">Table</label>
                        <select id="deleteTable" name="delete_table" class="form-input" onchange="this.form.submit()" required>
                            <option value="">-- Choose a table --</option>
                            <?php foreach ($tables as $tbl): ?>
                                <option value="<?= htmlspecialchars($tbl) ?>" <?= ($_POST['delete_table'] ?? '') === $tbl ? 'selected' : '' ?>><?= htmlspecialchars($tbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php
                    $deleteTable = $_POST['delete_table'] ?? null;
                    $deleteColumns = $deleteTable ? getTableColumns($deleteTable, $pdo) : [];
                    ?>
                    
                    <?php if ($deleteTable): ?>
                        <div class="form-group mb-3">
                            <label class="form-label">Conditions (WHERE)</label>
                            <div id="deleteConditionsContainer">
                                <?php
                                $conditions = $_POST['where'] ?? [[]];
                                foreach ($conditions as $index => $cond):
                                ?>
                                <div class="condition-row">
                                    <select name="where[<?=$index?>][column]" class="form-input">
                                        <option value="">Select field</option>
                                        <?php foreach ($deleteColumns as $col): ?>
                                            <option value="<?= htmlspecialchars($col) ?>" <?php if (($cond['column']??'') === $col) echo 'selected' ?>><?= htmlspecialchars($col) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="where[<?=$index?>][operator]" class="form-input">
                                        <?php
                                        $ops = ['='=>'Uguale', '!='=>'Diverso', '<'=>'Minore', '<='=>'Minore o uguale', '>'=>'Maggiore', '>='=>'Maggiore o uguale', 'LIKE'=>'Contiene'];
                                        foreach ($ops as $op => $label):
                                        ?>
                                        <option value="<?=$op?>" <?php if (($cond['operator']??'') === $op) echo 'selected' ?>><?=$label?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="where[<?=$index?>][value]" class="form-input" placeholder="Value" value="<?= htmlspecialchars($cond['value'] ?? '') ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm mt-2" onclick="addDeleteCondition()">
                                <i class="fas fa-plus"></i>
                                Add condition
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($queryType === 'CREATE'): ?>
                    <div class="form-group mb-3">
                        <label for="createTable" class="form-label">Table Name</label>
                        <input id="createTable" name="create_table" class="form-input" value="<?= htmlspecialchars($_POST['create_table'] ?? '') ?>" placeholder="Name of the new table" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Fields</label>
                        <div id="createFields">
                            <?php
                            $fields = $_POST['create_fields'] ?? [['name' => '', 'type' => 'TEXT', 'constraints' => '']];
                            foreach ($fields as $i => $f):
                            ?>
                            <div class="d-flex mb-2">
                                <input name="create_fields[<?= $i ?>][name]" class="form-input me-2" placeholder="Field name" value="<?= htmlspecialchars($f['name']) ?>" required>
                                <select name="create_fields[<?= $i ?>][type]" class="form-input me-2">
                                    <option value="TEXT" <?= $f['type'] === 'TEXT' ? 'selected' : '' ?>>TEXT</option>
                                    <option value="INTEGER" <?= $f['type'] === 'INTEGER' ? 'selected' : '' ?>>INTEGER</option>
                                    <option value="REAL" <?= $f['type'] === 'REAL' ? 'selected' : '' ?>>REAL</option>
                                    <option value="BLOB" <?= $f['type'] === 'BLOB' ? 'selected' : '' ?>>BLOB</option>
                                </select>
                                <input name="create_fields[<?= $i ?>][constraints]" class="form-input me-2" placeholder="Constraints (PRIMARY KEY, NOT NULL, etc.)" value="<?= htmlspecialchars($f['constraints']) ?>">
                                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.remove()">&times;</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addCreateField()">+ Add field</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($queryType === 'SELECT' && $table || $queryType !== 'SELECT'): ?>
                    <button type="submit" name="build_query" class="btn btn-primary">
                        <i class="fas fa-code"></i>
                        Build Query <?= $queryType ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Manual SQL Editor -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-terminal"></i>
                Manual SQL Editor
            </h3>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <textarea name="sql" 
                              class="form-input" 
                              rows="8" 
                              placeholder="-- Supported SQLite commands:
-- DML: SELECT, INSERT, UPDATE, DELETE
-- DDL: CREATE, ALTER, DROP, RENAME
-- Transactions: BEGIN, COMMIT, ROLLBACK
-- Indexes: CREATE INDEX, DROP INDEX
-- Views: CREATE VIEW, DROP VIEW
-- Triggers: CREATE TRIGGER, DROP TRIGGER
-- PRAGMA statements: PRAGMA table_info(table), PRAGMA index_list(table), etc.
-- ATTACH DATABASE, DETACH DATABASE
-- VACUUM, ANALYZE

-- Examples:
-- CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT);
-- CREATE INDEX idx_name ON users(name);
-- CREATE VIEW active_users AS SELECT * FROM users WHERE active=1;
-- PRAGMA table_info(users);
-- BEGIN TRANSACTION; ... COMMIT;"
                              style="font-family: 'Courier New', monospace; resize: vertical;"
                              id="sql-editor"><?= $built_query ? htmlspecialchars($built_query) : (isset($_POST['sql']) ? htmlspecialchars($_POST['sql']) : '') ?></textarea>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-lightbulb"></i>
                        Tips: Use Ctrl+Enter to execute query | F1 for SQLite documentation
                    </small>
                </div>
                <button type="submit" class="btn btn-primary mt-2">
                    <i class="fas fa-play"></i>
                    Run Query
                </button>
            </form>
        </div>
    </div>
    
    <?php if ($query_error): ?>
        <div class="alert alert-error">
            <strong><i class="fas fa-exclamation-triangle"></i> SQL Error:</strong><br>
            <?= htmlspecialchars($query_error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($result !== null): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Results
                </h3>
            </div>
            <div class="card-body">
                <?php if (is_string($result)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check"></i>
                        <?= htmlspecialchars($result) ?>
                    </div>
                <?php elseif (is_array($result) && !empty($result)): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($result[0]) as $column): ?>
                                        <th><?= htmlspecialchars($column) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-muted">
                        <i class="fas fa-info-circle"></i>
                        <?= count($result) ?> results found
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No results were returned from the query.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        // JavaScript functions to add fields dynamically
        
        // Add WHERE condition for SELECT
        function addCondition() {
            let cnt = document.querySelectorAll('#conditionsContainer .condition-row').length;
            let html = '<div class="condition-row">' +
                '<select name="where[' + cnt + '][column]" class="form-input">' +
                    '<option value="">Select field</option>' +
                    <?php foreach ($columns as $col): ?>
                        '"<option value="<?= htmlspecialchars($col) ?>"><?= htmlspecialchars($col) ?></option>" +' +
                    <?php endforeach; ?>
                '</select>' +
                '<select name="where[' + cnt + '][operator]" class="form-input">' +
                    '<option value="=">Uguale</option>' +
                    '<option value="!=">Diverso</option>' +
                    '<option value="<">Minore</option>' +
                    '<option value="<=">Minore o uguale</option>' +
                    '<option value=">">Maggiore</option>' +
                    '<option value=">=">Maggiore o uguale</option>' +
                    '<option value="LIKE">Contiene</option>' +
                '</select>' +
                '<input type="text" name="where[' + cnt + '][value]" class="form-input" placeholder="Value">' +
                '<button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>' +
            '</div>';
            document.getElementById('conditionsContainer').insertAdjacentHTML('beforeend', html);
        }
        
        // Aggiungi campo INSERT
        function addInsertField() {
            let html = '<div class="d-flex mb-2">' +
                '<input name="insert_cols[]" class="form-input me-2" placeholder="Column">' +
                '<input name="insert_vals[]" class="form-input" placeholder="Value">' +
                '<button type="button" class="btn btn-danger btn-sm ms-2" onclick="this.parentNode.remove()">&times;</button>' +
            '</div>';
            document.getElementById('insertCols').insertAdjacentHTML('beforeend', html);
        }
        
        // Add UPDATE SET field
        function addUpdateField() {
            let cnt = document.querySelectorAll('#updateSets > div').length;
            let html = '<div class="d-flex mb-2">' +
                '<select name="update_set[' + cnt + '][col]" class="form-input me-2">' +
                    '<option value="">Seleziona colonna</option>' +
                    <?php if (isset($updateColumns)): foreach ($updateColumns as $col): ?>
                        '"<option value="<?= htmlspecialchars($col) ?>"><?= htmlspecialchars($col) ?></option>" +' +
                    <?php endforeach; endif; ?>
                '</select>' +
                '<input name="update_set[' + cnt + '][val]" class="form-input" placeholder="New value">' +
                '<button type="button" class="btn btn-danger btn-sm ms-2" onclick="this.parentNode.remove()">&times;</button>' +
            '</div>';
            document.getElementById('updateSets').insertAdjacentHTML('beforeend', html);
        }
        
        // Add WHERE condition for UPDATE
        function addUpdateCondition() {
            let cnt = document.querySelectorAll('#updateConditionsContainer .condition-row').length;
            let html = '<div class="condition-row">' +
                '<select name="where[' + cnt + '][column]" class="form-input">' +
                    '<option value="">Select field</option>' +
                    <?php if (isset($updateColumns)): foreach ($updateColumns as $col): ?>
                        '"<option value="<?= htmlspecialchars($col) ?>"><?= htmlspecialchars($col) ?></option>" +' +
                    <?php endforeach; endif; ?>
                '</select>' +
                '<select name="where[' + cnt + '][operator]" class="form-input">' +
                    '<option value="=">Uguale</option>' +
                    '<option value="!=">Diverso</option>' +
                    '<option value="<">Minore</option>' +
                    '<option value="<=">Minore o uguale</option>' +
                    '<option value=">">Maggiore</option>' +
                    '<option value=">=">Maggiore o uguale</option>' +
                    '<option value="LIKE">Contiene</option>' +
                '</select>' +
                '<input type="text" name="where[' + cnt + '][value]" class="form-input" placeholder="Valore">' +
                '<button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>' +
            '</div>';
            document.getElementById('updateConditionsContainer').insertAdjacentHTML('beforeend', html);
        }
        
        // Add WHERE condition for DELETE
        function addDeleteCondition() {
            let cnt = document.querySelectorAll('#deleteConditionsContainer .condition-row').length;
            let html = '<div class="condition-row">' +
                '<select name="where[' + cnt + '][column]" class="form-input">' +
                    '<option value="">Select field</option>' +
                    <?php if (isset($deleteColumns)): foreach ($deleteColumns as $col): ?>
                        '"<option value="<?= htmlspecialchars($col) ?>"><?= htmlspecialchars($col) ?></option>" +' +
                    <?php endforeach; endif; ?>
                '</select>' +
                '<select name="where[' + cnt + '][operator]" class="form-input">' +
                    '<option value="=">Uguale</option>' +
                    '<option value="!=">Diverso</option>' +
                    '<option value="<">Minore</option>' +
                    '<option value="<=">Minore o uguale</option>' +
                    '<option value=">">Maggiore</option>' +
                    '<option value=">=">Maggiore o uguale</option>' +
                    '<option value="LIKE">Contiene</option>' +
                '</select>' +
                '<input type="text" name="where[' + cnt + '][value]" class="form-input" placeholder="Valore">' +
                '<button type="button" class="btn btn-danger btn-sm btn-remove-condition" title="Remove Condition">&times;</button>' +
            '</div>';
            document.getElementById('deleteConditionsContainer').insertAdjacentHTML('beforeend', html);
        }
        
        // Add CREATE TABLE field
        function addCreateField() {
            let cnt = document.querySelectorAll('#createFields > div').length;
            let html = '<div class="d-flex mb-2">' +
                '<input name="create_fields[' + cnt + '][name]" class="form-input me-2" placeholder="Field name" required>' +
                '<select name="create_fields[' + cnt + '][type]" class="form-input me-2">' +
                    '<option value="TEXT">TEXT</option>' +
                    '<option value="INTEGER">INTEGER</option>' +
                    '<option value="REAL">REAL</option>' +
                    '<option value="BLOB">BLOB</option>' +
                '</select>' +
                '<input name="create_fields[' + cnt + '][constraints]" class="form-input me-2" placeholder="Constraints (PRIMARY KEY, NOT NULL, etc.)">' +
                '<button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.remove()">&times;</button>' +
            '</div>';
            document.getElementById('createFields').insertAdjacentHTML('beforeend', html);
        }
        
        // Removing WHERE conditions
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-condition')) {
                e.target.parentNode.remove();
            }
        });
    </script>
<?php
}

function include_page_info() {
    global $dbfile, $pdo;
    $info = get_database_info($dbfile);
    ?>

    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-info-circle"></i>
            Database Information
        </h1>
        <p class="page-subtitle">
            Technical details and statistics for <?= htmlspecialchars($dbfile) ?>
        </p>
    </div>

    <?php if ($info): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-database"></i>
                    <?= htmlspecialchars($info['db_name']) ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td><strong>Database Name</strong></td>
                                <td><?= htmlspecialchars($info['db_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Complete route</strong></td>
                                <td><code><?= htmlspecialchars($info['path']) ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>File Size</strong></td>
                                <td><?= htmlspecialchars($info['size']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Edit</strong></td>
                                <td><?= htmlspecialchars($info['last_modified']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>SQLite Version</strong></td>
                                <td><?= htmlspecialchars($info['sqlite_version']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP Extension</strong></td>
                                <td><?= htmlspecialchars($info['sqlite_extension']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version</strong></td>
                                <td><?= htmlspecialchars($info['php_version']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Program Version</strong></td>
                                <td><?= htmlspecialchars($info['program_version']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($pdo): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i>
                        SQLite configuration
                    </h3>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $pragma_info = [];
                        $pragmas = ['journal_mode', 'synchronous', 'temp_store', 'locking_mode', 'page_size', 'cache_size'];

                        foreach ($pragmas as $pragma) {
                            $result = $pdo->query("PRAGMA $pragma")->fetchColumn();
                            $pragma_info[$pragma] = $result;
                        }
                    } catch (Exception $e) {
                        $pragma_info = [];
                    }
                    ?>

                    <?php if (!empty($pragma_info)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Configuration</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pragma_info as $key => $value): ?>
                                        <tr>
                                            <td><strong><?= ucfirst(str_replace('_', ' ', $key)) ?></strong></td>
                                            <td><code><?= htmlspecialchars($value) ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Unable to retrieve configuration information.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            Database not found or inaccessible.
        </div>
    <?php endif; ?>
    <?php
}

function include_page_browse() {
    global $dbfile, $pdo, $browse_error, $browse_data, $page_num, $per_page, $total_records, $total_pages;

    $table = $_GET['table'] ?? '';
    if (!$table || !$pdo) {
        echo '<div class="alert alert-error">Table not specified or database not available.</div>';
        return;
    }

    try {
        // Recupera metadati della tabella
        $table_info = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo '<div class="alert alert-error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return;
    }
    ?>

    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-table"></i>
            Browse: <?= htmlspecialchars($table) ?>
        </h1>
        <p class="page-subtitle">
            Viewing and editing table data
        </p>
    </div>

    <?php if (!empty($browse_error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($browse_error) ?>
        </div>
    <?php endif; ?>

    <!-- Form for inserting a new record -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-plus"></i>
                Add New Record
            </h3>
        </div>
        <div class="card-body">
            <form method="post" class="mb-3">
                <div class="form-row">
                    <?php foreach ($table_info as $col): 
                        if ($col['name'] === 'rowid') continue;
                        $name = $col['name'];
                        $type = strtoupper($col['type']);
                        // Scegli input type
                        if (strpos($type, 'INT') === 0) $input_type = 'number';
                        elseif (strpos($type, 'REAL') === 0) $input_type = 'number" step="any';
                        elseif (strpos($type, 'DATE') !== false) $input_type = 'date';
                        else $input_type = 'text';
                    ?>
                        <div class="form-group">
                            <label class="form-label"><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($type) ?>)</label>
                            <input type="<?= $input_type ?>" name="new_<?= $name ?>" class="form-input">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="insert_record" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    Add Record
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($browse_data)): ?>
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-inbox" style="font-size: 3rem; color: var(--secondary); margin-bottom: 1rem;"></i>
                <h3>Empty Table</h3>
                <p class="text-muted">This table does not yet contain data.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Data Table
                    <span class="nav-badge"><?= count($browse_data) ?> record</span>
                </h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="save_edits" value="1">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php
                                        $cols_for_edit = array_filter(array_keys($browse_data[0]), function($c) { return $c !== 'rowid'; });
                                        foreach($cols_for_edit as $colname):
                                    ?>
                                        <th><?= htmlspecialchars($colname) ?></th>
                                    <?php endforeach; ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($browse_data as $r => $row): ?>
                                    <tr>
                                        <input type="hidden" name="rowid[]" value="<?= intval($row['rowid']) ?>">
                                        <?php foreach ($cols_for_edit as $colname):
                                                // trova tipo colonna
                                                $col_meta = null;
                                                foreach ($table_info as $ci) { if ($ci['name'] === $colname) { $col_meta = $ci; break; } }
                                                $col_type = $col_meta['type'] ?? '';
                                                if (stripos($col_type, 'BLOB') !== false) {
                                                    echo '<td>';
                                                    if (!is_null($row[$colname]) && @getimagesizefromstring($row[$colname])) {
                                                        echo '<img src="data:image/*;base64,'.base64_encode($row[$colname]).'" class="blob-preview"><br>';
                                                        echo '<a href="?db='.urlencode($dbfile).'&page=browse&table='.urlencode($table).'&download_blob=1&rowid='.intval($row["rowid"]).'&col='.urlencode($colname).'">Download</a><br>';
                                                    } elseif (!is_null($row[$colname])) {
                                                        echo '<a href="?db='.urlencode($dbfile).'&page=browse&table='.urlencode($table).'&download_blob=1&rowid='.intval($row["rowid"]).'&col='.urlencode($colname).'">Download file</a><br>';
                                                    }
                                                    echo '<input type="file" name="new_'.htmlspecialchars($colname).'[]">';
                                                    echo '</td>';
                                                } else {
                                                    echo '<td><input class="form-input" type="text" name="'.htmlspecialchars($colname).'[]" value="'.htmlspecialchars($row[$colname]).'"></td>';
                                                }
                                            endforeach; ?>
                                        <td>
                                            <a href="?db=<?= urlencode($dbfile) ?>&page=browse&table=<?= urlencode($table) ?>&delrow=<?= intval($row['rowid']) ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Eliminare record?')">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="cols" value="<?= htmlspecialchars(json_encode($cols_for_edit)) ?>">
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="fas fa-save"></i>
                        Save changes
                    </button>
                </form>
                
                <!-- Pagination Controls -->
                <div class="mt-3" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        Showing <?= (($page_num - 1) * $per_page + 1) ?> - <?= min($page_num * $per_page, $total_records) ?> of <?= number_format($total_records) ?> records
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <form method="get" style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="hidden" name="db" value="<?= htmlspecialchars($dbfile) ?>">
                            <input type="hidden" name="page" value="browse">
                            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                            <label>Per page:</label>
                            <select name="pp" class="form-input" style="width: auto; padding: 0.5rem;" onchange="this.form.submit()">
                                <?php foreach ([25, 50, 100, 200, 500] as $pp): ?>
                                    <option value="<?= $pp ?>" <?= ($per_page == $pp) ? 'selected' : '' ?>><?= $pp ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 0.25rem; margin-top: 1rem; flex-wrap: wrap;">
                    <?php 
                    $base_url = "?db=" . urlencode($dbfile) . "&page=browse&table=" . urlencode($table) . "&pp=" . $per_page;
                    ?>
                    <?php if ($page_num > 1): ?>
                        <a href="<?= $base_url ?>&p=1" class="btn btn-sm btn-outline" title="First"><i class="fas fa-angle-double-left"></i></a>
                        <a href="<?= $base_url ?>&p=<?= $page_num - 1 ?>" class="btn btn-sm btn-outline" title="Previous"><i class="fas fa-angle-left"></i></a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page_num - 2);
                    $end_page = min($total_pages, $page_num + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $page_num): ?>
                            <span class="btn btn-sm btn-primary"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $base_url ?>&p=<?= $i ?>" class="btn btn-sm btn-outline"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page_num < $total_pages): ?>
                        <a href="<?= $base_url ?>&p=<?= $page_num + 1 ?>" class="btn btn-sm btn-outline" title="Next"><i class="fas fa-angle-right"></i></a>
                        <a href="<?= $base_url ?>&p=<?= $total_pages ?>" class="btn btn-sm btn-outline" title="Last"><i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php
}

function include_page_structure() {
    global $dbfile, $pdo, $error_structure;

    $table = $_GET['table'] ?? '';
    if (!$table || !$pdo) {
        echo '<div class="alert alert-error">Table not specified or database not available.</div>';
        return;
    }

    try {
        $cols = $pdo->query("PRAGMA table_info('$table')")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo '<div class="alert alert-error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return;
    }
    ?>
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-cogs"></i>
            Structure: <?= htmlspecialchars($table) ?>
        </h1>
        <p class="page-subtitle">
            Change table structure
        </p>
    </div>

    <?php if (!empty($error_structure)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error_structure) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-columns"></i>
                Columns (<?= count($cols) ?>)
            </h3>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="table-container mb-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Column name (edit)</th>
                                <th>Type</th>
                                <th>PK</th>
                                <th>AI</th>
                                <th>Not Null</th>
                                <th>Unique</th>
                                <th>Default</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody id="cols-body">
                        <?php foreach ($cols as $i => $col): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="col_oldname[]" value="<?= htmlspecialchars($col['name']) ?>">
                                    <input type="text" name="col_newname[]" class="form-input" value="<?= htmlspecialchars($col['name']) ?>" required>
                                </td>
                                <td>
                                    <select name="col_type[]" class="form-input" required>
                                        <?= type_options_html($col['type']) ?>
                                    </select>
                                </td>
                                <td class="text-center"><input type="checkbox" name="pk[<?=$i?>]" value="1" <?= ($col['pk'] ? 'checked' : '') ?>></td>
                                <td class="text-center"><input type="checkbox" name="ai[<?=$i?>]" value="1" <?= ($col['pk'] && stripos($col['type'], 'INTEGER') === 0 ? 'checked' : '') ?>></td>
                                <td class="text-center"><input type="checkbox" name="nn[<?=$i?>]" value="1" <?= ($col['notnull'] ? 'checked' : '') ?>></td>
                                <td class="text-center"><input type="checkbox" name="unique[<?=$i?>]" value="1"></td>
                                <td><input type="text" name="default[]" class="form-input" value="<?= htmlspecialchars($col['dflt_value']) ?>"></td>
                                <td class="text-center"><input type="checkbox" name="delcol[]" value="<?=$i?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline mb-2" onclick="addColumnRow()">
                    <i class="fas fa-plus"></i>
                    Add column
                </button>
                <button type="submit" name="save_structure" class="btn btn-success mb-2">
                    <i class="fas fa-save"></i>
                    Save changes
                </button>
            </form>
        </div>
    </div>
    <?php
}

function include_page_truncate() {
    global $dbfile, $pdo;

    $table = $_GET['table'] ?? '';
    if (!$table || !$pdo) {
        echo '<div class="alert alert-error">Table not specified or database not available.</div>';
        return;
    }

    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `" . str_replace('`', '``', $table) . "`")->fetchColumn();
    } catch (Exception $e) {
        $count = 0;
    }
    ?>

    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-trash"></i>
            Empty Table: <?= htmlspecialchars($table) ?>
        </h1>
        <p class="page-subtitle">
            Remove all data from the table
        </p>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                Confirm Operation
            </h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong><i class="fas fa-warning"></i> Attention!</strong><br>
                This operation will remove <strong>all the <?= number_format($count) ?> record</strong> 
                from the table <code><?= htmlspecialchars($table) ?></code>.<br>
                <strong>The operation is not reversible!</strong>
            </div>

            <?php if ($count > 0): ?>
                <form method="post">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="vacuum_after" id="vacuum_after" value="1">
                        <label class="form-check-label" for="vacuum_after">
                            Run VACUUM after emptying
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="confirm_truncate" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                            Confirm - Empty Table
                        </button>
                        <a href="?db=<?= urlencode($dbfile) ?>&page=browse&table=<?= urlencode($table) ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    The table is already empty.
                </div>
                <a href="?db=<?= urlencode($dbfile) ?>&page=browse&table=<?= urlencode($table) ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Return to the Table
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function include_page_print_structure() {
    global $dbfile, $pdo;
    ?>
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-print"></i>
            Print Database Structure
        </h1>
        <p class="page-subtitle">
            Generate printable structure report for <?= htmlspecialchars($dbfile) ?>
        </p>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Database Structure Report</h3>
        </div>
        <div class="card-body">
            <?php
            $structure = get_database_structure($pdo);
            if ($structure && !empty($structure['tables'])) {
                echo '<div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Click the button below to generate a printable report of the database structure.
                </div>';
                
                echo '<a href="?db=' . urlencode($dbfile) . '&page=print_output" 
                      target="_blank" class="btn btn-primary">
                    <i class="fas fa-print"></i>
                    Generate Printable Report
                </a>';
            } else {
                echo '<div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No tables found or unable to read database structure.
                </div>';
            }
            ?>
        </div>
    </div>
    <?php
}

function include_page_print_output() {
    global $dbfile, $pdo;
    
    $structure = get_database_structure($pdo);
    if ($structure) {
        echo generate_print_structure($structure, $dbfile);
    } else {
        echo "Unable to generate structure report.";
    }
}

// ============ INDEXES PAGE ============
function include_page_indexes() {
    global $dbfile, $pdo;
    
    $table = $_GET['table'] ?? '';
    if (!$table || !$pdo) {
        echo '<div class="alert alert-error">Table not specified or database not available.</div>';
        return;
    }
    
    $success_msg = '';
    $error_msg = '';
    
    // Create index
    if (isset($_POST['create_index'])) {
        $idx_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['index_name'] ?? '');
        $idx_cols = $_POST['index_cols'] ?? [];
        $idx_unique = isset($_POST['index_unique']) ? 'UNIQUE' : '';
        
        if ($idx_name && !empty($idx_cols)) {
            $cols_list = implode(', ', array_map(function($c) {
                return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $c) . '`';
            }, $idx_cols));
            try {
                $pdo->exec("CREATE $idx_unique INDEX `$idx_name` ON `$table` ($cols_list)");
                $success_msg = "Index created successfully";
            } catch (Exception $e) {
                $error_msg = "Error creating index: " . $e->getMessage();
            }
        }
    }
    
    // Drop index
    if (isset($_GET['drop_index'])) {
        $idx_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['drop_index']);
        try {
            $pdo->exec("DROP INDEX IF EXISTS `$idx_name`");
            $success_msg = "Index deleted successfully";
        } catch (Exception $e) {
            $error_msg = "Error deleting index: " . $e->getMessage();
        }
    }
    
    // Get existing indexes
    $indexes = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='$table' AND sql IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $columns = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-list-ol"></i> Indexes: <?= htmlspecialchars($table) ?></h1>
        <p class="page-subtitle">Manage table indexes</p>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>
    
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus"></i> Create New Index</h3></div>
        <div class="card-body">
            <form method="post">
                <div class="form-group mb-2">
                    <label class="form-label">Index Name</label>
                    <input type="text" name="index_name" class="form-input" required placeholder="idx_name">
                </div>
                <div class="form-group mb-2">
                    <label class="form-label">Columns (hold Ctrl to select multiple)</label>
                    <select name="index_cols[]" class="form-input" multiple size="5" required>
                        <?php foreach ($columns as $col): ?>
                            <option value="<?= htmlspecialchars($col['name']) ?>"><?= htmlspecialchars($col['name']) ?> (<?= htmlspecialchars($col['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="index_unique" id="idx_unique" value="1">
                    <label for="idx_unique">UNIQUE Index</label>
                </div>
                <button type="submit" name="create_index" class="btn btn-success"><i class="fas fa-plus"></i> Create Index</button>
                <a href="?db=<?= urlencode($dbfile) ?>&page=structure&table=<?= urlencode($table) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Structure</a>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3 class="card-title">Existing Indexes (<?= count($indexes) ?>)</h3></div>
        <div class="card-body">
            <?php if (empty($indexes)): ?>
                <p class="text-muted text-center">No indexes defined for this table.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Name</th><th>SQL Definition</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($indexes as $idx): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($idx['name']) ?></strong></td>
                                <td><code style="font-size: 0.85em;"><?= htmlspecialchars($idx['sql']) ?></code></td>
                                <td>
                                    <a href="?db=<?= urlencode($dbfile) ?>&page=indexes&table=<?= urlencode($table) ?>&drop_index=<?= urlencode($idx['name']) ?>" 
                                       class="btn btn-danger btn-sm" onclick="return confirm('Delete this index?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ============ VIEWS PAGE ============
function include_page_views() {
    global $dbfile, $pdo;
    
    $success_msg = '';
    $error_msg = '';
    
    // Create view
    if (isset($_POST['create_view'])) {
        $view_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['view_name'] ?? '');
        $view_sql = trim($_POST['view_sql'] ?? '');
        
        if ($view_name && $view_sql) {
            try {
                $pdo->exec("CREATE VIEW `$view_name` AS $view_sql");
                $success_msg = "View created successfully";
            } catch (Exception $e) {
                $error_msg = "Error creating view: " . $e->getMessage();
            }
        }
    }
    
    // Drop view
    if (isset($_GET['drop_view'])) {
        $view_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['drop_view']);
        try {
            $pdo->exec("DROP VIEW IF EXISTS `$view_name`");
            $success_msg = "View deleted successfully";
        } catch (Exception $e) {
            $error_msg = "Error deleting view: " . $e->getMessage();
        }
    }
    
    $views = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='view' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-eye"></i> Views</h1>
        <p class="page-subtitle">Manage database views for <?= htmlspecialchars($dbfile) ?></p>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>
    
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus"></i> Create New View</h3></div>
        <div class="card-body">
            <form method="post">
                <div class="form-group mb-2">
                    <label class="form-label">View Name</label>
                    <input type="text" name="view_name" class="form-input" required placeholder="v_viewname">
                </div>
                <div class="form-group mb-2">
                    <label class="form-label">SELECT Query</label>
                    <textarea name="view_sql" class="form-input" rows="5" required placeholder="SELECT col1, col2 FROM table WHERE ..."
                              style="font-family: 'Courier New', monospace;"></textarea>
                </div>
                <button type="submit" name="create_view" class="btn btn-success"><i class="fas fa-plus"></i> Create View</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3 class="card-title">Existing Views (<?= count($views) ?>)</h3></div>
        <div class="card-body">
            <?php if (empty($views)): ?>
                <p class="text-muted text-center">No views defined in this database.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Name</th><th>SQL Definition</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($views as $v): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($v['name']) ?></strong></td>
                                <td><code style="font-size: 0.8em; white-space: pre-wrap;"><?= htmlspecialchars($v['sql']) ?></code></td>
                                <td>
                                    <a href="?db=<?= urlencode($dbfile) ?>&page=browse&table=<?= urlencode($v['name']) ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Browse
                                    </a>
                                    <a href="?db=<?= urlencode($dbfile) ?>&page=views&drop_view=<?= urlencode($v['name']) ?>" 
                                       class="btn btn-danger btn-sm" onclick="return confirm('Delete this view?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ============ TRIGGERS PAGE ============
function include_page_triggers() {
    global $dbfile, $pdo;
    
    $success_msg = '';
    $error_msg = '';
    
    // Create trigger
    if (isset($_POST['create_trigger'])) {
        $trigger_sql = trim($_POST['trigger_sql'] ?? '');
        
        if ($trigger_sql) {
            try {
                $pdo->exec($trigger_sql);
                $success_msg = "Trigger created successfully";
            } catch (Exception $e) {
                $error_msg = "Error creating trigger: " . $e->getMessage();
            }
        }
    }
    
    // Drop trigger
    if (isset($_GET['drop_trigger'])) {
        $trigger_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['drop_trigger']);
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `$trigger_name`");
            $success_msg = "Trigger deleted successfully";
        } catch (Exception $e) {
            $error_msg = "Error deleting trigger: " . $e->getMessage();
        }
    }
    
    $triggers = $pdo->query("SELECT name, sql, tbl_name FROM sqlite_master WHERE type='trigger' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-bolt"></i> Triggers</h1>
        <p class="page-subtitle">Manage database triggers for <?= htmlspecialchars($dbfile) ?></p>
    </div>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>
    
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus"></i> Create New Trigger</h3></div>
        <div class="card-body">
            <form method="post">
                <div class="form-group mb-2">
                    <label class="form-label">Complete SQL Statement</label>
                    <textarea name="trigger_sql" class="form-input" rows="8" required 
                              placeholder="CREATE TRIGGER trigger_name AFTER INSERT ON table_name
BEGIN
    -- Your SQL statements here
    UPDATE other_table SET column = NEW.value WHERE id = NEW.id;
END;"
                              style="font-family: 'Courier New', monospace;"></textarea>
                </div>
                <div class="alert alert-info mb-3">
                    <strong>Examples:</strong><br>
                    <code>CREATE TRIGGER log_insert AFTER INSERT ON users BEGIN INSERT INTO audit_log (action, user_id) VALUES ('INSERT', NEW.id); END;</code><br>
                    <code>CREATE TRIGGER update_timestamp BEFORE UPDATE ON orders BEGIN UPDATE orders SET updated_at = datetime('now') WHERE id = OLD.id; END;</code>
                </div>
                <button type="submit" name="create_trigger" class="btn btn-success"><i class="fas fa-plus"></i> Create Trigger</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3 class="card-title">Existing Triggers (<?= count($triggers) ?>)</h3></div>
        <div class="card-body">
            <?php if (empty($triggers)): ?>
                <p class="text-muted text-center">No triggers defined in this database.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Name</th><th>Table</th><th>SQL Definition</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($triggers as $tr): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($tr['name']) ?></strong></td>
                                <td><?= htmlspecialchars($tr['tbl_name']) ?></td>
                                <td><code style="font-size: 0.75em; white-space: pre-wrap;"><?= htmlspecialchars($tr['sql']) ?></code></td>
                                <td>
                                    <a href="?db=<?= urlencode($dbfile) ?>&page=triggers&drop_trigger=<?= urlencode($tr['name']) ?>" 
                                       class="btn btn-danger btn-sm" onclick="return confirm('Delete this trigger?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

?>
<script>
document.querySelectorAll('.alert').forEach(function(alert) {
  if(alert.textContent.trim() === '') { alert.style.display = 'none'; }
});
</script>
