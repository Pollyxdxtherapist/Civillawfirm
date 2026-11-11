<?php
/* ==========================================================================
   /admin/make-hash.php  —  turns a password into the hash config.php wants.

   Use it once when setting up, then DELETE THIS FILE.

   It is harmless in itself: it only hashes whatever is typed into it, and
   knowing the hash of a password nobody uses tells an attacker nothing. But
   there is no reason to leave it lying about once the portal is working.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/page.php';

$hash = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pw = (string) ($_POST['password'] ?? '');
    if (strlen($pw) < 12) {
        $hash = 'Please choose a password of at least 12 characters.';
    } else {
        $hash = password_hash($pw, PASSWORD_BCRYPT);
    }
}

clf_admin_head('Make a password hash', false);
?>
<div class="adm-login">
  <h1>Make a password hash</h1>
  <p class="adm-meta">Type the password you want to use. Copy the result into
     <code>admin/inc/config.php</code>, then <strong>delete this file</strong>.</p>

  <form method="POST" action="">
    <p class="field">
      <label for="p">Password (at least 12 characters)</label>
      <input type="text" id="p" name="password" autocomplete="off" required autofocus>
      <span class="field-hint">Shown as you type, so you can check it before copying.</span>
    </p>
    <p class="form-actions"><button type="submit" class="btn btn-primary">Make the hash</button></p>
  </form>

  <?php if ($hash !== null): ?>
    <h2>Result</h2>
    <p class="adm-msg"><?= e($hash) ?></p>
    <p class="adm-meta">In config.php, the <code>users</code> line reads:<br>
       <code>'aditya' =&gt; '<em>the hash above</em>',</code></p>
  <?php endif; ?>
</div>
<?php
clf_admin_foot();
