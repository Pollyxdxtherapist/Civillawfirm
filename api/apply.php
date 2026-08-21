<?php
/* ==========================================================================
   POST /api/apply.php  —  the careers application form
   --------------------------------------------------------------------------
   Saves what the applicant typed into MySQL, and the curriculum vitae into a
   folder OUTSIDE public_html. Nothing is emailed. The chambers reads
   applications at /admin/.

   Setup is in README.md, section 1c2.
   ========================================================================== */

declare(strict_types=1);
require __DIR__ . '/../admin/inc/bootstrap.php';

$wants_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    clf_apply_reply($wants_json, 405, 'This address only accepts the careers form.');
}

try {
    /* Spam trap. A real applicant never sees this field, so anything in it is
       a robot. Answer as though it worked, and save nothing. */
    if (trim((string) ($_POST['botcheck'] ?? '')) !== '') {
        clf_apply_reply($wants_json, 200, 'Thank you — your application has been sent.');
    }

    /* Angle brackets are stripped so nothing tag-like is ever stored. */
    $field = static function (string $key, int $max): string {
        $value = str_replace(['<', '>'], '', (string) ($_POST[$key] ?? ''));
        return mb_substr(trim($value), 0, $max);
    };

    $name     = $field('name', 80);
    $position = $field('position', 40);
    $email    = $field('email', 120);
    $phone    = $field('phone', 20);
    $message  = $field('message', 1200);
    $lang     = in_array($_POST['lang'] ?? 'en', ['en', 'hi', 'bn'], true) ? $_POST['lang'] : 'en';

    if ($name === '' || $email === '' || $phone === '' || $message === '') {
        clf_apply_reply($wants_json, 400, 'Please fill in every field.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        clf_apply_reply($wants_json, 400, 'Please check the email address.');
    }
    if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
        clf_apply_reply($wants_json, 400, 'Please give a telephone number the chambers can call back on.');
    }

    /* ---- the curriculum vitae ------------------------------------------- */

    $cv = $_FILES['attachment'] ?? null;
    if (!$cv || ($cv['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        clf_apply_reply($wants_json, 400, 'Please attach a curriculum vitae.');
    }
    if ($cv['error'] === UPLOAD_ERR_INI_SIZE || $cv['error'] === UPLOAD_ERR_FORM_SIZE) {
        clf_apply_reply($wants_json, 400, 'The curriculum vitae must be 5 MB or smaller.');
    }
    if ($cv['error'] !== UPLOAD_ERR_OK) {
        clf_apply_reply($wants_json, 400, 'The curriculum vitae did not upload. Please try again.');
    }
    /* Confirms the file really came through PHP's upload handling and is not
       some other path being passed off as one. */
    if (!is_uploaded_file($cv['tmp_name'])) {
        clf_apply_reply($wants_json, 400, 'The curriculum vitae did not upload. Please try again.');
    }
    if ($cv['size'] <= 0 || $cv['size'] > CLF_MAX_CV_BYTES) {
        clf_apply_reply($wants_json, 400, 'The curriculum vitae must be 5 MB or smaller.');
    }

    $original = mb_substr(basename((string) $cv['name']), 0, 255);
    $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, CLF_ALLOWED_EXT, true)) {
        clf_apply_reply($wants_json, 400, 'The curriculum vitae must be a PDF or a Word document.');
    }
    /* Check what the file actually is, not just what it is called. */
    $mime = 'application/octet-stream';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($cv['tmp_name']);
    }
    $mime_ok = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
    if (!in_array($mime, $mime_ok[$ext], true)) {
        clf_apply_reply($wants_json, 400, 'The curriculum vitae must be a PDF or a Word document.');
    }

    /* The name on disk is random and has nothing to do with what the
       applicant called the file, so no name they choose can ever become a
       path or overwrite anything. */
    $stored = date('Y-m-d') . '-' . bin2hex(random_bytes(12)) . '.' . $ext;

    $dir = clf_storage_path();
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log('careers: cannot create storage directory ' . $dir);
        clf_apply_reply($wants_json, 503, 'The application could not be saved. Please email the chambers instead.');
    }
    if (!move_uploaded_file($cv['tmp_name'], $dir . '/' . $stored)) {
        error_log('careers: cannot write to ' . $dir);
        clf_apply_reply($wants_json, 503, 'The application could not be saved. Please email the chambers instead.');
    }
    @chmod($dir . '/' . $stored, 0640);

    /* ---- save the record ------------------------------------------------ */

    try {
        $sql = 'INSERT INTO applications
                  (name, position, email, phone, message, cv_stored, cv_original,
                   cv_bytes, lang, submitted_at, submitted_ip)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?)';
        clf_db()->prepare($sql)->execute([
            $name, $position, $email, $phone, $message, $stored, $original,
            (int) $cv['size'], $lang,
            @inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null,
        ]);
    } catch (Throwable $e) {
        /* Do not leave a file behind with no record pointing at it. */
        @unlink($dir . '/' . $stored);
        throw $e;
    }

    clf_apply_reply($wants_json, 200, 'Thank you — your application has been sent.');

} catch (Throwable $e) {
    error_log('careers: ' . $e->getMessage());
    clf_apply_reply($wants_json, 500, 'The application could not be saved. Please email the chambers instead.');
}

/* -------------------------------------------------------------------------
   Answers either as JSON (the page's own script asked for it) or as a plain
   page (JavaScript is switched off, so the form posted normally).
   ------------------------------------------------------------------------- */
function clf_apply_reply(bool $wants_json, int $status, string $message): void
{
    http_response_code($status);
    $ok = $status === 200;

    if ($wants_json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $ok, 'message' => $message]);
        exit;
    }

    $heading = $ok ? 'Application sent' : 'Application not sent';
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en-IN"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex">'
       . '<title>' . e($heading) . ' | Civil Law Firm</title>'
       . '<link rel="stylesheet" href="/css/styles.css?v=3"></head><body>'
       . '<main id="main"><div class="wrap"><header class="page-head">'
       . '<h1>' . e($heading) . '</h1><p class="lede">' . e($message) . '</p></header>'
       . '<p class="cta-row"><a class="btn btn-outline" href="/careers/">Back to Careers</a>'
       . '<a class="btn btn-ghost" href="/contact/">Contact the chambers</a></p>'
       . '</div></main></body></html>';
    exit;
}
