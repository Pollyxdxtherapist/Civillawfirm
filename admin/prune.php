<?php
/* ==========================================================================
   /admin/prune.php  —  deletes applications older than the retention period
   set in config.php ('retain_days'), and their curriculum vitae files.

   Open it while signed in to run it. Hostinger can also run it on a
   schedule: hPanel -> Advanced -> Cron Jobs, monthly:
       /usr/bin/php /home/uXXXXXXXX/public_html/admin/prune.php --cli
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    require __DIR__ . '/inc/page.php';
    clf_require_login();
}

$days = (int) (clf_config()['retain_days'] ?? 0);
$lines = [];

if ($days <= 0) {
    $lines[] = 'Retention is switched off (retain_days is 0). Nothing was deleted.';
} else {
    $stmt = clf_db()->prepare(
        'SELECT id, cv_stored FROM applications WHERE submitted_at < (UTC_TIMESTAMP() - INTERVAL ? DAY)'
    );
    $stmt->execute([$days]);
    $old = $stmt->fetchAll();

    foreach ($old as $row) {
        @unlink(clf_storage_path() . '/' . basename((string) $row['cv_stored']));
        clf_db()->prepare('DELETE FROM applications WHERE id = ?')->execute([(int) $row['id']]);
    }
    $lines[] = sprintf('Removed %d application(s) older than %d days.', count($old), $days);
}

if ($cli) {
    echo implode("\n", $lines), "\n";
    exit;
}

clf_admin_head('Prune old applications');
echo '<h1>Prune old applications</h1>';
foreach ($lines as $l) { echo '<p>' . e($l) . '</p>'; }
echo '<p class="cta-row"><a class="btn btn-outline" href="/admin/">Back to all applications</a></p>';
clf_admin_foot();
