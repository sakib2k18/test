-- ===========================================================================
--  KUET BUS SERVICE  -  DATABASE SCHEMA + SAMPLE DATA
--  ---------------------------------------------------------------------------
--  Server      : MySQL / MariaDB running on PORT 4306 (XAMPP)
--  Database    : university_bus_service
--  Import with : phpMyAdmin  ->  Import  ->  choose this file  ->  Go
--         or   : mysql -u root -P 4306 -h 127.0.0.1 < university_bus_service.sql
--
--  Default administrator (the only admin account):
--         email    : admin@kuet.ac.bd
--         password : admin@123
--  Sample student account:
--         email    : rakib@stud.kuet.ac.bd
--         password : user@123
-- ===========================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------------------------------------------------------------------------
-- 1. DATABASE
-- ---------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `university_bus_service`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `university_bus_service`;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `bus_facilities`;
DROP TABLE IF EXISTS `facilities`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `route_stops`;
DROP TABLE IF EXISTS `routes`;
DROP TABLE IF EXISTS `buses`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------------
-- 2. TABLES
-- ---------------------------------------------------------------------------

-- 2.1 users -----------------------------------------------------------------
-- Only two roles exist. Registration always creates 'user'; the single
-- 'admin' row is seeded below and can never be created from the website.
CREATE TABLE `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name`  VARCHAR(100)  NOT NULL,
    `email`      VARCHAR(150)  NOT NULL,
    `password`   VARCHAR(255)  NOT NULL,          -- bcrypt hash, never plain text
    `student_id` VARCHAR(30)       NULL,
    `department` VARCHAR(100)      NULL,
    `phone`      VARCHAR(20)       NULL,
    `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 buses -----------------------------------------------------------------
CREATE TABLE `buses` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bus_name`       VARCHAR(80)  NOT NULL,       -- e.g. "KUET Bus-01"
    `reg_number`     VARCHAR(40)  NOT NULL,       -- unique registration plate
    `model`          VARCHAR(80)      NULL,
    `capacity`       SMALLINT UNSIGNED NOT NULL DEFAULT 40,
    `bus_type`       ENUM('Regular','Student Bus','Faculty Bus (AC)','Faculty Bus (Non AC)')
                     NOT NULL DEFAULT 'Regular',
    `status`         ENUM('Active','Maintenance','Inactive') NOT NULL DEFAULT 'Active',
    `driver_name`    VARCHAR(80)      NULL,
    `driver_contact` VARCHAR(20)      NULL,
    `description`    TEXT             NULL,
    `image`          VARCHAR(255)     NULL,       -- filename inside assets/uploads/buses/
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_buses_reg` (`reg_number`),
    KEY `idx_buses_status` (`status`),
    KEY `idx_buses_type` (`bus_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3 routes ----------------------------------------------------------------
CREATE TABLE `routes` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `route_name`    VARCHAR(120) NOT NULL,
    `route_code`    VARCHAR(20)  NOT NULL,        -- unique short code, e.g. K-01
    `start_point`   VARCHAR(120) NOT NULL,
    `destination`   VARCHAR(120) NOT NULL,
    `description`   TEXT             NULL,
    `distance_km`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `duration_min`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status`        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_routes_code` (`route_code`),
    KEY `idx_routes_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4 route_stops  (ONE route -> MANY stops) --------------------------------
-- Deleting a route removes its stops automatically (ON DELETE CASCADE).
CREATE TABLE `route_stops` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `route_id`         INT UNSIGNED NOT NULL,
    `stop_name`        VARCHAR(120) NOT NULL,
    `stop_description` VARCHAR(255)     NULL,
    `stop_order`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `arrival_time`     TIME             NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_stops_route_order` (`route_id`, `stop_order`),
    CONSTRAINT `fk_stops_route` FOREIGN KEY (`route_id`)
        REFERENCES `routes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5 schedules  (ONE schedule -> ONE bus + ONE route) ----------------------
-- ON DELETE RESTRICT keeps the data consistent: a bus or a route that is
-- still used by a schedule cannot be deleted by accident.
CREATE TABLE `schedules` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bus_id`         INT UNSIGNED NOT NULL,
    `route_id`       INT UNSIGNED NOT NULL,
    `departure_time` TIME         NOT NULL,
    `arrival_time`   TIME         NOT NULL,
    `operating_days` VARCHAR(80)  NOT NULL,       -- e.g. "Saturday,Sunday,Monday"
    `status`         ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
    `notes`          VARCHAR(255)     NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sched_bus` (`bus_id`),
    KEY `idx_sched_route` (`route_id`),
    KEY `idx_sched_departure` (`departure_time`),
    CONSTRAINT `fk_sched_bus` FOREIGN KEY (`bus_id`)
        REFERENCES `buses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sched_route` FOREIGN KEY (`route_id`)
        REFERENCES `routes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6 announcements ---------------------------------------------------------
CREATE TABLE `announcements` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`             VARCHAR(160) NOT NULL,
    `short_description` VARCHAR(255) NOT NULL,
    `content`           TEXT         NOT NULL,
    `priority`          ENUM('Normal','Important','Urgent') NOT NULL DEFAULT 'Normal',
    `status`            ENUM('Published','Draft') NOT NULL DEFAULT 'Published',
    `published_on`      DATE         NOT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ann_status_date` (`status`, `published_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7 facilities + bus_facilities  (MANY buses <-> MANY facilities) ---------
CREATE TABLE `facilities` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(60) NOT NULL,
    `icon_key`  VARCHAR(30) NOT NULL DEFAULT 'check',  -- matches icon() in functions.php
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_facility_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bus_facilities` (
    `bus_id`      INT UNSIGNED NOT NULL,
    `facility_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`bus_id`, `facility_id`),
    KEY `idx_bf_facility` (`facility_id`),
    CONSTRAINT `fk_bf_bus` FOREIGN KEY (`bus_id`)
        REFERENCES `buses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bf_facility` FOREIGN KEY (`facility_id`)
        REFERENCES `facilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.8 contact_messages ------------------------------------------------------
CREATE TABLE `contact_messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL,
    `subject`    VARCHAR(150) NOT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_msg_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.9 activity_logs ---------------------------------------------------------
-- Keeps a trail of admin actions. If the user row disappears the log stays.
CREATE TABLE `activity_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED     NULL,
    `action`      VARCHAR(60)  NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_created` (`created_at`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. SAMPLE DATA
-- ---------------------------------------------------------------------------

-- 3.1 THE SINGLE ADMINISTRATOR + a few normal users -------------------------
-- Passwords are bcrypt hashes created with PHP password_hash().
--   admin@kuet.ac.bd        -> admin@123
--   every sample student    -> user@123
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `student_id`, `department`, `phone`, `role`) VALUES
(1, 'Transport Administrator', 'admin@kuet.ac.bd', '$2y$10$Kil3q5DSPmpNEVqhez7e9uV9SV38.dcpyGO7jBunyOPYZfsX6XmUy', NULL, 'Transport Office', '01711000000', 'admin'),
(2, 'Rakibul Hasan',   'rakib@stud.kuet.ac.bd',  '$2y$10$W5Y0khBZnyZmzCedBccGs.LEGJmfsuaO38A.o0UlCUUMaRA6Kqfza', '1907045', 'Computer Science and Engineering', '01712345678', 'user'),
(3, 'Nusrat Jahan',    'nusrat@stud.kuet.ac.bd', '$2y$10$W5Y0khBZnyZmzCedBccGs.LEGJmfsuaO38A.o0UlCUUMaRA6Kqfza', '2007112', 'Electrical and Electronic Engineering', '01812345678', 'user'),
(4, 'Sadman Sakib',    'sadman@stud.kuet.ac.bd', '$2y$10$W5Y0khBZnyZmzCedBccGs.LEGJmfsuaO38A.o0UlCUUMaRA6Kqfza', '2103021', 'Civil Engineering', '01912345678', 'user'),
(5, 'Farhana Akter',   'farhana@stud.kuet.ac.bd','$2y$10$W5Y0khBZnyZmzCedBccGs.LEGJmfsuaO38A.o0UlCUUMaRA6Kqfza', '1809067', 'Mechanical Engineering', '01612345678', 'user');

-- 3.2 BUSES -----------------------------------------------------------------
INSERT INTO `buses`
(`id`, `bus_name`, `reg_number`, `model`, `capacity`, `bus_type`, `status`, `driver_name`, `driver_contact`, `description`, `image`) VALUES
(1, 'KUET Bus-01', 'KHU-METRO-JA-11-0451', 'Ashok Leyland Viking', 52, 'Student Bus', 'Active', 'Md. Abdul Karim', '01711234501',
 'Primary morning shuttle for undergraduate students living in the Sonadanga and New Market area. Fitted with wide luggage racks and an emergency exit at the rear.', NULL),
(2, 'KUET Bus-02', 'KHU-METRO-JA-11-0452', 'Tata LP 909', 45, 'Student Bus', 'Active', 'Sheikh Jashim Uddin', '01711234502',
 'Serves the Khalishpur corridor twice every working day. Regularly serviced at the university workshop and equipped with a first-aid box.', NULL),
(3, 'KUET Bus-03', 'KHU-METRO-JA-11-0453', 'Hino AK1J', 50, 'Student Bus', 'Active', 'Mohammad Rezaul Islam', '01711234503',
 'High-capacity coach used for the Boyra and Nirala route. Comfortable cushioned seats and large windows for good ventilation.', NULL),
(4, 'KUET Bus-04', 'KHU-METRO-JA-11-0454', 'Ashok Leyland Falcon', 40, 'Faculty Bus (AC)', 'Active', 'Abdur Rahman Mollah', '01711234504',
 'Air-conditioned coach reserved for teachers and officers. Reclining seats, curtains and an on-board public address system.', NULL),
(5, 'KUET Bus-05', 'KHU-METRO-JA-11-0455', 'Tata Starbus Ultra', 38, 'Faculty Bus (Non AC)', 'Active', 'Nazrul Islam Sarker', '01711234505',
 'Non air-conditioned staff bus covering the Daulatpur and Fulbarigate area during office hours.', NULL),
(6, 'KUET Bus-06', 'KHU-METRO-JA-11-0456', 'Ashok Leyland Viking', 52, 'Student Bus', 'Active', 'Sohel Rana', '01711234506',
 'Evening return shuttle from the campus to Rupsha. Priority is given to students of the evening laboratory sessions.', NULL),
(7, 'KUET Bus-07', 'KHU-METRO-JA-11-0457', 'Hino RK1J', 48, 'Regular', 'Active', 'Mizanur Rahman', '01711234507',
 'General purpose bus used for departmental tours, industrial visits and additional trips during examination periods.', NULL),
(8, 'KUET Bus-08', 'KHU-METRO-JA-11-0458', 'Tata LP 709', 32, 'Regular', 'Maintenance', 'Jahangir Alam', '01711234508',
 'Currently in the university workshop for a scheduled engine overhaul and brake replacement. Expected back in service shortly.', NULL),
(9, 'KUET Bus-09', 'KHU-METRO-JA-11-0459', 'Ashok Leyland Lynx', 42, 'Student Bus', 'Active', 'Kamal Hossain', '01711234509',
 'Covers the Zero Point and Batiaghata road for students living outside the city centre.', NULL),
(10, 'KUET Bus-10', 'KHU-METRO-JA-11-0460', 'Tata Marcopolo', 36, 'Faculty Bus (AC)', 'Inactive', 'Rafiqul Islam', '01711234510',
 'Older air-conditioned coach kept as a stand-by vehicle. It is taken out only when another faculty bus is unavailable.', NULL);

-- 3.3 ROUTES ----------------------------------------------------------------
INSERT INTO `routes`
(`id`, `route_name`, `route_code`, `start_point`, `destination`, `description`, `distance_km`, `duration_min`, `status`) VALUES
(1, 'KUET Campus - Sonadanga', 'K-01', 'KUET Main Campus', 'Sonadanga Bus Terminal',
 'The busiest student route of the university. It connects the main campus with the southern part of Khulna city through Daulatpur and New Market.', 14.50, 55, 'Active'),
(2, 'KUET Campus - Khalishpur', 'K-02', 'KUET Main Campus', 'Khalishpur Housing Estate',
 'A short city route serving students and staff who live in the Khalishpur industrial and residential area.', 8.20, 35, 'Active'),
(3, 'KUET Campus - Boyra Bazar', 'K-03', 'KUET Main Campus', 'Boyra Bazar',
 'Passes through Nirala and Boyra Cross Road. Popular with students of the senior batches living in private messes.', 11.30, 45, 'Active'),
(4, 'KUET Campus - Rupsha', 'K-04', 'KUET Main Campus', 'Rupsha Ferry Ghat',
 'An east bound route crossing the city centre and Shibbari intersection before reaching the Rupsha river bank.', 16.80, 65, 'Active'),
(5, 'KUET Campus - Daulatpur', 'K-05', 'KUET Main Campus', 'Daulatpur Bazar',
 'The shortest route of the fleet, mainly used by teachers and officers who live close to the university.', 4.60, 20, 'Active'),
(6, 'KUET Campus - Zero Point', 'K-06', 'KUET Main Campus', 'Zero Point, Batiaghata Road',
 'Serves students commuting from the Batiaghata side of Khulna. Operates only on working days.', 18.40, 70, 'Active'),
(7, 'KUET Campus - Jessore Road', 'K-07', 'KUET Main Campus', 'Phultala, Jessore Road',
 'A long distance suburban route for staff members living along the Khulna-Jessore highway. Temporarily suspended for road repair work.', 22.00, 80, 'Inactive');

-- 3.4 ROUTE STOPS  (ordered timeline shown on the public route page) --------
INSERT INTO `route_stops` (`route_id`, `stop_name`, `stop_description`, `stop_order`, `arrival_time`) VALUES
-- Route 1 : KUET Campus - Sonadanga
(1, 'KUET Main Campus',     'Departure point beside the central auditorium', 1, '07:00:00'),
(1, 'KUET Main Gate',       'Fulbarigate side entrance of the university',   2, '07:05:00'),
(1, 'Fulbarigate Bazar',    'Opposite the local market',                     3, '07:12:00'),
(1, 'Daulatpur Bus Stand',  'Main junction of Daulatpur',                    4, '07:22:00'),
(1, 'Notun Rasta',          'Near the flyover crossing',                     5, '07:32:00'),
(1, 'Royal Mor',            'Beside the Royal Hotel intersection',           6, '07:42:00'),
(1, 'New Market',           'City centre shopping area',                     7, '07:50:00'),
(1, 'Sonadanga Bus Terminal','Final stop, inter-district terminal',          8, '07:58:00'),
-- Route 2 : KUET Campus - Khalishpur
(2, 'KUET Main Campus',     'Departure point beside the central auditorium', 1, '07:15:00'),
(2, 'KUET Main Gate',       'Fulbarigate side entrance',                     2, '07:20:00'),
(2, 'Daulatpur Bus Stand',  'Main junction of Daulatpur',                    3, '07:30:00'),
(2, 'Khalishpur Chourasta', 'Four way crossing near the jute mill',          4, '07:42:00'),
(2, 'Khalishpur Housing Estate','Final stop near the housing colony',        5, '07:50:00'),
-- Route 3 : KUET Campus - Boyra Bazar
(3, 'KUET Main Campus',     'Departure point beside the central auditorium', 1, '07:10:00'),
(3, 'Fulbarigate Bazar',    'Opposite the local market',                     2, '07:18:00'),
(3, 'Daulatpur Bus Stand',  'Main junction of Daulatpur',                    3, '07:26:00'),
(3, 'Nirala Cross Road',    'Beside the residential area',                   4, '07:38:00'),
(3, 'Boyra Cross Road',     'Near the government college',                   5, '07:48:00'),
(3, 'Boyra Bazar',          'Final stop at the market gate',                 6, '07:55:00'),
-- Route 4 : KUET Campus - Rupsha
(4, 'KUET Main Campus',     'Departure point beside the central auditorium', 1, '06:50:00'),
(4, 'KUET Main Gate',       'Fulbarigate side entrance',                     2, '06:55:00'),
(4, 'Daulatpur Bus Stand',  'Main junction of Daulatpur',                    3, '07:08:00'),
(4, 'Shibbari Mor',         'Central intersection of Khulna city',           4, '07:25:00'),
(4, 'Moylapota',            'Beside the city corporation office',            5, '07:35:00'),
(4, 'Rupsha Ferry Ghat',    'Final stop at the river bank',                  6, '07:55:00'),
-- Route 5 : KUET Campus - Daulatpur
(5, 'KUET Main Campus',     'Departure point beside the administrative building', 1, '08:00:00'),
(5, 'KUET Main Gate',       'Fulbarigate side entrance',                     2, '08:05:00'),
(5, 'Fulbarigate Bazar',    'Opposite the local market',                     3, '08:10:00'),
(5, 'Daulatpur Bazar',      'Final stop beside the railway crossing',        4, '08:20:00'),
-- Route 6 : KUET Campus - Zero Point
(6, 'KUET Main Campus',     'Departure point beside the central auditorium', 1, '06:45:00'),
(6, 'Daulatpur Bus Stand',  'Main junction of Daulatpur',                    2, '07:00:00'),
(6, 'Notun Rasta',          'Near the flyover crossing',                     3, '07:12:00'),
(6, 'Gollamari',            'Beside Khulna University main gate',            4, '07:30:00'),
(6, 'Zero Point',           'Final stop on the Batiaghata road',             5, '07:55:00'),
-- Route 7 : KUET Campus - Jessore Road (inactive)
(7, 'KUET Main Campus',     'Departure point beside the central auditorium', 1, '06:40:00'),
(7, 'Fulbarigate Bazar',    'Opposite the local market',                     2, '06:50:00'),
(7, 'Giljhora',             'Highway side stoppage',                         3, '07:05:00'),
(7, 'Phultala Bazar',       'Final stop on the Khulna-Jessore highway',      4, '07:35:00');

-- 3.5 SCHEDULES  (one bus + one route per schedule) -------------------------
INSERT INTO `schedules`
(`bus_id`, `route_id`, `departure_time`, `arrival_time`, `operating_days`, `status`, `notes`) VALUES
(1, 1, '07:00:00', '07:58:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Morning shuttle towards the city'),
(1, 1, '17:15:00', '18:15:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Evening return trip to the campus'),
(2, 2, '07:15:00', '07:50:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Morning trip'),
(2, 2, '16:45:00', '17:25:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Afternoon return trip'),
(3, 3, '07:10:00', '07:55:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Morning trip via Nirala'),
(3, 3, '17:00:00', '17:50:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday', 'Active', 'Evening trip via Nirala'),
(4, 5, '08:00:00', '08:20:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Faculty AC coach - morning'),
(4, 5, '16:30:00', '16:52:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Faculty AC coach - afternoon'),
(5, 5, '08:30:00', '08:52:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Officers and staff trip'),
(6, 4, '06:50:00', '07:55:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Long morning route to Rupsha'),
(6, 4, '18:00:00', '19:05:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Evening laboratory return trip'),
(9, 6, '06:45:00', '07:55:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday', 'Active', 'Serves the Batiaghata side'),
(9, 6, '17:30:00', '18:40:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday', 'Active', 'Return trip to Zero Point'),
(7, 1, '12:30:00', '13:30:00', 'Friday', 'Active', 'Weekend trip for hall residents'),
(7, 3, '09:30:00', '10:15:00', 'Saturday,Tuesday,Thursday', 'Suspended', 'Additional trip, suspended until further notice');

-- 3.6 ANNOUNCEMENTS ---------------------------------------------------------
INSERT INTO `announcements`
(`title`, `short_description`, `content`, `priority`, `status`, `published_on`) VALUES
('Revised bus schedule from the new semester',
 'All morning trips of routes K-01, K-02 and K-03 will start 15 minutes earlier from the beginning of the new semester.',
 'The Transport Office has revised the departure times of the morning shuttles so that students can reach their first class on time. From the first day of the new semester, buses on routes K-01 (Sonadanga), K-02 (Khalishpur) and K-03 (Boyra Bazar) will leave their starting points fifteen minutes earlier than the previous timetable. Evening return trips remain unchanged. Students are requested to check the Schedules page of this portal for the exact departure time of their own route before travelling.',
 'Important', 'Published', '2026-08-24'),

('Holiday transport arrangement',
 'A limited bus service will operate during the semester break for hall residents and staff on duty.',
 'During the semester break the regular shuttle service will be reduced. Only one trip per day will operate on routes K-01 and K-05. The trip will leave the main campus at 10:00 AM and return from Sonadanga at 4:30 PM. Hall residents and staff members who need transport outside these hours are requested to inform the Transport Office at least one day in advance so that arrangements can be made.',
 'Normal', 'Published', '2026-08-18'),

('KUET Bus-08 withdrawn for maintenance',
 'Bus-08 has been sent to the university workshop for an engine overhaul; its trips are covered by Bus-07.',
 'KUET Bus-08 has been withdrawn from service for a scheduled engine overhaul and brake replacement. During this period the additional trips normally operated by this bus will be covered by KUET Bus-07. The maintenance work is expected to finish within two weeks. Passengers may notice a slightly different seating capacity on the affected trips.',
 'Normal', 'Published', '2026-08-11'),

('Emergency notice: route K-07 suspended',
 'The Phultala route is suspended because of road repair work on the Khulna-Jessore highway.',
 'Because of ongoing road repair work on the Khulna-Jessore highway, route K-07 (KUET Campus - Phultala) has been suspended with immediate effect. Staff members who normally use this route are advised to use route K-05 up to Daulatpur and continue their journey with local transport. The service will resume as soon as the highway authority completes the repair work. We regret the inconvenience caused.',
 'Urgent', 'Published', '2026-08-06'),

('New stoppage added on route K-04',
 'Moylapota has been added as an official stoppage on the Rupsha route.',
 'Following requests from students living near the city corporation office, Moylapota has been added as an official stoppage on route K-04 (KUET Campus - Rupsha Ferry Ghat). The stoppage is placed between Shibbari Mor and Rupsha Ferry Ghat. The overall journey time of the route has increased by approximately five minutes. The updated stop list is available on the route details page of this portal.',
 'Normal', 'Published', '2026-07-29'),

('Safety guidelines for bus passengers',
 'Please follow these six simple rules while boarding, travelling on and leaving a university bus.',
 'For the safety of all passengers the Transport Office requests everyone to follow these guidelines. Wait for the bus to stop completely before boarding. Do not stand near the doors while the bus is moving. Keep the emergency exit free of bags at all times. Do not put any part of your body outside the window. Report any mechanical problem to the driver or to the Transport Office immediately. Carry your university identity card whenever you travel on a university bus.',
 'Important', 'Published', '2026-07-20'),

('Draft: winter timetable under review',
 'A revised winter timetable is being prepared and will be published after approval.',
 'A revised timetable for the winter months is currently being prepared by the Transport Office. The draft proposes a ten minute delay for all morning trips because of reduced visibility in foggy weather. The proposal is under review by the transport committee and will be published on this portal once it has been approved.',
 'Normal', 'Draft', '2026-08-28');

-- 3.7 FACILITIES + BUS <-> FACILITY LINKS -----------------------------------
INSERT INTO `facilities` (`id`, `name`, `icon_key`) VALUES
(1, 'Cushioned Seats',    'seat'),
(2, 'Air Conditioning',   'wifi'),
(3, 'First Aid Box',      'shield'),
(4, 'GPS Tracking',       'gps'),
(5, 'Emergency Exit',     'alert'),
(6, 'Luggage Rack',       'grid'),
(7, 'Fire Extinguisher',  'shield');

INSERT INTO `bus_facilities` (`bus_id`, `facility_id`) VALUES
(1,1),(1,3),(1,5),(1,6),(1,7),
(2,1),(2,3),(2,5),(2,7),
(3,1),(3,3),(3,4),(3,5),(3,6),
(4,1),(4,2),(4,3),(4,4),(4,5),(4,7),
(5,1),(5,3),(5,5),(5,7),
(6,1),(6,3),(6,5),(6,6),
(7,1),(7,3),(7,5),(7,6),(7,7),
(8,1),(8,3),(8,5),
(9,1),(9,3),(9,4),(9,5),
(10,1),(10,2),(10,3),(10,5);

-- 3.8 A COUPLE OF CONTACT MESSAGES ------------------------------------------
INSERT INTO `contact_messages` (`name`, `email`, `subject`, `message`, `is_read`) VALUES
('Tanvir Ahmed', 'tanvir@stud.kuet.ac.bd', 'Request for an extra evening trip',
 'Many students of the evening laboratory sessions finish after 6:30 PM and miss the last bus on route K-03. Could an extra trip be arranged?', 0),
('Shirin Sultana', 'shirin@kuet.ac.bd', 'Faculty bus pick-up point',
 'Is it possible to add a pick-up point near Nirala Cross Road for the faculty bus on route K-05?', 1);

-- 3.9 STARTING ACTIVITY LOG -------------------------------------------------
INSERT INTO `activity_logs` (`user_id`, `action`, `description`) VALUES
(1, 'System', 'Database installed with the initial KUET Bus Service sample data.');

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================================================
--  END OF FILE
-- ===========================================================================
