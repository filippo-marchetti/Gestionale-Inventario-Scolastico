-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 07, 2025 alle 12:53
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `istitutosdarzo`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `admin`
--

CREATE TABLE `admin` (
  `username` varchar(50) NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `cognome` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `admin`
--

INSERT INTO `admin` (`username`, `nome`, `cognome`, `email`, `password`) VALUES
('admin', 'admin', 'admin', 'admin@gmail.com', '1234');

-- --------------------------------------------------------

--
-- Struttura della tabella `aula`
--

CREATE TABLE `aula` (
  `ID_aula` varchar(20) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `tipologia` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `aula`
--

INSERT INTO `aula` (`ID_aula`, `descrizione`, `tipologia`) VALUES
('A7', 'nn', 'Aula'),
('INFO4', 'BOP', 'Laboratorio');

-- --------------------------------------------------------

--
-- Struttura della tabella `categoria`
--

CREATE TABLE `categoria` (
  `ID_categoria` varchar(50) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `indice_decadimento` float(10,10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `categoria`
--

INSERT INTO `categoria` (`ID_categoria`, `descrizione`, `indice_decadimento`) VALUES
('Computer', 'john', 0.0049999999);

-- --------------------------------------------------------

--
-- Struttura della tabella `dotazione`
--

CREATE TABLE `dotazione` (
  `codice` varchar(50) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `stato` varchar(20) DEFAULT NULL,
  `prezzo_stimato` float(10,2) DEFAULT NULL,
  `ID_aula` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `dotazione`
--

INSERT INTO `dotazione` (`codice`, `nome`, `categoria`, `descrizione`, `stato`, `prezzo_stimato`, `ID_aula`) VALUES
('SA08', 'PC0', 'Computer', 'dcv', 'Presente', 50.00, 'INFO4'),
('SA09', 'PC7', 'Computer', 'dvv', 'Presente', 30.00, 'A7'),
('SA10', 'PC9', 'Computer', 'dc', 'Presente', 50.00, 'INFO4');

-- --------------------------------------------------------

--
-- Struttura della tabella `inventario`
--

CREATE TABLE `inventario` (
  `codice_inventario` varchar(15) NOT NULL,
  `data_inventario` date DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `scuola_appartenenza` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `inventario`
--

INSERT INTO `inventario` (`codice_inventario`, `data_inventario`, `descrizione`, `scuola_appartenenza`) VALUES
('120400', '2024-06-13', 'sss', 'REIS00400D'),
('120956', '2024-06-05', 'dai c\'andom', 'REIS00400D');

-- --------------------------------------------------------

--
-- Struttura della tabella `riga_inventario`
--

CREATE TABLE `riga_inventario` (
  `ID_riga_inventario` int(11) NOT NULL,
  `codice_dotazione` varchar(50) DEFAULT NULL,
  `ID_aula` varchar(20) DEFAULT NULL,
  `codice_inventario` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `riga_inventario`
--

INSERT INTO `riga_inventario` (`ID_riga_inventario`, `codice_dotazione`, `ID_aula`, `codice_inventario`) VALUES
(1, '0000', 'INFO4', '120956'),
(2, '0000', 'INFO2', '120400');

-- --------------------------------------------------------

--
-- Struttura della tabella `scuola`
--

CREATE TABLE `scuola` (
  `codice_meccanografico` varchar(10) NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `indirizzo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `scuola`
--

INSERT INTO `scuola` (`codice_meccanografico`, `nome`, `indirizzo`) VALUES
('REIS00400D', 'Silvio D\'Arzo', 'via di casa mia');

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `username` varchar(50) NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `cognome` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `stato` varchar(20) NOT NULL,
  `scuola_appartenenza` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`username`, `nome`, `cognome`, `email`, `password`, `stato`, `scuola_appartenenza`) VALUES
('tecnico', 'ivan', 'il terribile', 'tec@gmail.com', '1234', 'attivo', 'REIS00400D');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `aula`
--
ALTER TABLE `aula`
  ADD PRIMARY KEY (`ID_aula`);

--
-- Indici per le tabelle `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`ID_categoria`);

--
-- Indici per le tabelle `dotazione`
--
ALTER TABLE `dotazione`
  ADD PRIMARY KEY (`codice`),
  ADD KEY `ID_aula` (`ID_aula`),
  ADD KEY `categoria` (`categoria`);

--
-- Indici per le tabelle `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`codice_inventario`),
  ADD KEY `scuola_appartenenza` (`scuola_appartenenza`);

--
-- Indici per le tabelle `riga_inventario`
--
ALTER TABLE `riga_inventario`
  ADD PRIMARY KEY (`ID_riga_inventario`),
  ADD KEY `codice_dotazione` (`codice_dotazione`),
  ADD KEY `ID_aula` (`ID_aula`),
  ADD KEY `codice_inventario` (`codice_inventario`);

--
-- Indici per le tabelle `scuola`
--
ALTER TABLE `scuola`
  ADD PRIMARY KEY (`codice_meccanografico`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `scuola_appartenenza` (`scuola_appartenenza`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `riga_inventario`
--
ALTER TABLE `riga_inventario`
  MODIFY `ID_riga_inventario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `dotazione`
--
ALTER TABLE `dotazione`
  ADD CONSTRAINT `dotazione_ibfk_1` FOREIGN KEY (`ID_aula`) REFERENCES `aula` (`ID_aula`),
  ADD CONSTRAINT `dotazione_ibfk_2` FOREIGN KEY (`categoria`) REFERENCES `categoria` (`ID_categoria`);

--
-- Limiti per la tabella `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`scuola_appartenenza`) REFERENCES `scuola` (`codice_meccanografico`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
