<?php
/**
 * sgiT Education - Zeichnungen Migration Tool
 * Verschiebt Bilder von alten zufälligen user_ids zu konsistenten IDs
 * 
 * @version 1.0
 * @date 11.12.2025
 */

session_start();
require_once __DIR__ . '/../includes/version.php';

// Nur Admin-Zugriff
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin_v4.php');
    exit;
}

$uploadDir = __DIR__ . '/../uploads/drawings';
$dbPath = __DIR__ . '/../AI/data/questions.db';

$message = '';
$userFolders = [];

// Alle User-Ordner scannen
if (is_dir($uploadDir)) {
    $folders = glob($uploadDir . '/user_*', GLOB_ONLYDIR);
    foreach ($folders as $folder) {
        $folderId = basename($folder);
        $files = glob($folder . '/*.png');
        $userFolders[] = [
            'id' => $folderId,
            'path' => $folder,
            'count' => count($files),
            'size' => count($files) > 0 ? array_sum(array_map('filesize', $files)) : 0,
            'files' => array_map('basename', $files)
        ];
    }
}

// Migration durchführen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
    $oldId = $_POST['old_id'];
    $newName = trim($_POST['new_name']);
    $newAge = intval($_POST['new_age']);
    
    if ($oldId && $newName && $newAge > 0) {
        // Neue konsistente ID berechnen
        $newId = 'user_' . substr(md5(strtolower($newName) . '_' . $newAge), 0, 12);
        $oldPath = $uploadDir . '/' . $oldId;
        $newPath = $uploadDir . '/' . $newId;
        
        if ($oldId !== $newId && is_dir($oldPath)) {
            // Zielordner erstellen falls nicht existiert
            if (!is_dir($newPath)) {
                mkdir($newPath, 0755, true);
            }
            
            // Dateien verschieben
            $moved = 0;
            foreach (glob($oldPath . '/*') as $file) {
                $newFile = $newPath . '/' . basename($file);
                if (rename($file, $newFile)) {
                    $moved++;
                }
            }
            
            // DB-Einträge aktualisieren
            try {
                $db = new PDO('sqlite:' . $dbPath);
                $stmt = $db->prepare("UPDATE drawings SET user_id = ? WHERE user_id = ?");
                $stmt->execute([$newId, $oldId]);
                $dbUpdated = $stmt->rowCount();
            } catch (Exception $e) {
                $dbUpdated = 0;
            }
            
            // Alten leeren Ordner löschen
            @rmdir($oldPath);
            
            $message = "✅ Migration erfolgreich: $moved Dateien, $dbUpdated DB-Einträge. Neue ID: $newId";
        } elseif ($oldId === $newId) {
            $message = "ℹ️ IDs sind bereits identisch - keine Migration nötig.";
        } else {
            $message = "⚠️ Quellordner nicht gefunden.";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode($message));
    exit;
}

$message = $_GET['msg'] ?? $message;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🖼️ Zeichnungen Migration - sgiT Admin</title>
    <style>
        :root { --sgit-dark: #1A3503; --sgit-green: #43D240; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a1a; color: white; padding: 20px; }
        .header { background: var(--sgit-dark); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 1.5em; display: flex; justify-content: space-between; }
        .header a { color: #888; text-decoration: none; font-size: 0.6em; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: rgba(67, 210, 64, 0.2); border: 1px solid var(--sgit-green); }
        .message.info { background: rgba(52, 152, 219, 0.2); border: 1px solid #3498db; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .card { background: #2a2a2a; border-radius: 10px; padding: 20px; border: 2px solid #333; }
        .card h3 { color: var(--sgit-green); margin-bottom: 10px; font-size: 0.95em; word-break: break-all; }
        .card .stats { color: #888; font-size: 0.9em; margin-bottom: 15px; }
        .card .files { max-height: 80px; overflow-y: auto; font-size: 0.75em; color: #666; background: #1a1a1a; padding: 8px; border-radius: 5px; margin-bottom: 15px; }
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .form-group { flex: 1; }
        .form-group label { display: block; color: #888; font-size: 0.8em; margin-bottom: 3px; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #444; border-radius: 5px; background: #1a1a1a; color: white; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; }
        .btn-primary { background: var(--sgit-green); color: var(--sgit-dark); }
        .empty { text-align: center; padding: 40px; color: #666; }
        .info-box { background: #333; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info-box h3 { color: var(--sgit-green); margin-bottom: 10px; font-size: 1em; }
        .info-box p { color: #aaa; line-height: 1.5; font-size: 0.9em; }
        code { background: #1a1a1a; padding: 2px 5px; border-radius: 3px; font-family: monospace; font-size: 0.85em; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖼️ Zeichnungen Migration <a href="/admin_v4.php">← Admin</a></h1>
    </div>
    
    <?php if ($message): ?>
    <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'info' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <div class="info-box">
        <h3>ℹ️ Problem & Lösung</h3>
        <p>
            <strong>Bisher:</strong> Jedes Gerät bekam eine zufällige <code>user_id</code> → Bilder "verschwanden".<br>
            <strong>Neu:</strong> Die ID wird aus <code>Name + Alter</code> berechnet → gleiche Bilder überall!<br>
            Hier kannst du alte Ordner den richtigen Kindern zuordnen.
        </p>
    </div>
    
    <?php if (empty($userFolders)): ?>
    <div class="empty">
        <h2>📁 Keine Ordner gefunden</h2>
        <p>Es gibt keine alten Zeichnungs-Ordner zu migrieren.</p>
    </div>
    <?php else: ?>
    <div class="grid">
        <?php foreach ($userFolders as $folder): ?>
        <div class="card">
            <h3>📁 <?= htmlspecialchars($folder['id']) ?></h3>
            <div class="stats">
                📊 <?= $folder['count'] ?> Bilder | 💾 <?= round($folder['size'] / 1024, 1) ?> KB
            </div>
            
            <?php if (!empty($folder['files'])): ?>
            <div class="files">
                <?php foreach (array_slice($folder['files'], 0, 3) as $file): ?>
                📄 <?= htmlspecialchars($file) ?><br>
                <?php endforeach; ?>
                <?php if (count($folder['files']) > 3): ?>
                <em>+<?= count($folder['files']) - 3 ?> weitere</em>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="old_id" value="<?= htmlspecialchars($folder['id']) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>👤 Name:</label>
                        <input type="text" name="new_name" required placeholder="Emma">
                    </div>
                    <div class="form-group" style="max-width:80px;">
                        <label>🎂 Alter:</label>
                        <input type="number" name="new_age" min="5" max="21" required placeholder="8">
                    </div>
                </div>
                <button type="submit" name="migrate" class="btn btn-primary">🔄 Migrieren</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>
