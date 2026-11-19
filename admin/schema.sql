-- ===========================================================================
-- Civil Law Firm — the one table the careers page needs.
--
-- Run this once, in hPanel -> Databases -> phpMyAdmin -> your database ->
-- the SQL tab. Paste the whole file in and press Go.
-- ===========================================================================

CREATE TABLE IF NOT EXISTS applications (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,

  -- What the applicant typed. Lengths match the maxlength on the form.
  name          VARCHAR(80)     NOT NULL,
  position      VARCHAR(40)     NOT NULL,
  email         VARCHAR(120)    NOT NULL,
  phone         VARCHAR(20)     NOT NULL,
  message       TEXT            NOT NULL,

  -- The curriculum vitae. cv_stored is a random name on disk; cv_original is
  -- what the applicant called it, shown in the portal and used for the
  -- download. The two are kept apart so that nothing an applicant chooses to
  -- call a file ever becomes a path on the server.
  cv_stored     VARCHAR(80)     NOT NULL,
  cv_original   VARCHAR(255)    NOT NULL,
  cv_bytes      INT UNSIGNED    NOT NULL,

  -- Housekeeping.
  lang          CHAR(2)         NOT NULL DEFAULT 'en',
  submitted_at  DATETIME        NOT NULL,
  submitted_ip  VARBINARY(16)   DEFAULT NULL,
  is_read       TINYINT(1)      NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  KEY idx_submitted_at (submitted_at),
  KEY idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
