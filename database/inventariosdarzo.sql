-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 18, 2025 alle 17:59
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
-- Database: `inventariosdarzo`
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
('29', 'lo fanno', 'aula'),
('A7', 'nn', 'Aula'),
('INFO2', 'whatsapp', 'laboratorio'),
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
('banco', 'ligma', 0.0000000000),
('Computer', 'john', 0.0049999999),
('proiettore', 'proietta le cose', 0.5000000000);

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
('1', 'PC10', 'Computer', 'pc', 'scartato', 50.00, 'INFO4'),
('12345', 'allah', 'banco', 'allah', 'archiviato', 0.00, NULL),
('12345678', 'Proiettore5', 'proiettore', 's', 'presente', 50.00, '29'),
('3', 'banco', 'banco', 'pll', 'archiviato', 0.00, 'INFO4'),
('4', 'PC12', 'Computer', 'iii', 'scartato', 50.00, 'INFO4'),
('5', 'PC9', 'Computer', 'fgf', 'archiviato', 0.00, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `inventario`
--

CREATE TABLE `inventario` (
  `codice_inventario` varchar(15) NOT NULL,
  `data_inventario` date DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `ID_aula` varchar(20) NOT NULL,
  `scuola_appartenenza` varchar(10) DEFAULT NULL,
  `ID_tecnico` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `inventario`
--

INSERT INTO `inventario` (`codice_inventario`, `data_inventario`, `descrizione`, `ID_aula`, `scuola_appartenenza`, `ID_tecnico`) VALUES
('497029', '2025-05-16', '88', 'INFO4', NULL, ''),
('683642', '2025-05-16', '77', 'INFO2', NULL, '');

-- --------------------------------------------------------

--
-- Struttura della tabella `riga_inventario`
--

CREATE TABLE `riga_inventario` (
  `ID_riga_inventario` int(11) NOT NULL,
  `codice_dotazione` varchar(50) DEFAULT NULL,
  `codice_inventario` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `riga_inventario`
--

INSERT INTO `riga_inventario` (`ID_riga_inventario`, `codice_dotazione`, `codice_inventario`) VALUES
(19, '1', '956194'),
(20, '1', '683642'),
(21, '1', '497029');

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
('FilippoTecnico', 'Filippo', 'Sicilia', 'filiipomatchetti@morto.com', 'ligma', 'attivo', 'REIS00400D');

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
  MODIFY `ID_riga_inventario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
