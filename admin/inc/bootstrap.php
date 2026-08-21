<?php
/* ==========================================================================
   Shared plumbing: configuration, database, sessions, sign-in.
   Included by every PHP file on the site. Nothing here prints anything.
   ========================================================================== */

declare(strict_types=1);

const CLF_MAX_CV_BYTES   = 5 * 1024 * 1024;              /* 5 MB */
const CLF_ALLOWED_EXT    = ['pdf', 'doc', 'docx'];
const CLF_LOGIN_MAX_TRY  = 8;                            /* per window */
const CLF_LOGIN_WINDOW   = 900;                          /* 15 minutes */

/* --- Configuration ------------------------------------------------------ */

function clf_config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) {
            throw new RuntimeException('config.php is missing. Copy config.sample.php to config.php and fill it in.');
        }
        /* config.php refuses to run unless this is set, so that if the server
           ever stops processing PHP the file cannot be served as plain text
           with the database password in it. */
        if (!defined('CLF_BOOTSTRAP')) {
            define('CLF_BOOTSTRAP', true);
        }
        $config = require $file;
    }
    return $config;
}

/* --- Database ----------------------------------------------------------- */

function clf_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = clf_config();
        /* db_dsn is only for testing, or if the site is ever moved to a
           different kind of database. Normally it is absent and the MySQL
           details above are used. */
        $dsn = $c['db_dsn']
            ?? sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['db_host'], $c['db_name']);
        $pdo = new PDO(
            $dsn,
            $c['db_user'] ?? null,
            $c['db_pass'] ?? null,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                /* Real prepared statements, so a value can never be read as SQL. */
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

/* --- Sessions ----------------------------------------------------------- */

function clf_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin/',
        'httponly' => true,                  /* JavaScript cannot read it */
        'secure'   => clf_is_https(),        /* HTTPS only, once there is HTTPS */
        'samesite' => 'Strict',              /* not sent from other sites */
    ]);
    session_name('clfadmin');
    session_start();
}

function clf_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

/* --- Signing in --------------------------------------------------------- */

function clf_is_signed_in(): bool
{
    clf_session_start();
    return !empty($_SESSION['user']);
}

/* Every admin page begins with this. */
function clf_require_login(): void
{
    if (!clf_is_signed_in()) {
        header('Location: /admin/?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin/'));
        exit;
    }
}

/* Returns true and starts the session, or false. Deliberately says nothing
   about WHICH half was wrong, and takes the same time either way. */
function clf_attempt_login(string $username, string $password): bool
{
    $users = clf_config()['users'] ?? [];
    $hash  = $users[$username] ?? null;

    if ($hash === null) {
        /* Hash anyway, so a wrong username is not measurably faster than a
           wrong password (which would let someone enumerate usernames). */
        password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidiu');
        return false;
    }

    if (!password_verify($password, $hash)) {
        return false;
    }

    clf_session_start();
    session_regenerate_id(true);            /* a fresh id, so a stolen one is useless */
    $_SESSION['user']       = $username;
    $_SESSION['started_at'] = time();
    return true;
}

function clf_logout(): void
{
    clf_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* --- Slowing down guessing ---------------------------------------------- */

function clf_login_attempts_exceeded(): bool
{
    clf_session_start();
    $tries = $_SESSION['login_tries'] ?? [];
    $tries = array_filter($tries, fn($t) => $t > time() - CLF_LOGIN_WINDOW);
    $_SESSION['login_tries'] = array_values($tries);
    return count($tries) >= CLF_LOGIN_MAX_TRY;
}

function clf_note_failed_login(): void
{
    clf_session_start();
    $_SESSION['login_tries'][] = time();
}

/* --- Cross-site request forgery ----------------------------------------- */

function clf_csrf_token(): string
{
    clf_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function clf_check_csrf(?string $sent): bool
{
    clf_session_start();
    return !empty($_SESSION['csrf']) && is_string($sent)
        && hash_equals($_SESSION['csrf'], $sent);
}

/* --- Small helpers ------------------------------------------------------ */

/* Everything printed into a page goes through this. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clf_storage_path(): string
{
    return rtrim(clf_config()['storage_path'], '/');
}

/* --- When the database will not co-operate ------------------------------
   Turns a PDO exception into something a person can act on. The admin pages
   show this instead of a bare "500", which says nothing.
   ------------------------------------------------------------------------ */

function clf_explain_db_error(Throwable $e): string
{
    $m = $e->getMessage();

    if (str_contains($m, '42S02')
        || stripos($m, "doesn't exist") !== false
        || stripos($m, 'no such table') !== false) {
        return 'The <code>applications</code> table has not been created yet. '
             . 'Open <strong>hPanel &rarr; Databases &rarr; phpMyAdmin</strong>, choose your database, '
             . 'click the <strong>SQL</strong> tab, and run the contents of <code>admin/schema.sql</code>. '
             . 'Make sure you are on the right database before you run it.';
    }
    if (str_contains($m, '1045') || stripos($m, 'Access denied') !== false) {
        return 'MySQL refused the username or password in <code>config.php</code>. '
             . 'On Hostinger the username carries the account prefix &mdash; it will look like '
             . '<code>u336873412_Admin</code>, not <code>Admin</code>. '
             . 'Check it against <strong>hPanel &rarr; Databases &rarr; Management</strong>.';
    }
    if (str_contains($m, '1049') || stripos($m, 'Unknown database') !== false) {
        return 'MySQL has no database by that name. Check <code>db_name</code> in <code>config.php</code> '
             . 'against the Databases list in hPanel. The name carries the account prefix and is '
             . '<strong>case-sensitive</strong>.';
    }
    if (str_contains($m, '2002') || stripos($m, 'Connection refused') !== false
        || stripos($m, 'No such file or directory') !== false) {
        return 'Could not reach MySQL at that address. On Hostinger <code>db_host</code> should be '
             . '<code>localhost</code>.';
    }
    return 'The database refused the request. The exact wording is in the error log &mdash; '
         . 'hPanel &rarr; <strong>Advanced &rarr; PHP Configuration</strong>.';
}

/* Shows a readable page and stops. $detail is markup we wrote ourselves. */
function clf_admin_error(string $heading, string $detail, ?Throwable $e = null): never
{
    if ($e !== null) {
        error_log('civillawfirm admin: ' . $e->getMessage());
    }
    http_response_code(500);
    require_once __DIR__ . '/page.php';
    clf_admin_head($heading, false);
    echo '<div style="max-width:38rem;margin:2rem auto">';
    echo '<h1>' . e($heading) . '</h1>';
    echo '<p>' . $detail . '</p>';
    echo '<p class="cta-row">'
       . '<a class="btn btn-primary" href="/admin/check.php">Run the setup check</a>'
       . '<a class="btn btn-ghost" href="/admin/">Try again</a></p>';
    echo '</div>';
    clf_admin_foot();
    exit;
}
