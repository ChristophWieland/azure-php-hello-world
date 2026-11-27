<?php
declare(strict_types=1);

session_start();

/*
 * Demo-Loginverwaltung ohne Datenbank.
 * Azure App Service (Linux): Daten werden standardmäßig unter /home/data abgelegt.
 * Lokal: Daten werden im Projektordner unter /data abgelegt.
 * Optional kann APP_STORAGE_PATH als App Setting gesetzt werden.
 */

$storageRoot = getenv('APP_STORAGE_PATH');
if (!$storageRoot) {
    $storageRoot = is_dir('/home') ? '/home/data' : __DIR__ . '/data';
}
if (!is_dir($storageRoot)) {
    mkdir($storageRoot, 0775, true);
}
$dataFile = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logins.json';

function loadLogins(string $file): array {
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveLogins(string $file, array $logins): bool {
    $json = json_encode(array_values($logins), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    $tmp = $file . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, $file);
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$logins = loadLogins($dataFile);
$error = '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        $error = 'Ungültige Anfrage. Bitte Seite neu laden.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save') {
            $id = trim((string)($_POST['id'] ?? ''));
            $system = trim((string)($_POST['system'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $url = trim((string)($_POST['url'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));

            if ($system === '' || $username === '') {
                $error = 'Bitte mindestens System und Benutzername ausfüllen.';
            } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                $error = 'Bitte eine gültige URL eingeben (z. B. https://example.com).';
            } else {
                if ($id === '') {
                    $id = bin2hex(random_bytes(8));
                    $logins[] = [
                        'id' => $id,
                        'system' => $system,
                        'username' => $username,
                        'url' => $url,
                        'description' => $description,
                        'updated_at' => date(DATE_ATOM),
                    ];
                    $notice = 'Eintrag wurde angelegt.';
                } else {
                    $found = false;
                    foreach ($logins as &$item) {
                        if (($item['id'] ?? '') === $id) {
                            $item['system'] = $system;
                            $item['username'] = $username;
                            $item['url'] = $url;
                            $item['description'] = $description;
                            $item['updated_at'] = date(DATE_ATOM);
                            $found = true;
                            break;
                        }
                    }
                    unset($item);
                    $notice = $found ? 'Eintrag wurde gespeichert.' : 'Eintrag nicht gefunden.';
                }
                if ($error === '' && !saveLogins($dataFile, $logins)) {
                    $error = 'Speichern fehlgeschlagen. Bitte Schreibrechte des Datenordners prüfen.';
                    $notice = '';
                }
            }
        } elseif ($action === 'delete') {
            $id = (string)($_POST['id'] ?? '');
            $before = count($logins);
            $logins = array_values(array_filter($logins, fn($item) => ($item['id'] ?? '') !== $id));
            if (count($logins) < $before) {
                if (saveLogins($dataFile, $logins)) {
                    $notice = 'Eintrag wurde gelöscht.';
                } else {
                    $error = 'Löschen fehlgeschlagen. Bitte Schreibrechte prüfen.';
                }
            }
        }
    }
}

$editId = (string)($_GET['edit'] ?? '');
$edit = null;
foreach ($logins as $item) {
    if (($item['id'] ?? '') === $editId) {
        $edit = $item;
        break;
    }
}
$q = trim((string)($_GET['q'] ?? ''));
$filtered = array_filter($logins, function ($item) use ($q) {
    if ($q === '') return true;
    $haystack = implode(' ', [
        $item['system'] ?? '',
        $item['username'] ?? '',
        $item['url'] ?? '',
        $item['description'] ?? '',
    ]);
    return mb_stripos($haystack, $q) !== false;
});
usort($filtered, fn($a, $b) => strcasecmp($a['system'] ?? '', $b['system'] ?? ''));

if ($notice !== '') $_SESSION['notice'] = $notice;
if ($error !== '') $_SESSION['error'] = $error;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$notice = (string)($_SESSION['notice'] ?? '');
$error = (string)($_SESSION['error'] ?? '');
unset($_SESSION['notice'], $_SESSION['error']);

$edit = $edit ?? ['id' => '', 'system' => '', 'username' => '', 'url' => '', 'description' => ''];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VWS – Loginverwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="index.php">VWS <span class="text-info">Loginverwaltung</span></a>
        <span class="navbar-text small">Prototyp · PHP</span>
    </div>
</nav>

<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Loginverwaltung</h1>
            <p class="text-secondary mb-0">Übersicht und Verwaltung von Systemzugängen</p>
        </div>
        <span class="badge text-bg-primary fs-6"><?= count($logins) ?> Einträge</span>
    </div>

    <div class="alert alert-warning" role="alert">
        <strong>Prototyp:</strong> Diese Anwendung hat keinen Zugriffsschutz und ist öffentlich erreichbar.
        Keine echten Passwörter oder vertraulichen Zugangsdaten eintragen.
    </div>

    <?php if ($notice): ?><div class="alert alert-success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <section class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold"><?= $edit['id'] ? 'Eintrag bearbeiten' : 'Neuen Eintrag anlegen' ?></div>
                <div class="card-body">
                    <form method="post" action="index.php" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
                        <div class="mb-3">
                            <label for="system" class="form-label">System / Anwendung *</label>
                            <input class="form-control" id="system" name="system" required maxlength="120" value="<?= e($edit['system']) ?>" placeholder="z. B. FDISK VWS">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Benutzername *</label>
                            <input class="form-control" id="username" name="username" required maxlength="120" value="<?= e($edit['username']) ?>" placeholder="Benutzername">
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label">URL</label>
                            <input class="form-control" id="url" name="url" type="url" maxlength="500" value="<?= e($edit['url']) ?>" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung / Bemerkung</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Optionale Bemerkung"><?= e($edit['description']) ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><?= $edit['id'] ? 'Änderungen speichern' : 'Eintrag anlegen' ?></button>
                            <?php if ($edit['id']): ?><a class="btn btn-outline-secondary" href="index.php">Abbrechen</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <form class="row g-2" method="get" action="index.php">
                        <div class="col">
                            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="System, Benutzername oder URL suchen ...">
                        </div>
                        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Suchen</button></div>
                        <?php if ($q !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="index.php">Zurücksetzen</a></div><?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr><th>System</th><th>Benutzername</th><th>URL</th><th class="text-end">Aktionen</th></tr>
                        </thead>
                        <tbody>
                        <?php if (!$filtered): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">Keine Einträge gefunden.</td></tr>
                        <?php else: foreach ($filtered as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($item['system'] ?? '') ?></div>
                                    <?php if (!empty($item['description'])): ?><div class="small text-secondary"><?= e($item['description']) ?></div><?php endif; ?>
                                </td>
                                <td><?= e($item['username'] ?? '') ?></td>
                                <td><?php if (!empty($item['url'])): ?><a href="<?= e($item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e(parse_url($item['url'], PHP_URL_HOST) ?: $item['url']) ?></a><?php else: ?><span class="text-secondary">–</span><?php endif; ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= e($item['id']) ?>">Bearbeiten</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Diesen Eintrag wirklich löschen?');">
                                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="small text-secondary mt-2 mb-0">Gespeichert werden die Einträge in einer JSON-Datei auf dem App-Service-Dateisystem.</p>
        </section>
    </div>
</main>
<footer class="container py-4 text-secondary small">VWS Loginverwaltung · Lernprojekt auf Azure</footer>
</body>
</html>
