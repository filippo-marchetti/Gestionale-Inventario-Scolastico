-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 29, 2025 alle 22:57
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
  `scuola_appartenenza` varchar(10) NOT NULL,
  `password` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `admin`
--

INSERT INTO `admin` (`username`, `nome`, `cognome`, `email`, `scuola_appartenenza`, `password`) VALUES
('admin', 'admin', 'admin', 'admin@gmail.com', 'REIS00400D', '1234');

-- --------------------------------------------------------

--
-- Struttura della tabella `aula`
--

CREATE TABLE `aula` (
  `ID_aula` varchar(20) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `stato` varchar(10) NOT NULL,
  `tipologia` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

--
-- Dump dei dati per la tabella `aula`
--

INSERT INTO `aula` (`ID_aula`, `descrizione`, `stato`, `tipologia`) VALUES
('29', 'aula ', 'attiva', 'aula'),
('A7', 'aula ', 'attiva', 'Aula'),
('INFO10', 'laboratorio', 'attiva', 'laboratorio'),
('INFO2', 'laboratorio', 'attiva', 'laboratorio'),
('INFO4', 'laboratorio', 'attiva', 'Laboratorio'),
('magazzino', 'Aula magazzino di sistema', 'attiva', 'magazzino');

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
('banco', 'integro', 0.0000000000),
('Computer', 'integro', 0.0049999999),
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
('1', 'PC10', 'Computer', 'pc', 'archiviato', 50.00, 'INFO10'),
('12345', 'allah', 'banco', 'solido', 'scartato', 0.00, NULL),
('12345678', 'Proiettore5', 'proiettore', 'funziona bene', 'scartato', 50.00, NULL),
('3', 'banco', 'banco', 'solido', 'archiviato', 0.00, 'INFO10'),
('4', 'PC12', 'Computer', 'pc', 'archiviato', 0.00, NULL),
('5', 'PC9', 'Computer', 'pc', 'scartato', 0.00, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `inventario`
--

CREATE TABLE `inventario` (
  `codice_inventario` varchar(15) NOT NULL,
  `data_inventario` datetime DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `ID_aula` varchar(20) NOT NULL,
  `scuola_appartenenza` varchar(10) DEFAULT NULL,
  `ID_tecnico` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `riga_inventario`
--

CREATE TABLE `riga_inventario` (
  `ID_riga_inventario` int(11) NOT NULL,
  `codice_dotazione` varchar(50) DEFAULT NULL,
  `codice_inventario` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_roman_ci;

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
('aaaaaaa', 'aaaaaaa', 'aaaaaaaa', 'aaaaaa@gmail.com', '1', 'attesa', 'REIS00400D'),
('FilippoTecnico', 'Filippo', 'Sicilia', 'filiipomatchetti@morto.com', 'ligma', 'attivo', 'REIS00400D');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `admin_ibfk_1` (`scuola_appartenenza`);

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
  ADD KEY `scuola_appartenenza` (`scuola_appartenenza`),
  ADD KEY `inventario_ibfk_2` (`ID_aula`),
  ADD KEY `inventario_ibfk_4` (`ID_tecnico`);

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
  MODIFY `ID_riga_inventario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`scuola_appartenenza`) REFERENCES `scuola` (`codice_meccanografico`);

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
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`scuola_appartenenza`) REFERENCES `scuola` (`codice_meccanografico`),
  ADD CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`ID_aula`) REFERENCES `aula` (`ID_aula`),
  ADD CONSTRAINT `inventario_ibfk_3` FOREIGN KEY (`ID_tecnico`) REFERENCES `utente` (`username`),
  ADD CONSTRAINT `inventario_ibfk_4` FOREIGN KEY (`ID_tecnico`) REFERENCES `admin` (`username`);

--
-- Limiti per la tabella `riga_inventario`
--
ALTER TABLE `riga_inventario`
  ADD CONSTRAINT `riga_inventario_ibfk_1` FOREIGN KEY (`codice_dotazione`) REFERENCES `dotazione` (`codice`),
  ADD CONSTRAINT `riga_inventario_ibfk_2` FOREIGN KEY (`codice_inventario`) REFERENCES `inventario` (`codice_inventario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
