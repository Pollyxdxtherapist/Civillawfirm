<?php
/* ==========================================================================
   /admin/  —  sign in, and the list of applications
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/page.php';

/* ---- not signed in: the sign-in form ---------------------------------- */

if (!clf_is_signed_in()) {

    $error = null;

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!clf_check_csrf($_POST['csrf'] ?? null)) {
            $error = 'The form expired. Please try again.';
        } elseif (clf_login_attempts_exceeded()) {
            $error = 'Too many attempts. Please wait fifteen minutes and try again.';
        } elseif (clf_attempt_login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            /* Only follow a "next" address inside /admin/, so this cannot be
               used to bounce someone to another website. */
            $next = (string) ($_GET['next'] ?? '/admin/');
            if (!str_starts_with($next, '/admin/')) {
                $next = '/admin/';
            }
            header('Location: ' . $next);
            exit;
        } else {
            clf_note_failed_login();
            $error = 'Those details were not recognised.';
        }
    }

    clf_admin_head('Sign in', false);
    ?>
    <div class="adm-login">
      <h1>Applications</h1>
      <p class="adm-meta">Sign in to read applications sent through the Careers page.</p>

      <?php if ($error !== null): ?>
        <p class="form-status form-bad" role="alert"><?= e($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf" value="<?= e(clf_csrf_token()) ?>">
        <p class="field">
          <label for="u">Username</label>
          <input type="text" id="u" name="username" autocomplete="username" required autofocus>
        </p>
        <p class="field">
          <label for="p">Password</label>
          <input type="password" id="p" name="password" autocomplete="current-password" required>
        </p>
        <p class="form-actions">
          <button type="submit" class="btn btn-primary">Sign in</button>
        </p>
      </form>
    </div>
    <?php
    clf_admin_foot();
    exit;
}

/* ---- signed in: the list ----------------------------------------------- */

$notice = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    if (!clf_check_csrf($_POST['csrf'] ?? null)) {
        $notice = 'That action expired. Please try again.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);

        if ($_POST['action'] === 'delete' && $id > 0) {
            $row = clf_db()->prepare('SELECT cv_stored FROM applications WHERE id = ?');
            $row->execute([$id]);
            if ($found = $row->fetch()) {
                /* basename() so a stored name can never climb out of the folder. */
                @unlink(clf_storage_path() . '/' . basename((string) $found['cv_stored']));
                clf_db()->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
                $notice = 'Application deleted, along with the curriculum vitae.';
            }
        }

        if ($_POST['action'] === 'toggle_read' && $id > 0) {
            clf_db()->prepare('UPDATE applications SET is_read = 1 - is_read WHERE id = ?')->execute([$id]);
        }
    }
    /* Redirect after posting, so a refresh does not repeat the action. */
    $_SESSION['flash'] = $notice;
    header('Location: /admin/');
    exit;
}

clf_session_start();
if (!empty($_SESSION['flash'])) {
    $notice = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

try {
    $rows = clf_db()->query(
        'SELECT id, name, position, email, phone, cv_original, lang, submitted_at, is_read
           FROM applications
       ORDER BY submitted_at DESC, id DESC
          LIMIT 500'
    )->fetchAll();
} catch (Throwable $ex) {
    clf_admin_error('The database is not ready yet', clf_explain_db_error($ex), $ex);
}

$unread = 0;
foreach ($rows as $r) {
    if (!$r['is_read']) { $unread++; }
}

clf_admin_head('Applications');
?>
<h1>Applications</h1>
<p class="adm-meta">
  <?= count($rows) ?> in total<?= $unread ? ', ' . $unread . ' unread' : '' ?>.
  Times are UTC.
</p>

<?php if ($notice !== null): ?>
  <p class="form-status" role="status"><?= e($notice) ?></p>
<?php endif; ?>

<?php if (!$rows): ?>
  <p class="adm-empty">No applications yet.</p>
<?php else: ?>
  <table class="adm-table">
    <thead>
      <tr>
        <th scope="col"></th>
        <th scope="col">Received</th>
        <th scope="col">Name</th>
        <th scope="col">Applying for</th>
        <th scope="col">Contact</th>
        <th scope="col">CV</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['is_read'] ? '' : 'is-new' ?>">
        <td><?= $r['is_read'] ? '' : '<span class="adm-dot" title="Unread"></span>' ?></td>
        <td><?= e(date('j M Y, H:i', strtotime((string) $r['submitted_at']))) ?></td>
        <td><a href="/admin/application.php?id=<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a></td>
        <td><?= e($r['position']) ?></td>
        <td>
          <a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><br>
          <span class="adm-meta"><?= e($r['phone']) ?></span>
        </td>
        <td><a href="/admin/download.php?id=<?= (int) $r['id'] ?>">Download</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php
clf_admin_foot();
