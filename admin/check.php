<?php
/* ==========================================================================
   /admin/check.php  —  says which part of the setup is not finished yet.

   Safe to leave in place: it needs a sign-in, and it never prints a password
   or any applicant's details.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/page.php';
clf_require_login();

$checks = [];
$add = static function (string $label, bool $ok, string $detail) use (&$checks): void {
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
};

/* 1. configuration ------------------------------------------------------- */
try {
    $c = clf_config();
    $add('config.php is readable', true, 'Found and understood.');
} catch (Throwable $e) {
    $add('config.php is readable', false, e($e->getMessage()));
    $c = null;
}

/* 2. the database connection --------------------------------------------- */
$db = null;
if ($c !== null) {
    try {
        $db = clf_db();
        $add('Connects to MySQL', true,
             'Connected as <code>' . e((string) ($c['db_user'] ?? '?')) . '</code> to <code>'
             . e((string) ($c['db_name'] ?? $c['db_dsn'] ?? '?')) . '</code>.');
    } catch (Throwable $e) {
        $add('Connects to MySQL', false, clf_explain_db_error($e));
    }
}

/* 3. the table ------------------------------------------------------------ */
if ($db !== null) {
    try {
        $n = (int) $db->query('SELECT COUNT(*) FROM applications')->fetchColumn();
        $add('The applications table exists', true,
             $n === 0 ? 'Present, and empty so far.' : 'Present, holding ' . $n . ' application(s).');
    } catch (Throwable $e) {
        $add('The applications table exists', false, clf_explain_db_error($e));
    }
}

/* 4. where the CV files go ------------------------------------------------ */
if ($c !== null) {
    $dir = clf_storage_path();
    $parent = dirname($dir);
    if (is_dir($dir) && is_writable($dir)) {
        $add('The CV folder is ready', true, 'Writable: <code>' . e($dir) . '</code>');
    } elseif (is_dir($parent) && is_writable($parent)) {
        $add('The CV folder is ready', true,
             '<code>' . e($dir) . '</code> does not exist yet, but its parent is writable, '
             . 'so it will be made on the first application.');
    } else {
        $add('The CV folder is ready', false,
             'Cannot write to <code>' . e($dir) . '</code>. Check <code>storage_path</code> in config.php, '
             . 'and that the folder exists in File Manager. It must sit <strong>outside</strong> '
             . 'public_html.');
    }

    /* It must not be reachable over the web. */
    $docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $real    = realpath($dir) ?: realpath($parent);
    if ($docroot && $real && str_starts_with($real, $docroot)) {
        $add('CV files are out of public reach', false,
             'The folder is <strong>inside</strong> the website directory, so a CV could be '
             . 'downloaded by anyone who guesses its address. Move it beside public_html, not in it.');
    } else {
        $add('CV files are out of public reach', true, 'The folder is outside the website directory.');
    }
}

/* 5. upload limits -------------------------------------------------------- */
$to_bytes = static function (string $v): int {
    $v = trim($v); $n = (int) $v; $u = strtolower(substr($v, -1));
    return match ($u) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
};
$up = $to_bytes((string) ini_get('upload_max_filesize'));
$post = $to_bytes((string) ini_get('post_max_size'));
$need = CLF_MAX_CV_BYTES;
$add('PHP accepts a 5 MB upload', $up >= $need && $post >= $need,
     'upload_max_filesize is <code>' . e((string) ini_get('upload_max_filesize'))
     . '</code>, post_max_size is <code>' . e((string) ini_get('post_max_size')) . '</code>.'
     . (($up >= $need && $post >= $need) ? '' :
        ' Raise both to at least <strong>8M</strong> in hPanel &rarr; Advanced &rarr; PHP Configuration.'));

/* 6. the PHP version ------------------------------------------------------ */
$add('PHP is new enough', PHP_VERSION_ID >= 80000,
     'Running PHP <code>' . e(PHP_VERSION) . '</code>. This code needs <strong>8.0</strong> or newer. '
     . (PHP_VERSION_ID >= 80000 ? '' :
        'Raise it in hPanel &rarr; Advanced &rarr; PHP Configuration &rarr; PHP version.'));

/* 7. leftovers ------------------------------------------------------------ */
$add('make-hash.php has been removed', !is_file(__DIR__ . '/make-hash.php'),
     is_file(__DIR__ . '/make-hash.php')
        ? 'Still present. Delete <code>admin/make-hash.php</code> now that the password is set.'
        : 'Gone, as it should be.');

$failed = count(array_filter($checks, fn($c) => !$c['ok']));

clf_admin_head('Setup check');
?>
<h1>Setup check</h1>
<p class="adm-meta">
  <?= $failed === 0 ? 'Everything below is in order.' : $failed . ' thing(s) still need attention.' ?>
</p>

<table class="adm-table">
  <tbody>
  <?php foreach ($checks as $c): ?>
    <tr>
      <td style="width:2rem;font-size:1.1rem;color:<?= $c['ok'] ? 'var(--accent)' : '#9a2f2f' ?>">
        <?= $c['ok'] ? '&check;' : '&times;' ?>
      </td>
      <td><strong><?= e($c['label']) ?></strong><br>
          <span class="adm-meta"><?= $c['detail'] ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<p class="cta-row"><a class="btn btn-outline" href="/admin/">Back to applications</a></p>
<?php
clf_admin_foot();
