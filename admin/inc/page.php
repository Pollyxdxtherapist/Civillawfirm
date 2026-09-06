<?php
/* Shared chrome for the admin pages. Deliberately plain: this is a working
   tool for the chambers, not part of the public website. It borrows the
   site's stylesheet so it does not look foreign. */

declare(strict_types=1);

function clf_admin_head(string $title, bool $with_nav = true): void
{
    header('Content-Type: text/html; charset=utf-8');
    /* Never let an admin page be cached or indexed. */
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    ?><!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> | Civil Law Firm</title>
<link rel="stylesheet" href="/css/styles.css?v=3">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<style>
  /* A handful of rules only these pages need. */
  .adm-bar { background: var(--surface); border-bottom: 1px solid var(--line); }
  .adm-bar .wrap { display: flex; align-items: center; gap: 1rem; min-height: 60px; flex-wrap: wrap; }
  .adm-bar strong { font-family: var(--font-serif); font-size: 1.05rem; }
  .adm-bar nav { margin-left: auto; display: flex; gap: 1rem; align-items: center; font-size: .95rem; }
  .adm-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: .95rem; }
  .adm-table th, .adm-table td { border-bottom: 1px solid var(--line); padding: .7rem .6rem; text-align: left; vertical-align: top; }
  .adm-table th { font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); font-weight: 600; }
  .adm-table tr.is-new td { font-weight: 600; }
  .adm-dot { display: inline-block; width: .5rem; height: .5rem; border-radius: 50%; background: var(--accent); }
  .adm-wrap { max-width: 62rem; margin: 0 auto; padding: 1.5rem 1.15rem 4rem; }
  .adm-login { max-width: 22rem; margin: 3rem auto; }
  .adm-empty { color: var(--muted); font-style: italic; padding: 2rem 0; }
  .adm-meta { color: var(--muted); font-size: .9rem; }
  .adm-msg { border-left: 3px solid var(--accent); padding: .1rem 0 .1rem 1rem; white-space: pre-wrap; }
</style>
</head>
<body>
<?php if ($with_nav): ?>
<div class="adm-bar">
  <div class="wrap">
    <strong>Civil Law Firm</strong>
    <span class="adm-meta">Applications</span>
    <nav>
      <a href="/admin/">All applications</a>
      <a href="/">The website</a>
      <a href="/admin/logout.php">Sign out</a>
    </nav>
  </div>
</div>
<?php endif; ?>
<main class="adm-wrap">
<?php
}

function clf_admin_foot(): void
{
    ?>
</main>
</body>
</html>
<?php
}
