-- gameBacklog seed data
-- Run AFTER schema.sql. All test accounts use the password: password123

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE entries; TRUNCATE game_platforms; TRUNCATE game_genres;
TRUNCATE platforms; TRUNCATE genres; TRUNCATE games; TRUNCATE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Users ----------
INSERT INTO users (id, username, email, password_hash, role, is_public, created_at) VALUES
(1, 'admin',     'admin@gamebacklog.test',     '$2y$10$hGVnmW9CQEtiTiraTh0.du0SsXL97D1i9iMbYCPyryb1igLeqtnuu', 'admin',     1, '2026-01-10 10:00:00'),
(2, 'moderator', 'moderator@gamebacklog.test', '$2y$10$hGVnmW9CQEtiTiraTh0.du0SsXL97D1i9iMbYCPyryb1igLeqtnuu', 'moderator', 1, '2026-02-03 12:30:00'),
(3, 'marko',     'marko@gamebacklog.test',     '$2y$10$hGVnmW9CQEtiTiraTh0.du0SsXL97D1i9iMbYCPyryb1igLeqtnuu', 'user',      1, '2026-03-15 18:20:00'),
(4, 'ana',       'ana@gamebacklog.test',       '$2y$10$hGVnmW9CQEtiTiraTh0.du0SsXL97D1i9iMbYCPyryb1igLeqtnuu', 'user',      0, '2026-05-22 09:45:00'),
(5, 'jovan',     'jovan@gamebacklog.test',     '$2y$10$hGVnmW9CQEtiTiraTh0.du0SsXL97D1i9iMbYCPyryb1igLeqtnuu', 'user',      1, '2026-09-01 21:10:00');

-- ---------- Genres ----------
INSERT INTO genres (id, name) VALUES
(1,'Action'),(2,'Adventure'),(3,'RPG'),(4,'Strategy'),(5,'Shooter'),
(6,'Platformer'),(7,'Puzzle'),(8,'Simulation'),(9,'Sports'),(10,'Racing'),
(11,'Roguelike'),(12,'Horror'),(13,'Fighting'),(14,'Indie');

-- ---------- Platforms ----------
INSERT INTO platforms (id, name) VALUES
(1,'PC'),(2,'PlayStation 5'),(3,'PlayStation 4'),(4,'Xbox Series X|S'),
(5,'Xbox One'),(6,'Nintendo Switch'),(7,'Mobile');

-- ---------- Games ----------
INSERT INTO games (id, title, release_year, description) VALUES
(1,  'The Witcher 3: Wild Hunt', 2015, 'Open-world RPG following monster hunter Geralt of Rivia as he searches for his adopted daughter.'),
(2,  'Elden Ring', 2022, 'Challenging open-world action RPG set in the Lands Between.'),
(3,  'Hades', 2020, 'Roguelike dungeon crawler where Zagreus tries to escape the Underworld.'),
(4,  'Hollow Knight', 2017, 'Atmospheric metroidvania set in the ruined insect kingdom of Hallownest.'),
(5,  'Stardew Valley', 2016, 'Farming and life simulation game set in a small countryside town.'),
(6,  'Celeste', 2018, 'Precision platformer about climbing a mountain and facing your own anxiety.'),
(7,  'Slay the Spire', 2019, 'Deck-building roguelike where you climb a spire full of monsters.'),
(8,  'Red Dead Redemption 2', 2018, 'Western action-adventure about outlaw Arthur Morgan and the Van der Linde gang.'),
(9,  'God of War', 2018, 'Kratos and his son Atreus journey through the realms of Norse mythology.'),
(10, 'The Legend of Zelda: Breath of the Wild', 2017, 'Open-air adventure across a ruined Hyrule.'),
(11, 'Super Mario Odyssey', 2017, '3D platformer where Mario travels between kingdoms with his companion Cappy.'),
(12, 'Portal 2', 2011, 'First-person puzzle game built around a portal gun.'),
(13, 'Doom Eternal', 2020, 'Fast-paced first-person shooter against demonic invaders.'),
(14, 'Baldur''s Gate 3', 2023, 'Story-rich party-based RPG based on Dungeons & Dragons.'),
(15, 'Disco Elysium', 2019, 'Detective RPG focused on dialogue, skills and choices.'),
(16, 'Cyberpunk 2077', 2020, 'Open-world action RPG set in the megacity of Night City.'),
(17, 'Resident Evil 4', 2023, 'Remake of the survival horror classic following agent Leon S. Kennedy.'),
(18, 'Street Fighter 6', 2023, 'Competitive fighting game with a single-player World Tour mode.'),
(19, 'Forza Horizon 5', 2021, 'Open-world racing game set in Mexico.'),
(20, 'Civilization VI', 2016, 'Turn-based strategy game about building an empire through history.'),
(21, 'Minecraft', 2011, 'Sandbox game about exploring, gathering resources and building.'),
(22, 'Dead Cells', 2018, 'Roguelite action platformer with permadeath and changing levels.'),
(23, 'Persona 5 Royal', 2019, 'Turn-based JRPG about high school students who lead double lives as Phantom Thieves.'),
(24, 'Sekiro: Shadows Die Twice', 2019, 'Demanding action-adventure set in a reimagined Sengoku-era Japan.'),
(25, 'Outer Wilds', 2019, 'Exploration mystery set in a solar system stuck in a time loop.'),
(26, 'EA Sports FC 24', 2023, 'Football simulation game.'),
(27, 'Rocket League', 2015, 'Soccer played with rocket-powered cars.'),
(28, 'Alan Wake 2', 2023, 'Survival horror story told through two playable characters.'),
(29, 'Factorio', 2020, 'Factory-building and automation strategy game.'),
(30, 'Balatro', 2024, 'Poker-inspired deck-building roguelike.');

-- ---------- Game genres ----------
INSERT INTO game_genres (game_id, genre_id) VALUES
(1,3),(1,1),(1,2),
(2,3),(2,1),
(3,11),(3,1),(3,14),
(4,6),(4,1),(4,14),
(5,8),(5,3),(5,14),
(6,6),(6,14),
(7,11),(7,4),(7,14),
(8,1),(8,2),
(9,1),(9,2),
(10,1),(10,2),
(11,6),(11,2),
(12,7),
(13,5),(13,1),
(14,3),(14,4),
(15,3),(15,14),
(16,3),(16,1),(16,5),
(17,12),(17,1),(17,5),
(18,13),
(19,10),
(20,4),
(21,8),(21,2),
(22,11),(22,1),(22,6),(22,14),
(23,3),
(24,1),(24,2),
(25,2),(25,7),(25,14),
(26,9),
(27,9),(27,10),
(28,12),(28,2),
(29,4),(29,8),(29,14),
(30,11),(30,4),(30,14);

-- ---------- Game platforms ----------
INSERT INTO game_platforms (game_id, platform_id) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),
(2,1),(2,2),(2,3),(2,4),(2,5),
(3,1),(3,2),(3,3),(3,4),(3,5),(3,6),
(4,1),(4,3),(4,5),(4,6),
(5,1),(5,3),(5,5),(5,6),(5,7),
(6,1),(6,3),(6,5),(6,6),
(7,1),(7,3),(7,5),(7,6),(7,7),
(8,1),(8,3),(8,5),
(9,1),(9,3),
(10,6),
(11,6),
(12,1),(12,6),
(13,1),(13,2),(13,3),(13,4),(13,5),(13,6),
(14,1),(14,2),(14,4),
(15,1),(15,2),(15,3),(15,4),(15,5),(15,6),
(16,1),(16,2),(16,3),(16,4),(16,5),
(17,1),(17,2),(17,3),(17,4),
(18,1),(18,2),(18,3),(18,4),
(19,1),(19,4),(19,5),
(20,1),(20,3),(20,5),(20,6),(20,7),
(21,1),(21,3),(21,5),(21,6),(21,7),
(22,1),(22,3),(22,5),(22,6),(22,7),
(23,1),(23,2),(23,3),(23,4),(23,5),(23,6),
(24,1),(24,3),(24,5),
(25,1),(25,3),(25,5),(25,6),
(26,1),(26,2),(26,3),(26,4),(26,5),(26,6),
(27,1),(27,3),(27,5),(27,6),
(28,1),(28,2),(28,4),
(29,1),(29,6),
(30,1),(30,2),(30,3),(30,4),(30,5),(30,6),(30,7);

-- ---------- Library entries ----------
-- Rule used: backlog = no dates, 0 hours; playing/dropped = start date only; completed = both dates
INSERT INTO entries (user_id, game_id, status, rating, review, notes, started_at, finished_at, hours, created_at) VALUES
-- marko (public)
(3, 1,  'completed', 10, 'Best side quests I have ever played. The Bloody Baron story alone is worth it.', 'Played on Death March', '2026-03-20', '2026-05-28', 142.5, '2026-03-20 19:00:00'),
(3, 3,  'completed', 9,  'Every run feels different and the story keeps going even when you die.', NULL, '2026-06-01', '2026-07-10', 48.0, '2026-06-01 20:00:00'),
(3, 2,  'playing',   NULL, NULL, 'Stuck on Malenia', '2026-08-05', NULL, 63.0, '2026-08-05 18:30:00'),
(3, 7,  'completed', 9,  'Perfect game to play in short sessions.', NULL, '2026-07-12', '2026-09-02', 55.5, '2026-07-12 22:00:00'),
(3, 14, 'backlog',   NULL, NULL, 'Waiting for winter break', NULL, NULL, 0, '2026-09-10 12:00:00'),
(3, 16, 'dropped',   5,  'Night City looks great but I lost interest halfway through.', NULL, '2026-04-02', NULL, 21.0, '2026-04-02 21:15:00'),
(3, 25, 'backlog',   NULL, NULL, NULL, NULL, NULL, 0, '2026-10-01 17:45:00'),
(3, 30, 'completed', 8,  'Very addictive, way too easy to say "just one more run".', NULL, '2026-09-15', '2026-10-20', 34.0, '2026-09-15 23:00:00'),

-- ana (private profile)
(4, 5,  'completed', 9,  'Relaxing and charming, I finished the community center in the first year.', NULL, '2026-05-25', '2026-08-14', 88.0, '2026-05-25 10:00:00'),
(4, 6,  'completed', 10, 'Hard but fair, and the soundtrack is amazing.', NULL, '2026-08-20', '2026-09-05', 19.5, '2026-08-20 16:00:00'),
(4, 4,  'playing',   NULL, NULL, NULL, '2026-09-10', NULL, 27.0, '2026-09-10 20:00:00'),
(4, 10, 'completed', 10, 'Pure freedom. The best exploration in any game.', NULL, '2026-06-01', '2026-07-30', 96.0, '2026-06-01 11:00:00'),
(4, 11, 'backlog',   NULL, NULL, NULL, NULL, NULL, 0, '2026-10-12 14:00:00'),
(4, 3,  'completed', 8,  NULL, NULL, '2026-10-01', '2026-11-08', 31.0, '2026-10-01 19:30:00'),

-- jovan (public)
(5, 13, 'completed', 8,  'Pure adrenaline from start to finish.', NULL, '2026-09-03', '2026-09-25', 24.0, '2026-09-03 22:00:00'),
(5, 17, 'completed', 9,  'Great remake that keeps the spirit of the original.', NULL, '2026-09-26', '2026-10-15', 18.5, '2026-09-26 21:00:00'),
(5, 18, 'playing',   NULL, NULL, 'Learning Ken', '2026-10-16', NULL, 40.0, '2026-10-16 20:00:00'),
(5, 1,  'backlog',   NULL, NULL, NULL, NULL, NULL, 0, '2026-09-05 13:00:00'),
(5, 2,  'dropped',   6,  'Too punishing for me right now.', NULL, '2026-09-10', NULL, 12.0, '2026-09-10 19:00:00'),
(5, 30, 'completed', 9,  NULL, NULL, '2026-10-20', '2026-11-15', 26.0, '2026-10-20 23:30:00'),
(5, 28, 'backlog',   NULL, NULL, NULL, NULL, NULL, 0, '2026-11-01 18:00:00');
