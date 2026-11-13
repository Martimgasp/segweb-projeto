-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: bolsas
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `bolsas`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bolsas` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `bolsas`;

--
-- Table structure for table `bolsas`
--

DROP TABLE IF EXISTS `bolsas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bolsas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `designacao` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_apoio` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_estudo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `requisitos` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `montante` decimal(10,2) NOT NULL,
  `prazo_candidatura` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bolsas_tipo` (`tipo_apoio`),
  KEY `idx_bolsas_area` (`area_estudo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `candidaturas`
--

DROP TABLE IF EXISTS `candidaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidaturas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `utilizador_id` int unsigned NOT NULL,
  `bolsa_id` int unsigned NOT NULL,
  `tecnico_id` int unsigned DEFAULT NULL,
  `instituicao` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rendimento_agregado` decimal(10,2) NOT NULL,
  `iban` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('submetida','em_analise','aprovada','rejeitada','a_aguardar_documentacao','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submetida',
  `data_submissao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_candidatura_user_bolsa` (`utilizador_id`,`bolsa_id`),
  KEY `idx_cand_estado` (`estado`),
  KEY `idx_cand_tecnico` (`tecnico_id`),
  KEY `fk_cand_bolsa` (`bolsa_id`),
  CONSTRAINT `fk_cand_bolsa` FOREIGN KEY (`bolsa_id`) REFERENCES `bolsas` (`id`),
  CONSTRAINT `fk_cand_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `utilizadores` (`id`),
  CONSTRAINT `fk_cand_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `candidatura_id` int unsigned NOT NULL,
  `esclarecimento_id` int unsigned DEFAULT NULL,
  `enviado_por` int unsigned NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_armazenado` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` int unsigned NOT NULL,
  `hash_sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enviado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_armazenado` (`nome_armazenado`),
  KEY `idx_doc_cand` (`candidatura_id`),
  KEY `fk_doc_escl` (`esclarecimento_id`),
  KEY `fk_doc_user` (`enviado_por`),
  CONSTRAINT `fk_doc_cand` FOREIGN KEY (`candidatura_id`) REFERENCES `candidaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_escl` FOREIGN KEY (`esclarecimento_id`) REFERENCES `esclarecimentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_doc_user` FOREIGN KEY (`enviado_por`) REFERENCES `utilizadores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `esclarecimentos`
--

DROP TABLE IF EXISTS `esclarecimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `esclarecimentos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `candidatura_id` int unsigned NOT NULL,
  `autor_id` int unsigned NOT NULL,
  `tipo` enum('pedido','resposta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_escl_cand` (`candidatura_id`),
  KEY `fk_escl_autor` (`autor_id`),
  CONSTRAINT `fk_escl_autor` FOREIGN KEY (`autor_id`) REFERENCES `utilizadores` (`id`),
  CONSTRAINT `fk_escl_cand` FOREIGN KEY (`candidatura_id`) REFERENCES `candidaturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historico_estados`
--

DROP TABLE IF EXISTS `historico_estados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_estados` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `candidatura_id` int unsigned NOT NULL,
  `estado_anterior` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_novo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alterado_por` int unsigned NOT NULL,
  `alterado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hist_cand` (`candidatura_id`),
  KEY `fk_hist_user` (`alterado_por`),
  CONSTRAINT `fk_hist_cand` FOREIGN KEY (`candidatura_id`) REFERENCES `candidaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_user` FOREIGN KEY (`alterado_por`) REFERENCES `utilizadores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `observacoes_internas`
--

DROP TABLE IF EXISTS `observacoes_internas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `observacoes_internas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `candidatura_id` int unsigned NOT NULL,
  `tecnico_id` int unsigned NOT NULL,
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_obs_cand` (`candidatura_id`),
  KEY `fk_obs_tecnico` (`tecnico_id`),
  CONSTRAINT `fk_obs_cand` FOREIGN KEY (`candidatura_id`) REFERENCES `candidaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obs_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `utilizadores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `utilizadores`
--

DROP TABLE IF EXISTS `utilizadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilizadores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `perfil` enum('candidato','tecnico') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'candidato',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `morada` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tentativas_falhadas` tinyint unsigned NOT NULL DEFAULT '0',
  `bloqueado_ate` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_utilizadores_username` (`username`),
  UNIQUE KEY `uq_utilizadores_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-13 13:18:13
