<?php
/* ==========================================================================
   /admin/application.php?id=N  —  one application in full
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/page.php';
clf_require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = clf_db()->prepare('SELECT * FROM applications WHERE id = ?');
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a) {
    http_response_code(404);
    clf_admin_head('Not found');
    echo '<h1>Not found</h1><p>That application no longer exists.</p>'
       . '<p class="cta-row"><a class="btn btn-outline" href="/admin/">Back to all applications</a></p>';
    clf_admin_foot();
    exit;
}

/* Opening an application marks it read. */
if (!$a['is_read']) {
    clf_db()->prepare('UPDATE applications SET is_read = 1 WHERE id = ?')->execute([$id]);
    $a['is_read'] = 1;
}

$size_kb = max(1, (int) round(((int) $a['cv_bytes']) / 1024));

clf_admin_head('Application — ' . $a['name']);
?>
<p class="adm-meta"><a href="/admin/">&larr; All applications</a></p>

<h1><?= e($a['name']) ?></h1>
<p class="adm-meta">
  Received <?= e(date('j F Y, H:i', strtotime((string) $a['submitted_at']))) ?> UTC
  &middot; applied from the <?= e(['en' => 'English', 'hi' => 'Hindi', 'bn' => 'Bengali'][$a['lang']] ?? 'English') ?> page
</p>

<div class="authority">
  <dl>
    <dt>Applying for</dt><dd><?= e($a['position']) ?></dd>
    <dt>Email</dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd>
    <dt>Telephone</dt><dd><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $a['phone'])) ?>"><?= e($a['phone']) ?></a></dd>
    <dt>Curriculum vitae</dt>
    <dd>
      <a href="/admin/download.php?id=<?= (int) $a['id'] ?>"><?= e($a['cv_original']) ?></a>
      <span class="adm-meta">(<?= $size_kb ?> KB)</span>
    </dd>
  </dl>
</div>

<h2>About the applicant</h2>
<p class="adm-msg"><?= e($a['message']) ?></p>

<p class="cta-row">
  <a class="btn btn-primary" href="/admin/download.php?id=<?= (int) $a['id'] ?>">Download curriculum vitae</a>
  <a class="btn btn-outline" href="mailto:<?= e($a['email']) ?>">Reply by email</a>
</p>

<form method="POST" action="/admin/" onsubmit="return confirm('Delete this application and its curriculum vitae? This cannot be undone.');">
  <input type="hidden" name="csrf" value="<?= e(clf_csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
  <input type="hidden" name="action" value="delete">
  <p class="form-actions"><button type="submit" class="btn btn-ghost">Delete this application</button></p>
</form>
<?php
clf_admin_foot();
