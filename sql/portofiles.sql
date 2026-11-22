-- =====================================================================
--  Portofiles — skema database MySQL / MariaDB
--  Cara pakai (XAMPP): buka phpMyAdmin → tab "Import" → pilih file ini → Go.
--  File ini akan membuat database `portofiles` beserta data contoh.
--
--  Akun demo (password semua akun: portofiles123)
--    maya@portofiles.test   → akun utama dengan 3 project
--    luca@portofiles.test, sofia@portofiles.test, noah@portofiles.test,
--    amara@portofiles.test, yuki@portofiles.test
-- =====================================================================

DROP DATABASE IF EXISTS portofiles;
CREATE DATABASE portofiles CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portofiles;

-- ---------------------------------------------------------------------
-- Pengguna (artis & pengunjung terdaftar)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(30)  NOT NULL UNIQUE,
  email           VARCHAR(190) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  display_name    VARCHAR(80)  NOT NULL,
  bio             TEXT NULL,
  website         VARCHAR(190) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Kategori karya (chip filter di halaman Discover)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(40) NOT NULL UNIQUE,
  name        VARCHAR(60) NOT NULL,
  sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Karya / project. status 'draft' tidak tampil di Discover.
-- `appreciations` adalah counter (jumlah like) agar query cepat.
-- ---------------------------------------------------------------------
CREATE TABLE artworks (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  category_id    TINYINT UNSIGNED NOT NULL,
  title          VARCHAR(120) NOT NULL,
  description    TEXT NULL,
  image_path     VARCHAR(255) NOT NULL,
  status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
  is_curated     TINYINT(1) NOT NULL DEFAULT 0,
  views          INT UNSIGNED NOT NULL DEFAULT 0,
  appreciations  INT UNSIGNED NOT NULL DEFAULT 0,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  published_at   DATETIME NULL,
  CONSTRAINT fk_art_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_art_cat  FOREIGN KEY (category_id) REFERENCES categories(id),
  INDEX idx_art_status (status, published_at),
  INDEX idx_art_user (user_id, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Like / appreciation. Juga dipakai sebagai "Saved work".
-- ---------------------------------------------------------------------
CREATE TABLE likes (
  user_id     INT UNSIGNED NOT NULL,
  artwork_id  INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, artwork_id),
  CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_like_art  FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
  INDEX idx_like_art (artwork_id, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Follow antar pengguna
-- ---------------------------------------------------------------------
CREATE TABLE follows (
  follower_id   INT UNSIGNED NOT NULL,
  following_id  INT UNSIGNED NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, following_id),
  CONSTRAINT fk_fol_a FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fol_b FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_fol_target (following_id, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Token "Keep me signed in" (selector + hash validator, bukan password)
-- ---------------------------------------------------------------------
CREATE TABLE remember_tokens (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  selector        CHAR(24) NOT NULL UNIQUE,
  validator_hash  CHAR(64) NOT NULL,
  expires_at      DATETIME NOT NULL,
  CONSTRAINT fk_tok_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
--  DATA CONTOH
-- =====================================================================
INSERT INTO categories (slug, name, sort_order) VALUES
  ('illustration',   'Illustration',   1),
  ('graphic-design', 'Graphic design', 2),
  ('photography',    'Photography',    3),
  ('3d-digital',     '3D & digital',   4),
  ('fine-art',       'Fine art',       5);

-- password: portofiles123
INSERT INTO users (username, email, password_hash, display_name, bio, website, created_at) VALUES
  ('mayachen',     'maya@portofiles.test',  '$2y$10$k66qQGx02iRcrlHazTeSEeD.P5auNRMvd83/.SIN0aFGpMOl.gdAy', 'Maya Chen',     NULL, 'mayachen.studio', NOW() - INTERVAL 120 DAY),
  ('lucamoretti',  'luca@portofiles.test',  '$2y$10$k66qQGx02iRcrlHazTeSEeD.P5auNRMvd83/.SIN0aFGpMOl.gdAy', 'Luca Moretti',  'Soft objects, hard light. 3D artist based in Milan.', NULL, NOW() - INTERVAL 90 DAY),
  ('sofiareyes',   'sofia@portofiles.test', '$2y$10$k66qQGx02iRcrlHazTeSEeD.P5auNRMvd83/.SIN0aFGpMOl.gdAy', 'Sofia Reyes',   'Photographing the quiet parts of sunny places.', NULL, NOW() - INTERVAL 80 DAY),
  ('noahwilliams', 'noah@portofiles.test',  '$2y$10$k66qQGx02iRcrlHazTeSEeD.P5auNRMvd83/.SIN0aFGpMOl.gdAy', 'Noah Williams', 'Graphic designer. Posters, type, and form studies.', NULL, NOW() - INTERVAL 70 DAY),
  ('amaraokafor',  'amara@portofiles.test', '$2y$10$k66qQGx02iRcrlHazTeSEeD.P5auNRMvd83/.SIN0aFGpMOl.gdAy', 'Amara Okafor',  'Oil paintings of slow afternoons.', NULL, NOW() - INTERVAL 60 DAY),
  ('yukitanaka',   'yuki@portofiles.test',  '$2y$10$k66qQGx02iRcrlHazTeSEeD.P5auNRMvd83/.SIN0aFGpMOl.gdAy', 'Yuki Tanaka',   'Digital landscapes for places that do not exist yet.', NULL, NOW() - INTERVAL 50 DAY);

INSERT INTO artworks (user_id, category_id, title, description, image_path, status, is_curated, views, appreciations, created_at, updated_at, published_at) VALUES
  (1, 1, 'Daydream in bloom',       'Cut-paper flowers in a blue jug, made on a slow spring afternoon.', 'assets/img/daydream-in-bloom.jpg',       'published', 1, 1820, 128, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
  (2, 4, 'Objects of tomorrow',     'A study of balance: chrome, clay and glass.',                       'assets/img/objects-of-tomorrow.jpg',     'published', 1, 1204,  96, NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY),
  (3, 3, 'The quiet between',       'Whitewashed stairs and a blue door at noon.',                       'assets/img/the-quiet-between.jpg',       'published', 1, 2390, 214, NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 12 DAY),
  (4, 2, 'Soft forms, bold ideas',  'Form study no. 003 from a series on geometry and knots.',           'assets/img/soft-forms-bold-ideas.jpg',   'published', 1,  940,  82, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 15 DAY),
  (5, 5, 'A slower kind of Sunday', 'Oranges, a striped jug and a linen cloth. Oil on canvas.',          'assets/img/a-slower-kind-of-sunday.jpg', 'published', 1, 1630, 167, NOW() - INTERVAL 18 DAY, NOW() - INTERVAL 18 DAY, NOW() - INTERVAL 18 DAY),
  (6, 4, 'Elsewhere, after hours',  'A doorway in the dunes, rendered at the last minute of sunset.',    'assets/img/elsewhere-after-hours.jpg',   'published', 1, 1480, 143, NOW() - INTERVAL 21 DAY, NOW() - INTERVAL 21 DAY, NOW() - INTERVAL 21 DAY),
  (1, 1, 'Little everyday things',  'Morning coffee, one orange and a book on a striped table.',         'assets/img/little-everyday-things.jpg',  'published', 0,  660,  48, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
  (1, 1, 'Garden notes',            'Tulips and leaves in a gouache-style pattern. Work in progress.',   'assets/img/garden-notes.jpg',            'draft',     0,    0,   0, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR, NULL);

-- Beberapa like terbaru untuk karya Maya (dipakai panel aktivitas & tren)
INSERT INTO likes (user_id, artwork_id, created_at) VALUES
  (2, 1, NOW() - INTERVAL 3 HOUR),
  (3, 1, NOW() - INTERVAL 5 HOUR),
  (4, 1, NOW() - INTERVAL 20 DAY),
  (5, 7, NOW() - INTERVAL 1 DAY),
  (6, 7, NOW() - INTERVAL 1 DAY),
  (1, 3, NOW() - INTERVAL 4 DAY),
  (1, 5, NOW() - INTERVAL 6 DAY);

-- Follower Maya
INSERT INTO follows (follower_id, following_id, created_at) VALUES
  (2, 1, NOW() - INTERVAL 2 DAY),
  (3, 1, NOW() - INTERVAL 2 DAY),
  (6, 1, NOW() - INTERVAL 2 DAY),
  (4, 1, NOW() - INTERVAL 45 DAY),
  (1, 3, NOW() - INTERVAL 10 DAY);
