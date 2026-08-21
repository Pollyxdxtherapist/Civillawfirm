<?php
/* ==========================================================================
   TEMPLATE -- copy this file to config.php and fill it in ON THE SERVER.

   config.php is listed in .gitignore and must NEVER be committed: this
   repository is public, so anything in it is readable by the whole world.

   How to create it on Hostinger:
     hPanel -> Files -> File Manager -> open  public_html/admin/inc/
     -> right-click config.sample.php -> Copy -> rename the copy config.php
     -> edit it and fill in the five values below.
   ========================================================================== */

/* Refuses to do anything unless it is being included by bootstrap.php. */
if (!defined('CLF_BOOTSTRAP')) {
    http_response_code(404);
    exit;
}

return [

    /* ---- 1. The MySQL database -------------------------------------------
       Create it in hPanel -> Databases -> Management. Hostinger shows you
       the database name, user and host once it is made. */
    'db_host' => 'localhost',
    'db_name' => 'uXXXXXXXX_civillawfirm',
    'db_user' => 'uXXXXXXXX_clf',
    'db_pass' => 'the database password',

    /* ---- 2. Where the curriculum vitae files are kept ---------------------
       This MUST be outside public_html, or anyone who guesses a file name
       can download an applicant's CV without logging in.

       On Hostinger your account looks like:
           /home/uXXXXXXXX/public_html      <- the website
           /home/uXXXXXXXX/clf-storage      <- create this, alongside it

       Put the full path here. Create the folder in File Manager first. */
    'storage_path' => '/home/uXXXXXXXX/clf-storage/cvs',

    /* ---- 3. Who can sign in to the admin portal --------------------------
       The password is stored as a bcrypt hash, never as plain text. To make
       a hash, open  /admin/make-hash.php  in your browser once, type the
       password you want, paste the result here, then DELETE make-hash.php.

       Add as many people as you like. */
    'users' => [
        'aditya' => '$2y$10$replace.this.with.the.hash.from.make-hash.php',
    ],

    /* ---- 4. How long applications are kept -------------------------------
       Applications older than this are removed by /admin/prune.php, along
       with their CV files. Set to 0 to keep everything for ever -- but see
       README section 1c2 on the DPDP Act before you do. */
    'retain_days' => 365,

];
