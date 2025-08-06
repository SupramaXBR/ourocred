-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 30/10/2024 às 15:00
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ourocreddb`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `IDECLI` varchar(10) NOT NULL,
  `CODCLI` int(11) NOT NULL,
  `CPFCLI` varchar(15) NOT NULL,
  `RGCLI` varchar(10) NOT NULL,
  `NOMCLI` varchar(64) NOT NULL,
  `MAECLI` varchar(64) NOT NULL,
  `NUNTEL` varchar(15) NOT NULL,
  `CEPCLI` varchar(10) NOT NULL,
  `ENDCLI` varchar(64) NOT NULL,
  `NUNCSA` varchar(6) NOT NULL,
  `CPLEND` varchar(64) NOT NULL,
  `BAICLI` varchar(32) NOT NULL,
  `UFDCLI` varchar(2) NOT NULL,
  `CODMUNIBGE` varchar(7) NOT NULL,
  `MUNCLI` varchar(32) NOT NULL,
  `MD5PW` varchar(64) NOT NULL,
  `IMG64` blob NOT NULL,
  `DTAINS` date NOT NULL,
  `DTAALT` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`IDECLI`,`CODCLI`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
