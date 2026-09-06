<?php
/* ==========================================================================
   /admin/download.php?id=N  —  hands over one curriculum vitae

   The files live outside public_html, so this script is the only way to
   reach one, and it will not run for anyone who has not signed in.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
clf_require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = clf_db()->prepare('SELECT cv_stored, cv_original FROM applications WHERE id = ?');
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a) {
    http_response_code(404);
    exit('No such application.');
}

/* basename() twice over: the name in the database is one we generated, but
   treating it as untrusted anyway means no stored value can ever point at a
   file outside the storage folder. */
$path = clf_storage_path() . '/' . basename((string) $a['cv_stored']);
if (!is_file($path)) {
    http_response_code(404);
    exit('The file is missing from the server.');
}

/* Offer it as a download under the name the applicant gave it, with any
   directory parts and quotes taken out. */
$name = preg_replace('/[^\w.\- ]/u', '_', basename((string) $a['cv_original'])) ?: 'cv';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
readfile($path);
