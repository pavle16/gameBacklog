# GameBacklog – uni project (PHP + MariaDB, solo)

Course project: game library/backlog tracker. Plain PHP (no framework, no Composer),
MariaDB via XAMPP, HTML/CSS/vanilla JS. Chart.js from CDN for statistics only.
UI text is in Serbian (Latin script). Code, comments and variable names in English.

## Structure
- config/db.php – PDO ($pdo), loads config/config.local.php (gitignored)
- includes/init.php – every page starts with require_once of this (session, db, helpers, auth)
- includes/functions.php – e(), url(), redirect(), csrf_field(), verify_csrf(), flash(), old(),
  cover_url() (cover_path = file name in public/uploads/covers, NULL → placeholder), status_label()
- includes/auth.php – current_user(), is_logged_in(), has_role(), require_login(), require_role(), login_user()
- includes/header.php / footer.php – layout; pages set $pageTitle before including header
- admin/ – moderator + admin pages, api/ – JSON endpoints (stats, export)
- public/css, public/js, public/uploads/covers (uploads gitignored)
- sql/schema.sql, sql/seed.sql – schema + test data (all test users: password123)

## Database
users(id, username, email, password_hash, role ENUM user/moderator/admin, is_public, created_at)
games(id, title, release_year, description, cover_path, created_at)
genres(id, name), platforms(id, name), game_genres(game_id, genre_id), game_platforms(game_id, platform_id)
entries(id, user_id, game_id, status ENUM backlog/playing/completed/dropped, rating 1-10 NULL,
        review, review_removed, notes, started_at, finished_at, hours, created_at, updated_at)
        UNIQUE(user_id, game_id)

## Rules (always follow)
- PDO prepared statements for every query with user input – never concatenate input into SQL
- Escape every output of user data with e()
- Every POST form includes csrf_field(); every POST handler calls verify_csrf() first
- Protected pages call require_login() or require_role(...) at the top
- Check ownership: users can only modify their own entries/profile
- Pattern: process POST at top of file -> on success flash() + redirect() (PRG); on error re-render form with old() values
- Admins can do everything moderators can: use require_role('moderator', 'admin')
- Keep it simple and readable – I must be able to explain every line at the project defense
- Don't change schema.sql without telling me; give me ALTER statements if needed
- Reuse the existing CSS classes in public/css/style.css; add new ones there, no inline styles