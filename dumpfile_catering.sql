-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Gegenereerd op: 27 jul 2023 om 00:15
-- Serverversie: 5.7.39
-- PHP-versie: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `catering`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Facility`
--

CREATE TABLE `Facility` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Gegevens worden geëxporteerd voor tabel `Facility`
--

INSERT INTO `Facility` (`id`, `name`, `creation_date`, `location_id`) VALUES
(1, 'Facility A', '2023-07-26 00:13:16', 1),
(2, 'Facility B', '2023-07-26 00:13:16', 2),
(3, 'Facility C', '2023-07-26 00:13:16', 3),
(4, 'Facility D', '2023-07-26 00:13:16', 4),
(5, 'Facility E', '2023-07-26 00:13:16', 5),
(8, 'Facility L', '2023-07-27 00:05:01', 7),
(9, 'Facility M', '2023-07-27 00:05:08', 8),
(10, 'Facility N', '2023-07-27 00:05:12', 9),
(13, 'Facility O', '2023-07-27 00:09:28', 12),
(14, 'Facility P', '2023-07-27 00:09:32', 13),
(15, 'Facility Q', '2023-07-27 00:09:35', 14);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Facility_Tag`
--

CREATE TABLE `Facility_Tag` (
  `facility_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Gegevens worden geëxporteerd voor tabel `Facility_Tag`
--

INSERT INTO `Facility_Tag` (`facility_id`, `tag_id`) VALUES
(1, 1),
(8, 1),
(9, 1),
(10, 1),
(13, 1),
(14, 1),
(15, 1),
(2, 2),
(8, 2),
(9, 2),
(10, 2),
(13, 2),
(14, 2),
(15, 2),
(3, 3),
(4, 4),
(5, 5);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Location`
--

CREATE TABLE `Location` (
  `id` int(11) NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip_code` varchar(11) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Gegevens worden geëxporteerd voor tabel `Location`
--

INSERT INTO `Location` (`id`, `city`, `address`, `zip_code`, `country_code`, `phone_number`) VALUES
(1, 'Amsterdam', 'Damrak 1', '1012LG', 'NL', '+31206251333'),
(2, 'Rotterdam', 'Coolsingel 1', '3012AA', 'NL', '+31102140214'),
(3, 'The Hague', 'Korte Voorhout 7', '2511CW', 'NL', '+31703456456'),
(4, 'Utrecht', 'Domplein 29', '3512JE', 'NL', '+31302536725'),
(5, 'Eindhoven', 'Stratumseind 32', '5611ET', 'NL', '+31402402340'),
(7, 'CityName', 'Street Name', 'Zip Code', 'NL', 'Phone Number'),
(8, 'CityName', 'Street Name', 'Zip Code', 'NL', 'Phone Number'),
(9, 'CityName', 'Street Name', 'Zip Code', 'NL', 'Phone Number'),
(12, 'CityName', 'Street Name', 'Zip Code', 'NL', 'Phone Number'),
(13, 'CityName', 'Street Name', 'Zip Code', 'NL', 'Phone Number'),
(14, 'CityName', 'Street Name', 'Zip Code', 'NL', 'Phone Number');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Tag`
--

CREATE TABLE `Tag` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Gegevens worden geëxporteerd voor tabel `Tag`
--

INSERT INTO `Tag` (`id`, `name`) VALUES
(1, 'Tag1'),
(2, 'Tag2'),
(3, 'Tag3'),
(4, 'Tag4'),
(5, 'Tag5');

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `Facility`
--
ALTER TABLE `Facility`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexen voor tabel `Facility_Tag`
--
ALTER TABLE `Facility_Tag`
  ADD PRIMARY KEY (`facility_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexen voor tabel `Location`
--
ALTER TABLE `Location`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Tag`
--
ALTER TABLE `Tag`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `Facility`
--
ALTER TABLE `Facility`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT voor een tabel `Location`
--
ALTER TABLE `Location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT voor een tabel `Tag`
--
ALTER TABLE `Tag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `Facility`
--
ALTER TABLE `Facility`
  ADD CONSTRAINT `facility_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `Location` (`id`);

--
-- Beperkingen voor tabel `Facility_Tag`
--
ALTER TABLE `Facility_Tag`
  ADD CONSTRAINT `facility_tag_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `Facility` (`id`),
  ADD CONSTRAINT `facility_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `Tag` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
