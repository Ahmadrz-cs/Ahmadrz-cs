/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: db    Database: crowddb
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `address1` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `address2` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `address3` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `city` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `region` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `postCode` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `country` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_6FCA7516A76ED395` (`user_id`),
  CONSTRAINT `FK_6FCA7516A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES
(1,1,'Manchester House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(2,2,'Salford House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(3,3,'Castle Ewell House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(4,4,'London House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(5,14,'London House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(6,16,'London House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(7,19,'bc_investor1 House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(8,20,'bc_investor2 House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(9,21,'bc_investor3 House','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(10,25,'Public House 1','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(11,26,'Public House 2','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(12,32,'200','Julius Road',NULL,'Bristol','England','BS78EU','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(13,31,'236','Julius Road',NULL,'Bristol','England','BA133BN','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(14,30,'148','Julius Road',NULL,'Bristol','England','BA133BN','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(15,29,'201','Julius Road',NULL,'Bristol','England','BS78EU','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(16,28,'200','Julius Road',NULL,'Bristol','England','BS78EU','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(17,27,'200','Julius Road',NULL,'Bristol','England','BS78EU','GB',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(18,35,'registration-complete','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL),
(19,36,'approved-user','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(20,37,'user204','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(21,38,'company-min-verified@crowdtek.co.uk','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(22,NULL,'email-not-verified','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(23,NULL,'Email-verified','1 London Road',NULL,'London','England','E1','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(24,39,'Woodside','Leigh On Sea',NULL,'London','England','SS9 4QU','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(25,40,'Salford House','1 London Road',NULL,'London','England','E14 5AB','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(26,41,'Castle Ewell House','1 London Road',NULL,'London','England','E14 5AB','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(27,42,'StampDuty House','1 London Road',NULL,'London','England','E14 5AB','GB',NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(28,43,'Ben Charlton House','52 Wood Lane',NULL,'BAGILLT','England','CH6 2NL','GB',NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(29,44,'Holly Bird House','29 Park End St',NULL,'London','England','RH13 8BX','GB',NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(30,45,'Ben Man House','1 London Road',NULL,'London','England','E14 5AB','GB',NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(31,53,'Holly Bird House','1 London Road',NULL,'London','England','E14 5AB','GB',NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL);
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `app_setting`
--

DROP TABLE IF EXISTS `app_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_setting`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `app_setting` WRITE;
/*!40000 ALTER TABLE `app_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_setting` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `assessment_response`
--

DROP TABLE IF EXISTS `assessment_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_id` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_420D1ADDDD3DD5F1` (`assessment_id`),
  KEY `IDX_420D1ADD1E27F6BF` (`question_id`),
  KEY `IDX_420D1ADD998666D1` (`choice_id`),
  KEY `IDX_420D1ADD3174800F` (`createdBy_id`),
  KEY `IDX_420D1ADD65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_44FDA12E1E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`),
  CONSTRAINT `FK_44FDA12E3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_44FDA12E65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_44FDA12E998666D1` FOREIGN KEY (`choice_id`) REFERENCES `question_choice` (`id`),
  CONSTRAINT `FK_44FDA12EDD3DD5F1` FOREIGN KEY (`assessment_id`) REFERENCES `user_assessment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_response`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `assessment_response` WRITE;
/*!40000 ALTER TABLE `assessment_response` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_response` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `asset_add_fields`
--

DROP TABLE IF EXISTS `asset_add_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_add_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `fieldKey` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `value` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_54DA41FE5DA1941` (`asset_id`),
  CONSTRAINT `FK_54DA41FE5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_add_fields`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `asset_add_fields` WRITE;
/*!40000 ALTER TABLE `asset_add_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_add_fields` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `asset_addresses`
--

DROP TABLE IF EXISTS `asset_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `address2` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `address3` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `city` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `region` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `postCode` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `country` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `latitude` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `longitude` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_82F09D2C5DA1941` (`asset_id`),
  CONSTRAINT `FK_82F09D2C5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_addresses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `asset_addresses` WRITE;
/*!40000 ALTER TABLE `asset_addresses` DISABLE KEYS */;
INSERT INTO `asset_addresses` VALUES
(1,1,'Public Admin Asset 1London House','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,2,'Public Admin Asset 2London House','1 London Road',NULL,'London','England','E1','GB','-0.0836314','51.5187516',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,13,'London House','1 London Road',NULL,'London','England','E1','GB',NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(4,14,'London House','1 London Road',NULL,'London','England','E1','GB',NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(5,16,'London House','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(6,17,'London House - add1','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(7,18,'London House - add2','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(8,19,'London House  - add3','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(9,20,'London House  - add4','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(10,21,'London House  - add5','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(11,22,'Public Asset 1London House','1 London Road',NULL,'London','England','E1','GB','51.5187516','-0.0836314',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(12,23,'Public Asset 2London House','1 London Road',NULL,'London','England','E1','GB','-0.1458265','51.5311716',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(13,24,'Bristol Harbour','17 Wharf Tides',NULL,'Bristol','England','BR2 9TD','GB','51.450352','-2.607590',NULL,'2019-12-09 16:31:47','2020-03-24 19:02:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(14,25,'Bristol Harbour','21 Wharf Tides',NULL,'Bristol','England','BR2 9TD','GB','51.450358','-2.607590',NULL,'2019-12-09 16:32:39','2020-03-24 19:02:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(15,26,'Lodge de Lac','4 Verton Close','Derwentwater','Keswick','England','CA12 6HJ','GB','54.583075','-3.161549',NULL,'2019-12-09 16:33:36','2020-03-24 19:03:01','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(16,27,'Light at the Peninsula','5 Seanorth Cove',NULL,'Penzance','England','TR20 7BT','GB','50.065039','-5.713433',NULL,'2019-12-09 16:34:33','2020-03-24 19:03:07','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(17,28,'Wayward Plaza','4 Crossbank Road',NULL,'London','England','GW64 7HG','GB','51.511723','-0.081691',NULL,'2019-12-09 16:35:27','2020-03-24 19:03:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(18,29,'Partingdale House','35 Sutton Road',NULL,'Reading','England','RG1 8PQ','GB','51.457539','-0.970469',NULL,'2019-12-09 16:36:37','2020-03-24 19:03:28','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(19,30,'Kolness Guesthouse','8 Shiverton Road','Derwentwate','Okehampton','England','EX20 8JR','GB','50.736806','-3.998528',NULL,'2019-12-09 16:38:03','2020-03-24 19:03:36','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(20,31,'Royal Way Gardens','25 Royal Way',NULL,'Cambridge','England','CB2 4TM','GB','52.169423','0.118152',NULL,'2019-12-09 16:40:23','2020-03-24 19:03:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(21,32,'Sandfox Fields','6 Sandfox Fields',NULL,'Ashford','England','TN24 6RB','GB','51.146848','0.892883',NULL,'2019-12-11 15:24:41','2020-03-24 19:03:58','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(22,33,'Silverhood Down - Brighton','24 Silverhood Down',NULL,'Brighton','England','BN5 1GT','GB','50.834171','-0.157922',NULL,'2019-12-11 15:30:55','2020-03-24 19:04:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(23,34,'Apt 12, Clarence Hold','Clarence Way','Camden Town','London','England','NW1 7YH','GB','51.543887','-0.144751',NULL,'2019-12-11 16:17:08','2020-03-24 19:04:09','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(24,35,'20 King Street',NULL,NULL,'King\'s Lynn',NULL,'PE30 1ET','GB','52.75562','0.393727',NULL,'2026-04-29 15:30:46','2026-04-29 15:36:37','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `asset_addresses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `asset_docs`
--

DROP TABLE IF EXISTS `asset_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B533EE7FC33F7837` (`document_id`),
  KEY `IDX_B533EE7F5DA1941` (`asset_id`),
  CONSTRAINT `FK_B533EE7F5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_B533EE7FC33F7837` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_docs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `asset_docs` WRITE;
/*!40000 ALTER TABLE `asset_docs` DISABLE KEYS */;
INSERT INTO `asset_docs` VALUES
(1,1,4,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,2,5,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,1,6,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(4,2,7,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(5,3,14,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(6,3,15,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(7,16,19,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(8,17,20,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(9,18,21,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(10,19,22,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(11,20,23,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(12,21,24,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(13,21,25,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(14,22,26,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(15,23,27,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(16,23,28,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(17,22,29,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(18,22,31,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(19,23,32,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(20,24,46,NULL,'2019-12-09 17:16:34','2019-12-09 17:16:34','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(21,25,47,NULL,'2019-12-09 17:16:45','2019-12-09 17:16:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(22,26,48,NULL,'2019-12-09 17:16:54','2019-12-09 17:16:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(23,27,49,NULL,'2019-12-09 17:17:04','2019-12-09 17:17:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(24,28,50,NULL,'2019-12-09 17:17:38','2019-12-09 17:17:38','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(25,29,51,NULL,'2019-12-09 17:17:46','2019-12-09 17:17:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(26,30,52,NULL,'2019-12-09 17:17:55','2019-12-09 17:17:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(27,31,53,NULL,'2019-12-09 17:18:03','2019-12-09 17:18:03','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(28,27,61,NULL,'2019-12-09 17:32:38','2019-12-09 17:32:38','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(29,28,62,NULL,'2019-12-09 17:32:59','2019-12-09 17:32:59','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(30,26,63,NULL,'2019-12-09 17:34:05','2019-12-09 17:34:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(31,31,64,NULL,'2019-12-09 17:34:51','2019-12-09 17:34:51','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(32,24,65,NULL,'2019-12-09 17:35:35','2019-12-09 17:35:35','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(33,25,66,NULL,'2019-12-09 17:35:43','2019-12-09 17:35:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(34,30,67,NULL,'2019-12-09 17:36:20','2019-12-09 17:36:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(35,29,68,NULL,'2019-12-09 17:41:57','2019-12-09 17:41:57','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(36,32,73,NULL,'2019-12-11 15:41:46','2019-12-11 15:41:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(37,33,74,NULL,'2019-12-11 15:41:55','2019-12-11 15:41:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(39,33,76,NULL,'2019-12-11 15:42:12','2019-12-11 15:42:12','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(40,34,77,NULL,'2019-12-11 16:19:36','2019-12-11 16:19:36','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(41,34,78,NULL,'2019-12-11 16:19:45','2019-12-11 16:19:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(42,29,79,NULL,'2019-12-12 14:46:40','2019-12-12 14:46:40','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(43,29,80,NULL,'2019-12-12 14:48:35','2019-12-12 14:48:35','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(44,32,81,NULL,'2019-12-12 14:48:47','2019-12-12 14:48:47','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(45,29,82,NULL,'2019-12-12 14:50:52','2019-12-12 14:50:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(46,3,17,NULL,'2019-12-09 12:23:06','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(47,3,18,NULL,'2019-12-09 12:23:06','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(48,24,54,NULL,'2019-12-09 17:18:54','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(49,25,55,NULL,'2019-12-09 17:19:04','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(50,26,56,NULL,'2019-12-09 17:19:15','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(51,27,57,NULL,'2019-12-09 17:19:23','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(52,28,58,NULL,'2019-12-09 17:19:32','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(53,28,59,NULL,'2019-12-09 17:19:45','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(54,30,60,NULL,'2019-12-09 17:19:56','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `asset_docs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `asset_fee`
--

DROP TABLE IF EXISTS `asset_fee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_fee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `band` int(11) DEFAULT NULL,
  `fee` int(11) DEFAULT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FC43A7395DA1941` (`asset_id`),
  CONSTRAINT `FK_FC43A7395DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_fee`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `asset_fee` WRITE;
/*!40000 ALTER TABLE `asset_fee` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_fee` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `asset_members`
--

DROP TABLE IF EXISTS `asset_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `membertype` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_264E93A9A76ED395` (`user_id`),
  KEY `IDX_264E93A95DA1941` (`asset_id`),
  CONSTRAINT `FK_264E93A95DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_264E93A9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_members`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `asset_members` WRITE;
/*!40000 ALTER TABLE `asset_members` DISABLE KEYS */;
INSERT INTO `asset_members` VALUES
(1,1,1,'Author',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,2,1,'Member',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,1,2,'Author',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(4,10,13,'Member',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(5,4,3,'Member',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(6,4,4,'Member',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(7,1,22,'Author',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(8,1,23,'Author',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(9,26,23,'Member',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(10,1,24,'Author',NULL,'2019-12-09 16:31:47','2019-12-09 16:31:47','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(11,1,25,'Author',NULL,'2019-12-09 16:32:39','2019-12-09 16:32:39','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(12,1,26,'Author',NULL,'2019-12-09 16:33:36','2019-12-09 16:33:36','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(13,1,27,'Author',NULL,'2019-12-09 16:34:33','2019-12-09 16:34:33','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(14,1,28,'Author',NULL,'2019-12-09 16:35:27','2019-12-09 16:35:27','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(15,1,29,'Author',NULL,'2019-12-09 16:36:37','2019-12-09 16:36:37','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(16,1,30,'Author',NULL,'2019-12-09 16:38:03','2019-12-09 16:38:03','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(17,1,31,'Author',NULL,'2019-12-09 16:40:23','2019-12-09 16:40:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(18,1,32,'Author',NULL,'2019-12-11 15:24:41','2019-12-11 15:24:41','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(19,1,33,'Author',NULL,'2019-12-11 15:30:55','2019-12-11 15:30:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(20,1,34,'Author',NULL,'2019-12-11 16:17:08','2019-12-11 16:17:08','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(21,1,35,'Author',NULL,'2026-04-29 15:30:46','2026-04-29 15:30:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `asset_members` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `asset_status_log`
--

DROP TABLE IF EXISTS `asset_status_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `occuredAt` datetime NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `transitionedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_97B1DB3D5DA1941` (`asset_id`),
  KEY `IDX_97B1DB3D4A8EA82E` (`transitionedBy_id`),
  CONSTRAINT `FK_59FA4E335DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_97B1DB3D4A8EA82E` FOREIGN KEY (`transitionedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_status_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `asset_status_log` WRITE;
/*!40000 ALTER TABLE `asset_status_log` DISABLE KEYS */;
INSERT INTO `asset_status_log` VALUES
(1,1,'active',NULL,'2026-04-22 15:27:07','2026-04-22 15:27:13','2026-04-22 15:27:13',1),
(2,24,'active',NULL,'2026-04-22 15:35:13','2026-04-22 15:35:15','2026-04-22 15:35:15',1),
(3,25,'active',NULL,'2026-04-22 15:35:23','2026-04-22 15:35:25','2026-04-22 15:35:25',1),
(4,26,'active',NULL,'2026-04-22 15:35:32','2026-04-22 15:35:33','2026-04-22 15:35:33',1),
(5,27,'active',NULL,'2026-04-22 15:35:41','2026-04-22 15:35:43','2026-04-22 15:35:43',1),
(6,28,'active',NULL,'2026-04-22 15:35:50','2026-04-22 15:35:52','2026-04-22 15:35:52',1),
(7,29,'active',NULL,'2026-04-22 15:35:58','2026-04-22 15:36:00','2026-04-22 15:36:00',1),
(8,30,'active',NULL,'2026-04-22 15:36:06','2026-04-22 15:36:08','2026-04-22 15:36:08',1),
(9,31,'active',NULL,'2026-04-22 15:36:15','2026-04-22 15:36:17','2026-04-22 15:36:17',1),
(10,32,'active',NULL,'2026-04-22 15:36:26','2026-04-22 15:36:28','2026-04-22 15:36:28',1),
(11,33,'active',NULL,'2026-04-22 15:36:34','2026-04-22 15:36:36','2026-04-22 15:36:36',1),
(12,34,'active',NULL,'2026-04-22 15:36:40','2026-04-22 15:36:42','2026-04-22 15:36:42',1),
(13,35,'archived','Test archived','2026-04-29 15:38:15','2026-04-29 15:38:24','2026-04-29 15:38:24',1),
(14,24,'acquiring',NULL,'2026-04-29 15:47:33','2026-04-29 15:47:37','2026-04-29 15:47:37',1);
/*!40000 ALTER TABLE `asset_status_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `additionalType` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `alternateName` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `briefDescription` longtext DEFAULT NULL,
  `companyNumber` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `detailedDesc` longtext DEFAULT NULL,
  `displayName` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `legalName` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `orgEmail` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `sector` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `taxId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `telephone` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `fundingGoal` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `amountOfShares` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `setupFee` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `adminFee` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `managementFee` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `profitShare` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `stampDutyUser` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `assetType` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `investmentTerm` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `grossRentalReturnPA` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `netRentalReturnPA` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `grossCapitalAppreciation` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `netCapitalAppreciation` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `netCapitalAppreciationYield` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `gross_yield` decimal(10,2) DEFAULT NULL,
  `pointsOfInterest` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `sellRestricted` tinyint(4) NOT NULL DEFAULT 0,
  `pricePerShare` decimal(10,2) DEFAULT NULL,
  `visibility` int(11) NOT NULL DEFAULT 0,
  `mangoPayUserId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `mangoPayWalletId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `additional_wallet` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `contactPoint_id` int(11) DEFAULT NULL,
  `assetStatus_id` int(11) DEFAULT NULL,
  `expensesWalletId` varchar(255) DEFAULT NULL,
  `taxWalletId` varchar(255) DEFAULT NULL,
  `treasuryWalletId` varchar(255) DEFAULT NULL,
  `depositWalletId` varchar(255) DEFAULT NULL,
  `distributionWalletId` varchar(255) DEFAULT NULL,
  `financialYearStart` date DEFAULT NULL,
  `taskTracker_id` int(11) DEFAULT NULL,
  `netProjectedYield` decimal(8,4) DEFAULT NULL,
  `netProjectedIncome` decimal(15,2) DEFAULT NULL,
  `termStart` date DEFAULT NULL,
  `featured` int(11) NOT NULL DEFAULT 0,
  `buyRestricted` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_79D17D8E45ADDDFE` (`assetStatus_id`),
  UNIQUE KEY `UNIQ_79D17D8E725E4C97` (`taskTracker_id`),
  KEY `IDX_79D17D8ED7396C08` (`contactPoint_id`),
  CONSTRAINT `FK_79D17D8E45ADDDFE` FOREIGN KEY (`assetStatus_id`) REFERENCES `assets_status` (`id`),
  CONSTRAINT `FK_79D17D8E725E4C97` FOREIGN KEY (`taskTracker_id`) REFERENCES `task_tracker` (`id`),
  CONSTRAINT `FK_79D17D8ED7396C08` FOREIGN KEY (`contactPoint_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES
(1,'House 1',NULL,NULL,'Development Teams Test property for testing platform - House 1',NULL,'Development Teams Test property for testing platform - House 1','House 1',NULL,'orgemail@asset',NULL,NULL,NULL,'100000','100000','2.5','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\":\"train station\",\"longitude\":\"51.4413423\",\"latitude\":\"0.3692587\"}]',0,1.00,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 12:22:59','2026-04-22 16:03:17','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0511,5110.00,NULL,1,0),
(2,'Public Admin Asset 2',NULL,NULL,NULL,NULL,NULL,'Public Admin Asset 2',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\":\"Bog station\",\"longitude\":\"51.4413423\",\"latitude\":\"0.3692587\"}]',1,1.50,0,'18465323','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 12:22:59','2019-12-09 12:22:59','admin@crowdtek.co.uk',NULL,1,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(3,'Master Test',NULL,NULL,NULL,NULL,NULL,'Master Test',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,4,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(4,'Joany Conn',NULL,NULL,NULL,NULL,NULL,'Mr. Mack Schinner',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(5,'Mr. Raoul Mraz DDS',NULL,NULL,NULL,NULL,NULL,'Miss Jalyn Brakus Jr.',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(6,'Eveline Bosco I',NULL,NULL,NULL,NULL,NULL,'Madilyn Stracke',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(7,'Dr. Carolyn Schinner',NULL,NULL,NULL,NULL,NULL,'Gaston Hoeger',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(8,'Prof. Michele Lind',NULL,NULL,NULL,NULL,NULL,'Muhammad Mann',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(9,'Justus Altenwerth',NULL,NULL,NULL,NULL,NULL,'Ivah Hudson',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(10,'Percy Hayes',NULL,NULL,NULL,NULL,NULL,'Sammy Harris',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(11,'Cayla Stanton',NULL,NULL,NULL,NULL,NULL,'Mr. Tristin Ortiz II',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(12,'Cullen Tillman',NULL,NULL,NULL,NULL,NULL,'Tara Koelpin',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,0,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,5,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(13,'Christ Klocko',NULL,NULL,NULL,NULL,NULL,'Christ Klocko',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,2,'18465323',NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,14,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(14,'Elsie Thiel',NULL,NULL,NULL,NULL,NULL,'Elsie Thiel',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'18465323','19076889',NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,16,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(15,'Prof. Laila Corkery',NULL,NULL,NULL,NULL,NULL,'Prof. Laila Corkery',NULL,'orgemail13@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,NULL,NULL,1,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,4,15,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(16,'BC Asset with no Offering',NULL,NULL,NULL,NULL,'{ \"getInvestorCount\" : \"0\", \"getInvestmentCount\" : \"0\", \"getRaisedAmount\" : \"0\", \"getRaisedPercent\" : \"0\" }','BC Asset with no Offering',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1.50,0,NULL,NULL,NULL,1,'2019-12-09 12:23:08','2019-12-09 12:23:08','bc_investor1@test.com',NULL,19,16,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(17,'BC Asset with 1 Offering no investments',NULL,NULL,NULL,NULL,'{ \"getInvestorCount\" : \"0\", \"getInvestmentCount\" : \"0\", \"getRaisedAmount\" : \"0\", \"getRaisedPercent\" : \"0\" }','BC Asset with 1 Offering no investments',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'type1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1.50,0,NULL,NULL,NULL,1,'2019-12-09 12:23:08','2019-12-09 12:23:08','bc_investor1@test.com',NULL,19,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(18,'BC Asset with 1 Offering 1 Investment',NULL,NULL,NULL,NULL,'{ \"getInvestorCount\" : \"1\", \"getInvestmentCount\" : \"1\", \"getRaisedAmount\" : \"2000\", \"getRaisedPercent\" : \"100\" }','BC Asset with 1 Offering 1 Investment',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1.50,0,NULL,NULL,NULL,1,'2019-12-09 12:23:08','2019-12-09 12:23:08','bc_investor1@test.com',NULL,19,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(19,'BC Asset with 1 Offering 2 Investments',NULL,NULL,NULL,NULL,'{ \"getInvestorCount\" : \"2\", \"getInvestmentCount\" : \"2\", \"getRaisedAmount\" : \"2000\", \"getRaisedPercent\" : \"50\" }','BC Asset with 1 Offering 2 Investments',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1.50,0,NULL,NULL,NULL,1,'2019-12-09 12:23:08','2019-12-09 12:23:08','bc_investor1@test.com',NULL,19,19,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(20,'BC Asset with 1 Offering 3 Investments 2 Users',NULL,NULL,NULL,NULL,'{ \"getInvestorCount\" : \"2\", \"getInvestmentCount\" : \"3\", \"getRaisedAmount\" : \"3000\", \"getRaisedPercent\" : \"25\" }','BC Asset with 1 Offering 3 Investments 2 Users',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Commercial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1.50,0,NULL,NULL,NULL,1,'2019-12-09 12:23:08','2019-12-09 12:23:08','bc_investor1@test.com',NULL,19,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(21,'BC Asset with 2 Offering 2 Investments 2 Users',NULL,NULL,NULL,NULL,'','BC Asset with 2 Offering 2 Investments 2 Users',NULL,'orgemail@asset',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Residential',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1.50,0,NULL,NULL,NULL,1,'2019-12-09 12:23:08','2019-12-09 12:23:08','bc_investor1@test.com',NULL,19,21,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(22,'Public Asset 1',NULL,NULL,'123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',NULL,'123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890','Public Asset 1',NULL,'orgemail@asset',NULL,NULL,NULL,'1000000','1000000','2.5','50','10','15','stampduty@yielders.co.uk','Residential','36','[{\"time\":1,\"value\":\"10\"},{\"time\":2,\"value\":\"10\"},{\"time\":3,\"value\":\"10\"},{\"time\":4,\"value\":\"10\"},{\"time\":5,\"value\":\"10\"}]','[{\"time\":1,\"value\":\"10\"},{\"time\":2,\"value\":\"10\"},{\"time\":3,\"value\":\"10\"},{\"time\":4,\"value\":\"10\"},{\"time\":5,\"value\":\"10\"}]','[{\"time\":1,\"value\":\"15\"},{\"time\":2,\"value\":\"20\"},{\"time\":3,\"value\":\"27\"},{\"time\":4,\"value\":\"32\"},{\"time\":5,\"value\":\"40\"}]',NULL,'[{\"time\":1,\"value\":\"19.3\"},{\"time\":2,\"value\":\"22.4\"},{\"time\":3,\"value\":\"25.7\"},{\"time\":4,\"value\":\"29.4\"},{\"time\":5,\"value\":\"37.9\"}]',10.00,'[{\"name\":\"train station\",\"longitude\":\"51.4413423\",\"latitude\":\"0.3692587\"}]',0,1.00,2,'18465323','60827613','60827614',1,'2019-12-09 12:23:09','2019-12-09 12:23:09','admin@crowdtek.co.uk',NULL,1,22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(23,'Public Asset 2',NULL,NULL,'123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',NULL,'123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890','Public Asset 2',NULL,'orgemail@asset',NULL,NULL,NULL,'1000000','1000000','2.5','50','10','15','stampduty@yielders.co.uk','Residential','36','[{\"time\":1,\"value\":\"10\"},{\"time\":2,\"value\":\"10\"},{\"time\":3,\"value\":\"10\"},{\"time\":4,\"value\":\"10\"},{\"time\":5,\"value\":\"10\"}]','[{\"time\":1,\"value\":\"10\"},{\"time\":2,\"value\":\"10\"},{\"time\":3,\"value\":\"10\"},{\"time\":4,\"value\":\"10\"},{\"time\":5,\"value\":\"10\"}]','[{\"time\":1,\"value\":\"15\"},{\"time\":2,\"value\":\"20\"},{\"time\":3,\"value\":\"27\"},{\"time\":4,\"value\":\"32\"},{\"time\":5,\"value\":\"40\"}]',NULL,'[{\"time\":1,\"value\":\"19.3\"},{\"time\":2,\"value\":\"22.4\"},{\"time\":3,\"value\":\"25.7\"},{\"time\":4,\"value\":\"29.4\"},{\"time\":5,\"value\":\"37.9\"}]',10.00,'[{\"name\":\"Central London station\",\"longitude\":\"51.538239\",\"latitude\":\"-0.143058\"}]',0,1.00,2,'18465323','60860084','60860085',1,'2019-12-09 12:23:09','2019-12-09 12:23:09','admin@crowdtek.co.uk',NULL,1,23,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(24,'Quayside Apartments A - Bristol',NULL,'Bristol Wharf Apts. A','Newly renovated apartments set in a classical quayside building.','SPVYTEST001','Newly renovated apartments set in a classical quayside building.','Quayside Apartments A - Bristol',NULL,NULL,NULL,NULL,NULL,'220000','125000','0','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.76,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:31:47','2026-04-22 16:03:57','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,24,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0455,10000.00,NULL,0,0),
(25,'Quayside Apartments B - Bristol',NULL,'Bristol Wharf Apts. b','Newly renovated apartments set in a classical quayside building with rooftop terrace.','SPVYTEST002','Newly renovated apartments set in a classical quayside building with rooftop terrace.','Quayside Apartments B - Bristol',NULL,NULL,NULL,NULL,NULL,'282000','150000','0','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.88,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:32:39','2026-04-22 16:04:28','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0470,13260.00,NULL,0,0),
(26,'Lodge de Lac - Cumbria',NULL,'Lakeside lodge - Cumbria','Scenic lodge by the lake surrounded by woodland in the Lake District','SPVYTEST0003','Scenic lodge by the lake surrounded by woodland in the Lake District','Lodge de Lac - Cumbria',NULL,NULL,NULL,NULL,NULL,'116000','100000','0','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.16,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:33:36','2026-04-22 16:05:56','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,26,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0603,6994.00,NULL,0,0),
(27,'Light at the Peninsula - Land\'s End',NULL,'Land\'s End sea home','Cosy home at the tip of England with a view of the sea and lighthouse.','SPVYTEST004','Cosy home at the tip of England with a view of the sea and lighthouse.','Light at the Peninsula - Land\'s End',NULL,NULL,NULL,NULL,NULL,'98400','80000','0','50','10','15','stampduty@yielders.co.uk','Residential','36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.23,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:34:33','2026-04-22 16:05:36','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,27,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0633,6228.00,NULL,0,0),
(28,'Wayward Plaza - London','development','Wayward Plaza Co-working space','Newly renovated co-working space nestled in an up-and-coming part of London.','SPVYTEST005',NULL,'Wayward Plaza - London',NULL,NULL,NULL,NULL,NULL,'664000','200000','0','50','10','15','stampduty@yielders.co.uk','Commercial','12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,3.32,2,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:35:27','2021-02-03 13:37:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(29,'Partingdale House A - Reading',NULL,'Partingdale House - apartment complex','One of several new build homes in the Reading centre regeneration projects.','SPVYTEST006','One of several new build homes in the Reading centre regeneration projects.','Partingdale House A - Reading',NULL,NULL,NULL,NULL,NULL,'72000','50000','0','50','10','15','stampduty@yielders.co.uk','Residential','48',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.44,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:36:37','2026-04-22 16:07:35','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,29,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0544,3920.00,NULL,0,0),
(30,'Kolness by the Moor - Okehampton',NULL,'Kolness by the Moor Guesthouse','Traditional feel guesthouse for travellers visiting the Dartmoor national park','SPVYTEST007','Traditional feel guesthouse for travellers visiting the Dartmoor national park','Kolness by the Moor - Okehampton',NULL,NULL,NULL,NULL,NULL,'15400','10000','0','50','10','15','stampduty@yielders.co.uk','Commercial','24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.54,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:38:03','2026-04-22 16:06:47','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0657,1012.00,NULL,0,0),
(31,'Royal Way Gardens  - Cambridge',NULL,'25 Royal Way  - Cambridge','New build area will contemporary energy efficient homes. Surrounded by greenspace but still within walking distance of amenities. Well served by modern fibre based internet provided by Gigaclear.','SPVYTEST008','New build area will contemporary energy efficient homes. Surrounded by greenspace but still within walking distance of amenities. Well served by modern fibre based internet provided by Gigaclear.','Royal Way Gardens  - Cambridge',NULL,NULL,NULL,NULL,NULL,'106000','50000','0','50','10','15','stampduty@yielders.co.uk','Residential','36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,2.12,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-09 16:40:23','2026-04-22 16:12:48','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,31,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0526,5580.00,NULL,12,0),
(32,'Sandfox Fields - Kent',NULL,'Sandfox Fields - Kent','Quiet neighbourhood nestled on the outskirts of Ashford, Kent; The Garden of England.','SPVYTEST009','Quiet neighbourhood nestled on the outskirts of Ashford, Kent; The Garden of England.','Sandfox Fields - Kent',NULL,NULL,NULL,NULL,NULL,'60500','50000','0','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.21,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-11 15:24:41','2026-04-22 16:07:08','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,32,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0574,3470.00,NULL,0,0),
(33,'Silverhood Down - Brighton',NULL,'24 Silverhood Down','Comfy 3 bedroom house in the popular coastal town, Brighton.','SPVYTEST010','Comfy 3 bedroom house in the popular coastal town, Brighton.','Silverhood Down - Brighton',NULL,NULL,NULL,NULL,NULL,'94500','50000','0','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1.89,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-11 15:30:55','2026-04-22 16:08:35','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,33,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0730,6895.00,NULL,0,0),
(34,'Clarence Hold A - Camden',NULL,'Apartment 12 Clarence Hold, Camden - London','Apartment in the hustle and bustle of Camden Town in zone 2 of London.','SPVYTEST011','Apartment in the hustle and bustle of Camden Town in zone 2 of London.','Clarence Hold A - Camden',NULL,NULL,NULL,NULL,NULL,'256000','100000','0','50','10','15','stampduty@yielders.co.uk','Residential','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,2.56,0,'21597406','wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2019-12-11 16:17:08','2026-04-22 16:12:32','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.0489,12520.00,NULL,5,0),
(35,'Orchid House - Kings Lynn',NULL,NULL,'Mixed used building consisting of a 3 unit commercial ground floor and 6 apartments on the upper floors.','SPVT00442','Mixed used building consisting of a 3 unit commercial ground floor and 6 apartments on the upper floors.','Orchid House - Kings Lynn',NULL,'team@yielders.co.uk',NULL,NULL,NULL,'1250000','156250',NULL,NULL,NULL,NULL,'stampduty@yielders.co.uk','Commercial','24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,8.00,0,NULL,'wlt_m_01HW5QW7MQSDYJ6FWNAH949M1T','wlt_m_01HW5QW8PFZPJD4838KQ9AB8PS',1,'2026-04-29 15:30:46','2026-04-29 15:38:10','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,35,NULL,NULL,NULL,NULL,NULL,'2026-02-06',NULL,0.0506,63250.00,'2022-04-05',0,0);
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `assets_status`
--

DROP TABLE IF EXISTS `assets_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draftOn` datetime DEFAULT NULL,
  `isDraft` tinyint(1) NOT NULL,
  `archivedOn` datetime DEFAULT NULL,
  `isArchived` tinyint(1) NOT NULL,
  `cancelledOn` datetime DEFAULT NULL,
  `isCancelled` tinyint(1) NOT NULL,
  `submittedOn` datetime DEFAULT NULL,
  `isSubmitted` tinyint(1) NOT NULL,
  `rejectedOn` datetime DEFAULT NULL,
  `isRejected` tinyint(1) NOT NULL,
  `publishedOn` datetime DEFAULT NULL,
  `isPublished` tinyint(1) NOT NULL,
  `lifecycleStatus` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets_status`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `assets_status` WRITE;
/*!40000 ALTER TABLE `assets_status` DISABLE KEYS */;
INSERT INTO `assets_status` VALUES
(1,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,NULL,0,'2019-12-09 12:22:58',1,'published',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,NULL,0,'2019-12-09 12:22:58',1,'published',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(4,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(5,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(6,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(7,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(8,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(9,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(10,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(11,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(12,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(13,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(14,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(15,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(16,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(17,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(18,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(19,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(20,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(21,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(22,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,'2019-12-09 12:23:09',1,'published',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(23,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(24,'2019-12-09 16:31:47',1,NULL,0,NULL,0,'2019-12-09 16:40:47',0,NULL,0,'2019-12-09 16:41:11',1,'published',NULL,'2019-12-09 16:31:47','2019-12-09 16:41:11','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(25,'2019-12-09 16:32:39',1,NULL,0,NULL,0,'2019-12-09 16:40:50',0,NULL,0,'2019-12-09 16:41:14',1,'published',NULL,'2019-12-09 16:32:39','2019-12-09 16:41:14','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(26,'2019-12-09 16:33:36',1,NULL,0,NULL,0,'2019-12-09 16:40:52',0,NULL,0,'2019-12-09 16:41:17',1,'published',NULL,'2019-12-09 16:33:36','2019-12-09 16:41:17','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(27,'2019-12-09 16:34:33',1,NULL,0,NULL,0,'2019-12-09 16:40:53',0,NULL,0,'2019-12-09 16:41:16',1,'published',NULL,'2019-12-09 16:34:33','2019-12-09 16:41:16','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(28,'2019-12-09 16:35:27',1,NULL,0,NULL,0,'2019-12-09 16:40:54',0,NULL,0,'2019-12-09 16:41:19',1,'published',NULL,'2019-12-09 16:35:27','2019-12-09 16:41:19','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(29,'2019-12-09 16:36:37',1,NULL,0,NULL,0,'2019-12-09 16:40:55',0,NULL,0,'2019-12-09 16:41:20',1,'published',NULL,'2019-12-09 16:36:37','2019-12-09 16:41:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(30,'2019-12-09 16:38:03',1,NULL,0,NULL,0,'2019-12-09 16:40:56',0,NULL,0,'2019-12-09 16:41:22',1,'published',NULL,'2019-12-09 16:38:03','2019-12-09 16:41:22','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(31,'2019-12-09 16:40:23',1,NULL,0,NULL,0,'2019-12-09 16:40:57',0,NULL,0,'2019-12-09 16:41:23',1,'published',NULL,'2019-12-09 16:40:23','2019-12-09 16:41:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(32,'2019-12-11 15:24:41',1,NULL,0,NULL,0,'2019-12-11 15:30:59',0,NULL,0,'2019-12-11 15:31:06',1,'published',NULL,'2019-12-11 15:24:41','2019-12-11 15:31:06','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(33,'2019-12-11 15:30:55',1,NULL,0,NULL,0,'2019-12-11 15:31:01',0,NULL,0,'2019-12-11 15:31:08',1,'published',NULL,'2019-12-11 15:30:55','2019-12-11 15:31:08','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(34,'2019-12-11 16:17:08',1,NULL,0,NULL,0,'2019-12-11 16:17:20',0,NULL,0,'2019-12-11 16:17:23',1,'published',NULL,'2019-12-11 16:17:08','2019-12-11 16:17:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(35,'2026-04-29 15:30:46',1,NULL,0,NULL,0,NULL,0,NULL,0,'2026-04-29 15:30:46',1,'published',NULL,'2026-04-29 15:30:46','2026-04-29 15:30:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `assets_status` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bank_account`
--

DROP TABLE IF EXISTS `bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `accountType` varchar(32) NOT NULL,
  `accountNumber` varchar(34) DEFAULT NULL,
  `bankIdentifierCode` varchar(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `providerId` varchar(64) DEFAULT NULL,
  `accountHolderType` varchar(32) NOT NULL,
  `country` varchar(3) NOT NULL,
  `bankName` varchar(50) DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  `accountHolderName` varchar(255) DEFAULT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `accountHolderAddress_id` int(11) DEFAULT NULL,
  `approvedBy_id` int(11) DEFAULT NULL,
  `accountHolderLastName` varchar(255) DEFAULT NULL,
  `fingerprint` varchar(255) DEFAULT NULL,
  `displayName` varchar(80) DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `uuid` binary(16) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_53A23E0AD17F50A6` (`uuid`),
  UNIQUE KEY `UNIQ_53A23E0A1D81C79D` (`accountHolderAddress_id`),
  KEY `IDX_53A23E0AA76ED395` (`user_id`),
  KEY `IDX_53A23E0AFACFC38A` (`approvedBy_id`),
  CONSTRAINT `FK_ED4128111D81C79D` FOREIGN KEY (`accountHolderAddress_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `FK_ED412811A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_ED412811FACFC38A` FOREIGN KEY (`approvedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_account`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bank_account` WRITE;
/*!40000 ALTER TABLE `bank_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_account` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `position` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `regAddress1` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `regAddress2` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `regAddress3` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `beneficialOwners` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `directors` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `regCountry` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `businessNature` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `telephone` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `postCode` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `buildingName` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `registrationNumber` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `otherName` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `companyWebsite` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `operatingAddress` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `operatingPostCode` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES
(1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL),
(9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL),
(10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL),
(14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL),
(15,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(16,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(19,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL),
(21,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL),
(22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(23,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(24,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(26,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(27,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(29,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL),
(30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL),
(31,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(32,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(33,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL),
(35,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL),
(36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(37,'user204',NULL,'address line1','address line2','address line1','[{\"first_name\":\"bob\", \"last_name\":\"smith\"}]','[{\"first_name\":\"bob\", \"last_name\":\"smith\"}, {\"first_name\":\"juile\", \"last_name\":\"smith\"}]','GB',NULL,NULL,NULL,'building @user204->firstName','1896722',NULL,'www.something.com',NULL,NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(38,'company-min-verified@crowdtek.co.uk',NULL,'address line1','address line2','address line1','[{\"first_name\":\"john\", \"last_name\":\"adams\"}]','[{\"first_name\":\"raj\", \"last_name\":\"smith\"}, {\"first_name\":\"juile\", \"last_name\":\"jones\"}]','GB',NULL,NULL,NULL,'building @user205->firstName','8813512',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(39,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:14','2019-12-09 12:23:14',NULL,NULL),
(40,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:14','2019-12-09 12:23:14',NULL,NULL),
(41,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(43,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(44,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:16','2019-12-09 12:23:16',NULL,NULL),
(45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:16','2019-12-09 12:23:16',NULL,NULL),
(46,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL),
(47,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL),
(48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL),
(49,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:18','2019-12-09 12:23:18',NULL,NULL),
(50,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:18','2019-12-09 12:23:18',NULL,NULL),
(51,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:19','2019-12-09 12:23:19',NULL,NULL),
(52,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:19','2019-12-09 12:23:19',NULL,NULL),
(53,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:19','2019-12-09 12:23:19',NULL,NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `contego_logs`
--

DROP TABLE IF EXISTS `contego_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contego_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_name` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `rag` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `kyc_score` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `kyc_type` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `ext_reference_id` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `pdf_report_url` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `user` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contego_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `contego_logs` WRITE;
/*!40000 ALTER TABLE `contego_logs` DISABLE KEYS */;
INSERT INTO `contego_logs` VALUES
(1,'AML Check Test data','GREEN','700','PERSON CHECK','1000001','http://www.bbc.co.uk','admin@crowdtek.co.uk',NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(2,'AML Check Test data','GREEN','950','PERSON CHECK','1000002','http://www.bbc.co.uk','keesh@crowdtek.co.uk',NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(3,'AML Check Test data','AMBER','14','PERSON CHECK','1000003','http://www.bbc.co.uk','keesh@crowdtek.co.uk',NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL);
/*!40000 ALTER TABLE `contego_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `contego_score`
--

DROP TABLE IF EXISTS `contego_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contego_score` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rag` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `kyc_score` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `rule_messages` longtext NOT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contego_score`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `contego_score` WRITE;
/*!40000 ALTER TABLE `contego_score` DISABLE KEYS */;
INSERT INTO `contego_score` VALUES
(1,'AMBER','120','all good',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(2,'GREEN','120','all good',NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(3,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(4,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(5,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(6,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(7,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(8,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(9,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(10,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(11,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(12,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(13,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(14,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(15,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL),
(16,'GREEN','120','all good',1,'2020-05-21 11:14:49','2020-05-21 11:14:49',NULL,NULL);
/*!40000 ALTER TABLE `contego_score` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `direct_debit`
--

DROP TABLE IF EXISTS `direct_debit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `direct_debit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `mangopay_bank_account_Id` int(11) NOT NULL,
  `mangopay_mandate_Id` int(11) NOT NULL,
  `account_type` varchar(2) NOT NULL COMMENT '(DC2Type:string)',
  `mandate_create_date` date NOT NULL,
  `direct_debit_active` tinyint(1) NOT NULL,
  `currency` varchar(3) NOT NULL COMMENT '(DC2Type:string)',
  `amount` int(11) NOT NULL,
  `mandate_url` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `last_settlement_date` date DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_EC85ED90A76ED395` (`user_id`),
  CONSTRAINT `FK_EC85ED90A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direct_debit`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `direct_debit` WRITE;
/*!40000 ALTER TABLE `direct_debit` DISABLE KEYS */;
/*!40000 ALTER TABLE `direct_debit` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES
('DoctrineMigrations\\Version20211013204924',NULL,NULL),
('DoctrineMigrations\\Version20211025203008',NULL,NULL),
('DoctrineMigrations\\Version20220131122620',NULL,NULL),
('DoctrineMigrations\\Version20220131154955',NULL,NULL),
('DoctrineMigrations\\Version20220308162010',NULL,NULL),
('DoctrineMigrations\\Version20220628165730',NULL,NULL),
('DoctrineMigrations\\Version20220704154539',NULL,NULL),
('DoctrineMigrations\\Version20220714161712',NULL,NULL),
('DoctrineMigrations\\Version20220728150605','2022-10-06 13:22:19',131),
('DoctrineMigrations\\Version20220915165506','2022-10-06 13:22:19',3),
('DoctrineMigrations\\Version20221013183015','2022-11-04 17:32:01',29),
('DoctrineMigrations\\Version20221205162933','2023-04-24 10:41:38',138),
('DoctrineMigrations\\Version20230321122336','2023-04-24 10:41:38',27),
('DoctrineMigrations\\Version20230414122013','2023-04-24 10:41:38',22),
('DoctrineMigrations\\Version20230418105615','2023-04-24 10:41:38',225),
('DoctrineMigrations\\Version20230420130207','2023-04-24 10:41:39',31),
('DoctrineMigrations\\Version20230420220313','2023-04-24 10:41:39',289),
('DoctrineMigrations\\Version20230421132934','2023-04-24 10:41:39',84),
('DoctrineMigrations\\Version20230505104247','2023-05-05 17:20:46',238),
('DoctrineMigrations\\Version20230511100159','2023-10-16 12:40:47',29),
('DoctrineMigrations\\Version20230524104436','2023-10-16 12:40:47',27),
('DoctrineMigrations\\Version20230605121952','2023-10-16 12:40:47',27),
('DoctrineMigrations\\Version20230623171504','2023-10-16 12:40:47',145),
('DoctrineMigrations\\Version20230629122301','2023-10-16 12:40:48',227),
('DoctrineMigrations\\Version20230706155521','2023-10-16 12:40:48',129),
('DoctrineMigrations\\Version20230721160134','2023-10-16 12:40:48',27),
('DoctrineMigrations\\Version20230901133543','2023-10-16 12:40:48',27),
('DoctrineMigrations\\Version20231117121927','2024-06-12 13:24:37',31),
('DoctrineMigrations\\Version20240610141427','2024-06-12 13:24:37',18),
('DoctrineMigrations\\Version20240611180540','2024-06-12 13:24:37',71),
('DoctrineMigrations\\Version20240724102737','2024-08-12 12:54:15',335),
('DoctrineMigrations\\Version20240729140043','2024-08-12 12:54:16',1173),
('DoctrineMigrations\\Version20240828100205','2024-11-18 11:40:10',758),
('DoctrineMigrations\\Version20241105130308','2024-11-18 11:40:11',242),
('DoctrineMigrations\\Version20250123112752','2025-05-30 14:31:53',65),
('DoctrineMigrations\\Version20250509094614','2025-05-30 14:31:53',95),
('DoctrineMigrations\\Version20250510113932','2025-05-30 14:31:53',285),
('DoctrineMigrations\\Version20250530100802','2025-06-19 14:02:26',41),
('DoctrineMigrations\\Version20250716115309','2025-07-21 14:49:04',91),
('DoctrineMigrations\\Version20250718163520','2025-07-21 14:49:04',318),
('DoctrineMigrations\\Version20250718170050','2025-07-21 14:49:04',1891),
('DoctrineMigrations\\Version20250901182336','2025-09-11 14:48:50',115),
('DoctrineMigrations\\Version20251015133308','2025-10-22 15:30:30',616),
('DoctrineMigrations\\Version20251017135304','2025-11-18 14:42:52',333),
('DoctrineMigrations\\Version20260213150635','2026-02-16 10:58:25',418),
('DoctrineMigrations\\Version20260218183106','2026-03-31 09:27:07',2223),
('DoctrineMigrations\\Version20260409164253','2026-04-14 18:09:45',111);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `name` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `description` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `type` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `alias` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `tag` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `category` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `documentUrl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES
(1,'male-passport.jpg',NULL,'my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL,'fixtures/male-passport.jpg'),
(2,'female-passport.jpg',NULL,'my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL,'fixtures/female-passport.jpg'),
(3,'female-passport.jpg',NULL,'my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL,'fixtures/female-passport.jpg'),
(4,'Test_PDF.pdf',NULL,'client doc','application/pdf',NULL,'read_to_activate',NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/Test_PDF.pdf'),
(5,'Test_PDF.pdf',NULL,'client doc','application/pdf',NULL,'read_to_activate',NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/Test_PDF.pdf'),
(6,'warbler.jpg',NULL,NULL,'image/jpeg',NULL,'property_photos',NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/warbler.jpg'),
(7,'bird.jpg',NULL,NULL,'image/jpeg',NULL,'property_photos',NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/bird.jpg'),
(8,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,NULL,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/Test_PDF.pdf'),
(9,'Test_Excel.xlsx',NULL,NULL,'application/pdf',NULL,NULL,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/Test_Excel.xlsx'),
(10,'Test_WordDoc.docx',NULL,NULL,'application/pdf',NULL,NULL,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/Test_WordDoc.docx'),
(11,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,NULL,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/Test_PDF.pdf'),
(12,'share_certificate.jpg',NULL,'Share Certificate','image/jpeg',NULL,'share_certificate',NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/share_certificate.jpg'),
(13,'share_certificate.jpg',NULL,'Share Certificate','image/jpeg',NULL,'share_certificate',NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,'fixtures/share_certificate.jpg'),
(14,'image-1.jpg','image-1.jpg','asset something','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,'fixtures/image-1.jpg'),
(15,'anotherfile.jpg','anotherfile.jpg','asset something 2','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,NULL),
(16,'male-passport.jpg',NULL,'my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,'fixtures/male-passport.jpg'),
(17,'Test_Excel.xlsx',NULL,'offering doc 1','pdf',NULL,'calculations',NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,'fixtures/Test_Excel.xlsx'),
(18,'Test_Excel.xlsx',NULL,'offering doc2','pdf',NULL,'calculations',NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,'fixtures/Test_Excel.xlsx'),
(19,'file.jpg','file.jpg','Business Scenario asset 1','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(20,'file.jpg','file.jpg','Business Scenario asset 2','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(21,'file.jpg','file.jpg','Business Scenario asset 3','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(22,'file.jpg','file.jpg','Business Scenario asset 4','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(23,'file.jpg','file.jpg','Business Scenario asset 5','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(24,'file.jpg','file.jpg','Business Scenario asset 6','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(25,'file.jpg','file.jpg','Business Scenario asset 6','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,NULL),
(26,'warbler.jpg',NULL,'photo of property','image/jpeg',NULL,'property_photos',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,'fixtures/warbler.jpg'),
(27,'bird.jpg',NULL,'photo of property','image/jpeg',NULL,'property_photos',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,'fixtures/bird.jpg'),
(28,'robin.jpg',NULL,'photo of property','image/jpeg',NULL,'property_photos',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,'fixtures/robin.jpg'),
(29,'logo.jpg',NULL,'subway logo','image/jpeg',NULL,'logo',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,'fixtures/logo.jpg'),
(30,'anotherfile.jpg','anotherfile.jpg','public offering something 2','image/jpeg',NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,NULL),
(31,'Test_PDF.pdf',NULL,NULL,'document/pdf',NULL,'read_to_activate',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,'fixtures/Test_PDF.pdf'),
(32,'Test_PDF.pdf',NULL,NULL,'document/pdf',NULL,'read_to_activate',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,'fixtures/Test_PDF.pdf'),
(33,'male-passport.jpg',NULL,'my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL,'fixtures/male-passport.jpg'),
(34,'male-passport.jpg',NULL,'investor-keesh my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/male-passport.jpg'),
(35,'avatar.jpg',NULL,'investor-keesh my avatar','image/jpeg',NULL,'avatar',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/avatar.jpg'),
(36,'female-passport.jpg',NULL,'Jess Sing my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/female-passport.jpg'),
(37,'bird.jpg',NULL,'Jess Sing my avatar','image/jpeg',NULL,'avatar',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/bird.jpg'),
(38,'male-passport.jpg',NULL,'Ben Man my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/male-passport.jpg'),
(39,'avatar.jpg',NULL,'Ben Man my avatar','image/jpeg',NULL,'avatar',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/avatar.jpg'),
(40,'male-passport.jpg',NULL,'Jen Red my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/male-passport.jpg'),
(41,'avatar.jpg',NULL,'Jen Red Man my avatar','image/jpeg',NULL,'avatar',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/avatar.jpg'),
(42,'male-passport.jpg',NULL,'Max Mel my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/male-passport.jpg'),
(43,'avatar.jpg',NULL,'Max Mel Man my avatar','image/jpeg',NULL,'avatar',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/avatar.jpg'),
(44,'male-passport.jpg',NULL,'Ben Sherman my passport','image/jpeg',NULL,'proof_of_identity',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/male-passport.jpg'),
(45,'avatar.jpg',NULL,'Ben Sherman my avatar','image/jpeg',NULL,'avatar',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,'fixtures/avatar.jpg'),
(46,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:16:34','2019-12-09 17:16:34','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(47,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:16:45','2019-12-09 17:16:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(48,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:16:54','2019-12-09 17:16:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(49,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:17:04','2019-12-09 17:17:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(50,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:17:38','2019-12-09 17:17:38','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(51,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:17:46','2019-12-09 17:17:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(52,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:17:55','2019-12-09 17:17:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(53,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-09 17:18:03','2019-12-09 17:18:03','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(54,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:18:54','2019-12-09 17:18:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(55,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:19:04','2019-12-09 17:19:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(56,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:19:15','2019-12-09 17:19:15','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(57,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:19:23','2019-12-09 17:19:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(58,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:19:32','2019-12-09 17:19:32','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(59,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:19:45','2019-12-09 17:19:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(60,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'calculations',NULL,1,'2019-12-09 17:19:56','2019-12-09 17:19:56','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(61,'lighthousestock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:32:38','2019-12-09 17:32:38','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/lighthousestock.jpg'),
(62,'sotckoffice.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:32:59','2019-12-09 17:32:59','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/sotckoffice.jpg'),
(63,'woodlandlodgestock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:34:05','2019-12-09 17:34:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/woodlandlodgestock.jpg'),
(64,'royalwayproperty.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:34:51','2019-12-09 17:34:51','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/royalwayproperty.jpg'),
(65,'quaysidestock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:35:35','2019-12-09 17:35:35','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/quaysidestock.jpg'),
(66,'quaysidestock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:35:43','2019-12-09 17:35:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/quaysidestock.jpg'),
(67,'lodgingsstock.png',NULL,NULL,'image/png',NULL,'logo',NULL,1,'2019-12-09 17:36:20','2019-12-09 17:36:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/lodgingsstock.png'),
(68,'newbuildstock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-09 17:41:57','2019-12-09 17:41:57','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/newbuildstock.jpg'),
(69,'Test_PDF.pdf','Test_PDF.pdf',NULL,'application/pdf',NULL,'calculations',NULL,43,'2019-12-10 13:25:38','2019-12-10 13:25:38','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk',NULL),
(70,'Test_PDF.pdf','Test_PDF.pdf',NULL,'application/pdf',NULL,'calculations',NULL,43,'2019-12-10 13:25:50','2019-12-10 13:25:50','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk',NULL),
(71,'Test_PDF.pdf','Test_PDF.pdf',NULL,'application/pdf',NULL,'calculations',NULL,44,'2019-12-10 13:40:28','2019-12-10 13:40:28','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com',NULL),
(72,'Test_PDF.pdf','Test_PDF.pdf',NULL,'application/pdf',NULL,'calculations',NULL,44,'2019-12-10 13:40:52','2019-12-10 13:40:52','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com',NULL),
(73,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-11 15:41:46','2019-12-11 15:41:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(74,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-11 15:41:55','2019-12-11 15:41:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(76,'brightonstock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-11 15:42:12','2019-12-11 15:42:12','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/brightonstock.jpg'),
(77,'camdenstock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-11 16:19:36','2019-12-11 16:19:36','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/camdenstock.jpg'),
(78,'Test_PDF.pdf',NULL,NULL,'application/pdf',NULL,'read_to_activate',NULL,1,'2019-12-11 16:19:45','2019-12-11 16:19:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/Test_PDF.pdf'),
(79,'kitchenstock.jpg',NULL,NULL,'image/jpeg',NULL,'property_photos',NULL,1,'2019-12-12 14:46:40','2019-12-12 14:46:40','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/kitchenstock.jpg'),
(80,'livingroomstock.jpg',NULL,NULL,'image/jpeg',NULL,'property_photos',NULL,1,'2019-12-12 14:48:35','2019-12-12 14:48:35','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/livingroomstock.jpg'),
(81,'kentstock.jpg',NULL,NULL,'image/jpeg',NULL,'logo',NULL,1,'2019-12-12 14:48:47','2019-12-12 14:48:47','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/kentstock.jpg'),
(82,'smallbedroomstock.jpg',NULL,NULL,'image/jpeg',NULL,'property_photos',NULL,1,'2019-12-12 14:50:52','2019-12-12 14:50:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/smallbedroomstock.jpg'),
(83,'share_certificate.jpg',NULL,'Ben Autotest Investment 30 - Royal Way Gardens Cambridge','image/jpeg',NULL,'share_certificate',NULL,1,'2020-03-24 19:05:37','2020-03-24 19:05:51','admin@crowdtek.co.uk','admin@crowdtek.co.uk','fixtures/share_certificate.jpg');
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ext_log_entries`
--

DROP TABLE IF EXISTS `ext_log_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ext_log_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(8) NOT NULL COMMENT '(DC2Type:string)',
  `logged_at` datetime NOT NULL,
  `object_id` varchar(64) DEFAULT NULL COMMENT '(DC2Type:string)',
  `object_class` varchar(191) NOT NULL,
  `version` int(11) NOT NULL,
  `data` longtext DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_class_lookup_idx` (`object_class`),
  KEY `log_date_lookup_idx` (`logged_at`),
  KEY `log_user_lookup_idx` (`username`),
  KEY `log_version_lookup_idx` (`object_id`,`object_class`,`version`)
) ENGINE=InnoDB AUTO_INCREMENT=981 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ext_log_entries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ext_log_entries` WRITE;
/*!40000 ALTER TABLE `ext_log_entries` DISABLE KEYS */;
INSERT INTO `ext_log_entries` VALUES
(966,'create','2022-01-18 11:25:27','39','BusinessBundle\\Entity\\Investment',1,'a:18:{s:10:\"visibility\";i:0;s:8:\"for_sale\";i:0;s:4:\"name\";C:22:\"UserBundle\\Entity\\User\":225:{a:8:{i:0;s:60:\"$2y$13$i2NknwL0TwbGTdO4MEcBDuvu9dWkOfBPaMthk2VIVFiliYlnD8AYi\";i:1;N;i:2;s:19:\"ed.autotest@red.com\";i:3;s:19:\"ed.autotest@red.com\";i:4;b:1;i:5;i:45;i:6;s:19:\"ed.autotest@red.com\";i:7;s:19:\"ed.autotest@red.com\";}}s:15:\"investmentValue\";s:3:\"968\";s:14:\"numberOfShares\";N;s:8:\"currency\";s:3:\"GBP\";s:12:\"interestRate\";N;s:4:\"term\";s:1:\"5\";s:16:\"orgPricePerShare\";s:4:\"1.21\";s:13:\"PricePerShare\";N;s:12:\"share_amount\";s:3:\"800\";s:14:\"transaction_id\";s:8:\"87564976\";s:4:\"type\";s:10:\"off-market\";s:8:\"comments\";N;s:19:\"extraSharesDivested\";i:0;s:4:\"user\";a:1:{s:2:\"id\";i:45;}s:8:\"offering\";a:1:{s:2:\"id\";i:43;}s:16:\"investmentStatus\";a:1:{s:2:\"id\";i:39;}}','admin@crowdtek.co.uk'),
(967,'create','2022-01-18 11:25:27','39','BusinessBundle\\Entity\\InvestmentStatus',1,'a:12:{s:6:\"openOn\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2022-01-18 11:25:27.272697\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:13:\"Europe/London\";}s:6:\"isOpen\";b:1;s:10:\"rejectedOn\";N;s:10:\"isRejected\";b:0;s:10:\"approvedOn\";N;s:10:\"isApproved\";b:0;s:11:\"withdrawnOn\";N;s:11:\"isWithdrawn\";b:0;s:9:\"settledOn\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2021-05-09 00:00:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:13:\"Europe/London\";}s:9:\"isSettled\";b:1;s:15:\"lifecycleStatus\";s:7:\"settled\";s:13:\"stampDutyPaid\";b:0;}','admin@crowdtek.co.uk'),
(968,'create','2022-01-18 11:26:05','40','BusinessBundle\\Entity\\Investment',1,'a:18:{s:10:\"visibility\";i:0;s:8:\"for_sale\";i:0;s:4:\"name\";C:22:\"UserBundle\\Entity\\User\":257:{a:8:{i:0;s:60:\"$2y$13$4r8iwEpQE84pwCXlkwK2M.4RN/rd0RYlDZ7PE772HJLEZNy.ZbSxK\";i:1;N;i:2;s:27:\"jim.autotest@crowdtek.co.uk\";i:3;s:27:\"jim.autotest@crowdtek.co.uk\";i:4;b:1;i:5;i:53;i:6;s:27:\"jim.autotest@crowdtek.co.uk\";i:7;s:27:\"jim.autotest@crowdtek.co.uk\";}}s:15:\"investmentValue\";s:4:\"1452\";s:14:\"numberOfShares\";N;s:8:\"currency\";s:3:\"GBP\";s:12:\"interestRate\";N;s:4:\"term\";s:1:\"5\";s:16:\"orgPricePerShare\";s:4:\"1.21\";s:13:\"PricePerShare\";N;s:12:\"share_amount\";s:4:\"1200\";s:14:\"transaction_id\";s:8:\"22546378\";s:4:\"type\";s:10:\"off-market\";s:8:\"comments\";N;s:19:\"extraSharesDivested\";i:0;s:4:\"user\";a:1:{s:2:\"id\";i:53;}s:8:\"offering\";a:1:{s:2:\"id\";i:43;}s:16:\"investmentStatus\";a:1:{s:2:\"id\";i:40;}}','admin@crowdtek.co.uk'),
(969,'create','2022-01-18 11:26:05','40','BusinessBundle\\Entity\\InvestmentStatus',1,'a:12:{s:6:\"openOn\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2022-01-18 11:26:05.914779\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:13:\"Europe/London\";}s:6:\"isOpen\";b:1;s:10:\"rejectedOn\";N;s:10:\"isRejected\";b:0;s:10:\"approvedOn\";N;s:10:\"isApproved\";b:0;s:11:\"withdrawnOn\";N;s:11:\"isWithdrawn\";b:0;s:9:\"settledOn\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2021-08-20 00:00:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:13:\"Europe/London\";}s:9:\"isSettled\";b:1;s:15:\"lifecycleStatus\";s:7:\"settled\";s:13:\"stampDutyPaid\";b:0;}','admin@crowdtek.co.uk'),
(970,'create','2022-03-15 12:35:39','59','BusinessBundle\\Entity\\Payout',1,'a:11:{s:14:\"additionalType\";N;s:8:\"currency\";s:3:\"GBP\";s:10:\"payoutType\";i:1;s:7:\"dueDate\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2020-03-01 00:00:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:10:\"minPayment\";N;s:12:\"payoutAmount\";s:6:\"112.54\";s:3:\"fee\";N;s:11:\"ownerObject\";N;s:13:\"transactionId\";N;s:12:\"shareholding\";i:50;s:10:\"investment\";a:1:{s:2:\"id\";i:35;}}','admin@crowdtek.co.uk'),
(971,'update','2024-08-12 12:56:00','53','UserBundle\\Entity\\User',1,'a:2:{s:6:\"gender\";s:4:\"MALE\";s:13:\"honoricSuffix\";N;}','admin@crowdtek.co.uk'),
(972,'update','2024-08-12 12:56:00','51','UserBundle\\Entity\\Investor',1,'a:2:{s:18:\"cxbLtdCompInvestor\";b:0;s:17:\"corporateInvestor\";b:0;}','admin@crowdtek.co.uk'),
(973,'update','2024-08-12 12:56:31','45','UserBundle\\Entity\\User',1,'a:2:{s:6:\"gender\";s:4:\"MALE\";s:13:\"honoricSuffix\";N;}','admin@crowdtek.co.uk'),
(974,'update','2024-08-12 12:56:31','45','UserBundle\\Entity\\Investor',1,'a:2:{s:18:\"cxbLtdCompInvestor\";b:0;s:17:\"corporateInvestor\";b:0;}','admin@crowdtek.co.uk'),
(975,'update','2024-08-12 12:57:34','44','UserBundle\\Entity\\User',1,'a:2:{s:6:\"gender\";s:6:\"FEMALE\";s:13:\"honoricSuffix\";N;}','admin@crowdtek.co.uk'),
(976,'update','2024-08-12 12:57:34','44','UserBundle\\Entity\\Investor',1,'a:2:{s:18:\"cxbLtdCompInvestor\";b:0;s:17:\"corporateInvestor\";b:0;}','admin@crowdtek.co.uk'),
(977,'update','2024-08-12 12:58:03','43','UserBundle\\Entity\\User',1,'a:2:{s:6:\"gender\";s:4:\"MALE\";s:13:\"honoricSuffix\";N;}','admin@crowdtek.co.uk'),
(978,'update','2024-08-12 12:58:03','43','UserBundle\\Entity\\Investor',1,'a:4:{s:16:\"cxbWorthInvestor\";b:0;s:17:\"cxbRestrictedUser\";b:0;s:18:\"cxbLtdCompInvestor\";b:0;s:17:\"corporateInvestor\";b:0;}','admin@crowdtek.co.uk'),
(979,'update','2024-08-12 13:49:51','51','UserBundle\\Entity\\Status',1,'a:3:{s:14:\"regCompletedOn\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2024-08-12 13:49:51.902158\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:14:\"isRegCompleted\";b:1;s:15:\"lifecycleStatus\";s:21:\"registration_complete\";}','admin@crowdtek.co.uk'),
(980,'update','2024-08-12 13:49:54','51','UserBundle\\Entity\\Status',2,'a:3:{s:10:\"approvedOn\";O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2024-08-12 13:49:54.858897\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:10:\"isApproved\";b:1;s:15:\"lifecycleStatus\";s:8:\"approved\";}','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `ext_log_entries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `getAssetData`
--

DROP TABLE IF EXISTS `getAssetData`;
/*!50001 DROP VIEW IF EXISTS `getAssetData`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getAssetData` AS SELECT
 1 AS `id`,
  1 AS `name`,
  1 AS `additionalType`,
  1 AS `alternateName`,
  1 AS `briefDescription`,
  1 AS `companyNumber`,
  1 AS `creditScore`,
  1 AS `detailedDesc`,
  1 AS `displayName`,
  1 AS `facebookUri`,
  1 AS `foundingDate`,
  1 AS `foundingLocation`,
  1 AS `legalName`,
  1 AS `linkedinUri`,
  1 AS `youtubeUri`,
  1 AS `twitterUri`,
  1 AS `location`,
  1 AS `logo`,
  1 AS `orgEmail`,
  1 AS `orgWebsite`,
  1 AS `sector`,
  1 AS `taxId`,
  1 AS `telephone`,
  1 AS `fundingGoal`,
  1 AS `amountOfShares`,
  1 AS `setupFee`,
  1 AS `adminFee`,
  1 AS `managementFee`,
  1 AS `profitShare`,
  1 AS `stampDutyUser`,
  1 AS `assetType`,
  1 AS `investmentTerm`,
  1 AS `grossRentalReturnPA`,
  1 AS `netRentalReturnPA`,
  1 AS `grossCapitalAppreciation`,
  1 AS `netCapitalAppreciation`,
  1 AS `netCapitalAppreciationYield`,
  1 AS `gross_yield`,
  1 AS `pointsOfInterest`,
  1 AS `blockedForSale`,
  1 AS `pricePerShare`,
  1 AS `visibility`,
  1 AS `mangoPayUserId`,
  1 AS `mangoPayWalletId`,
  1 AS `additional_wallet`,
  1 AS `createdById`,
  1 AS `createdAt`,
  1 AS `updatedAt`,
  1 AS `createdBy`,
  1 AS `updatedBy`,
  1 AS `contactPoint_id`,
  1 AS `assetStatus_id`,
  1 AS `draftOn`,
  1 AS `isDraft`,
  1 AS `archivedOn`,
  1 AS `isArchived`,
  1 AS `cancelledOn`,
  1 AS `isCancelled`,
  1 AS `submittedOn`,
  1 AS `isSubmitted`,
  1 AS `rejectedOn`,
  1 AS `isRejected`,
  1 AS `publishedOn`,
  1 AS `isPublished`,
  1 AS `lifecycleStatus`,
  1 AS `status.createdById`,
  1 AS `status.createdAt`,
  1 AS `status.updatedAt`,
  1 AS `status.createdBy`,
  1 AS `status.updatedBy` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getCapitalRepayments`
--

DROP TABLE IF EXISTS `getCapitalRepayments`;
/*!50001 DROP VIEW IF EXISTS `getCapitalRepayments`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getCapitalRepayments` AS SELECT
 1 AS `inv_id`,
  1 AS `capitalRepaid` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getContegoLog`
--

DROP TABLE IF EXISTS `getContegoLog`;
/*!50001 DROP VIEW IF EXISTS `getContegoLog`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getContegoLog` AS SELECT
 1 AS `id`,
  1 AS `profile_name`,
  1 AS `rag`,
  1 AS `kyc_score`,
  1 AS `kyc_type`,
  1 AS `ext_reference_id`,
  1 AS `pdf_report_url`,
  1 AS `user`,
  1 AS `createdAt` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getDivestments`
--

DROP TABLE IF EXISTS `getDivestments`;
/*!50001 DROP VIEW IF EXISTS `getDivestments`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getDivestments` AS SELECT
 1 AS `inv_id`,
  1 AS `divested_amount`,
  1 AS `divested_shares`,
  1 AS `divestment_trades` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getHoldingData`
--

DROP TABLE IF EXISTS `getHoldingData`;
/*!50001 DROP VIEW IF EXISTS `getHoldingData`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getHoldingData` AS SELECT
 1 AS `id`,
  1 AS `asset_id`,
  1 AS `name`,
  1 AS `user_id`,
  1 AS `email`,
  1 AS `investment_id`,
  1 AS `investmest_shareamount`,
  1 AS `investment_name`,
  1 AS `transaction_id`,
  1 AS `share_amount`,
  1 AS `createdById`,
  1 AS `createdAt`,
  1 AS `updatedAt`,
  1 AS `createdBy`,
  1 AS `updatedBy` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getInvestmentData`
--

DROP TABLE IF EXISTS `getInvestmentData`;
/*!50001 DROP VIEW IF EXISTS `getInvestmentData`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getInvestmentData` AS SELECT
 1 AS `id`,
  1 AS `user_id`,
  1 AS `off_id`,
  1 AS `assetId`,
  1 AS `Offering Name`,
  1 AS `visibility`,
  1 AS `for_sale`,
  1 AS `name`,
  1 AS `investmentValue`,
  1 AS `numberOfShares`,
  1 AS `currency`,
  1 AS `interestRate`,
  1 AS `term`,
  1 AS `orgPricePerShare`,
  1 AS `PricePerShare`,
  1 AS `share_amount`,
  1 AS `transaction_id`,
  1 AS `type`,
  1 AS `comments`,
  1 AS `createdById`,
  1 AS `createdAt`,
  1 AS `updatedAt`,
  1 AS `createdBy`,
  1 AS `updatedBy`,
  1 AS `investmentStatus_id`,
  1 AS `openOn`,
  1 AS `isOpen`,
  1 AS `rejectedOn`,
  1 AS `isRejected`,
  1 AS `approvedOn`,
  1 AS `isApproved`,
  1 AS `withdrawnOn`,
  1 AS `isWithdrawn`,
  1 AS `settledOn`,
  1 AS `isSettled`,
  1 AS `lifecycleStatus`,
  1 AS `signedUpWithRefCode`,
  1 AS `referersUsername`,
  1 AS `status.createdById`,
  1 AS `status.createdAt`,
  1 AS `status.updatedAt`,
  1 AS `status.createdBy`,
  1 AS `status.updatedBy`,
  1 AS `capitalRepaid`,
  1 AS `isRetention`,
  1 AS `cxbWorthInvestor`,
  1 AS `cxbSophisticatedInvestor`,
  1 AS `cxbRestrictedUser`,
  1 AS `corporateInvestor`,
  1 AS `estimatedStampDuty` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getInvestmentPayouts`
--

DROP TABLE IF EXISTS `getInvestmentPayouts`;
/*!50001 DROP VIEW IF EXISTS `getInvestmentPayouts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getInvestmentPayouts` AS SELECT
 1 AS `investment_id`,
  1 AS `offering_id`,
  1 AS `asset_id`,
  1 AS `asset_name`,
  1 AS `investor_id`,
  1 AS `investment_value`,
  1 AS `no_of_shares`,
  1 AS `org_price_per_share`,
  1 AS `price_per_share`,
  1 AS `share_amount`,
  1 AS `investment_created_at`,
  1 AS `investment_created_by`,
  1 AS `investment_status`,
  1 AS `dueDate`,
  1 AS `payoutAmount`,
  1 AS `fee`,
  1 AS `payout_created_at`,
  1 AS `payout_created_by`,
  1 AS `payout_owner_object`,
  1 AS `payout_transaction_id` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getLastActivity`
--

DROP TABLE IF EXISTS `getLastActivity`;
/*!50001 DROP VIEW IF EXISTS `getLastActivity`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getLastActivity` AS SELECT
 1 AS `id`,
  1 AS `action`,
  1 AS `logged_at`,
  1 AS `object_id`,
  1 AS `object_class`,
  1 AS `version`,
  1 AS `data`,
  1 AS `username` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getOfferingData`
--

DROP TABLE IF EXISTS `getOfferingData`;
/*!50001 DROP VIEW IF EXISTS `getOfferingData`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getOfferingData` AS SELECT
 1 AS `id`,
  1 AS `asset_id`,
  1 AS `inv_id`,
  1 AS `offeringType`,
  1 AS `name`,
  1 AS `additionalType`,
  1 AS `category`,
  1 AS `dealDescription`,
  1 AS `creditScore`,
  1 AS `fundingGoal`,
  1 AS `externalCommitments`,
  1 AS `isFeatured`,
  1 AS `isSecondaryMrkt`,
  1 AS `loanToValue`,
  1 AS `valuation`,
  1 AS `equityOffered`,
  1 AS `noOfShares`,
  1 AS `pricePerShare`,
  1 AS `debtInterestRate`,
  1 AS `county`,
  1 AS `debtTerm`,
  1 AS `netRentProjected`,
  1 AS `grossRentProjected`,
  1 AS `grossProjectReturn`,
  1 AS `offeringTerm`,
  1 AS `openDate`,
  1 AS `closeDate`,
  1 AS `minCommitUser`,
  1 AS `maxCommitUser`,
  1 AS `maxOverFunding`,
  1 AS `primaryOfferingId`,
  1 AS `comments`,
  1 AS `visibility`,
  1 AS `additional_type`,
  1 AS `currency`,
  1 AS `createdById`,
  1 AS `createdAt`,
  1 AS `updatedAt`,
  1 AS `createdBy`,
  1 AS `updatedBy`,
  1 AS `offeringStatus_id`,
  1 AS `discr`,
  1 AS `draftedOn`,
  1 AS `isDraft`,
  1 AS `archivedOn`,
  1 AS `isArchived`,
  1 AS `cancelledOn`,
  1 AS `isCancelled`,
  1 AS `submittedOn`,
  1 AS `isSubmitted`,
  1 AS `rejectedOn`,
  1 AS `isRejected`,
  1 AS `approvedOn`,
  1 AS `isApproved`,
  1 AS `publishedOn`,
  1 AS `isPublished`,
  1 AS `restrictedOn`,
  1 AS `isRestricted`,
  1 AS `closedOn`,
  1 AS `isClosed`,
  1 AS `settledOn`,
  1 AS `isSettled`,
  1 AS `lifecycleStatus`,
  1 AS `status.createdById`,
  1 AS `status.createdAt`,
  1 AS `status.updatedAt`,
  1 AS `status.createdBy`,
  1 AS `status.updatedBy` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getShareHoldings`
--

DROP TABLE IF EXISTS `getShareHoldings`;
/*!50001 DROP VIEW IF EXISTS `getShareHoldings`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getShareHoldings` AS SELECT
 1 AS `asset`,
  1 AS `assetId`,
  1 AS `user`,
  1 AS `userId`,
  1 AS `currentHolding`,
  1 AS `originalHolding`,
  1 AS `divestedHolding`,
  1 AS `divestmentTrades`,
  1 AS `capitalRepayments` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getShareRegister`
--

DROP TABLE IF EXISTS `getShareRegister`;
/*!50001 DROP VIEW IF EXISTS `getShareRegister`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getShareRegister` AS SELECT
 1 AS `id`,
  1 AS `Share No.`,
  1 AS `Investment Type`,
  1 AS `SPV No.`,
  1 AS `Account No.`,
  1 AS `User Email`,
  1 AS `Username`,
  1 AS `Title`,
  1 AS `firstName`,
  1 AS `lastName`,
  1 AS `address1`,
  1 AS `address2`,
  1 AS `city`,
  1 AS `region`,
  1 AS `postCode`,
  1 AS `country`,
  1 AS `User Registered`,
  1 AS `assetId`,
  1 AS `Asset Name`,
  1 AS `Asset Street Address`,
  1 AS `Asset City`,
  1 AS `Asset Region`,
  1 AS `Asset Postcode`,
  1 AS `Asset Country`,
  1 AS `Number Of Shares`,
  1 AS `pricePerShare`,
  1 AS `Share Capital`,
  1 AS `Seller Profile Id`,
  1 AS `Seller Username`,
  1 AS `Seller Email`,
  1 AS `Seller Title`,
  1 AS `Seller Firstname`,
  1 AS `Seller Lastname`,
  1 AS `Seller Address1`,
  1 AS `Seller City`,
  1 AS `Seller Region`,
  1 AS `Seller Postcode`,
  1 AS `Seller Country`,
  1 AS `createdAt`,
  1 AS `settledOn`,
  1 AS `Original Investment`,
  1 AS `Amount Divested`,
  1 AS `Shares Divested`,
  1 AS `Current Holding`,
  1 AS `Current Share Holding`,
  1 AS `MP Debited Wallet Id`,
  1 AS `MP Credited Wallet Id`,
  1 AS `Transaction Id`,
  1 AS `User Company Name`,
  1 AS `User Company Registration`,
  1 AS `User Company Reg Address1`,
  1 AS `User Company PostCode`,
  1 AS `User Company Approved On`,
  1 AS `Capital Repaid`,
  1 AS `isRetention`,
  1 AS `cxbWorthInvestor`,
  1 AS `cxbSophisticatedInvestor`,
  1 AS `cxbRestrictedUser`,
  1 AS `corporateInvestor` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getShareTrades`
--

DROP TABLE IF EXISTS `getShareTrades`;
/*!50001 DROP VIEW IF EXISTS `getShareTrades`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getShareTrades` AS SELECT
 1 AS `investment`,
  1 AS `asset`,
  1 AS `buyer`,
  1 AS `seller`,
  1 AS `numberOfShares`,
  1 AS `investedOn`,
  1 AS `settledOn`,
  1 AS `assetId`,
  1 AS `buyerId`,
  1 AS `sellerId` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getTransactionData`
--

DROP TABLE IF EXISTS `getTransactionData`;
/*!50001 DROP VIEW IF EXISTS `getTransactionData`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getTransactionData` AS SELECT
 1 AS `id`,
  1 AS `user_id`,
  1 AS `currency`,
  1 AS `payment_status`,
  1 AS `createdById`,
  1 AS `createdAt`,
  1 AS `updatedAt`,
  1 AS `createdBy`,
  1 AS `updatedBy`,
  1 AS `inv_id`,
  1 AS `creditor_id`,
  1 AS `debitor_id`,
  1 AS `debited_wallet_id`,
  1 AS `credited_wallet_id`,
  1 AS `offering_id`,
  1 AS `share_amount`,
  1 AS `value_amount`,
  1 AS `fee_amount`,
  1 AS `trans_type`,
  1 AS `comments`,
  1 AS `external_id` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getUserData`
--

DROP TABLE IF EXISTS `getUserData`;
/*!50001 DROP VIEW IF EXISTS `getUserData`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getUserData` AS SELECT
 1 AS `id`,
  1 AS `username`,
  1 AS `email`,
  1 AS `enabled`,
  1 AS `last_login`,
  1 AS `joinDate`,
  1 AS `firstname`,
  1 AS `lastname`,
  1 AS `gender`,
  1 AS `type`,
  1 AS `middlename`,
  1 AS `honoricPrefix`,
  1 AS `honoricSuffix`,
  1 AS `company_id`,
  1 AS `jobTitle`,
  1 AS `location`,
  1 AS `address1`,
  1 AS `address2`,
  1 AS `address3`,
  1 AS `city`,
  1 AS `region`,
  1 AS `postCode`,
  1 AS `country`,
  1 AS `nationality`,
  1 AS `mobile`,
  1 AS `phone1`,
  1 AS `phone2`,
  1 AS `birthCountry`,
  1 AS `birthDate`,
  1 AS `birthPlace`,
  1 AS `drivingLicenseNo`,
  1 AS `passportNumber`,
  1 AS `passportCountry`,
  1 AS `passportExpiry`,
  1 AS `incomeRange`,
  1 AS `mangoPayUserId`,
  1 AS `mangoPayWalletId`,
  1 AS `isVIP`,
  1 AS `occupation`,
  1 AS `additionalType`,
  1 AS `additionalName`,
  1 AS `affiliateCode`,
  1 AS `biography`,
  1 AS `externalReferenceId`,
  1 AS `referralCode`,
  1 AS `sector`,
  1 AS `tagline`,
  1 AS `taxId`,
  1 AS `timezone`,
  1 AS `website`,
  1 AS `term_service_accepted`,
  1 AS `gdpr_accepted`,
  1 AS `linkedinId`,
  1 AS `twitterId`,
  1 AS `facebookId`,
  1 AS `createdAt`,
  1 AS `updatedAt`,
  1 AS `cxbWorthInvestor`,
  1 AS `cxbSophisticatedInvestor`,
  1 AS `cxbRestrictedUser`,
  1 AS `cxbLtdCompInvestor`,
  1 AS `alwaysGoUp`,
  1 AS `incomeEveryMonth`,
  1 AS `neverExit`,
  1 AS `poiFileId`,
  1 AS `wordsOfOwn`,
  1 AS `corporateInvestor`,
  1 AS `isEmailValidated`,
  1 AS `isKycApproved`,
  1 AS `isApproved`,
  1 AS `isRegCompleted`,
  1 AS `isBlocked`,
  1 AS `salesforceId`,
  1 AS `question3`,
  1 AS `referralLink`,
  1 AS `fatca`,
  1 AS `companyApprovedOn`,
  1 AS `questionnaire_passed`,
  1 AS `questionnaire_attempts` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `getUserResetStatus`
--

DROP TABLE IF EXISTS `getUserResetStatus`;
/*!50001 DROP VIEW IF EXISTS `getUserResetStatus`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `getUserResetStatus` AS SELECT
 1 AS `id`,
  1 AS `email`,
  1 AS `last_login`,
  1 AS `confirmation_token`,
  1 AS `password_requested_at`,
  1 AS `lastLoginAt`,
  1 AS `setPasswdExpiry` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `investment_add_fields`
--

DROP TABLE IF EXISTS `investment_add_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `investment_add_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `investment_id` int(11) DEFAULT NULL,
  `fieldKey` varchar(255) NOT NULL,
  `fieldValue` varchar(255) NOT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_8B43AC356E1B4FD5` (`investment_id`),
  CONSTRAINT `FK_8B43AC356E1B4FD5` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investment_add_fields`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `investment_add_fields` WRITE;
/*!40000 ALTER TABLE `investment_add_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `investment_add_fields` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `investment_docs`
--

DROP TABLE IF EXISTS `investment_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `investment_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `investment_id` int(11) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_22163E61C33F7837` (`document_id`),
  KEY `IDX_22163E616E1B4FD5` (`investment_id`),
  CONSTRAINT `FK_22163E616E1B4FD5` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  CONSTRAINT `FK_22163E61C33F7837` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investment_docs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `investment_docs` WRITE;
/*!40000 ALTER TABLE `investment_docs` DISABLE KEYS */;
INSERT INTO `investment_docs` VALUES
(1,1,10,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,2,11,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,1,12,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(4,2,13,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(5,30,83,NULL,'2020-03-24 19:05:37','2020-03-24 19:05:37','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `investment_docs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `investments`
--

DROP TABLE IF EXISTS `investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `investments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `off_id` int(11) DEFAULT NULL,
  `visibility` int(11) NOT NULL DEFAULT 0,
  `for_sale` int(11) NOT NULL DEFAULT 0,
  `name` varchar(360) NOT NULL COMMENT '(DC2Type:string)',
  `investmentValue` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `numberOfShares` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `currency` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `interestRate` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `term` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `orgPricePerShare` decimal(10,2) DEFAULT 0.00,
  `PricePerShare` decimal(10,2) DEFAULT 0.00,
  `share_amount` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `comments` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `type` varchar(10) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `investmentStatus_id` int(11) DEFAULT NULL,
  `extraSharesDivested` int(11) NOT NULL DEFAULT 0,
  `tradeOrder_id` int(11) DEFAULT NULL,
  `shareTrade_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_74FD72E0FE7A1E62` (`investmentStatus_id`),
  UNIQUE KEY `UNIQ_74FD72E0F321B6E5` (`tradeOrder_id`),
  UNIQUE KEY `UNIQ_74FD72E0C37FBED7` (`shareTrade_id`),
  KEY `IDX_74FD72E0A76ED395` (`user_id`),
  KEY `IDX_74FD72E0D95F2A3C` (`off_id`),
  CONSTRAINT `FK_74FD72E0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_74FD72E0C37FBED7` FOREIGN KEY (`shareTrade_id`) REFERENCES `share_trade` (`id`),
  CONSTRAINT `FK_74FD72E0D95F2A3C` FOREIGN KEY (`off_id`) REFERENCES `offerings` (`id`),
  CONSTRAINT `FK_74FD72E0F321B6E5` FOREIGN KEY (`tradeOrder_id`) REFERENCES `trade_order` (`id`),
  CONSTRAINT `FK_74FD72E0FE7A1E62` FOREIGN KEY (`investmentStatus_id`) REFERENCES `investments_status` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `investments` WRITE;
/*!40000 ALTER TABLE `investments` DISABLE KEYS */;
INSERT INTO `investments` VALUES
(1,1,1,0,0,'admin@crowdtek.co.uk :Inv Id: 1 :Offering: Admin offering 1 - House 1','1000','10','GBP','0.1','24',2.99,NULL,'10',NULL,'Aliquam fugiat sit ratione est.',NULL,NULL,'2007-03-01 09:18:43','2026-03-31 09:45:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,0,56,9),
(2,1,2,0,0,'admin@crowdtek.co.uk :Inv Id: 2 :Offering: Admin offering 2 - Public Admin Asset 2','1000','10','GBP','0.1','24',2.99,NULL,'10',NULL,'Adipisci nihil enim dolorem aliquid sint.',NULL,NULL,'2000-06-29 22:57:35','2026-03-31 09:45:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk',2,0,57,10),
(3,4,3,2,0,'Master Test 1','1000','53','AZN','55012','8',1.10,NULL,'53',NULL,'Dolore velit impedit eum deserunt quia.',NULL,NULL,'2006-01-21 05:20:39','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',3,0,58,11),
(4,4,3,1,0,'Master Test 2','20000','33','SBD','45844','9',1.10,NULL,'33',NULL,'Voluptates dolor et sapiente perspiciatis.',NULL,NULL,'1979-08-27 13:49:38','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',4,0,59,12),
(5,13,4,0,0,'maiores_575','12451.50657888','37','SGD','4988321','8',1.10,NULL,'37',NULL,'Et et ipsa animi facere voluptatum et et.',NULL,NULL,'2004-01-03 16:51:21','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',5,0,60,13),
(6,9,4,0,0,'voluptatem_207','238265.49952181','10','SDG','52512','9',1.10,NULL,'10',NULL,'Provident quasi qui cum quos sit aut quasi soluta.',NULL,NULL,'1993-09-04 10:11:35','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',6,0,61,14),
(7,6,5,1,0,'quasi_757','693868.691894','82','GMD','100','6',1.10,NULL,'82',NULL,'Ad fugiat non ex aliquid eveniet.',NULL,NULL,'2008-04-23 07:51:43','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',7,0,62,15),
(8,7,6,0,0,'eligendi_655','67.600876','92','ZMW','78148','1',1.10,NULL,'92',NULL,'Est velit et voluptas laboriosam explicabo.',NULL,NULL,'2000-11-01 14:30:53','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',8,0,63,16),
(9,7,7,2,0,'velit_480','51.429292321','46','MXN','100','6',1.10,NULL,'46',NULL,'Aut et sunt praesentium ullam minus iure dignissimos.',NULL,NULL,'1988-03-12 11:52:15','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',9,0,64,17),
(10,5,8,2,0,'numquam_470','117287867.12468','32','LBP','947399','1',1.10,NULL,'32',NULL,'Necessitatibus ipsam numquam a consequatur.',NULL,NULL,'1977-07-25 06:55:40','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',10,0,65,18),
(11,5,9,1,0,'at_346','2334777','19','JOD','100','0',1.10,NULL,'19',NULL,'Aut qui ipsa minus nostrum.',NULL,NULL,'1977-05-16 06:22:08','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',11,0,66,19),
(12,6,10,0,0,'harum_892','1041','64','SEK','237955977','8',1.10,NULL,'64',NULL,'Odit dolorum recusandae eos atque ullam.',NULL,NULL,'1979-04-20 15:41:00','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',12,0,67,20),
(13,18,5,2,0,'nostrum_70','23.22','90','HRK','182412','6',1.10,NULL,'90',NULL,'Ut voluptas sint fugiat esse quasi in.',NULL,NULL,'2008-04-10 09:49:12','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',13,0,68,21),
(14,4,3,0,0,'Test Investment 16','20000','32','SGD','179941','2',1.10,NULL,'32',NULL,'Inventore ut aliquam cumque nihil itaque corrupti.',NULL,NULL,'2012-07-02 01:25:41','2026-03-31 09:45:54','Userfake@test.com','admin@crowdtek.co.uk',14,0,69,22),
(15,20,18,0,0,'{ \"getCapitalOutstanding\" : \"10\" }','1000','70','XPF','382','4',1.10,NULL,'70',NULL,NULL,NULL,NULL,'1987-09-28 01:38:45','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',15,0,70,23),
(16,20,19,0,0,'saepe_626','1000','75','TOP','11797155','9',1.10,NULL,'75',NULL,NULL,NULL,NULL,'2015-01-20 03:02:05','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',16,0,71,24),
(17,21,19,0,0,'numquam_116','1000','60','ILS','100','4',1.10,NULL,'60',NULL,NULL,NULL,NULL,'1985-12-29 13:15:37','2026-03-31 09:45:54','bc_investor3@test.com','admin@crowdtek.co.uk',17,0,72,25),
(18,20,20,0,0,'in_772','1000','91','CVE','100','5',1.10,NULL,'91',NULL,NULL,NULL,NULL,'2003-10-16 04:10:42','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',18,0,73,26),
(19,21,20,0,0,'eos_953','1000','84','TOP','256','0',1.10,NULL,'84',NULL,NULL,NULL,NULL,'1996-07-18 09:47:26','2026-03-31 09:45:54','bc_investor3@test.com','admin@crowdtek.co.uk',19,0,74,27),
(20,20,20,0,0,'ut_34','1000','23','BND','41050','8',1.10,NULL,'23',NULL,NULL,NULL,NULL,'1971-10-26 02:48:49','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',20,0,75,28),
(21,20,21,0,0,'consectetur_445','1500','86','JPY','6899','3',1.10,NULL,'86',NULL,NULL,NULL,NULL,'1971-06-13 23:17:51','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',21,0,76,29),
(22,21,21,0,0,'nihil_499','1500','40','LSL','4749','6',1.10,NULL,'40',NULL,NULL,NULL,NULL,'1970-01-06 12:19:31','2026-03-31 09:45:54','bc_investor3@test.com','admin@crowdtek.co.uk',22,0,77,30),
(23,20,22,0,0,'#1 Original investment in relisting investment test','3000','3000','GBP','1.25','3',2.00,NULL,'3000',NULL,NULL,NULL,NULL,'1995-02-23 05:48:11','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',23,0,78,31),
(24,21,22,0,0,'#2 Original investment in relisting investment test','4000','85','GBP','100','2',2.00,NULL,'85',NULL,NULL,NULL,NULL,'2013-12-25 07:35:30','2026-03-31 09:45:54','bc_investor3@test.com','admin@crowdtek.co.uk',24,0,79,32),
(25,20,23,0,0,'Relisting investment','1000','1000','GBP','105497','6',1.10,NULL,'1000',NULL,NULL,NULL,NULL,'1994-12-22 20:47:48','2026-03-31 09:45:54','bc_investor2@test.com','admin@crowdtek.co.uk',25,0,80,33),
(26,43,29,0,1,'ben.autotest@crowdtek.co.uk','266.80',NULL,NULL,NULL,NULL,1.16,NULL,'230','72308767',NULL,NULL,43,'2019-12-10 10:50:25','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',26,0,81,34),
(27,43,34,0,0,'ben.autotest@crowdtek.co.uk','187.88',NULL,NULL,NULL,NULL,1.54,NULL,'122','72308773',NULL,NULL,43,'2019-12-10 10:51:12','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',27,0,82,35),
(28,43,33,0,1,'ben.autotest@crowdtek.co.uk','311.04',NULL,NULL,NULL,NULL,1.44,NULL,'216','72308776',NULL,NULL,43,'2019-12-10 10:51:38','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',28,0,83,36),
(29,43,30,0,1,'ben.autotest@crowdtek.co.uk','279.21',NULL,NULL,NULL,NULL,1.23,NULL,'227','72308797',NULL,NULL,43,'2019-12-10 10:52:02','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',29,0,84,37),
(30,43,35,0,1,'ben.autotest@crowdtek.co.uk','1023.96',NULL,NULL,NULL,NULL,2.12,NULL,'483','72308869',NULL,NULL,43,'2019-12-10 10:52:29','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',30,0,85,38),
(31,44,35,0,0,'holly.autotest@helpmewithit.com','644.48',NULL,NULL,NULL,NULL,2.12,NULL,'304','TestFullyDivested',NULL,NULL,44,'2019-12-10 11:05:12','2026-03-31 09:45:54','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',31,304,86,39),
(32,44,34,0,1,'holly.autotest@helpmewithit.com','843.92',NULL,NULL,NULL,NULL,1.54,NULL,'548','72310231',NULL,NULL,44,'2019-12-10 11:05:30','2026-03-31 09:45:54','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',32,0,87,40),
(33,44,33,0,0,'holly.autotest@helpmewithit.com','247.68',NULL,NULL,NULL,NULL,1.44,NULL,'172','72310251',NULL,NULL,44,'2019-12-10 11:05:51','2026-03-31 09:45:54','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',33,0,88,41),
(34,44,30,0,0,'holly.autotest@helpmewithit.com','453.87',NULL,NULL,NULL,NULL,1.23,NULL,'369','72310277',NULL,NULL,44,'2019-12-10 11:06:34','2026-03-31 09:45:54','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',34,0,89,42),
(35,44,36,0,0,'holly.autotest@helpmewithit.com','106.00',NULL,NULL,NULL,NULL,2.12,NULL,'50','72311288',NULL,NULL,44,'2019-12-10 11:16:38','2026-03-31 09:45:54','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',35,50,90,43),
(36,43,34,0,0,'ben.autotest@crowdtek.co.uk','848.54',NULL,NULL,NULL,NULL,1.54,NULL,'551','72312907','Stamp duty aggregate test',NULL,43,'2019-12-10 11:45:52','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',36,0,91,44),
(37,44,31,0,1,'holly.autotest@helpmewithit.com','1447.52',NULL,NULL,NULL,NULL,3.32,NULL,'436','72318058',NULL,NULL,44,'2019-12-10 13:26:34','2026-03-31 09:45:54','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',37,0,92,45),
(38,43,29,0,0,'ben.autotest@crowdtek.co.uk','11333.20',NULL,NULL,NULL,NULL,1.16,NULL,'9770','73663603',NULL,NULL,43,'2020-01-15 14:59:08','2026-03-31 09:45:54','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',38,0,93,46),
(39,45,43,0,0,'ed.autotest@red.com','968',NULL,'GBP',NULL,'5',1.21,NULL,'800','87564976',NULL,'off-market',1,'2022-01-18 11:25:27','2026-03-31 09:45:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk',39,0,94,47),
(40,53,43,0,0,'jim.autotest@crowdtek.co.uk','1452',NULL,'GBP',NULL,'5',1.21,NULL,'1200','22546378',NULL,'off-market',1,'2022-01-18 11:26:05','2026-03-31 09:45:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk',40,0,95,48);
/*!40000 ALTER TABLE `investments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `investments_status`
--

DROP TABLE IF EXISTS `investments_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `investments_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openOn` datetime DEFAULT NULL,
  `isOpen` tinyint(1) NOT NULL,
  `rejectedOn` datetime DEFAULT NULL,
  `isRejected` tinyint(1) NOT NULL,
  `approvedOn` datetime DEFAULT NULL,
  `isApproved` tinyint(1) NOT NULL,
  `withdrawnOn` datetime DEFAULT NULL,
  `isWithdrawn` tinyint(1) NOT NULL,
  `settledOn` datetime DEFAULT NULL,
  `isSettled` tinyint(1) NOT NULL,
  `lifecycleStatus` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `stampDutyPaid` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investments_status`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `investments_status` WRITE;
/*!40000 ALTER TABLE `investments_status` DISABLE KEYS */;
INSERT INTO `investments_status` VALUES
(1,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,0),
(2,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL,0),
(3,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(4,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(5,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(6,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(7,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(8,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(9,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(10,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(11,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(12,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(13,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(14,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,'open',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0),
(15,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(16,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(17,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(18,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(19,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(20,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(21,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(22,'2019-12-09 12:23:06',1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(23,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,'2019-12-09 12:23:06',1,'settled',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(24,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,'2019-12-09 12:23:06',1,'settled',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(25,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,'2019-12-09 12:23:06',1,'settled',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0),
(26,'2019-12-10 10:50:25',1,NULL,0,'2019-12-10 10:50:25',0,NULL,0,'2019-12-10 11:07:44',1,'settled',NULL,'2019-12-10 10:50:25','2019-12-10 11:07:44','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(27,'2019-12-10 10:51:12',1,NULL,0,'2019-12-10 10:51:12',0,NULL,0,'2019-12-10 11:07:51',1,'settled',NULL,'2019-12-10 10:51:12','2019-12-10 11:07:51','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(28,'2019-12-10 10:51:38',1,NULL,0,'2019-12-10 10:51:38',0,NULL,0,'2019-12-10 11:07:59',1,'settled',NULL,'2019-12-10 10:51:38','2019-12-10 11:07:59','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(29,'2019-12-10 10:52:02',1,NULL,0,'2019-12-10 10:52:02',0,NULL,0,'2019-12-10 11:08:10',1,'settled',NULL,'2019-12-10 10:52:02','2019-12-10 11:08:10','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(30,'2019-12-10 10:52:29',1,NULL,0,'2019-12-10 10:52:29',0,NULL,0,'2019-12-10 11:08:19',1,'settled',NULL,'2019-12-10 10:52:29','2019-12-10 11:08:19','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(31,'2019-12-10 11:05:12',1,NULL,0,'2019-12-10 11:05:12',0,NULL,0,'2019-12-10 11:08:26',1,'settled',NULL,'2019-12-10 11:05:12','2019-12-10 11:08:26','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',0),
(32,'2019-12-10 11:05:30',1,NULL,0,'2019-12-10 11:05:30',0,NULL,0,'2019-12-10 11:08:32',1,'settled',NULL,'2019-12-10 11:05:30','2019-12-10 11:08:32','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',0),
(33,'2019-12-10 11:05:51',1,NULL,0,'2019-12-10 11:05:51',0,NULL,0,'2019-12-10 11:08:38',1,'settled',NULL,'2019-12-10 11:05:51','2019-12-10 11:08:38','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',0),
(34,'2019-12-10 11:06:34',1,NULL,0,'2019-12-10 11:06:34',0,NULL,0,'2019-12-10 11:08:43',1,'settled',NULL,'2019-12-10 11:06:34','2019-12-10 11:08:43','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',0),
(35,'2019-12-10 11:16:38',1,NULL,0,'2019-12-10 11:16:38',0,NULL,0,'2019-12-10 11:17:02',1,'settled',NULL,'2019-12-10 11:16:38','2019-12-10 11:17:02','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',0),
(36,'2019-12-10 11:45:52',1,NULL,0,'2019-12-10 11:45:52',1,NULL,0,NULL,0,'approved',NULL,'2019-12-10 11:45:52','2019-12-10 11:45:52','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk',0),
(37,'2019-12-10 13:26:34',1,NULL,0,'2019-12-10 13:26:34',0,NULL,0,'2019-12-10 13:27:01',1,'settled',NULL,'2019-12-10 13:26:34','2019-12-10 13:27:01','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',0),
(38,'2020-01-15 14:59:08',1,NULL,0,'2020-01-15 14:59:08',0,NULL,0,'2020-01-15 14:59:40',1,'settled',NULL,'2020-01-15 14:59:08','2020-01-15 14:59:40','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(39,'2022-01-18 11:25:27',1,NULL,0,NULL,0,NULL,0,'2021-05-09 00:00:00',1,'settled',NULL,'2022-01-18 11:25:27','2022-01-18 11:25:27','admin@crowdtek.co.uk','admin@crowdtek.co.uk',0),
(40,'2022-01-18 11:26:05',1,NULL,0,NULL,0,NULL,0,'2021-08-20 00:00:00',1,'settled',NULL,'2022-01-18 11:26:05','2022-01-18 11:26:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk',0);
/*!40000 ALTER TABLE `investments_status` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `kyc_profile`
--

DROP TABLE IF EXISTS `kyc_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verified` tinyint(1) NOT NULL,
  `lastReviewedAt` datetime DEFAULT NULL,
  `dueDiligenceLevel` int(11) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `verifiedBy_id` int(11) DEFAULT NULL,
  `buyRestricted` tinyint(1) NOT NULL DEFAULT 0,
  `sellRestricted` tinyint(1) NOT NULL DEFAULT 0,
  `depositRestricted` tinyint(1) NOT NULL DEFAULT 0,
  `withdrawRestricted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `IDX_E7FD27B8291AAFE6` (`verifiedBy_id`),
  CONSTRAINT `FK_54320A1A291AAFE6` FOREIGN KEY (`verifiedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_profile`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `kyc_profile` WRITE;
/*!40000 ALTER TABLE `kyc_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_profile` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `kyc_report`
--

DROP TABLE IF EXISTS `kyc_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `providerName` varchar(255) NOT NULL,
  `providerReferenceId` varchar(255) NOT NULL,
  `checkType` varchar(255) NOT NULL,
  `result` varchar(255) NOT NULL,
  `score` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `checkedAt` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_68990A5E23EDC87` (`subject_id`),
  CONSTRAINT `FK_C1CE86A623EDC87` FOREIGN KEY (`subject_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_report`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `kyc_report` WRITE;
/*!40000 ALTER TABLE `kyc_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_report` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `kyc_review`
--

DROP TABLE IF EXISTS `kyc_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `decision` tinyint(1) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `identityReview` tinyint(1) NOT NULL,
  `addressReview` tinyint(1) NOT NULL,
  `countryReview` tinyint(1) NOT NULL,
  `kycProviderReview` tinyint(1) NOT NULL,
  `dueDiligenceLevelReview` tinyint(1) NOT NULL,
  `kycSurveyReview` tinyint(1) NOT NULL,
  `transactionsReview` tinyint(1) NOT NULL,
  `completedAt` datetime DEFAULT NULL,
  `reviewType` varchar(255) NOT NULL DEFAULT 'adhoc',
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `reviewedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D5F5FC1C23EDC87` (`subject_id`),
  KEY `IDX_D5F5FC1C9C6A92E` (`reviewedBy_id`),
  CONSTRAINT `FK_7CA270E423EDC87` FOREIGN KEY (`subject_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_7CA270E49C6A92E` FOREIGN KEY (`reviewedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_review`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `kyc_review` WRITE;
/*!40000 ALTER TABLE `kyc_review` DISABLE KEYS */;
INSERT INTO `kyc_review` VALUES
(1,43,'open',NULL,'For automated testing',1,0,0,1,0,0,0,NULL,'recurring','admin@crowdtek.co.uk','admin@crowdtek.co.uk','2025-04-28 10:53:21','2025-04-28 10:53:32',NULL),
(2,53,'open',NULL,'For automated testing',1,0,0,1,0,0,0,NULL,'recurring','admin@crowdtek.co.uk','admin@crowdtek.co.uk','2025-04-28 10:53:21','2025-04-28 10:53:32',NULL);
/*!40000 ALTER TABLE `kyc_review` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mails`
--

DROP TABLE IF EXISTS `mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `name` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `subject` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `body` longtext NOT NULL,
  `sendAdmin` tinyint(1) DEFAULT NULL,
  `sendUser` tinyint(1) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mails`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mails` WRITE;
/*!40000 ALTER TABLE `mails` DISABLE KEYS */;
INSERT INTO `mails` VALUES
(1,'investment.new','Thank You for Investing','Thank You for Investing','Hello {{user.fullname}}, <br/><br/> Your investment into <strong>{{asset.name}} - {{offering.name}}</strong> of <strong>{{investment.investmentValue}} GBP</strong> has been registered successfully. <br/><br/>We will notify you at the end of the month, once your investment has been settled and your shares are issued. <br/><br/>Please note - rental income is paid 1 month in arrears, from the point of settlement. <br/><br/>If you have any questions please contact the team; team@yielders.co.uk <br/><br/>Thanks,',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(2,'investment.settled','Investment Settled','Investment Settled','Hi {{user.fullname}}<br><br> Congratulations! Your investment has officially been settled and you are now lawful the owner of ({{investment.numberOfShares}}) shares in {{asset.name}}. <br><br> Our management team have been working extremely hard in the background to ensure all legalities and share register updates have been made. You will now be able to access your share certificate under your investment in My Portfolio alongside all other documentation in relation to your investment. <br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(3,'user.registration','Congratulations on becoming a Yielder !','Congratulations on becoming a Yielder !','Hi {{user.fullname}}, <br/><br> Congratulations on becoming a Yielder!<br/><br/> You have made the first step towards a successful investment portfolio.<br/><br/> Please follow the link below to verify your email address and complete your profile. <br/><br/> <a href=\"{{url}}\">Verify Email</a> <br/>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(4,'user.registration.admin','Congratulations on becoming a Yielder !','Congratulations on becoming a Yielder !','Hi Team Yielders, <br/><br>\n<strong>{{user.fullname}} - {{user.email}}</strong> has signed up to your site. <br/>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(5,'user.password.forgot','Password Reset ','Password Reset','Hi {{user.fullname}}, <br/><br/>\nWe have received your request to reset your Yielders password.<br/><br/>\n<strong>Password Re-set:</strong><br/> 1) Your Password must include at least 8 characters including a minimum of 1 capital letter and 1 number.<br/><br/>\nClick here to create a new password: <br/> <a href=\"{{url}}\">Reset Password</a> <br/><br/> 1) Caution - This link will expire within 24 hours.<br/> 2) Once password has been reset, you can log into your account immediately.<br/> 3) If you have any further problems - please do not hesitate to get in contact with team@yielders.co.uk, quoting your user email account.<br/>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(6,'user.password.forgot.admin','Password Reset ','Password Reset','Hi Team Yielders, <br/><br/>\nUser <strong>{{user.fullname}} - {{user.email}}</strong> has requested to reset their account password<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(7,'offering.new','New Offering created','New Offering created','Dear admin team ,<br><br> A new offering is now available for our users to Invest in<br> Offering : {{offering.Name}} for Asset {{asset.Name}} <br><br> If you have not completed your registration, please continue to do so at www.yielders.co.uk.<br> If you are already a registered user, please log in, top your e-wallets and  become a part of this unique investment opportunity. <br><br> If you require any further assistance, please contact us on <br> team@yielders.co.uk<br> 02072054650 <br><br> Thank you<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(8,'relist.offering.new.admin','Request to sell shares','Request to sell shares','Hi Yielders Team ,<br><br> User <strong>{{user.fullname}} - ID:{{user.id}}</strong> has requested to sell shares in <strong>{{asset.Name}} - {{offering.Name}}</strong><br> <strong>Number of shares for sale:</strong> {{offering.fundingGoal / asset.pricePerShare}}<br> <strong>Value of Shares:</strong> £{{offering.fundingGoal}} <br><br> <a href=\"https://backoffice.yielders.co.uk/admin/offering/{{offering.Id}}/edit\">Offering Link</a> <br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(9,'relist.offering.new','Request to sell shares','Request to sell shares','Hi {{user.fullname}} ,<br><br> Your request for selling your shares in <strong>{{asset.Name}} - {{offering.Name}}</strong><br> has been request. A member of Yielders team will get in touch with you shortly.<br><br> <strong>Number of shares for sale:</strong> {{offering.fundingGoal / asset.pricePerShare}}<br> <strong>Value of Shares:</strong> £{{offering.fundingGoal}} <br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(10,'fundinggoal.fiftypercent','Funding Goal Fifty Percent','Funding Goal Fifty Percent','Hi {{user.firstname}},<br><br> Half way there! We have managed to raise 50% investment in {{asset.name}} - {{offering.name}}.<br> <br><br>Theres still time for you to get involved! <br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(11,'contego.response.green','Contego Response Green','Contego Response Green','Hi Admin team , <br/> <br> Contego has responded with GREEN for the following User {{user.username}}<br/> ',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(12,'contego.response.red','Contego Response Red','Contego Response Red','Hi Admin team , <br/> <br> Contego has responded with RED for the following User {{user.username}}<br/> ',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(13,'contego.response.amber','Contego Response Amber','Contego Response Amber','Hi Admin team , <br/><br> Contego has responded with AMBER for the following User {{user.username}}<br/> ',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(14,'fundinggoal.hundredpercent','Funding Goal Hundred Percent','Funding Goal Hundred Percent','Hi {{user.firstname}},<br><br> The investment round for  {{asset.name}} -{{offering.name}} has now closed!<br><br> Congratulations on your investment! We will be in contact shortly to confirm your settled investments, share certificates and dividend payments. <br><br> If you have any questions about your investment please get in contact with the team - team@yielders.co.uk <br><br> Thank You<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(15,'offering.cancelled','Offering Cancelled','Offering Cancelled','Hi {{user.fullname}},<br><br> Unfortunately due to unforeseen circumstances, we have had to cancel the following offering:<br>{{asset.name}} -{{offering.name}} <br><br> We are endeavouring to come to a solution and will be in contact once we have further information. However, in the mean time please feel free to explore our other assets. <br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(16,'transaction.created','Transaction Created','Transaction Created','Hi {{user.fullname}},<br><br> A financial transaction for the following amount has been created {{investment.investmentValue}}<br><br> Transaction id : {{transaction.id}}<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(17,'transaction.failed','Transaction Failed','Transaction Failed','Hi {{user.fullname}} <br><br> The transaction for investment for {{investment.investmentValue}} has failed, the admin are looking into the issue.<br><br> Transaction id : {{transaction.id}}<br><br> Thanks,',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(18,'transaction.paid','Transaction Paid','Transaction Paid','Hi {{user.fullname}}<br><br> Your investment for {{investment.investmentValue}} has been successfully executed.<br><br> Transaction id : {{transaction.id}}<br><br>Thanks,',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(19,'transaction.cancelled','Transaction Cancelled','Transaction Cancelled','Hi {{user.fullname}}<br><br> The transaction for investment for {{investment.investmentValue}} has been cancelled by the Admin team.<br><br> Transaction id : {{transaction.id}}<br><br>Thanks,',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(20,'asset.new','New Asset Created','New Asset Created','Dear {{user.fullname}},<br><br> The following Asset has been created<br> Asset Name :  {{asset.name}}<br><br> Thank you<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(21,'user.approved','User Approved','User Approved','Dear {{user.fullname}},<br><br> You have been approved to start trading !<br><br> Thank you<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(22,'yielder.top','Top Yielder','Top Yielder','Dear {{user.fullname}},<br><br> You have been approved to start trading !<br><br> Thank you<br>,',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(23,'mangopay.payin_banktransfer','Bankwire Transfer Details','Bankwire Transfer Details','Dear {{user.fullname}},<br><br>\nPlease see below the details in relation to bank wire transfer.<br><br>\n<strong>Type:</strong> {{ type }}<br> <strong>Owner:</strong> {{ ownerName }}<br> <strong>IBAN:</strong> GB09 BARC 2000 0063 9564 74<br> <strong>BIC:</strong> {{ BIC }}<br> <strong>Wire Reference:</strong> {{ wireReference }}<br> <strong>Sort Code:</strong> 20-00-00<br> <strong>Account:</strong> 63956474<br><br>\n<strong>Here is a brief guide on how to process a Bankwire Transfer:</strong><br><br> A bank wire Pay-in is a request to process a payment by bank transfer (BACS/CHAPS). <br><br>\n<strong>To send us a bank transfer, you will have to do the following:</strong><br> - There is a dedicated team who processes these requests every day at 11am and 3pm. If you send over a bank transfer at 4pm- the funds will be added to your E-wallet the following day at 11am.<br> - The amount is debited when the MangoPay team has processed the request.<br> - Once the MangoPay team has processed the request, you will receive a notification via e-mail of the successful transfer.<br> - If in the unlikely event that this happens, we are more than happy to do everything we can to help. You can contact us on team@yielders.co.uk<br> <br> <strong>Online banking</strong><br> 1. Log on to your bank<br> 2. Select pay/pay new payee<br> 3. Input payment instruction details with the information provided by the platform<br> 4. Enter the unique Yielders account reference. This sends the funds direct to your E-wallet<br> 5. Enter amount to pay<br> 6. Confirm payment & send <br><br>\n<strong>Pay by telephone</strong><br> 1. Contact your bank and speak with personnel that can make a payment for you<br> 2. Give them the details we have provided you<br> 3. Confirm to make payment<br><br>\nIf you have any questions, do not hesitate to contact us. <br> Thank you<br>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(24,'user.gdpr_reject.admin','User Rejected GDPR','User has rejected GDPR','Hi Team Yielders, <br/><br>\n<strong>{{user.fullname}} - {{user.email}}</strong> has rejected GDPR. <br/>',1,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(25,'user.onboarding.complete','User Onboarding Complete','Congratulations - You\'re a Yielder!','Hi {{user.fullname}}, <br/><br> Congratulations on becoming a Yielder!<br/><br/> Your profile is now complete, you can now add funds to your account and proceed to invest.<br/><br/> If you require any further assistance, please do not hesitate to get in contact on <a href=\"mailto:team@yielders.co.uk\">team@yielders.co.uk</a>. <br/>',0,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(26,'user.onboarding.complete.admin','Admin - User Onboarding Complete','User Onboarding Complete','Hi Team Yielders, <br/><br> {{user.fullname}} - {{user.email}} has completed their profile. Ready to invest.',1,0,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(27,'user.onboarding.resubmit','User Onboarding Resubmit ID','User Onboarding In Progress','Hi {{user.fullname}}, <br/><br> Thank you for signing up to the Yielders platform.<br/><br/> We are working on completing your profile, this can take up to 4 (working) hours. Sit tight and we\'ll be in contact as soon as your profile is finalised. In the meantime you can still browse and invest on the platform (subject to T&C\'s). <br/><br/> <a href=\"https://www.yielders.co.uk/all-properties\">Browse all properties</a> <br/><br/>',0,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(28,'user.onboarding.resubmit.admin','Admin - User Onboarding In Progress','User Onboarding In Progress','Hi Team Yielders, <br/><br> {{user.fullname}} - {{user.email}} has completed their profile, but ID is not verified.<br/><br/> Client needs to resubmit ID<br/><br/>',1,0,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(29,'user.onboarding.contact','User Onboarding Contact Admin','Contact Admin','Hi {{user.fullname}}, <br/><br> Thank you for signing up to the Yielders platform.<br/><br/> We are working on verifying your profile, this can take up to 24 hours. Sit tight and we\'ll be in contact as soon as your profile is complete. </br></br>',0,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(30,'user.onboarding.contact.admin','Admin - User Onboarding Contact Admin','Contact Admin','Hi Team Yielders, <br/><br> {{user.fullname}} - {{user.email}} has not been on-boarded; please review their profile.<br/><br/>',1,0,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(31,'user.onboarding.questionnaire_reset','Continue Onboarding','Continue Onboarding','Hi {{user.fullname}}, <br/><br> Thank you for your interest in Yielders! You can now <a href=\"https://yielders.co.uk/login\">log back in</a> and complete your profile.<br/><br/> In the meantime, if you require any further assistance please do not hesitate to contact us. </br></br>',0,1,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(32,'user.mfa.email_code','Yielders Login Verification Code','Yielders Login Verification Code','Hi {{user.firstname}},<br><br>Please enter the following code to finish logging in:<br><br><b>{{ user.security.emailAuthCode }}</b><br>',0,1,0,'2020-09-21 14:54:00','2020-09-21 14:54:00',NULL,NULL),
(33,'dividend.payment_confirmation','Dividend Payment Confirmation','Dividend Payment Confirmation','Dear {{ user.fullname }},<br><br> We are pleased to let you know that your dividend of £{{ amount }} for {{ assetName }} has been paid for {{ month }}, straight into your e-wallet. This amount is based on your shareholding of {{ numOfShares }}, and may be subject to fluctuation based on aspects such as arrears or unexpected expenses incurred by the SPV.<br><br>If you have any questions, please don\'t hesitate to get in contact with the Team through email <a href=\'mailto:team@yielders.co.uk\'>team@yielders.co.uk</a>.<br><br> ',0,1,0,'2020-10-06 10:42:53','2020-10-06 10:42:53',NULL,NULL);
/*!40000 ALTER TABLE `mails` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `oauth2_access_token`
--

DROP TABLE IF EXISTS `oauth2_access_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth2_access_token` (
  `identifier` char(80) NOT NULL COMMENT '(DC2Type:string)',
  `client` varchar(32) NOT NULL COMMENT '(DC2Type:string)',
  `expiry` datetime NOT NULL,
  `userIdentifier` varchar(128) DEFAULT NULL COMMENT '(DC2Type:string)',
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  PRIMARY KEY (`identifier`),
  KEY `IDX_454D9673C7440455` (`client`),
  CONSTRAINT `FK_454D9673C7440455` FOREIGN KEY (`client`) REFERENCES `oauth2_client` (`identifier`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_access_token`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `oauth2_access_token` WRITE;
/*!40000 ALTER TABLE `oauth2_access_token` DISABLE KEYS */;
INSERT INTO `oauth2_access_token` VALUES
('0716e9894a98a864ade90a807608116e506cceb89f945da488a28dd3cb61033d8d3b47c377eda022','904c1b4d9a15529ed70ff5e686345a9f','2021-07-05 13:49:19','ben.autotest@crowdtek.co.uk','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',0),
('4edebb3b7148daefc919515e2cd86ed2a6941e6dc6a294b3685103c32f17b23467ee2b5b2273bfd3','904c1b4d9a15529ed70ff5e686345a9f','2026-04-29 16:44:30','ben.autotest@crowdtek.co.uk','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',0),
('831b993f9623b79bb330a6224230ac3214030962ea6adf77abe92f7f2e81c002bf03c6e08157a784','904c1b4d9a15529ed70ff5e686345a9f','2026-06-09 17:54:36','ben.autotest@crowdtek.co.uk','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',0),
('ad21eb4f96c4d354ac0c5eff03467baa1b5288efb4c0534069fbbe31f1b64f5745f6acbef5c1ecbf','904c1b4d9a15529ed70ff5e686345a9f','2026-04-22 16:30:40','ben.autotest@crowdtek.co.uk','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',0),
('bd0a3b929e71576398ddbbeae47fd1c3258b887ded8152e62c4f5588b2006c5868d2b11c05a0c924','904c1b4d9a15529ed70ff5e686345a9f','2026-04-22 16:59:33','ben.autotest@crowdtek.co.uk','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',0),
('c17d2bf1ec71aa36b7f576006354f52ecd6f27f1f14986b9ce4607c590a81f1b419d478edffccc84','904c1b4d9a15529ed70ff5e686345a9f','2023-04-04 17:10:16','ben.autotest@crowdtek.co.uk','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',0);
/*!40000 ALTER TABLE `oauth2_access_token` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `oauth2_authorization_code`
--

DROP TABLE IF EXISTS `oauth2_authorization_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth2_authorization_code` (
  `identifier` char(80) NOT NULL COMMENT '(DC2Type:string)',
  `client` varchar(32) NOT NULL COMMENT '(DC2Type:string)',
  `expiry` datetime NOT NULL,
  `userIdentifier` varchar(128) DEFAULT NULL COMMENT '(DC2Type:string)',
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  PRIMARY KEY (`identifier`),
  KEY `IDX_509FEF5FC7440455` (`client`),
  CONSTRAINT `FK_509FEF5FC7440455` FOREIGN KEY (`client`) REFERENCES `oauth2_client` (`identifier`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_authorization_code`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `oauth2_authorization_code` WRITE;
/*!40000 ALTER TABLE `oauth2_authorization_code` DISABLE KEYS */;
INSERT INTO `oauth2_authorization_code` VALUES
('13f763b073009b79d83aa560755b000742420da049c69f24448627d84e66f298aac7bbea8a08644c','904c1b4d9a15529ed70ff5e686345a9f','2023-04-04 16:20:16','ben.autotest@crowdtek.co.uk',NULL,1),
('4a4fb317f52add61801b802ab7e5d0444cc3b0a21580d18ad3a97a685cbfda9d46c321b442e6356b','904c1b4d9a15529ed70ff5e686345a9f','2026-06-09 17:04:35','ben.autotest@crowdtek.co.uk',NULL,1),
('94ebff2dd11455ece730e31cb148001b5a1bab369910361c9da4dc94460b58e2dfc606a3cab03800','904c1b4d9a15529ed70ff5e686345a9f','2026-04-29 15:54:30','ben.autotest@crowdtek.co.uk',NULL,1),
('c959516fd7e5cc4a1815898cafe7ac708f36bd7e603fba2a091df7cdafd7c6c74be3f4cc67a32536','904c1b4d9a15529ed70ff5e686345a9f','2026-04-22 16:09:33','ben.autotest@crowdtek.co.uk',NULL,1);
/*!40000 ALTER TABLE `oauth2_authorization_code` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `oauth2_client`
--

DROP TABLE IF EXISTS `oauth2_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth2_client` (
  `identifier` varchar(32) NOT NULL COMMENT '(DC2Type:string)',
  `secret` varchar(128) DEFAULT NULL COMMENT '(DC2Type:string)',
  `redirectUris` text DEFAULT NULL,
  `grants` text DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `allowPlainTextPkce` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_client`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `oauth2_client` WRITE;
/*!40000 ALTER TABLE `oauth2_client` DISABLE KEYS */;
INSERT INTO `oauth2_client` VALUES
('904c1b4d9a15529ed70ff5e686345a9f','71dcf8066c14b07a772a3c8af9217c318dc4385f9fa8215ab5eb11e511fd2cbda50714463088226b8ceefed0126482f2c36f2f4fe7ec4f5ba7c47935b02a9c8e','http://local.qac.com/auth/callback http://front.dev.local/auth/callback http://front.dev.local:8001/auth/callback https://test-front.yielderverse.co.uk/auth/callback http://test-front.yielderverse.co.uk/auth/callback https://stable-front.crowdtek.co.uk/auth/callback https://nda-front.crowdtek.co.uk/auth/callback https://mig-front.crowdtek.co.uk/auth/callback https://preview-front.yielderverse.co.uk/auth/callback https://next-front.yielderverse.co.uk/auth/callback https://dev-front.yielderverse.co.uk/auth/callback http://127.0.0.1:8000/auth/callback http://front-ci.dev/auth/callback','client_credentials password authorization_code refresh_token','asset:read asset:write investment:read investment:write offering:read offering:write payout:read payout:write user:read user:write',1,0,'');
/*!40000 ALTER TABLE `oauth2_client` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `oauth2_refresh_token`
--

DROP TABLE IF EXISTS `oauth2_refresh_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth2_refresh_token` (
  `identifier` char(80) NOT NULL COMMENT '(DC2Type:string)',
  `access_token` char(80) DEFAULT NULL COMMENT '(DC2Type:string)',
  `expiry` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  PRIMARY KEY (`identifier`),
  KEY `IDX_4DD90732B6A2DD68` (`access_token`),
  CONSTRAINT `FK_4DD90732B6A2DD68` FOREIGN KEY (`access_token`) REFERENCES `oauth2_access_token` (`identifier`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_refresh_token`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `oauth2_refresh_token` WRITE;
/*!40000 ALTER TABLE `oauth2_refresh_token` DISABLE KEYS */;
INSERT INTO `oauth2_refresh_token` VALUES
('02249aeb7f6e861b8fa3d259c5d93049f997459e0a63087802a2bacf92fe898bf20d3888904b7e06','831b993f9623b79bb330a6224230ac3214030962ea6adf77abe92f7f2e81c002bf03c6e08157a784','2026-07-09 16:54:36',0),
('3cd294241c59d5c0c1f944944f44243dcd362c9ac1f0f14adc2a6e09abca909a322b29f9c9c7be3a','0716e9894a98a864ade90a807608116e506cceb89f945da488a28dd3cb61033d8d3b47c377eda022','2021-08-05 12:49:19',0),
('68d6f287d5bb3eca55975a1c49d6bec2b414405bcfe46e54dcc06c7199682f078e41f4576481bb12','4edebb3b7148daefc919515e2cd86ed2a6941e6dc6a294b3685103c32f17b23467ee2b5b2273bfd3','2026-05-29 15:44:30',0),
('dc10c8b1a9232d588fbfe55fddaf8a0bd60dd8e25c282d7049bf279768a4551af70368de52de74f1','bd0a3b929e71576398ddbbeae47fd1c3258b887ded8152e62c4f5588b2006c5868d2b11c05a0c924','2026-05-22 15:59:33',0);
/*!40000 ALTER TABLE `oauth2_refresh_token` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `offering_add_fields`
--

DROP TABLE IF EXISTS `offering_add_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offering_add_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `off_id` int(11) DEFAULT NULL,
  `fieldKey` varchar(255) NOT NULL,
  `fieldValue` varchar(255) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_321CAAD290AB2FC9` (`fieldKey`),
  KEY `IDX_321CAAD2D95F2A3C` (`off_id`),
  CONSTRAINT `FK_321CAAD2D95F2A3C` FOREIGN KEY (`off_id`) REFERENCES `offerings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offering_add_fields`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `offering_add_fields` WRITE;
/*!40000 ALTER TABLE `offering_add_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `offering_add_fields` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `offering_docs`
--

DROP TABLE IF EXISTS `offering_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offering_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `off_id` int(11) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7BEEE766C33F7837` (`document_id`),
  KEY `IDX_7BEEE766D95F2A3C` (`off_id`),
  CONSTRAINT `FK_7BEEE766C33F7837` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  CONSTRAINT `FK_7BEEE766D95F2A3C` FOREIGN KEY (`off_id`) REFERENCES `offerings` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offering_docs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `offering_docs` WRITE;
/*!40000 ALTER TABLE `offering_docs` DISABLE KEYS */;
INSERT INTO `offering_docs` VALUES
(1,1,8,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,2,9,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,3,17,3,'2019-12-09 12:23:06','2026-03-31 09:46:05',NULL,'admin@crowdtek.co.uk'),
(4,3,18,3,'2019-12-09 12:23:06','2026-03-31 09:46:05',NULL,'admin@crowdtek.co.uk'),
(5,24,29,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(6,25,30,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(7,27,54,24,'2019-12-09 17:18:54','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(8,28,55,25,'2019-12-09 17:19:04','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(9,29,56,26,'2019-12-09 17:19:15','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(10,30,57,27,'2019-12-09 17:19:23','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(11,31,58,28,'2019-12-09 17:19:32','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(12,32,59,28,'2019-12-09 17:19:45','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(13,34,60,30,'2019-12-09 17:19:56','2026-03-31 09:46:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(14,39,69,NULL,'2019-12-10 13:25:38','2019-12-10 13:25:38','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(15,40,70,NULL,'2019-12-10 13:25:50','2019-12-10 13:25:50','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(16,41,71,NULL,'2019-12-10 13:40:28','2019-12-10 13:40:28','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(17,42,72,NULL,'2019-12-10 13:40:52','2019-12-10 13:40:52','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com');
/*!40000 ALTER TABLE `offering_docs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `offerings`
--

DROP TABLE IF EXISTS `offerings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offerings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inv_id` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `offeringType` varchar(50) NOT NULL COMMENT '(DC2Type:string)',
  `name` varchar(50) NOT NULL COMMENT '(DC2Type:string)',
  `additionalType` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `category` varchar(20) DEFAULT NULL COMMENT '(DC2Type:string)',
  `fundingGoal` decimal(10,2) DEFAULT 0.00,
  `externalCommitments` decimal(15,2) DEFAULT 0.00,
  `isFeatured` tinyint(1) DEFAULT 0,
  `isSecondaryMrkt` tinyint(1) DEFAULT 0,
  `valuation` decimal(15,2) DEFAULT NULL,
  `equityOffered` decimal(15,2) DEFAULT 0.00,
  `noOfShares` int(11) DEFAULT 0,
  `pricePerShare` decimal(10,2) DEFAULT 0.00,
  `netRentProjected` decimal(10,2) DEFAULT NULL,
  `grossRentProjected` decimal(10,2) DEFAULT NULL,
  `grossProjectReturn` decimal(10,2) DEFAULT NULL,
  `offeringTerm` int(11) DEFAULT NULL,
  `openDate` datetime DEFAULT NULL,
  `closeDate` datetime DEFAULT NULL,
  `minCommitUser` decimal(15,2) DEFAULT 0.00,
  `maxCommitUser` decimal(15,2) DEFAULT 0.00,
  `maxOverFunding` decimal(15,2) DEFAULT 0.00,
  `primaryOfferingId` int(11) DEFAULT 0,
  `comments` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `visibility` int(11) NOT NULL DEFAULT 0,
  `currency` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `offeringStatus_id` int(11) DEFAULT NULL,
  `transactionId` varchar(255) DEFAULT NULL,
  `tradeOrder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A7CD243B2F27AA81` (`offeringStatus_id`),
  UNIQUE KEY `UNIQ_A7CD243BF321B6E5` (`tradeOrder_id`),
  KEY `IDX_A7CD243B6F6FD57F` (`inv_id`),
  KEY `IDX_A7CD243B5DA1941` (`asset_id`),
  CONSTRAINT `FK_A7CD243B2F27AA81` FOREIGN KEY (`offeringStatus_id`) REFERENCES `offerings_status` (`id`),
  CONSTRAINT `FK_A7CD243B5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_A7CD243B6F6FD57F` FOREIGN KEY (`inv_id`) REFERENCES `investments` (`id`),
  CONSTRAINT `FK_A7CD243BF321B6E5` FOREIGN KEY (`tradeOrder_id`) REFERENCES `trade_order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offerings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `offerings` WRITE;
/*!40000 ALTER TABLE `offerings` DISABLE KEYS */;
INSERT INTO `offerings` VALUES
(1,NULL,1,'offering','Admin offering 1 - House 1',NULL,'at',10000.00,0.00,1,1,0.00,0.00,0,1.10,NULL,NULL,NULL,24,NULL,NULL,0.00,0.00,0.00,0,'Quam quaerat ipsum earum vel qui itaque voluptatem.',0,NULL,1,'2011-04-25 15:30:39','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',1,NULL,1),
(2,NULL,2,'offering','Admin offering 2 - Public Admin Asset 2',NULL,'dicta',10000.00,0.00,0,0,0.00,0.00,0,1.50,NULL,NULL,NULL,12,NULL,NULL,0.00,0.00,0.00,0,'Autem sint laboriosam et aut.',2,NULL,1,'1990-08-02 00:41:10','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',2,NULL,2),
(3,NULL,3,'offering','Master Test',NULL,'magnam',72858566.74,0.00,1,1,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Nostrum corporis hic sit sit soluta veniam illum.',0,NULL,1,'2001-10-20 15:33:40','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',3,NULL,3),
(4,NULL,7,'offering','voluptatum_109',NULL,'blanditiis',47807250.70,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Est nam ipsa ut accusantium assumenda et fugit.',1,NULL,1,'1975-06-14 12:17:17','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',4,NULL,4),
(5,NULL,10,'offering','corrupti_72',NULL,'dolorum',20027141.18,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Qui deserunt qui veniam eligendi enim asperiores dolore saepe.',1,NULL,1,'1978-12-15 01:24:00','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',5,NULL,5),
(6,NULL,9,'offering','voluptatem_911',NULL,'molestias',8861626.72,0.00,1,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Odit occaecati consectetur aut exercitationem cupiditate ullam.',1,NULL,1,'2000-09-14 17:57:33','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',6,NULL,6),
(7,NULL,10,'offering','qui_849',NULL,'sunt',34529746.51,0.00,1,1,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Recusandae omnis id nostrum nobis doloribus recusandae.',2,NULL,1,'2001-11-15 03:18:47','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',7,NULL,7),
(8,NULL,3,'offering','repellat_157',NULL,'dignissimos',20390623.20,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Tenetur est iure nihil et voluptatem.',0,NULL,1,'1977-10-01 00:27:37','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',8,NULL,8),
(9,NULL,5,'offering','labore_110',NULL,'nihil',50572622.07,0.00,1,1,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Quasi consectetur laborum necessitatibus aut.',0,NULL,1,'2006-04-28 05:40:46','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',9,NULL,9),
(10,NULL,12,'offering','fuga_814',NULL,'et',52642592.99,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Nisi quas cupiditate quae repellendus eaque et itaque amet.',2,NULL,1,'2012-10-03 08:24:13','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',10,NULL,10),
(11,NULL,9,'offering','labore_995',NULL,'eveniet',14454554.60,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Omnis quis magnam labore non.',1,NULL,1,'2017-06-02 18:03:02','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',11,NULL,11),
(12,NULL,9,'offering','iusto_56',NULL,'vel',65631585.66,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Aliquid doloremque corrupti quia non vel ut quia id.',2,NULL,1,'1985-06-05 07:11:01','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',12,NULL,12),
(13,NULL,3,'offering','dolorem_151',NULL,'eligendi',89016629.67,79596693.40,0,1,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Aliquam ut omnis velit ad excepturi.',0,NULL,1,'2004-10-13 23:09:05','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',13,NULL,13),
(14,NULL,15,'offering','molestiae_767',NULL,'nulla',40087373.17,57373190.46,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Minima blanditiis et earum quia.',2,NULL,1,'1989-12-24 02:07:06','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',14,NULL,14),
(15,NULL,15,'offering','molestiae_774',NULL,'ullam',53365334.73,50163622.97,0,1,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Mollitia sint et esse aliquam in esse omnis.',2,NULL,1,'1984-10-15 05:36:03','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',15,NULL,15),
(16,NULL,15,'offering','offering14',NULL,'velit',32598981.07,88692045.22,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Odio neque aut quisquam.',0,NULL,1,'1989-12-10 01:54:11','2026-03-31 09:27:29','Userfake@test.com','admin@crowdtek.co.uk',16,NULL,16),
(17,NULL,17,'offering','consectetur_42',NULL,'reiciendis',1000.00,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Recusandae ad reiciendis architecto tempore adipisci impedit.',0,NULL,1,'1987-09-27 23:04:43','2026-03-31 09:27:29','bc_investor1@test.com','admin@crowdtek.co.uk',17,NULL,17),
(18,NULL,18,'offering','non_341',NULL,'neque',2000.00,1000.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Ipsum ut qui nostrum deserunt.',0,NULL,1,'2000-12-15 21:46:57','2026-03-31 09:27:29','bc_investor1@test.com','admin@crowdtek.co.uk',18,NULL,18),
(19,NULL,19,'offering','nisi_548',NULL,'reiciendis',4000.00,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Quo exercitationem repudiandae nihil quia ut.',0,NULL,1,'1979-04-25 18:26:06','2026-03-31 09:27:29','bc_investor1@test.com','admin@crowdtek.co.uk',22,NULL,19),
(20,NULL,20,'offering','amet_329',NULL,'sunt',12000.00,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Accusantium sapiente voluptatum sint tempora quae sapiente.',0,NULL,1,'1992-08-06 08:41:31','2026-03-31 09:27:29','bc_investor1@test.com','admin@crowdtek.co.uk',19,NULL,20),
(21,NULL,21,'offering','maxime_381',NULL,'aut',32855282.80,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Perferendis neque facere reiciendis vero esse est fugiat.',0,NULL,1,'1979-12-02 11:12:56','2026-03-31 09:27:29','bc_investor1@test.com','admin@crowdtek.co.uk',23,NULL,21),
(22,NULL,21,'offering','Relisting example - This the original offering',NULL,'necessitatibus',31997144.13,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Temporibus magni ratione quasi perferendis voluptatibus.',0,NULL,1,'1992-07-24 17:17:44','2026-03-31 09:27:29','bc_investor1@test.com','admin@crowdtek.co.uk',20,NULL,22),
(23,NULL,21,'offering','Relisting example - This the relisting offering',NULL,'et',63551715.94,0.00,0,1,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,22,'Nisi laborum tempore ipsa autem velit.',0,NULL,20,'1989-01-02 02:34:33','2026-03-31 09:27:29','bc_investor2@test.com','admin@crowdtek.co.uk',21,NULL,23),
(24,NULL,22,'offering','Public offering - Public Asset 1',NULL,'et',3500.00,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Laudantium ea ut maxime qui magnam natus sed quod.',2,NULL,1,'1983-10-22 14:03:58','2026-03-31 09:27:29','public_user1@gmail.com','admin@crowdtek.co.uk',24,NULL,24),
(25,NULL,23,'offering','Public offering - Public Asset 2 - part 1',NULL,'qui',73.00,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Et ut error quas facilis nostrum.',2,NULL,1,'1998-02-05 21:17:08','2026-03-31 09:27:29','public_user2@gmail.com','admin@crowdtek.co.uk',25,NULL,25),
(26,NULL,23,'offering','Public offering - Public Asset 2 - part 2',NULL,'rem',9377.00,0.00,0,0,0.00,0.00,0,1.10,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0,'Dolorem doloremque vitae numquam aut ullam ullam commodi repellat.',2,NULL,1,'1995-05-01 20:01:17','2026-03-31 09:27:29','public_user2@gmail.com','admin@crowdtek.co.uk',26,NULL,26),
(27,NULL,24,'prefunding','Quayside Apartments A - Bristol',NULL,NULL,22000.00,0.00,0,1,0.00,0.00,12500,1.76,4.56,NULL,25.32,5,NULL,NULL,100.32,1500.00,22000.00,0,NULL,2,NULL,1,'2019-12-09 16:44:18','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',27,NULL,27),
(28,NULL,25,'offering','Quayside Apartments B - Bristol',NULL,NULL,282000.00,0.00,1,1,0.00,100.00,0,0.00,4.98,NULL,26.64,5,NULL,NULL,0.00,0.00,282000.00,0,NULL,0,NULL,1,'2019-12-09 16:45:35','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',28,NULL,28),
(29,NULL,26,'offering','Lodge de Lac - Cumbria',NULL,NULL,11600.00,0.00,1,1,0.00,0.00,0,0.00,6.02,NULL,32.44,5,NULL,NULL,0.00,0.00,11600.00,0,NULL,0,NULL,1,'2019-12-09 16:46:38','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',29,NULL,29),
(30,NULL,27,'offering','Light at the Peninsula - Land\'s End',NULL,NULL,6150.00,0.00,0,1,0.00,10.00,0,0.00,6.33,NULL,21.22,3,NULL,NULL,0.00,0.00,6150.00,0,NULL,0,NULL,1,'2019-12-09 16:47:47','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',30,NULL,30),
(31,NULL,28,'offering','Wayward Plaza 1 - London',NULL,NULL,66400.00,0.00,0,1,0.00,0.00,0,0.00,12.54,NULL,12.54,1,NULL,NULL,0.00,0.00,66400.00,0,NULL,0,NULL,1,'2019-12-09 16:48:43','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',31,NULL,31),
(32,NULL,28,'offering','Wayward Plaza 2 - London',NULL,NULL,66400.00,0.00,1,1,0.00,10.00,0,0.00,12.36,NULL,12.36,1,NULL,NULL,0.00,0.00,66400.00,0,NULL,0,NULL,1,'2019-12-09 16:49:26','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',32,NULL,32),
(33,NULL,29,'offering','Partingdale House A - Reading',NULL,NULL,36000.00,0.00,0,1,0.00,20.00,0,0.00,5.96,NULL,25.36,4,NULL,NULL,0.00,0.00,36000.00,0,NULL,0,NULL,1,'2019-12-09 16:50:39','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',33,NULL,33),
(34,NULL,30,'offering','Kolness by the Moor - Okehampton',NULL,NULL,14400.00,0.00,1,1,0.00,20.00,0,0.00,6.58,NULL,13.54,2,NULL,NULL,0.00,0.00,14400.00,0,NULL,0,NULL,1,'2019-12-09 16:51:44','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',34,NULL,34),
(35,NULL,31,'offering','Royal Way Gardens  - Cambridge',NULL,NULL,106000.00,0.00,1,1,0.00,100.00,0,0.00,5.87,NULL,23.22,3,NULL,NULL,0.00,0.00,106000.00,0,NULL,0,NULL,1,'2019-12-09 16:52:25','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',35,NULL,35),
(36,30,31,'offering','Royal Way Gardens  - Cambridge',NULL,NULL,212.00,0.00,0,1,0.00,100.00,0,0.00,5.87,NULL,23.22,3,NULL,NULL,0.00,0.00,106000.00,35,NULL,0,NULL,43,'2019-12-10 11:15:43','2026-03-31 09:27:29','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',36,NULL,36),
(37,30,31,'offering','Royal Way Gardens  - Cambridge',NULL,NULL,159.00,0.00,0,1,0.00,75.00,0,0.00,5.87,NULL,23.22,3,NULL,NULL,0.00,0.00,106000.00,35,NULL,0,NULL,43,'2019-12-10 13:24:56','2026-03-31 09:27:29','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',37,NULL,37),
(38,28,29,'offering','Partingdale House A - Reading',NULL,NULL,69.12,0.00,0,1,0.00,48.00,0,0.00,5.96,NULL,25.36,4,NULL,NULL,0.00,0.00,36000.00,33,NULL,0,NULL,43,'2019-12-10 13:25:14','2026-03-31 09:27:29','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',38,NULL,38),
(39,29,27,'offering','Light at the Peninsula - Land\'s End',NULL,NULL,55.35,0.00,0,1,0.00,45.00,0,0.00,6.33,NULL,21.22,3,NULL,NULL,0.00,0.00,6150.00,30,NULL,0,NULL,43,'2019-12-10 13:25:38','2026-03-31 09:27:29','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',39,NULL,39),
(40,26,26,'offering','Lodge de Lac - Cumbria',NULL,NULL,34.80,0.00,0,1,0.00,30.00,0,0.00,6.02,NULL,32.44,5,NULL,NULL,0.00,0.00,11600.00,29,NULL,0,NULL,43,'2019-12-10 13:25:50','2026-03-31 09:27:29','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk',40,NULL,40),
(41,37,28,'offering','Wayward Plaza 1 - London',NULL,NULL,119.52,0.00,0,1,0.00,36.00,0,0.00,12.54,NULL,12.54,1,NULL,NULL,0.00,0.00,66400.00,31,NULL,2,NULL,44,'2019-12-10 13:40:28','2026-03-31 09:27:29','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',41,NULL,41),
(42,32,30,'offering','Kolness by the Moor - Okehampton',NULL,NULL,73.92,0.00,0,1,0.00,48.00,0,0.00,6.58,NULL,13.54,2,NULL,NULL,0.00,0.00,14400.00,34,NULL,0,NULL,44,'2019-12-10 13:40:52','2026-03-31 09:27:29','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk',42,NULL,42),
(43,NULL,32,'offering','Sandfox Fields - Kent',NULL,NULL,60500.00,0.00,1,1,0.00,0.00,0,0.00,6.32,NULL,26.77,4,NULL,NULL,0.00,0.00,60500.00,0,NULL,0,NULL,1,'2019-12-11 15:33:33','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',43,NULL,43),
(44,NULL,33,'offering','Silverhood Down - Brighton',NULL,NULL,47250.00,0.00,0,1,0.00,0.00,0,0.00,7.12,NULL,39.21,5,NULL,NULL,0.00,0.00,47250.00,0,NULL,0,NULL,1,'2019-12-11 15:34:39','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',44,NULL,44),
(45,NULL,27,'offering','Light at the Peninsula Block B - Land\'s End',NULL,NULL,12300.00,0.00,0,1,0.00,0.00,0,0.00,5.68,NULL,16.86,3,NULL,NULL,0.00,0.00,12300.00,0,NULL,0,NULL,1,'2019-12-11 15:37:22','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',45,NULL,45),
(46,NULL,29,'offering','Partingdale House A Block 2 - Reading',NULL,NULL,18000.00,0.00,0,1,0.00,0.00,0,1.44,5.91,NULL,25.36,4,NULL,NULL,144.00,1440.00,18000.00,0,NULL,0,NULL,1,'2019-12-11 15:38:54','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',46,NULL,46),
(47,NULL,34,'offering','Clarence Hold A - Camden',NULL,NULL,25600.00,0.00,1,1,0.00,0.00,0,0.00,4.89,NULL,28.33,5,NULL,NULL,0.00,0.00,25600.00,0,NULL,0,NULL,1,'2019-12-11 16:20:30','2026-03-31 09:27:29','admin@crowdtek.co.uk','admin@crowdtek.co.uk',47,NULL,47);
/*!40000 ALTER TABLE `offerings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `offerings_status`
--

DROP TABLE IF EXISTS `offerings_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offerings_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draftedOn` datetime DEFAULT NULL,
  `isDraft` tinyint(1) NOT NULL,
  `archivedOn` datetime DEFAULT NULL,
  `isArchived` tinyint(1) NOT NULL,
  `cancelledOn` datetime DEFAULT NULL,
  `isCancelled` tinyint(1) NOT NULL,
  `submittedOn` datetime DEFAULT NULL,
  `isSubmitted` tinyint(1) NOT NULL,
  `rejectedOn` datetime DEFAULT NULL,
  `isRejected` tinyint(1) NOT NULL,
  `approvedOn` datetime DEFAULT NULL,
  `isApproved` tinyint(1) NOT NULL,
  `publishedOn` datetime DEFAULT NULL,
  `isPublished` tinyint(1) NOT NULL,
  `restrictedOn` datetime DEFAULT NULL,
  `isRestricted` tinyint(1) NOT NULL,
  `closedOn` datetime DEFAULT NULL,
  `isClosed` tinyint(1) NOT NULL,
  `settledOn` datetime DEFAULT NULL,
  `isSettled` tinyint(1) NOT NULL,
  `lifecycleStatus` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offerings_status`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `offerings_status` WRITE;
/*!40000 ALTER TABLE `offerings_status` DISABLE KEYS */;
INSERT INTO `offerings_status` VALUES
(1,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(2,'2019-12-09 12:22:58',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59',NULL,NULL),
(3,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(4,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(5,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(6,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(7,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(8,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(9,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(10,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(11,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(12,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(13,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(14,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(15,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(16,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(17,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'submitted',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(18,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,1,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,'approved',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(19,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,1,NULL,0,NULL,1,NULL,1,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(20,'2019-12-09 12:23:06',1,NULL,0,'2019-12-11 15:51:29',1,NULL,1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,'cancelled',NULL,'2019-12-09 12:23:08','2019-12-11 15:51:29',NULL,'admin@crowdtek.co.uk'),
(21,'2019-12-09 12:23:06',1,NULL,0,'2019-12-11 15:51:45',1,NULL,1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,'cancelled',NULL,'2019-12-09 12:23:08','2019-12-11 15:51:45',NULL,'admin@crowdtek.co.uk'),
(22,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'submitted',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(23,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(24,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(25,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(26,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0,'draft',NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(27,'2019-12-09 16:44:18',1,NULL,0,NULL,0,'2019-12-09 16:52:57',1,NULL,0,'2019-12-09 16:54:32',0,'2019-12-09 16:55:04',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:44:18','2019-12-09 16:55:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(28,'2019-12-09 16:45:35',1,NULL,0,NULL,0,'2019-12-09 16:52:56',1,NULL,0,'2019-12-09 16:54:38',0,'2019-12-09 16:55:08',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:45:35','2019-12-09 16:55:08','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(29,'2019-12-09 16:46:38',1,NULL,0,NULL,0,'2019-12-09 16:52:54',1,NULL,0,'2019-12-09 16:54:44',0,'2019-12-09 16:55:12',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:46:38','2019-12-09 16:55:12','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(30,'2019-12-09 16:47:47',1,NULL,0,NULL,0,'2019-12-09 16:52:53',1,NULL,0,'2019-12-09 16:54:47',0,'2019-12-09 16:55:14',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:47:47','2019-12-09 16:55:14','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(31,'2019-12-09 16:48:43',1,NULL,0,NULL,0,'2019-12-09 16:52:51',1,NULL,0,'2019-12-09 16:54:50',0,'2019-12-09 16:55:17',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:48:43','2019-12-09 16:55:17','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(32,'2019-12-09 16:49:26',1,NULL,0,NULL,0,'2019-12-09 16:52:50',1,NULL,0,'2019-12-09 16:54:53',0,'2019-12-09 16:55:20',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:49:26','2019-12-09 16:55:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(33,'2019-12-09 16:50:39',1,NULL,0,NULL,0,'2019-12-09 16:52:48',1,NULL,0,'2019-12-09 16:54:56',0,'2019-12-09 16:55:23',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:50:39','2019-12-09 16:55:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(34,'2019-12-09 16:51:44',1,NULL,0,NULL,0,'2019-12-09 16:52:46',1,NULL,0,'2019-12-09 16:54:58',0,'2019-12-09 16:55:25',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:51:44','2019-12-09 16:55:25','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(35,'2019-12-09 16:52:25',1,NULL,0,NULL,0,'2019-12-09 16:52:44',1,NULL,0,'2019-12-09 16:54:59',0,'2019-12-09 16:55:27',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-09 16:52:25','2019-12-09 16:55:27','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(36,'2019-12-10 11:15:43',1,NULL,0,NULL,0,'2019-12-10 11:16:04',1,NULL,0,'2019-12-10 11:16:06',0,'2019-12-10 11:16:09',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 11:15:43','2019-12-10 11:16:09','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk'),
(37,'2019-12-10 13:24:56',1,NULL,0,NULL,0,'2019-12-10 13:41:10',1,NULL,0,'2019-12-10 13:41:26',0,'2019-12-10 13:41:34',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 13:24:56','2019-12-10 13:41:34','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk'),
(38,'2019-12-10 13:25:14',1,NULL,0,NULL,0,'2019-12-10 13:41:12',1,NULL,0,'2019-12-10 13:41:25',0,'2019-12-10 13:41:37',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 13:25:14','2019-12-10 13:41:37','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk'),
(39,'2019-12-10 13:25:38',1,NULL,0,NULL,0,'2019-12-10 13:41:13',1,NULL,0,'2019-12-10 13:41:22',0,'2019-12-10 13:41:39',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 13:25:38','2019-12-10 13:41:39','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk'),
(40,'2019-12-10 13:25:50',1,NULL,0,NULL,0,'2019-12-10 13:41:15',1,NULL,0,'2019-12-10 13:41:21',0,'2019-12-10 13:41:40',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 13:25:50','2019-12-10 13:41:40','ben.autotest@crowdtek.co.uk','admin@crowdtek.co.uk'),
(41,'2019-12-10 13:40:28',1,NULL,0,NULL,0,'2019-12-10 13:41:16',1,NULL,0,'2019-12-10 13:41:20',0,'2019-12-10 13:41:42',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 13:40:28','2019-12-10 13:41:42','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk'),
(42,'2019-12-10 13:40:52',1,NULL,0,NULL,0,'2019-12-10 13:41:17',1,NULL,0,'2019-12-10 13:41:19',0,'2019-12-10 13:41:43',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-10 13:40:52','2019-12-10 13:41:43','holly.autotest@helpmewithit.com','admin@crowdtek.co.uk'),
(43,'2019-12-11 15:33:33',1,NULL,0,NULL,0,'2019-12-11 15:39:01',1,NULL,0,'2019-12-11 15:39:13',0,'2019-12-11 15:39:15',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-11 15:33:33','2019-12-11 15:39:15','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(44,'2019-12-11 15:34:39',1,NULL,0,NULL,0,'2019-12-11 15:39:02',1,NULL,0,'2019-12-11 15:39:10',0,'2019-12-11 15:39:17',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-11 15:34:39','2019-12-11 15:39:17','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(45,'2019-12-11 15:37:22',1,NULL,0,NULL,0,'2019-12-11 15:39:04',1,NULL,0,'2019-12-11 15:39:09',0,'2019-12-11 15:39:18',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-11 15:37:22','2019-12-11 15:39:18','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(46,'2019-12-11 15:38:54',1,NULL,0,NULL,0,'2019-12-11 15:39:05',1,NULL,0,'2019-12-11 15:39:07',0,'2019-12-11 15:39:20',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-11 15:38:54','2019-12-11 15:39:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(47,'2019-12-11 16:20:30',1,NULL,0,NULL,0,'2019-12-11 16:20:46',1,NULL,0,'2019-12-11 16:20:48',0,'2019-12-11 16:20:50',1,NULL,0,NULL,0,NULL,0,'published',NULL,'2019-12-11 16:20:30','2019-12-11 16:20:50','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `offerings_status` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `onboarding_profile`
--

DROP TABLE IF EXISTS `onboarding_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cooloffEnd` datetime DEFAULT NULL,
  `cooloffAccepted` tinyint(1) DEFAULT NULL,
  `riskWarningAccepted` tinyint(1) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `categoryReviewedAt` datetime DEFAULT NULL,
  `assessmentPassed` tinyint(1) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `realEstatePlanAccess` tinyint(1) NOT NULL DEFAULT 0,
  `realEstateBuildAccess` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_profile`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `onboarding_profile` WRITE;
/*!40000 ALTER TABLE `onboarding_profile` DISABLE KEYS */;
INSERT INTO `onboarding_profile` VALUES
(1,'2019-12-10 12:23:00',1,1,'sophisticated',NOW(),1,'2024-08-12 12:56:00','2024-08-12 12:58:39',0,0),
(2,'2019-12-10 12:23:00',1,1,'restricted',NOW(),1,'2024-08-12 12:56:31','2024-08-12 12:56:31',0,0),
(3,'2019-12-10 12:23:00',1,1,'restricted',NOW(),1,'2024-08-12 12:57:34','2024-08-12 12:57:34',0,0),
(4,'2019-12-10 12:23:00',1,1,'restricted',NOW(),1,'2024-08-12 12:58:03','2024-08-12 12:58:03',0,0);
/*!40000 ALTER TABLE `onboarding_profile` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payment_order`
--

DROP TABLE IF EXISTS `payment_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `paymentType` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scheduledFor` date NOT NULL,
  `status` varchar(255) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `approvedBy_id` int(11) DEFAULT NULL,
  `debitWallet` varchar(255) DEFAULT NULL,
  `tradeOrder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A260A52A5DA1941` (`asset_id`),
  KEY `IDX_A260A52AFACFC38A` (`approvedBy_id`),
  KEY `IDX_A260A52AF321B6E5` (`tradeOrder_id`),
  CONSTRAINT `FK_A260A52A5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_A260A52AF321B6E5` FOREIGN KEY (`tradeOrder_id`) REFERENCES `trade_order` (`id`),
  CONSTRAINT `FK_A260A52AFACFC38A` FOREIGN KEY (`approvedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_order`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_order` WRITE;
/*!40000 ALTER TABLE `payment_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_order` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payment_request`
--

DROP TABLE IF EXISTS `payment_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payee_id` int(11) NOT NULL,
  `payout_id` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `amount` decimal(15,2) NOT NULL,
  `shareholding` int(11) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `paymentOrder_id` int(11) NOT NULL,
  `payeeNotifiedAt` datetime DEFAULT NULL,
  `statusInfo` varchar(255) DEFAULT NULL,
  `shareTrade_id` int(11) DEFAULT NULL,
  `tradeOrder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_22DE8175C6D61B7F` (`payout_id`),
  KEY `IDX_22DE817519E8E0F9` (`paymentOrder_id`),
  KEY `IDX_22DE8175CB4B68F` (`payee_id`),
  KEY `IDX_22DE8175C37FBED7` (`shareTrade_id`),
  KEY `IDX_22DE8175F321B6E5` (`tradeOrder_id`),
  CONSTRAINT `FK_22DE817519E8E0F9` FOREIGN KEY (`paymentOrder_id`) REFERENCES `payment_order` (`id`),
  CONSTRAINT `FK_22DE8175C37FBED7` FOREIGN KEY (`shareTrade_id`) REFERENCES `share_trade` (`id`),
  CONSTRAINT `FK_22DE8175C6D61B7F` FOREIGN KEY (`payout_id`) REFERENCES `payouts` (`id`),
  CONSTRAINT `FK_22DE8175CB4B68F` FOREIGN KEY (`payee_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_22DE8175F321B6E5` FOREIGN KEY (`tradeOrder_id`) REFERENCES `trade_order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_request`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_request` WRITE;
/*!40000 ALTER TABLE `payment_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_request` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payout_add_fields`
--

DROP TABLE IF EXISTS `payout_add_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_add_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payout_id` int(11) DEFAULT NULL,
  `fieldKey` varchar(20) NOT NULL COMMENT '(DC2Type:string)',
  `fieldValue` varchar(50) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8A7543E890AB2FC9` (`fieldKey`),
  KEY `IDX_8A7543E8C6D61B7F` (`payout_id`),
  CONSTRAINT `FK_8A7543E8C6D61B7F` FOREIGN KEY (`payout_id`) REFERENCES `payouts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_add_fields`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payout_add_fields` WRITE;
/*!40000 ALTER TABLE `payout_add_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_add_fields` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payouts`
--

DROP TABLE IF EXISTS `payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `investment_id` int(11) DEFAULT NULL,
  `additionalType` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `currency` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `payoutType` int(11) DEFAULT NULL,
  `dueDate` datetime NOT NULL,
  `payoutAmount` decimal(15,2) DEFAULT NULL,
  `fee` decimal(15,2) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `transactionId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `asset_id` int(11) DEFAULT NULL,
  `credited_user_id` int(11) DEFAULT NULL,
  `shareholding` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `IDX_F54E808E6E1B4FD5` (`investment_id`),
  KEY `IDX_F54E808E5DA1941` (`asset_id`),
  KEY `IDX_F54E808EEF99CD64` (`credited_user_id`),
  CONSTRAINT `FK_F54E808E5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_F54E808E6E1B4FD5` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  CONSTRAINT `FK_F54E808EEF99CD64` FOREIGN KEY (`credited_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payouts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payouts` WRITE;
/*!40000 ALTER TABLE `payouts` DISABLE KEYS */;
INSERT INTO `payouts` VALUES
(1,1,NULL,'GBP',NULL,'2019-12-13 05:52:39',1000.00,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59','admin@crowdtek.co.uk',NULL,NULL,1,1,0),
(2,2,NULL,'GBP',NULL,'2019-12-09 19:14:01',1000.00,NULL,NULL,'2019-12-09 12:22:59','2019-12-09 12:22:59','admin@crowdtek.co.uk',NULL,NULL,2,1,0),
(3,3,NULL,'GBP',NULL,'2019-12-14 08:14:09',1000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(4,4,NULL,'GBP',NULL,'2019-12-10 07:00:46',2000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(5,3,NULL,'GBP',NULL,'2019-12-14 20:27:38',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(6,3,NULL,'GBP',NULL,'2019-12-14 05:39:31',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(7,3,NULL,'GBP',NULL,'2019-12-15 01:46:47',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(8,3,NULL,'GBP',NULL,'2019-12-10 04:13:20',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(9,3,NULL,'GBP',NULL,'2019-12-14 09:52:41',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(10,3,NULL,'GBP',NULL,'2019-12-11 11:22:19',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(11,3,NULL,'GBP',NULL,'2019-12-12 05:41:46',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','Userfake@test.com',NULL,NULL,3,4,0),
(12,13,NULL,'GBP',NULL,'2019-12-12 23:57:07',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','user15Investor@test.com',NULL,NULL,10,18,0),
(13,13,NULL,'GBP',NULL,'2019-12-18 07:45:49',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','user15Investor@test.com',NULL,NULL,10,18,0),
(14,13,NULL,'GBP',NULL,'2019-12-14 05:53:05',3000.99,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06','user15Investor@test.com',NULL,NULL,10,18,0),
(15,15,NULL,'GBP',NULL,'2019-12-13 04:56:26',15.00,NULL,NULL,'2016-11-15 02:53:33','2019-11-28 03:06:26','bc_investor2@test.com',NULL,NULL,18,20,0),
(16,15,NULL,'GBP',NULL,'2019-12-14 00:45:39',10.00,NULL,NULL,'1979-06-17 03:15:46','1980-06-03 09:14:54','bc_investor2@test.com',NULL,NULL,18,20,0),
(17,26,NULL,'GBP',0,'2019-11-01 00:00:00',2.00,NULL,1,'2019-12-10 11:40:04','2019-12-10 11:40:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,26,43,0),
(18,35,NULL,'GBP',0,'2019-08-01 00:00:00',0.25,NULL,1,'2019-12-10 11:40:42','2019-12-10 11:40:42','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(19,30,NULL,'GBP',0,'2019-08-01 00:00:00',2.20,NULL,1,'2019-12-10 11:40:43','2019-12-10 11:40:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(20,31,NULL,'GBP',0,'2019-08-01 00:00:00',1.55,NULL,1,'2019-12-10 11:40:44','2019-12-10 11:40:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(21,35,NULL,'GBP',0,'2019-07-01 00:00:00',0.25,NULL,1,'2019-12-10 11:40:52','2019-12-10 11:40:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(22,30,NULL,'GBP',0,'2019-07-01 00:00:00',2.20,NULL,1,'2019-12-10 11:40:53','2019-12-10 11:40:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(23,31,NULL,'GBP',0,'2019-07-01 00:00:00',1.55,NULL,1,'2019-12-10 11:40:54','2019-12-10 11:40:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(24,35,NULL,'GBP',0,'2019-06-01 00:00:00',0.19,NULL,1,'2019-12-10 11:41:11','2019-12-10 11:41:11','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(25,30,NULL,'GBP',0,'2019-06-01 00:00:00',1.65,NULL,1,'2019-12-10 11:41:12','2019-12-10 11:41:12','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(26,31,NULL,'GBP',0,'2019-06-01 00:00:00',1.16,NULL,1,'2019-12-10 11:41:13','2019-12-10 11:41:13','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(27,35,NULL,'GBP',0,'2019-09-01 00:00:00',0.25,NULL,1,'2019-12-10 11:41:42','2019-12-10 11:41:42','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(28,30,NULL,'GBP',0,'2019-09-01 00:00:00',2.20,NULL,1,'2019-12-10 11:41:43','2019-12-10 11:41:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(29,31,NULL,'GBP',0,'2019-09-01 00:00:00',1.55,NULL,1,'2019-12-10 11:41:44','2019-12-10 11:41:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(30,35,NULL,'GBP',0,'2019-10-01 00:00:00',0.25,NULL,1,'2019-12-10 11:41:54','2019-12-10 11:41:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(31,30,NULL,'GBP',0,'2019-10-01 00:00:00',2.20,NULL,1,'2019-12-10 11:41:55','2019-12-10 11:41:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(32,31,NULL,'GBP',0,'2019-10-01 00:00:00',1.55,NULL,1,'2019-12-10 11:41:56','2019-12-10 11:41:56','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(33,35,NULL,'GBP',0,'2019-11-01 00:00:00',0.25,NULL,1,'2019-12-10 11:42:03','2019-12-10 11:42:03','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(34,30,NULL,'GBP',0,'2019-11-01 00:00:00',2.20,NULL,1,'2019-12-10 11:42:05','2019-12-10 11:42:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(35,31,NULL,'GBP',0,'2019-11-01 00:00:00',1.55,NULL,1,'2019-12-10 11:42:05','2019-12-10 11:42:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(36,35,NULL,'GBP',0,'2019-12-01 00:00:00',0.25,NULL,1,'2019-12-10 11:42:22','2019-12-10 11:42:22','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(37,30,NULL,'GBP',0,'2019-12-01 00:00:00',2.20,NULL,1,'2019-12-10 11:42:23','2019-12-10 11:42:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,43,0),
(38,31,NULL,'GBP',0,'2019-12-01 00:00:00',1.55,NULL,1,'2019-12-10 11:42:24','2019-12-10 11:42:24','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,0),
(39,29,NULL,'GBP',0,'2019-08-01 00:00:00',1.14,NULL,1,'2019-12-10 11:42:44','2019-12-10 11:42:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,43,0),
(40,34,NULL,'GBP',0,'2019-08-01 00:00:00',1.86,NULL,1,'2019-12-10 11:42:45','2019-12-10 11:42:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,44,0),
(41,29,NULL,'GBP',0,'2019-09-01 00:00:00',1.14,NULL,1,'2019-12-10 11:42:53','2019-12-10 11:42:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,43,0),
(42,34,NULL,'GBP',0,'2019-09-01 00:00:00',1.86,NULL,1,'2019-12-10 11:42:53','2019-12-10 11:42:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,44,0),
(43,29,NULL,'GBP',0,'2019-10-01 00:00:00',1.14,NULL,1,'2019-12-10 11:43:00','2019-12-10 11:43:00','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,43,0),
(44,34,NULL,'GBP',0,'2019-10-01 00:00:00',1.86,NULL,1,'2019-12-10 11:43:01','2019-12-10 11:43:01','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,44,0),
(45,29,NULL,'GBP',0,'2019-11-01 00:00:00',1.14,NULL,1,'2019-12-10 11:43:08','2019-12-10 11:43:08','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,43,0),
(46,34,NULL,'GBP',0,'2019-11-01 00:00:00',1.86,NULL,1,'2019-12-10 11:43:09','2019-12-10 11:43:09','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,44,0),
(47,29,NULL,'GBP',0,'2019-12-01 00:00:00',1.90,NULL,1,'2019-12-10 11:43:19','2019-12-10 11:43:19','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,43,0),
(48,34,NULL,'GBP',0,'2019-12-01 00:00:00',3.10,NULL,1,'2019-12-10 11:43:20','2019-12-10 11:43:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,27,44,0),
(49,28,NULL,'GBP',0,'2019-10-01 00:00:00',2.23,NULL,1,'2019-12-10 11:43:45','2019-12-10 11:43:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,29,43,0),
(50,33,NULL,'GBP',0,'2019-10-01 00:00:00',1.77,NULL,1,'2019-12-10 11:43:46','2019-12-10 11:43:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,29,44,0),
(51,28,NULL,'GBP',0,'2019-11-01 00:00:00',2.23,NULL,1,'2019-12-10 11:43:52','2019-12-10 11:43:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,29,43,0),
(52,33,NULL,'GBP',0,'2019-11-01 00:00:00',1.77,NULL,1,'2019-12-10 11:43:53','2019-12-10 11:43:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,29,44,0),
(53,28,NULL,'GBP',0,'2019-12-01 00:00:00',1.11,NULL,1,'2019-12-10 11:44:01','2019-12-10 11:44:01','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,29,43,0),
(54,33,NULL,'GBP',0,'2019-12-01 00:00:00',0.89,NULL,1,'2019-12-10 11:44:02','2019-12-10 11:44:02','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,29,44,0),
(55,27,NULL,'GBP',0,'2019-11-01 00:00:00',0.91,NULL,1,'2019-12-10 11:44:25','2019-12-10 11:44:25','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,30,43,0),
(56,32,NULL,'GBP',0,'2019-11-01 00:00:00',4.09,NULL,1,'2019-12-10 11:44:26','2019-12-10 11:44:26','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,30,44,0),
(57,27,NULL,'GBP',0,'2019-12-01 00:00:00',0.91,NULL,1,'2019-12-10 11:44:33','2019-12-10 11:44:33','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,30,43,0),
(58,32,NULL,'GBP',0,'2019-12-01 00:00:00',4.09,NULL,1,'2019-12-10 11:44:34','2019-12-10 11:44:34','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,30,44,0),
(59,35,NULL,'GBP',1,'2020-03-01 00:00:00',112.54,NULL,1,'2022-03-15 12:35:39','2022-03-15 12:35:39','admin@crowdtek.co.uk','admin@crowdtek.co.uk',NULL,31,44,50);
/*!40000 ALTER TABLE `payouts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `payoutsReport`
--

DROP TABLE IF EXISTS `payoutsReport`;
/*!50001 DROP VIEW IF EXISTS `payoutsReport`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `payoutsReport` AS SELECT
 1 AS `id`,
  1 AS `payoutAmount`,
  1 AS `dueDate`,
  1 AS `payoutType`,
  1 AS `currency`,
  1 AS `fee`,
  1 AS `shareholding`,
  1 AS `createdAt`,
  1 AS `createdBy`,
  1 AS `updatedAt`,
  1 AS `updatedBy`,
  1 AS `creditedUserId`,
  1 AS `creditedUser`,
  1 AS `assetId`,
  1 AS `assetSpv`,
  1 AS `assetName`,
  1 AS `investmentId` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `prefundingRetention`
--

DROP TABLE IF EXISTS `prefundingRetention`;
/*!50001 DROP VIEW IF EXISTS `prefundingRetention`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `prefundingRetention` AS SELECT
 1 AS `inv_id`,
  1 AS `prefundingId` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionType` varchar(255) NOT NULL,
  `section` int(11) DEFAULT NULL,
  `content` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6F7494E3174800F` (`createdBy_id`),
  KEY `IDX_B6F7494E65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_4F812B183174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_4F812B1865FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `question` WRITE;
/*!40000 ALTER TABLE `question` DISABLE KEYS */;
INSERT INTO `question` VALUES
(1,'appropriateness',1,'Contractual nature test question',1,0,'2024-08-12 17:55:18','2024-08-12 17:56:39',1,1),
(2,'appropriateness',2,'Financial loss test question?',1,0,'2024-08-12 17:55:28','2024-08-12 17:56:22',1,1),
(3,'appropriateness',5,'FSCS protection test question',1,0,'2024-08-12 17:55:40','2024-08-12 17:56:15',1,1);
/*!40000 ALTER TABLE `question` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `question_choice`
--

DROP TABLE IF EXISTS `question_choice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_choice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `correct` tinyint(1) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C6F6759A1E27F6BF` (`question_id`),
  KEY `IDX_C6F6759A3174800F` (`createdBy_id`),
  KEY `IDX_C6F6759A65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_B1B0DAE41E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`),
  CONSTRAINT `FK_B1B0DAE43174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_B1B0DAE465FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_choice`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `question_choice` WRITE;
/*!40000 ALTER TABLE `question_choice` DISABLE KEYS */;
INSERT INTO `question_choice` VALUES
(1,3,'Wrong answer',1,0,'2024-08-12 17:55:51','2024-08-12 17:55:51',1,1),
(2,3,'Correct answer',1,1,'2024-08-12 17:56:00','2024-08-12 17:56:00',1,1),
(3,2,'Correct answer',1,1,'2024-08-12 17:56:30','2024-08-12 17:56:30',1,1),
(4,2,'Wrong answer',1,0,'2024-08-12 17:56:35','2024-08-12 17:56:35',1,1),
(5,1,'Correct answer test',1,1,'2024-08-12 17:56:49','2024-08-12 17:56:49',1,1),
(6,1,'Wrong answer test',1,0,'2024-08-12 17:57:02','2024-08-12 17:57:09',1,1);
/*!40000 ALTER TABLE `question_choice` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `report`
--

DROP TABLE IF EXISTS `report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `origin` varchar(255) NOT NULL,
  `resourceId` varchar(255) NOT NULL,
  `referenceId` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `step` varchar(255) DEFAULT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `report` WRITE;
/*!40000 ALTER TABLE `report` DISABLE KEYS */;
/*!40000 ALTER TABLE `report` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `report_set`
--

DROP TABLE IF EXISTS `report_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `reportSetType` varchar(255) NOT NULL DEFAULT 'custom',
  `description` varchar(255) DEFAULT NULL,
  `periodStart` date DEFAULT NULL,
  `periodEnd` date DEFAULT NULL,
  `progress` smallint(6) NOT NULL DEFAULT 1,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_16EF5D5B5DA1941` (`asset_id`),
  CONSTRAINT `FK_713B806F5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_set`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `report_set` WRITE;
/*!40000 ALTER TABLE `report_set` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_set` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `report_set_report`
--

DROP TABLE IF EXISTS `report_set_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_set_report` (
  `report_set_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  PRIMARY KEY (`report_set_id`,`report_id`),
  KEY `IDX_73286E2E4BD2A4C0` (`report_id`),
  KEY `IDX_73286E2E3908D887` (`report_set_id`),
  CONSTRAINT `FK_3428E104BD2A4C0` FOREIGN KEY (`report_id`) REFERENCES `report` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_73286E2E3908D887` FOREIGN KEY (`report_set_id`) REFERENCES `report_set` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_set_report`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `report_set_report` WRITE;
/*!40000 ALTER TABLE `report_set_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_set_report` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reset_password_request`
--

DROP TABLE IF EXISTS `reset_password_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_password_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(20) NOT NULL,
  `hashedToken` varchar(100) NOT NULL,
  `requestedAt` datetime NOT NULL,
  `expiresAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7CE748AA76ED395` (`user_id`),
  CONSTRAINT `FK_35370143A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_password_request`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reset_password_request` WRITE;
/*!40000 ALTER TABLE `reset_password_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_password_request` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `shareRegister`
--

DROP TABLE IF EXISTS `shareRegister`;
/*!50001 DROP VIEW IF EXISTS `shareRegister`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `shareRegister` AS SELECT
 1 AS `id`,
  1 AS `type`,
  1 AS `transactionId`,
  1 AS `SPV`,
  1 AS `assetName`,
  1 AS `assetId`,
  1 AS `pricePerShare`,
  1 AS `numberOfShares`,
  1 AS `calculatedInvestmentValue`,
  1 AS `recordedInvestmentValue`,
  1 AS `Amount Divested`,
  1 AS `Shares Divested`,
  1 AS `Current Holding`,
  1 AS `Current Share Holding`,
  1 AS `capitalRepaid`,
  1 AS `isRetention`,
  1 AS `createdAt`,
  1 AS `settledOn`,
  1 AS `buyerId`,
  1 AS `buyerEmail`,
  1 AS `buyerUsername`,
  1 AS `buyerTitle`,
  1 AS `buyerFirstname`,
  1 AS `buyerLastname`,
  1 AS `buyerAddressLine1`,
  1 AS `buyerAddressLine2`,
  1 AS `buyerAddressCity`,
  1 AS `buyerAddressRegion`,
  1 AS `buyerAddressPostCode`,
  1 AS `buyerAddressCountry`,
  1 AS `buyerCreatedAt`,
  1 AS `sellerId`,
  1 AS `sellerUsername`,
  1 AS `sellerEmail`,
  1 AS `sellerTitle`,
  1 AS `sellerFirstname`,
  1 AS `sellerLastname`,
  1 AS `sellerAddressLine2`,
  1 AS `sellerAddressCity`,
  1 AS `sellerAddressRegion`,
  1 AS `sellerAddressPostcode`,
  1 AS `sellerAddressCountry`,
  1 AS `buyerCompanyName`,
  1 AS `buyerCompanyRegNumber`,
  1 AS `buyerCompanyRegAddress1`,
  1 AS `buyerCompanyPostCode`,
  1 AS `buyerCompanyApprovedOn`,
  1 AS `cxbWorthInvestor`,
  1 AS `cxbSophisticatedInvestor`,
  1 AS `cxbRestrictedUser`,
  1 AS `corporateInvestor`,
  1 AS `estimatedStampDuty` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `share_trade`
--

DROP TABLE IF EXISTS `share_trade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_trade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` binary(16) NOT NULL,
  `derived` tinyint(4) NOT NULL DEFAULT 0,
  `numberOfShares` int(11) NOT NULL DEFAULT 0,
  `pricePerShare` decimal(12,6) NOT NULL DEFAULT 0.000000,
  `tradeValue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `buyOrder_id` int(11) NOT NULL,
  `sellOrder_id` int(11) NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F774AF8BD17F50A6` (`uuid`),
  KEY `IDX_F774AF8B3E947540` (`buyOrder_id`),
  KEY `IDX_F774AF8B69C5E958` (`sellOrder_id`),
  KEY `IDX_F774AF8B3174800F` (`createdBy_id`),
  KEY `IDX_F774AF8B65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_F774AF8B3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_F774AF8B3E947540` FOREIGN KEY (`buyOrder_id`) REFERENCES `trade_order` (`id`),
  CONSTRAINT `FK_F774AF8B65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_F774AF8B69C5E958` FOREIGN KEY (`sellOrder_id`) REFERENCES `trade_order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `share_trade`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `share_trade` WRITE;
/*!40000 ALTER TABLE `share_trade` DISABLE KEYS */;
INSERT INTO `share_trade` VALUES
(9,0x019D43490FA0743CB6120D801094E29F,0,10,2.990000,1000.00,'2007-03-01 09:18:43','2026-03-31 09:45:54',56,1,1,1),
(10,0x019D43490FA47F91A8F05A04A6DC66FD,0,10,2.990000,1000.00,'2000-06-29 22:57:35','2026-03-31 09:45:54',57,2,1,1),
(11,0x019D43490FA77CF69C8D7219E81B5DA5,0,53,1.100000,1000.00,'2006-01-21 05:20:39','2026-03-31 09:45:54',58,3,4,1),
(12,0x019D43490FA975A18271074726BDCF6C,0,33,1.100000,20000.00,'1979-08-27 13:49:38','2026-03-31 09:45:54',59,3,4,1),
(13,0x019D43490FAC74EF9CB93C5C159C19C6,0,37,1.100000,12451.51,'2004-01-03 16:51:21','2026-03-31 09:45:54',60,4,13,1),
(14,0x019D43490FAD7AC99CCBDECA6EF10CDB,0,10,1.100000,238265.50,'1993-09-04 10:11:35','2026-03-31 09:45:54',61,4,9,1),
(15,0x019D43490FB0780CB57210D80B892714,0,82,1.100000,693868.69,'2008-04-23 07:51:43','2026-03-31 09:45:54',62,5,6,1),
(16,0x019D43490FB373EF9B51FA0E91CF7915,0,92,1.100000,67.60,'2000-11-01 14:30:53','2026-03-31 09:45:54',63,6,7,1),
(17,0x019D43490FB6768EB4691CAAB0FFE30C,0,46,1.100000,51.43,'1988-03-12 11:52:15','2026-03-31 09:45:54',64,7,7,1),
(18,0x019D43490FB9720D9153D8B03410AEAC,0,32,1.100000,117287867.12,'1977-07-25 06:55:40','2026-03-31 09:45:54',65,8,5,1),
(19,0x019D43490FBB7D24BFA67C61238F9663,0,19,1.100000,2334777.00,'1977-05-16 06:22:08','2026-03-31 09:45:54',66,9,5,1),
(20,0x019D43490FBE774BBF5F0F20F030A702,0,64,1.100000,1041.00,'1979-04-20 15:41:00','2026-03-31 09:45:54',67,10,6,1),
(21,0x019D43490FBF7DF3885DA06E65FADBAF,0,90,1.100000,23.22,'2008-04-10 09:49:12','2026-03-31 09:45:54',68,5,18,1),
(22,0x019D43490FC17B949CA328A532F8D996,0,32,1.100000,20000.00,'2012-07-02 01:25:41','2026-03-31 09:45:54',69,3,4,1),
(23,0x019D43490FC476F5B0B7E6D7149D3AB8,0,70,1.100000,1000.00,'1987-09-28 01:38:45','2026-03-31 09:45:54',70,18,20,1),
(24,0x019D43490FC879A1912AEE2065C28CA7,0,75,1.100000,1000.00,'2015-01-20 03:02:05','2026-03-31 09:45:54',71,19,20,1),
(25,0x019D43490FCA702AAA843489A9341766,0,60,1.100000,1000.00,'1985-12-29 13:15:37','2026-03-31 09:45:54',72,19,21,1),
(26,0x019D43490FCC7820989A61243DECE755,0,91,1.100000,1000.00,'2003-10-16 04:10:42','2026-03-31 09:45:54',73,20,20,1),
(27,0x019D43490FCE707D8A2ED8F55D17592E,0,84,1.100000,1000.00,'1996-07-18 09:47:26','2026-03-31 09:45:54',74,20,21,1),
(28,0x019D43490FCF7986B7642D062D0CF4E6,0,23,1.100000,1000.00,'1971-10-26 02:48:49','2026-03-31 09:45:54',75,20,20,1),
(29,0x019D43490FD279259F7F361D91470095,0,86,1.100000,1500.00,'1971-06-13 23:17:51','2026-03-31 09:45:54',76,21,20,1),
(30,0x019D43490FD471F0B2CEDD48EFFA7C89,0,40,1.100000,1500.00,'1970-01-06 12:19:31','2026-03-31 09:45:54',77,21,21,1),
(31,0x019D43490FD77A819834EB82C24CCB10,0,3000,2.000000,3000.00,'1995-02-23 05:48:11','2026-03-31 09:45:54',78,22,20,1),
(32,0x019D43490FD9741BAC52625F24C396A9,0,85,2.000000,4000.00,'2013-12-25 07:35:30','2026-03-31 09:45:54',79,22,21,1),
(33,0x019D43490FDC7108ADC152D90AB95246,0,1000,1.100000,1000.00,'1994-12-22 20:47:48','2026-03-31 09:45:54',80,23,20,1),
(34,0x019D43490FDE7E80925AB98345C9BFCC,0,230,1.160000,266.80,'2019-12-10 10:50:25','2026-03-31 09:45:54',81,29,43,1),
(35,0x019D43490FE17B72A0884100D619D74E,0,122,1.540000,187.88,'2019-12-10 10:51:12','2026-03-31 09:45:54',82,34,43,1),
(36,0x019D43490FE57122B4C61BB178F111FB,0,216,1.440000,311.04,'2019-12-10 10:51:38','2026-03-31 09:45:54',83,33,43,1),
(37,0x019D43490FE87F0DA68A2728947ED159,0,227,1.230000,279.21,'2019-12-10 10:52:02','2026-03-31 09:45:54',84,30,43,1),
(38,0x019D43490FEC7028A7A924DEAB7D8424,0,483,2.120000,1023.96,'2019-12-10 10:52:29','2026-03-31 09:45:54',85,35,43,1),
(39,0x019D43490FED7CF6801B74B1AC0ABF64,0,304,2.120000,644.48,'2019-12-10 11:05:12','2026-03-31 09:45:54',86,35,44,1),
(40,0x019D43490FEF76F6BB9B4C19A69C875E,0,548,1.540000,843.92,'2019-12-10 11:05:30','2026-03-31 09:45:54',87,34,44,1),
(41,0x019D43490FF17177A0CB161533618A24,0,172,1.440000,247.68,'2019-12-10 11:05:51','2026-03-31 09:45:54',88,33,44,1),
(42,0x019D43490FF2796BA469CD10FC8C2BA1,0,369,1.230000,453.87,'2019-12-10 11:06:34','2026-03-31 09:45:54',89,30,44,1),
(43,0x019D43490FF575BB8E84782B709FEBEC,0,50,2.120000,106.00,'2019-12-10 11:16:38','2026-03-31 09:45:54',90,36,44,1),
(44,0x019D43490FF7728D9511FAD58E6B2370,0,551,1.540000,848.54,'2019-12-10 11:45:52','2026-03-31 09:45:54',91,34,43,1),
(45,0x019D43490FF97ED5AAE3AB76891ED0F7,0,436,3.320000,1447.52,'2019-12-10 13:26:34','2026-03-31 09:45:54',92,31,44,1),
(46,0x019D43490FFB7AF285D54325E97F0572,0,9770,1.160000,11333.20,'2020-01-15 14:59:08','2026-03-31 09:45:54',93,29,43,1),
(47,0x019D43490FFE77D4A60F810E0091E223,0,800,1.210000,968.00,'2022-01-18 11:25:27','2026-03-31 09:45:54',94,43,45,1),
(48,0x019D43490FFF7E2D9765A67D3FA2DF5C,0,1200,1.210000,1452.00,'2022-01-18 11:26:05','2026-03-31 09:45:54',95,43,53,1),
(49,0x019EAD715C8573138D032C5ACF7151C7,0,15,1.160000,17.40,'2026-06-09 17:32:28','2026-06-09 17:32:28',97,40,1,1);
/*!40000 ALTER TABLE `share_trade` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `share_trade_status_log`
--

DROP TABLE IF EXISTS `share_trade_status_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_trade_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notes` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `occuredAt` datetime NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `transitionedBy_id` int(11) DEFAULT NULL,
  `shareTrade_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6669A4124A8EA82E` (`transitionedBy_id`),
  KEY `IDX_6669A412C37FBED7` (`shareTrade_id`),
  CONSTRAINT `FK_6669A4124A8EA82E` FOREIGN KEY (`transitionedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_6669A412C37FBED7` FOREIGN KEY (`shareTrade_id`) REFERENCES `share_trade` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `share_trade_status_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `share_trade_status_log` WRITE;
/*!40000 ALTER TABLE `share_trade_status_log` DISABLE KEYS */;
INSERT INTO `share_trade_status_log` VALUES
(1,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,23),
(2,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,24),
(3,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,25),
(4,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,26),
(5,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,27),
(6,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,28),
(7,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,29),
(8,NULL,'unsettled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,30),
(9,NULL,'settled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,31),
(10,NULL,'settled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,32),
(11,NULL,'settled','2019-12-09 12:23:06','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,33),
(12,NULL,'unsettled','2019-12-10 10:50:25','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,34),
(13,NULL,'settled','2019-12-10 11:07:44','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,34),
(14,NULL,'unsettled','2019-12-10 10:51:12','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,35),
(15,NULL,'settled','2019-12-10 11:07:51','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,35),
(16,NULL,'unsettled','2019-12-10 10:51:38','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,36),
(17,NULL,'settled','2019-12-10 11:07:59','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,36),
(18,NULL,'unsettled','2019-12-10 10:52:02','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,37),
(19,NULL,'settled','2019-12-10 11:08:10','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,37),
(20,NULL,'unsettled','2019-12-10 10:52:29','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,38),
(21,NULL,'settled','2019-12-10 11:08:19','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,38),
(22,NULL,'unsettled','2019-12-10 11:05:12','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,39),
(23,NULL,'settled','2019-12-10 11:08:26','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,39),
(24,NULL,'unsettled','2019-12-10 11:05:30','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,40),
(25,NULL,'settled','2019-12-10 11:08:32','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,40),
(26,NULL,'unsettled','2019-12-10 11:05:51','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,41),
(27,NULL,'settled','2019-12-10 11:08:38','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,41),
(28,NULL,'unsettled','2019-12-10 11:06:34','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,42),
(29,NULL,'settled','2019-12-10 11:08:43','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,42),
(30,NULL,'unsettled','2019-12-10 11:16:38','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,43),
(31,NULL,'settled','2019-12-10 11:17:02','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,43),
(32,NULL,'unsettled','2019-12-10 11:45:52','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,44),
(33,NULL,'unsettled','2019-12-10 13:26:34','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,45),
(34,NULL,'settled','2019-12-10 13:27:01','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,45),
(35,NULL,'unsettled','2020-01-15 14:59:08','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,46),
(36,NULL,'settled','2020-01-15 14:59:40','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,46),
(37,NULL,'settled','2022-01-18 11:25:27','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,47),
(38,NULL,'settled','2022-01-18 11:26:05','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,48),
(39,NULL,'unsettled','2026-06-09 17:32:28','2026-06-09 17:32:28','2026-06-09 17:32:28',NULL,49);
/*!40000 ALTER TABLE `share_trade_status_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `share_transfer_order`
--

DROP TABLE IF EXISTS `share_transfer_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_transfer_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scheduledFor` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `periodStart` date NOT NULL,
  `periodEnd` date NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `approvedBy_id` int(11) DEFAULT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  `repaymentStart` datetime DEFAULT NULL,
  `repaymentEnd` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AB9D42FA5DA1941` (`asset_id`),
  KEY `IDX_AB9D42FAFACFC38A` (`approvedBy_id`),
  KEY `IDX_AB9D42FA3174800F` (`createdBy_id`),
  KEY `IDX_AB9D42FA65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_50F95D3A3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_50F95D3A5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_50F95D3A65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_50F95D3AFACFC38A` FOREIGN KEY (`approvedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `share_transfer_order`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `share_transfer_order` WRITE;
/*!40000 ALTER TABLE `share_transfer_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_transfer_order` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `share_transfer_request`
--

DROP TABLE IF EXISTS `share_transfer_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_transfer_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `shares` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `shareTransferOrder_id` int(11) NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  `shareTrade_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9D883A4C1301E95F` (`shareTransferOrder_id`),
  KEY `IDX_9D883A4C8DE820D9` (`seller_id`),
  KEY `IDX_9D883A4C6C755722` (`buyer_id`),
  KEY `IDX_9D883A4C6E1B4FD5` (`investment_id`),
  KEY `IDX_9D883A4C3174800F` (`createdBy_id`),
  KEY `IDX_9D883A4C65FF1AEC` (`updatedBy_id`),
  KEY `IDX_9D883A4CC37FBED7` (`shareTrade_id`),
  CONSTRAINT `FK_9D883A4CC37FBED7` FOREIGN KEY (`shareTrade_id`) REFERENCES `share_trade` (`id`),
  CONSTRAINT `FK_DB7A1B931301E95F` FOREIGN KEY (`shareTransferOrder_id`) REFERENCES `share_transfer_order` (`id`),
  CONSTRAINT `FK_DB7A1B933174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_DB7A1B9365FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_DB7A1B936C755722` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_DB7A1B936E1B4FD5` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  CONSTRAINT `FK_DB7A1B938DE820D9` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `share_transfer_request`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `share_transfer_request` WRITE;
/*!40000 ALTER TABLE `share_transfer_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_transfer_request` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `task_tracker`
--

DROP TABLE IF EXISTS `task_tracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_tracker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `taskTrackerType` varchar(255) NOT NULL,
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tasks`)),
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_tracker`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `task_tracker` WRITE;
/*!40000 ALTER TABLE `task_tracker` DISABLE KEYS */;
INSERT INTO `task_tracker` VALUES
(2,'{\"monthend\":\"2025-04\",\"autoUpdate\":true,\"syncedAt\":null}','monthend','{\"incomeDeposits\":\"pending\",\"incomeDisaggregations\":\"pending\",\"dividends\":\"pending\",\"settlements\":\"pending\",\"repayments\":\"pending\",\"feeCollections\":\"pending\"}','2025-04-28 10:52:24','2025-04-28 10:52:24');
/*!40000 ALTER TABLE `task_tracker` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `trade_order`
--

DROP TABLE IF EXISTS `trade_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trade_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `minimumShares` int(11) DEFAULT NULL,
  `maximumShares` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `uuid` binary(16) NOT NULL,
  `expiration` datetime DEFAULT NULL,
  `transactionReference` varchar(255) DEFAULT NULL,
  `direction` int(11) NOT NULL,
  `numberOfShares` int(11) NOT NULL DEFAULT 0,
  `pricePerShare` decimal(12,6) NOT NULL DEFAULT 0.000000,
  `type` varchar(255) NOT NULL DEFAULT 'market',
  `fees` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taxes` decimal(12,2) NOT NULL DEFAULT 0.00,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `asset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  `complementaryOrder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_DF24437BD17F50A6` (`uuid`),
  UNIQUE KEY `UNIQ_DF24437B2FC0CB0F` (`transaction_id`),
  UNIQUE KEY `UNIQ_DF24437B71B85DE2` (`complementaryOrder_id`),
  KEY `IDX_DF24437B5DA1941` (`asset_id`),
  KEY `IDX_DF24437BA76ED395` (`user_id`),
  KEY `IDX_DF24437B3174800F` (`createdBy_id`),
  KEY `IDX_DF24437B65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_DF24437B2FC0CB0F` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`),
  CONSTRAINT `FK_DF24437B3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_DF24437B5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_DF24437B65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_DF24437B71B85DE2` FOREIGN KEY (`complementaryOrder_id`) REFERENCES `trade_order` (`id`),
  CONSTRAINT `FK_DF24437BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trade_order`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `trade_order` WRITE;
/*!40000 ALTER TABLE `trade_order` DISABLE KEYS */;
INSERT INTO `trade_order` VALUES
(1,NULL,NULL,'port:o1',0x019D43382FD77EFE99C05FAF4C32F27F,NULL,NULL,-1,9091,1.100000,'initial',0.00,0.00,'2011-04-25 15:30:39','2026-03-31 09:27:28',NULL,1,1,1,1,NULL),
(2,NULL,NULL,'port:o2',0x019D43382FDD7C82869CEEA0849DB8CE,NULL,NULL,-1,6667,1.500000,'market',0.00,0.00,'1990-08-02 00:41:10','2026-03-31 09:27:28',NULL,2,1,1,1,NULL),
(3,NULL,NULL,'port:o3',0x019D43382FDF745FA152E6F0382E3F67,NULL,NULL,-1,66235061,1.100000,'initial',0.00,0.00,'2001-10-20 15:33:40','2026-03-31 09:27:28',NULL,3,1,1,1,NULL),
(4,NULL,NULL,'port:o4',0x019D43382FE0710D8F22E8E49B11A4ED,NULL,NULL,-1,43461137,1.100000,'market',0.00,0.00,'1975-06-14 12:17:17','2026-03-31 09:27:28',NULL,7,1,1,1,NULL),
(5,NULL,NULL,'port:o5',0x019D43382FE170659987EE8D61781415,NULL,NULL,-1,18206492,1.100000,'market',0.00,0.00,'1978-12-15 01:24:00','2026-03-31 09:27:28',NULL,10,1,1,1,NULL),
(6,NULL,NULL,'port:o6',0x019D43382FE17DDD9987EE8D624CD110,NULL,NULL,-1,8056025,1.100000,'market',0.00,0.00,'2000-09-14 17:57:33','2026-03-31 09:27:28',NULL,9,1,1,1,NULL),
(7,NULL,NULL,'port:o7',0x019D43382FE37603832023A8B800EB60,NULL,NULL,-1,31390679,1.100000,'initial',0.00,0.00,'2001-11-15 03:18:47','2026-03-31 09:27:28',NULL,10,1,1,1,NULL),
(8,NULL,NULL,'port:o8',0x019D43382FE4724EA5632D551AD17D6E,NULL,NULL,-1,18536931,1.100000,'market',0.00,0.00,'1977-10-01 00:27:37','2026-03-31 09:27:28',NULL,3,1,1,1,NULL),
(9,NULL,NULL,'port:o9',0x019D43382FE57A519AC6EEA3ADF00705,NULL,NULL,-1,45975111,1.100000,'initial',0.00,0.00,'2006-04-28 05:40:46','2026-03-31 09:27:28',NULL,5,1,1,1,NULL),
(10,NULL,NULL,'port:o10',0x019D43382FE677469B4AAB00D9436B34,NULL,NULL,-1,47856903,1.100000,'market',0.00,0.00,'2012-10-03 08:24:13','2026-03-31 09:27:28',NULL,12,1,1,1,NULL),
(11,NULL,NULL,'port:o11',0x019D43382FE773F2AF6D46318322CE4C,NULL,NULL,-1,13140505,1.100000,'market',0.00,0.00,'2017-06-02 18:03:02','2026-03-31 09:27:28',NULL,9,1,1,1,NULL),
(12,NULL,NULL,'port:o12',0x019D43382FE8708DA8ACDD2A6E5D8606,NULL,NULL,-1,59665078,1.100000,'market',0.00,0.00,'1985-06-05 07:11:01','2026-03-31 09:27:29',NULL,9,1,1,1,NULL),
(13,NULL,NULL,'port:o13',0x019D43382FE9771CA43C70FC03ED63A2,NULL,NULL,-1,80924209,1.100000,'initial',0.00,0.00,'2004-10-13 23:09:05','2026-03-31 09:27:29',NULL,3,1,1,1,NULL),
(14,NULL,NULL,'port:o14',0x019D43382FEA734E9EE0093487C91CE5,NULL,NULL,-1,36443067,1.100000,'market',0.00,0.00,'1989-12-24 02:07:06','2026-03-31 09:27:29',NULL,15,1,1,1,NULL),
(15,NULL,NULL,'port:o15',0x019D43382FEC73938D90F1E3DF87B9E4,NULL,NULL,-1,48513941,1.100000,'initial',0.00,0.00,'1984-10-15 05:36:03','2026-03-31 09:27:29',NULL,15,1,1,1,NULL),
(16,NULL,NULL,'port:o16',0x019D43382FED74EB820464D698EDCE70,NULL,NULL,-1,29635438,1.100000,'market',0.00,0.00,'1989-12-10 01:54:11','2026-03-31 09:27:29',NULL,15,1,1,1,NULL),
(17,NULL,NULL,'port:o17',0x019D43382FEE780CA073F45E8B1B57A6,NULL,NULL,-1,910,1.100000,'market',0.00,0.00,'1987-09-27 23:04:43','2026-03-31 09:27:29',NULL,17,1,1,1,NULL),
(18,NULL,NULL,'port:o18',0x019D43382FEF75C98E3B641EE15A08CE,NULL,NULL,-1,1819,1.100000,'market',0.00,0.00,'2000-12-15 21:46:57','2026-03-31 09:27:29',NULL,18,1,1,1,NULL),
(19,NULL,NULL,'port:o19',0x019D43382FF072DD8A39AD4F21C69D62,NULL,NULL,-1,3637,1.100000,'market',0.00,0.00,'1979-04-25 18:26:06','2026-03-31 09:27:29',NULL,19,1,1,1,NULL),
(20,NULL,NULL,'port:o20',0x019D43382FF17091BF74C7A5EFB92A98,NULL,NULL,-1,10910,1.100000,'market',0.00,0.00,'1992-08-06 08:41:31','2026-03-31 09:27:29',NULL,20,1,1,1,NULL),
(21,NULL,NULL,'port:o21',0x019D43382FF17E61BF74C7A5EFD284A2,NULL,NULL,-1,29868439,1.100000,'market',0.00,0.00,'1979-12-02 11:12:56','2026-03-31 09:27:29',NULL,21,1,1,1,NULL),
(22,NULL,NULL,'port:o22',0x019D43382FF372A0B01E0027E9C89365,NULL,NULL,-1,29088313,1.100000,'market',0.00,0.00,'1992-07-24 17:17:44','2026-03-31 09:27:29',NULL,21,1,1,1,NULL),
(23,NULL,NULL,'port:o23',0x019D43382FF776A29C8739D5897E1CD9,NULL,NULL,-1,57774288,1.100000,'initial',0.00,0.00,'1989-01-02 02:34:33','2026-03-31 09:27:29',NULL,21,1,1,1,NULL),
(24,NULL,NULL,'port:o24',0x019D43382FFA70A8A8B01E5EA94A8A37,NULL,NULL,-1,3182,1.100000,'market',0.00,0.00,'1983-10-22 14:03:58','2026-03-31 09:27:29',NULL,22,1,1,1,NULL),
(25,NULL,NULL,'port:o25',0x019D43382FFB75D3881EBACD20C329C7,NULL,NULL,-1,67,1.100000,'market',0.00,0.00,'1998-02-05 21:17:08','2026-03-31 09:27:29',NULL,23,1,1,1,NULL),
(26,NULL,NULL,'port:o26',0x019D43382FFC74118DBED748F2C39A40,NULL,NULL,-1,8525,1.100000,'market',0.00,0.00,'1995-05-01 20:01:17','2026-03-31 09:27:29',NULL,23,1,1,1,NULL),
(27,57,852,'port:o27',0x019D43382FFD7D95B271909DAEAB3488,NULL,NULL,-1,12500,1.760000,'initial',0.00,0.00,'2019-12-09 16:44:18','2026-03-31 09:27:29',NULL,24,1,1,1,NULL),
(28,NULL,NULL,'port:o28',0x019D433830057416BBEACA87716A6E90,NULL,NULL,-1,150000,1.880000,'initial',0.00,0.00,'2019-12-09 16:45:35','2026-03-31 09:27:29',NULL,25,1,1,1,NULL),
(29,NULL,NULL,'port:o29',0x019D4338300873678346056958F79553,NULL,NULL,-1,10000,1.160000,'initial',0.00,0.00,'2019-12-09 16:46:38','2026-03-31 09:27:29',NULL,26,1,1,1,NULL),
(30,NULL,NULL,'port:o30',0x019D4338300C7BA9895BD8772617C7FC,NULL,NULL,-1,5000,1.230000,'initial',0.00,0.00,'2019-12-09 16:47:47','2026-03-31 09:27:29',NULL,27,1,1,1,NULL),
(31,NULL,NULL,'port:o31',0x019D433830107EC0BD913E058C07BF6E,NULL,NULL,-1,20000,3.320000,'initial',0.00,0.00,'2019-12-09 16:48:43','2026-03-31 09:27:29',NULL,28,1,1,1,NULL),
(32,NULL,NULL,'port:o32',0x019D4338301472C68124E577EA69881E,NULL,NULL,-1,20000,3.320000,'initial',0.00,0.00,'2019-12-09 16:49:26','2026-03-31 09:27:29',NULL,28,1,1,1,NULL),
(33,NULL,NULL,'port:o33',0x019D4338301772BFA57A1C2FE71DDA66,NULL,NULL,-1,25000,1.440000,'initial',0.00,0.00,'2019-12-09 16:50:39','2026-03-31 09:27:29',NULL,29,1,1,1,NULL),
(34,NULL,NULL,'port:o34',0x019D4338301B74FBAFA6498AD65E8A9F,NULL,NULL,-1,9351,1.540000,'initial',0.00,0.00,'2019-12-09 16:51:44','2026-03-31 09:27:29',NULL,30,1,1,1,NULL),
(35,NULL,NULL,'port:o35',0x019D4338302072338310262DD063E986,NULL,NULL,-1,50000,2.120000,'initial',0.00,0.00,'2019-12-09 16:52:25','2026-03-31 09:27:29',NULL,31,1,1,1,NULL),
(36,NULL,NULL,'port:o36',0x019D4338302376619E283E9EFCB64B22,NULL,NULL,-1,100,2.120000,'market',0.00,0.00,'2019-12-10 11:15:43','2026-03-31 09:27:29',NULL,31,43,43,1,NULL),
(37,NULL,NULL,'port:o37',0x019D433830257D6BAFFE8FE2ACEAB5DC,NULL,NULL,-1,75,2.120000,'market',0.00,0.00,'2019-12-10 13:24:56','2026-03-31 09:27:29',NULL,31,43,43,1,NULL),
(38,NULL,NULL,'port:o38',0x019D4338302773A9BFB7C6BB05D8ECA2,NULL,NULL,-1,48,1.440000,'market',0.00,0.00,'2019-12-10 13:25:14','2026-03-31 09:27:29',NULL,29,43,43,1,NULL),
(39,NULL,NULL,'port:o39',0x019D43383028795586BC5EC0519C6CD8,NULL,NULL,-1,45,1.230000,'market',0.00,0.00,'2019-12-10 13:25:38','2026-03-31 09:27:29',NULL,27,43,43,1,NULL),
(40,NULL,NULL,'port:o40',0x019D433830297DAE93BE9F875C4A70A7,NULL,NULL,-1,30,1.160000,'market',0.00,0.00,'2019-12-10 13:25:50','2026-03-31 09:27:29',NULL,26,43,43,1,NULL),
(41,NULL,NULL,'port:o41',0x019D4338302B72E9A689DCA397F21EDE,NULL,NULL,-1,36,3.320000,'market',0.00,0.00,'2019-12-10 13:40:28','2026-03-31 09:27:29',NULL,28,44,44,1,NULL),
(42,NULL,NULL,'port:o42',0x019D4338302C770A9C7FA5924FD0C93A,NULL,NULL,-1,48,1.540000,'market',0.00,0.00,'2019-12-10 13:40:52','2026-03-31 09:27:29',NULL,30,44,44,1,NULL),
(43,NULL,NULL,'port:o43',0x019D4338302F7478AD74A7F995B0E056,NULL,NULL,-1,50000,1.210000,'initial',0.00,0.00,'2019-12-11 15:33:33','2026-03-31 09:27:29',NULL,32,1,1,1,NULL),
(44,NULL,NULL,'port:o44',0x019D433830337972BFF8B5F13ED946DA,NULL,NULL,-1,25000,1.890000,'initial',0.00,0.00,'2019-12-11 15:34:39','2026-03-31 09:27:29',NULL,33,1,1,1,NULL),
(45,NULL,NULL,'port:o45',0x019D433830357BCD89C09AF55193AE97,NULL,NULL,-1,10000,1.230000,'initial',0.00,0.00,'2019-12-11 15:37:22','2026-03-31 09:27:29',NULL,27,1,1,1,NULL),
(46,100,1000,'port:o46',0x019D4338303872009C715A6FB08385CA,NULL,NULL,-1,12500,1.440000,'initial',0.00,0.00,'2019-12-11 15:38:54','2026-03-31 09:27:29',NULL,29,1,1,1,NULL),
(47,NULL,NULL,'port:o47',0x019D4338303A7D1DA75879E2CE243917,NULL,NULL,-1,10000,2.560000,'initial',0.00,0.00,'2019-12-11 16:20:30','2026-03-31 09:27:29',NULL,34,1,1,1,NULL),
(56,NULL,NULL,'port:o1:i1 Aliquam fugiat sit ratione est.',0x019D43490FA07218B6120D801024EEF8,NULL,NULL,1,10,2.990000,'market',0.00,0.00,'2007-03-01 09:18:43','2026-03-31 09:45:54',NULL,1,1,1,1,NULL),
(57,NULL,NULL,'port:o2:i2 Adipisci nihil enim dolorem aliquid sint.',0x019D43490FA47F0DA8F05A04A66C86AD,NULL,NULL,1,10,2.990000,'market',0.00,0.00,'2000-06-29 22:57:35','2026-03-31 09:45:54',NULL,2,1,1,1,NULL),
(58,NULL,NULL,'port:o3:i3 Dolore velit impedit eum deserunt quia.',0x019D43490FA77C5A9C8D7219E817673F,NULL,NULL,1,53,1.100000,'market',0.00,0.00,'2006-01-21 05:20:39','2026-03-31 09:45:54',NULL,3,4,4,1,NULL),
(59,NULL,NULL,'port:o3:i4 Voluptates dolor et sapiente perspiciatis.',0x019D43490FA975418271074726315EF3,NULL,NULL,1,33,1.100000,'market',0.00,0.00,'1979-08-27 13:49:38','2026-03-31 09:45:54',NULL,3,4,4,1,NULL),
(60,NULL,NULL,'port:o4:i5 Et et ipsa animi facere voluptatum et et.',0x019D43490FAC74939CB93C5C1535238E,NULL,NULL,1,37,1.100000,'market',0.00,0.00,'2004-01-03 16:51:21','2026-03-31 09:45:54',NULL,7,13,13,1,NULL),
(61,NULL,NULL,'port:o4:i6 Provident quasi qui cum quos sit aut quasi soluta.',0x019D43490FAD7A619CCBDECA6EE92249,NULL,NULL,1,10,1.100000,'market',0.00,0.00,'1993-09-04 10:11:35','2026-03-31 09:45:54',NULL,7,9,9,1,NULL),
(62,NULL,NULL,'port:o5:i7 Ad fugiat non ex aliquid eveniet.',0x019D43490FB0779CB57210D80B7FE4A5,NULL,NULL,1,82,1.100000,'market',0.00,0.00,'2008-04-23 07:51:43','2026-03-31 09:45:54',NULL,10,6,6,1,NULL),
(63,NULL,NULL,'port:o6:i8 Est velit et voluptas laboriosam explicabo.',0x019D43490FB373679B51FA0E91B977BD,NULL,NULL,1,92,1.100000,'market',0.00,0.00,'2000-11-01 14:30:53','2026-03-31 09:45:54',NULL,9,7,7,1,NULL),
(64,NULL,NULL,'port:o7:i9 Aut et sunt praesentium ullam minus iure dignissimos.',0x019D43490FB675D2B4691CAAB091DDF6,NULL,NULL,1,46,1.100000,'market',0.00,0.00,'1988-03-12 11:52:15','2026-03-31 09:45:54',NULL,10,7,7,1,NULL),
(65,NULL,NULL,'port:o8:i10 Necessitatibus ipsam numquam a consequatur.',0x019D43490FB971A99153D8B033354FAA,NULL,NULL,1,32,1.100000,'market',0.00,0.00,'1977-07-25 06:55:40','2026-03-31 09:45:54',NULL,3,5,5,1,NULL),
(66,NULL,NULL,'port:o9:i11 Aut qui ipsa minus nostrum.',0x019D43490FBB7CC0BFA67C6123172ADE,NULL,NULL,1,19,1.100000,'market',0.00,0.00,'1977-05-16 06:22:08','2026-03-31 09:45:54',NULL,5,5,5,1,NULL),
(67,NULL,NULL,'port:o10:i12 Odit dolorum recusandae eos atque ullam.',0x019D43490FBE76E3BF5F0F20EFAFED77,NULL,NULL,1,64,1.100000,'market',0.00,0.00,'1979-04-20 15:41:00','2026-03-31 09:45:54',NULL,12,6,6,1,NULL),
(68,NULL,NULL,'port:o5:i13 Ut voluptas sint fugiat esse quasi in.',0x019D43490FBF7D93885DA06E65002262,NULL,NULL,1,90,1.100000,'market',0.00,0.00,'2008-04-10 09:49:12','2026-03-31 09:45:54',NULL,10,18,18,1,NULL),
(69,NULL,NULL,'port:o3:i14 Inventore ut aliquam cumque nihil itaque corrupti.',0x019D43490FC17B489CA328A532B31F1E,NULL,NULL,1,32,1.100000,'market',0.00,0.00,'2012-07-02 01:25:41','2026-03-31 09:45:54',NULL,3,4,4,1,NULL),
(70,NULL,NULL,'port:o18:i15 ',0x019D43490FC47691B0B7E6D714030957,NULL,NULL,1,70,1.100000,'market',0.00,0.00,'1987-09-28 01:38:45','2026-03-31 09:45:54',NULL,18,20,20,1,NULL),
(71,NULL,NULL,'port:o19:i16 ',0x019D43490FC8792D912AEE2065728C30,NULL,NULL,1,75,1.100000,'market',0.00,0.00,'2015-01-20 03:02:05','2026-03-31 09:45:54',NULL,19,20,20,1,NULL),
(72,NULL,NULL,'port:o19:i17 ',0x019D43490FC97F84B211B0DBB5BC556E,NULL,NULL,1,60,1.100000,'market',0.00,0.00,'1985-12-29 13:15:37','2026-03-31 09:45:54',NULL,19,21,21,1,NULL),
(73,NULL,NULL,'port:o20:i18 ',0x019D43490FCC77B8989A61243D1C0121,NULL,NULL,1,91,1.100000,'market',0.00,0.00,'2003-10-16 04:10:42','2026-03-31 09:45:54',NULL,20,20,20,1,NULL),
(74,NULL,NULL,'port:o20:i19 ',0x019D43490FCE70458A2ED8F55C39009C,NULL,NULL,1,84,1.100000,'market',0.00,0.00,'1996-07-18 09:47:26','2026-03-31 09:45:54',NULL,20,21,21,1,NULL),
(75,NULL,NULL,'port:o20:i20 ',0x019D43490FCF790EB7642D062CBCDA63,NULL,NULL,1,23,1.100000,'market',0.00,0.00,'1971-10-26 02:48:49','2026-03-31 09:45:54',NULL,20,20,20,1,NULL),
(76,NULL,NULL,'port:o21:i21 ',0x019D43490FD278D99F7F361D906FCC72,NULL,NULL,1,86,1.100000,'market',0.00,0.00,'1971-06-13 23:17:51','2026-03-31 09:45:54',NULL,21,20,20,1,NULL),
(77,NULL,NULL,'port:o21:i22 ',0x019D43490FD47124B2CEDD48EFF8F2FD,NULL,NULL,1,40,1.100000,'market',0.00,0.00,'1970-01-06 12:19:31','2026-03-31 09:45:54',NULL,21,21,21,1,NULL),
(78,NULL,NULL,'port:o22:i23 ',0x019D43490FD77A119834EB82C1EB9EEA,NULL,NULL,1,3000,2.000000,'market',0.00,0.00,'1995-02-23 05:48:11','2026-03-31 09:45:54',NULL,21,20,20,1,NULL),
(79,NULL,NULL,'port:o22:i24 ',0x019D43490FD973B7AC52625F245DB09D,NULL,NULL,1,85,2.000000,'market',0.00,0.00,'2013-12-25 07:35:30','2026-03-31 09:45:54',NULL,21,21,21,1,NULL),
(80,NULL,NULL,'port:o23:i25 ',0x019D43490FDC70C8ADC152D90A074DE1,NULL,NULL,1,1000,1.100000,'market',0.00,0.00,'1994-12-22 20:47:48','2026-03-31 09:45:54',NULL,21,20,20,1,NULL),
(81,NULL,NULL,'port:o29:i26 ',0x019D43490FDE7E48925AB98344DF6DF1,NULL,'72308767',1,230,1.160000,'market',0.00,0.00,'2019-12-10 10:50:25','2026-03-31 09:45:54',2,26,43,43,1,NULL),
(82,NULL,NULL,'port:o34:i27 ',0x019D43490FE17B3AA0884100D589AA8C,NULL,'72308773',1,122,1.540000,'market',0.00,0.00,'2019-12-10 10:51:12','2026-03-31 09:45:54',3,30,43,43,1,NULL),
(83,NULL,NULL,'port:o33:i28 ',0x019D43490FE57076B4C61BB1781F42AA,NULL,'72308776',1,216,1.440000,'market',0.00,0.00,'2019-12-10 10:51:38','2026-03-31 09:45:54',4,29,43,43,1,NULL),
(84,NULL,NULL,'port:o30:i29 ',0x019D43490FE87E95A68A27289474DA59,NULL,'72308797',1,227,1.230000,'market',0.00,0.00,'2019-12-10 10:52:02','2026-03-31 09:45:54',5,27,43,43,1,NULL),
(85,NULL,NULL,'port:o35:i30 ',0x019D43490FEB7F48B6C8F2485386F1E6,NULL,'72308869',1,483,2.120000,'market',0.00,0.00,'2019-12-10 10:52:29','2026-03-31 09:45:54',6,31,43,43,1,NULL),
(86,NULL,NULL,'port:o35:i31 ',0x019D43490FED7CA6801B74B1ABB261C1,NULL,'TestFullyDivested',1,304,2.120000,'market',0.00,0.00,'2019-12-10 11:05:12','2026-03-31 09:45:54',NULL,31,44,44,1,NULL),
(87,NULL,NULL,'port:o34:i32 ',0x019D43490FEF7696BB9B4C19A62D3AC6,NULL,'72310231',1,548,1.540000,'market',0.00,0.00,'2019-12-10 11:05:30','2026-03-31 09:45:54',8,30,44,44,1,NULL),
(88,NULL,NULL,'port:o33:i33 ',0x019D43490FF1711BA0CB1615326AE230,NULL,'72310251',1,172,1.440000,'market',0.00,0.00,'2019-12-10 11:05:51','2026-03-31 09:45:54',9,29,44,44,1,NULL),
(89,NULL,NULL,'port:o30:i34 ',0x019D43490FF2791BA469CD10FBC5A60D,NULL,'72310277',1,369,1.230000,'market',0.00,0.00,'2019-12-10 11:06:34','2026-03-31 09:45:54',10,27,44,44,1,NULL),
(90,NULL,NULL,'port:o36:i35 ',0x019D43490FF575378E84782B6FA66635,NULL,'72311288',1,50,2.120000,'market',0.00,0.00,'2019-12-10 11:16:38','2026-03-31 09:45:54',22,31,44,44,1,NULL),
(91,NULL,NULL,'port:o34:i36 Stamp duty aggregate test',0x019D43490FF772399511FAD58E530077,NULL,'72312907',1,551,1.540000,'market',0.00,0.00,'2019-12-10 11:45:52','2026-03-31 09:45:54',66,30,43,43,1,NULL),
(92,NULL,NULL,'port:o31:i37 ',0x019D43490FF97E95AAE3AB76881EF391,NULL,'72318058',1,436,3.320000,'market',0.00,0.00,'2019-12-10 13:26:34','2026-03-31 09:45:54',70,28,44,44,1,NULL),
(93,NULL,NULL,'port:o29:i38 ',0x019D43490FFB7A8285D54325E88F011B,NULL,'73663603',1,9770,1.160000,'market',0.00,0.00,'2020-01-15 14:59:08','2026-03-31 09:45:54',75,26,43,43,1,NULL),
(94,NULL,NULL,'port:o43:i39 ',0x019D43490FFE7764A60F810DFFEAD8A5,NULL,'87564976',1,800,1.210000,'off_market',0.00,0.00,'2022-01-18 11:25:27','2026-03-31 09:45:54',NULL,32,45,45,1,NULL),
(95,NULL,NULL,'port:o43:i40 ',0x019D43490FFF7DD99765A67D3F143A26,NULL,'22546378',1,1200,1.210000,'off_market',0.00,0.00,'2022-01-18 11:26:05','2026-03-31 09:45:54',NULL,32,53,53,1,NULL),
(96,13,NULL,NULL,0x019DD9E9252077C4A8CDF52630B0DA91,NULL,NULL,-1,156250,8.000000,'initial',0.00,0.00,'2026-04-29 15:43:48','2026-04-29 15:43:48',NULL,35,1,1,1,NULL),
(97,NULL,NULL,NULL,0x019EAD701E1572C4B4A3051B905D3510,NULL,NULL,1,15,1.160000,'market',0.00,0.00,'2026-06-09 17:31:06','2026-06-09 17:31:06',NULL,26,44,1,1,NULL);
/*!40000 ALTER TABLE `trade_order` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `trade_order_status_log`
--

DROP TABLE IF EXISTS `trade_order_status_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trade_order_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notes` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `occuredAt` datetime NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `transitionedBy_id` int(11) DEFAULT NULL,
  `tradeOrder_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9AB516144A8EA82E` (`transitionedBy_id`),
  KEY `IDX_9AB51614F321B6E5` (`tradeOrder_id`),
  CONSTRAINT `FK_9AB516144A8EA82E` FOREIGN KEY (`transitionedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_9AB51614F321B6E5` FOREIGN KEY (`tradeOrder_id`) REFERENCES `trade_order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trade_order_status_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `trade_order_status_log` WRITE;
/*!40000 ALTER TABLE `trade_order_status_log` DISABLE KEYS */;
INSERT INTO `trade_order_status_log` VALUES
(1,NULL,'active','2007-03-01 09:18:43','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,1),
(2,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,2),
(3,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,4),
(4,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,5),
(5,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,6),
(6,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,8),
(7,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,10),
(8,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:28','2026-03-31 09:27:28',NULL,11),
(9,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,12),
(10,NULL,'completed','2019-12-09 12:23:06','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,14),
(11,NULL,'active','2019-12-09 12:23:06','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,16),
(12,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,17),
(13,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,19),
(14,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,20),
(15,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,21),
(16,NULL,'active','1995-02-23 05:48:11','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,22),
(17,NULL,'cancelled','2019-12-11 15:51:29','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,22),
(18,NULL,'active','1994-12-22 20:47:48','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,23),
(19,NULL,'cancelled','2019-12-11 15:51:45','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,23),
(20,NULL,'active','2019-12-09 12:23:09','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,24),
(21,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,24),
(22,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,25),
(23,NULL,'cancelled','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,26),
(24,NULL,'active','2019-12-09 16:55:04','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,27),
(25,NULL,'active','2019-12-09 16:55:08','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,28),
(26,NULL,'active','2019-12-09 16:55:12','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,29),
(27,NULL,'completed','2020-01-15 14:59:08','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,29),
(28,NULL,'active','2019-12-09 16:55:14','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,30),
(29,NULL,'active','2019-12-09 16:55:17','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,31),
(30,NULL,'active','2019-12-09 16:55:20','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,32),
(31,NULL,'active','2019-12-09 16:55:23','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,33),
(32,NULL,'active','2019-12-09 16:55:25','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,34),
(33,NULL,'active','2019-12-09 16:55:27','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,35),
(34,NULL,'active','2019-12-10 11:16:09','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,36),
(35,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,36),
(36,NULL,'active','2019-12-10 13:41:34','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,37),
(37,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,37),
(38,NULL,'active','2019-12-10 13:41:37','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,38),
(39,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,38),
(40,NULL,'active','2019-12-10 13:41:39','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,39),
(41,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,39),
(42,NULL,'active','2019-12-10 13:41:40','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,40),
(43,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,40),
(44,NULL,'active','2019-12-10 13:41:42','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,41),
(45,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,41),
(46,NULL,'active','2019-12-10 13:41:43','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,42),
(47,NULL,'active','2026-03-31 00:00:00','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,42),
(48,NULL,'active','2019-12-11 15:39:15','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,43),
(49,NULL,'active','2019-12-11 15:39:17','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,44),
(50,NULL,'active','2019-12-11 15:39:18','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,45),
(51,NULL,'active','2019-12-11 15:39:20','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,46),
(52,NULL,'active','2019-12-11 16:20:50','2026-03-31 09:27:29','2026-03-31 09:27:29',NULL,47),
(61,NULL,'completed','2007-03-01 09:18:43','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,56),
(62,NULL,'completed','2000-06-29 22:57:35','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,57),
(63,NULL,'completed','2006-01-21 05:20:39','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,58),
(64,NULL,'completed','1979-08-27 13:49:38','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,59),
(65,NULL,'completed','2004-01-03 16:51:21','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,60),
(66,NULL,'completed','1993-09-04 10:11:35','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,61),
(67,NULL,'completed','2008-04-23 07:51:43','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,62),
(68,NULL,'completed','2000-11-01 14:30:53','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,63),
(69,NULL,'completed','1988-03-12 11:52:15','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,64),
(70,NULL,'completed','1977-07-25 06:55:40','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,65),
(71,NULL,'completed','1977-05-16 06:22:08','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,66),
(72,NULL,'completed','1979-04-20 15:41:00','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,67),
(73,NULL,'completed','2008-04-10 09:49:12','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,68),
(74,NULL,'completed','2012-07-02 01:25:41','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,69),
(75,NULL,'completed','1987-09-28 01:38:45','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,70),
(76,NULL,'completed','2015-01-20 03:02:05','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,71),
(77,NULL,'completed','1985-12-29 13:15:37','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,72),
(78,NULL,'completed','2003-10-16 04:10:42','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,73),
(79,NULL,'completed','1996-07-18 09:47:26','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,74),
(80,NULL,'completed','1971-10-26 02:48:49','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,75),
(81,NULL,'completed','1971-06-13 23:17:51','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,76),
(82,NULL,'completed','1970-01-06 12:19:31','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,77),
(83,NULL,'completed','1995-02-23 05:48:11','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,78),
(84,NULL,'completed','2013-12-25 07:35:30','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,79),
(85,NULL,'completed','1994-12-22 20:47:48','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,80),
(86,NULL,'completed','2019-12-10 10:50:25','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,81),
(87,NULL,'completed','2019-12-10 10:51:12','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,82),
(88,NULL,'completed','2019-12-10 10:51:38','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,83),
(89,NULL,'completed','2019-12-10 10:52:02','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,84),
(90,NULL,'completed','2019-12-10 10:52:29','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,85),
(91,NULL,'completed','2019-12-10 11:05:12','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,86),
(92,NULL,'completed','2019-12-10 11:05:30','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,87),
(93,NULL,'completed','2019-12-10 11:05:51','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,88),
(94,NULL,'completed','2019-12-10 11:06:34','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,89),
(95,NULL,'completed','2019-12-10 11:16:38','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,90),
(96,NULL,'completed','2019-12-10 11:45:52','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,91),
(97,NULL,'completed','2019-12-10 13:26:34','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,92),
(98,NULL,'completed','2020-01-15 14:59:08','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,93),
(99,NULL,'completed','2022-01-18 11:25:27','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,94),
(100,NULL,'completed','2022-01-18 11:26:05','2026-03-31 09:45:54','2026-03-31 09:45:54',NULL,95),
(101,NULL,'completed','2026-04-29 15:43:48','2026-04-29 15:43:48','2026-04-29 15:43:48',NULL,96),
(102,NULL,'active','2026-04-29 15:44:10','2026-04-29 15:44:14','2026-04-29 15:44:14',1,96),
(103,NULL,'completed','2026-06-09 17:31:06','2026-06-09 17:31:06','2026-06-09 17:31:06',NULL,97);
/*!40000 ALTER TABLE `trade_order_status_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `inv_id` int(11) DEFAULT 0,
  `creditor_id` int(11) DEFAULT 0,
  `debitor_id` int(11) DEFAULT 0,
  `debited_wallet_id` varchar(255) DEFAULT NULL,
  `credited_wallet_id` varchar(255) DEFAULT NULL,
  `offering_id` int(11) DEFAULT 0,
  `share_amount` int(11) DEFAULT NULL,
  `value_amount` decimal(14,2) DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT NULL,
  `trans_type` varchar(10) DEFAULT NULL COMMENT '(DC2Type:string)',
  `currency` varchar(3) DEFAULT NULL COMMENT '(DC2Type:string)',
  `comments` varchar(1024) DEFAULT NULL COMMENT '(DC2Type:string)',
  `payment_status` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `external_id` varchar(255) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_EAA81A4CA76ED395` (`user_id`),
  CONSTRAINT `FK_EAA81A4CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES
(1,NULL,1233123213,999,998,'600002','600001',NULL,NULL,1000.00,NULL,'NP','GBP',NULL,'FAKE-SUCCEEDED','12345',NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(2,NULL,26,NULL,43,'38649258','21955794',NULL,NULL,26680.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72308767',NULL,'2019-12-10 10:50:25','2019-12-10 10:50:25','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(3,NULL,27,NULL,43,'38649258','21955794',NULL,NULL,18788.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72308773',NULL,'2019-12-10 10:51:12','2019-12-10 10:51:12','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(4,NULL,28,NULL,43,'38649258','21955794',NULL,NULL,31104.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72308776',NULL,'2019-12-10 10:51:38','2019-12-10 10:51:38','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(5,NULL,29,NULL,43,'38649258','21955794',NULL,NULL,27921.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72308797',NULL,'2019-12-10 10:52:02','2019-12-10 10:52:02','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(6,NULL,30,NULL,43,'38649258','21955794',NULL,NULL,102396.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72308869',NULL,'2019-12-10 10:52:29','2019-12-10 10:52:29','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(7,NULL,31,NULL,44,'38650317','21955794',NULL,NULL,64448.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72310203',NULL,'2019-12-10 11:05:11','2019-12-10 11:05:12','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(8,NULL,32,NULL,44,'38650317','21955794',NULL,NULL,84392.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72310231',NULL,'2019-12-10 11:05:30','2019-12-10 11:05:30','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(9,NULL,33,NULL,44,'38650317','21955794',NULL,NULL,24768.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72310251',NULL,'2019-12-10 11:05:51','2019-12-10 11:05:51','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(10,NULL,34,NULL,44,'38650317','21955794',NULL,NULL,45387.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72310277',NULL,'2019-12-10 11:06:34','2019-12-10 11:06:34','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(11,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,26680.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310335',NULL,'2019-12-10 11:07:44','2019-12-10 11:07:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(12,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,18788.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310338',NULL,'2019-12-10 11:07:51','2019-12-10 11:07:51','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(13,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,31104.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310345',NULL,'2019-12-10 11:07:59','2019-12-10 11:07:59','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(14,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,27921.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310347',NULL,'2019-12-10 11:08:10','2019-12-10 11:08:10','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(15,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,102396.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310351',NULL,'2019-12-10 11:08:18','2019-12-10 11:08:18','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(16,NULL,NULL,NULL,1,'21955794','21782823',NULL,NULL,1000.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310352',NULL,'2019-12-10 11:08:19','2019-12-10 11:08:19','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(17,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,64448.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310358',NULL,'2019-12-10 11:08:26','2019-12-10 11:08:26','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(18,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,84392.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310363',NULL,'2019-12-10 11:08:32','2019-12-10 11:08:32','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(19,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,24768.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310366',NULL,'2019-12-10 11:08:38','2019-12-10 11:08:38','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(20,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,45387.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72310368',NULL,'2019-12-10 11:08:43','2019-12-10 11:08:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(21,NULL,NULL,NULL,43,'38649258','21955794',NULL,NULL,5000.00,5000.00,'NP','GBP',NULL,'SUCCEEDED','72311224',NULL,'2019-12-10 11:15:43','2019-12-10 11:15:43','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(22,NULL,35,NULL,44,'38650317','21955794',NULL,NULL,10600.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72311288',NULL,'2019-12-10 11:16:38','2019-12-10 11:16:38','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(23,NULL,NULL,NULL,1,'21955794','38649258',NULL,NULL,10600.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72311315',NULL,'2019-12-10 11:17:02','2019-12-10 11:17:02','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(24,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,200.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312704',NULL,'2019-12-10 11:40:04','2019-12-10 11:40:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(25,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,25.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312760',NULL,'2019-12-10 11:40:42','2019-12-10 11:40:42','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(26,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,220.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312762',NULL,'2019-12-10 11:40:43','2019-12-10 11:40:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(27,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,155.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312764',NULL,'2019-12-10 11:40:44','2019-12-10 11:40:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(28,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,25.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312769',NULL,'2019-12-10 11:40:52','2019-12-10 11:40:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(29,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,220.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312771',NULL,'2019-12-10 11:40:53','2019-12-10 11:40:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(30,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,155.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312773',NULL,'2019-12-10 11:40:54','2019-12-10 11:40:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(31,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,19.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312781',NULL,'2019-12-10 11:41:11','2019-12-10 11:41:11','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(32,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,165.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312782',NULL,'2019-12-10 11:41:12','2019-12-10 11:41:12','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(33,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,116.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312783',NULL,'2019-12-10 11:41:13','2019-12-10 11:41:13','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(34,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,25.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312792',NULL,'2019-12-10 11:41:42','2019-12-10 11:41:42','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(35,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,220.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312793',NULL,'2019-12-10 11:41:43','2019-12-10 11:41:43','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(36,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,155.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312794',NULL,'2019-12-10 11:41:44','2019-12-10 11:41:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(37,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,25.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312797',NULL,'2019-12-10 11:41:54','2019-12-10 11:41:54','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(38,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,220.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312800',NULL,'2019-12-10 11:41:55','2019-12-10 11:41:55','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(39,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,155.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312801',NULL,'2019-12-10 11:41:56','2019-12-10 11:41:56','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(40,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,25.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312806',NULL,'2019-12-10 11:42:03','2019-12-10 11:42:03','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(41,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,220.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312808',NULL,'2019-12-10 11:42:04','2019-12-10 11:42:04','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(42,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,155.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312812',NULL,'2019-12-10 11:42:05','2019-12-10 11:42:05','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(43,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,25.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312829',NULL,'2019-12-10 11:42:22','2019-12-10 11:42:22','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(44,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,220.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312832',NULL,'2019-12-10 11:42:23','2019-12-10 11:42:23','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(45,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,155.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312834',NULL,'2019-12-10 11:42:24','2019-12-10 11:42:24','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(46,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,114.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312848',NULL,'2019-12-10 11:42:44','2019-12-10 11:42:44','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(47,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,186.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312849',NULL,'2019-12-10 11:42:45','2019-12-10 11:42:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(48,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,114.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312850',NULL,'2019-12-10 11:42:52','2019-12-10 11:42:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(49,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,186.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312851',NULL,'2019-12-10 11:42:53','2019-12-10 11:42:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(50,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,114.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312852',NULL,'2019-12-10 11:43:00','2019-12-10 11:43:00','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(51,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,186.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312853',NULL,'2019-12-10 11:43:01','2019-12-10 11:43:01','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(52,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,114.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312855',NULL,'2019-12-10 11:43:08','2019-12-10 11:43:08','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(53,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,186.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312856',NULL,'2019-12-10 11:43:09','2019-12-10 11:43:09','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(54,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,190.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312858',NULL,'2019-12-10 11:43:19','2019-12-10 11:43:19','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(55,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,310.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312860',NULL,'2019-12-10 11:43:20','2019-12-10 11:43:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(56,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,223.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312865',NULL,'2019-12-10 11:43:45','2019-12-10 11:43:45','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(57,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,177.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312866',NULL,'2019-12-10 11:43:46','2019-12-10 11:43:46','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(58,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,223.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312867',NULL,'2019-12-10 11:43:52','2019-12-10 11:43:52','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(59,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,177.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312868',NULL,'2019-12-10 11:43:53','2019-12-10 11:43:53','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(60,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,111.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312871',NULL,'2019-12-10 11:44:01','2019-12-10 11:44:01','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(61,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,89.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312872',NULL,'2019-12-10 11:44:02','2019-12-10 11:44:02','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(62,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,91.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312876',NULL,'2019-12-10 11:44:25','2019-12-10 11:44:25','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(63,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,409.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312877',NULL,'2019-12-10 11:44:26','2019-12-10 11:44:26','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(64,NULL,NULL,NULL,1,'21958568','38649258',NULL,NULL,91.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312878',NULL,'2019-12-10 11:44:33','2019-12-10 11:44:33','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(65,NULL,NULL,NULL,1,'21958568','38650317',NULL,NULL,409.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72312879',NULL,'2019-12-10 11:44:34','2019-12-10 11:44:34','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(66,NULL,36,NULL,43,'38649258','21955794',NULL,NULL,11858.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72312907',NULL,'2019-12-10 11:45:52','2019-12-10 11:45:52','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(67,NULL,NULL,NULL,43,'38649258','21955794',NULL,NULL,5000.00,5000.00,'NP','GBP',NULL,'SUCCEEDED','72318021',NULL,'2019-12-10 13:25:14','2019-12-10 13:25:14','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(68,NULL,NULL,NULL,43,'38649258','21955794',NULL,NULL,5000.00,5000.00,'NP','GBP',NULL,'SUCCEEDED','72318025',NULL,'2019-12-10 13:25:38','2019-12-10 13:25:38','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(69,NULL,NULL,NULL,43,'38649258','21955794',NULL,NULL,5000.00,5000.00,'NP','GBP',NULL,'SUCCEEDED','72318031',NULL,'2019-12-10 13:25:50','2019-12-10 13:25:50','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(70,NULL,37,NULL,44,'38650317','21955794',NULL,NULL,145752.00,0.00,'NP','GBP',NULL,'SUCCEEDED','72318058',NULL,'2019-12-10 13:26:34','2019-12-10 13:26:34','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(71,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,144752.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72318063',NULL,'2019-12-10 13:27:00','2019-12-10 13:27:00','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(72,NULL,NULL,NULL,1,'21955794','21782823',NULL,NULL,1000.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','72318064',NULL,'2019-12-10 13:27:00','2019-12-10 13:27:00','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(73,NULL,NULL,NULL,44,'38650317','21955794',NULL,NULL,5000.00,5000.00,'NP','GBP',NULL,'SUCCEEDED','72319317',NULL,'2019-12-10 13:40:28','2019-12-10 13:40:28','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(74,NULL,NULL,NULL,44,'38650317','21955794',NULL,NULL,5000.00,5000.00,'NP','GBP',NULL,'SUCCEEDED','72319357',NULL,'2019-12-10 13:40:52','2019-12-10 13:40:52','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(75,NULL,38,NULL,43,'38649258','21955794',NULL,NULL,1139320.00,0.00,'NP','GBP',NULL,'SUCCEEDED','73663603',NULL,'2020-01-15 14:59:08','2020-01-15 14:59:08','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(76,NULL,NULL,NULL,1,'21955794','20549156',NULL,NULL,1133320.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','73663710',NULL,'2020-01-15 14:59:39','2020-01-15 14:59:39','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(77,NULL,NULL,NULL,1,'21955794','21782823',NULL,NULL,6000.00,0.00,'DIV','GBP',NULL,'SUCCEEDED','73663711',NULL,'2020-01-15 14:59:40','2020-01-15 14:59:40','admin@crowdtek.co.uk','admin@crowdtek.co.uk');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transfer_order`
--

DROP TABLE IF EXISTS `transfer_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scheduledFor` date NOT NULL,
  `status` varchar(255) NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `approvedBy_id` int(11) DEFAULT NULL,
  `transferType` varchar(255) NOT NULL DEFAULT 'custom',
  `targetTotal` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_56AD66C05DA1941` (`asset_id`),
  KEY `IDX_56AD66C0FACFC38A` (`approvedBy_id`),
  CONSTRAINT `FK_56AD66C05DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_56AD66C0FACFC38A` FOREIGN KEY (`approvedBy_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_order`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transfer_order` WRITE;
/*!40000 ALTER TABLE `transfer_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_order` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transfer_request`
--

DROP TABLE IF EXISTS `transfer_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debitWalletId` varchar(255) NOT NULL,
  `creditWalletId` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `createdBy` varchar(255) DEFAULT NULL,
  `updatedBy` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `transferOrder_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `userNotifiedAt` datetime DEFAULT NULL,
  `mode` smallint(6) NOT NULL DEFAULT 0,
  `statusInfo` varchar(255) DEFAULT NULL,
  `shareTrade_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8422FDD42FC0CB0F` (`transaction_id`),
  KEY `IDX_8422FDD4BF149C58` (`transferOrder_id`),
  KEY `IDX_8422FDD46E1B4FD5` (`investment_id`),
  KEY `IDX_8422FDD45DA1941` (`asset_id`),
  KEY `IDX_8422FDD4C37FBED7` (`shareTrade_id`),
  CONSTRAINT `FK_8422FDD42FC0CB0F` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`),
  CONSTRAINT `FK_8422FDD45DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `FK_8422FDD46E1B4FD5` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  CONSTRAINT `FK_8422FDD4BF149C58` FOREIGN KEY (`transferOrder_id`) REFERENCES `transfer_order` (`id`),
  CONSTRAINT `FK_8422FDD4C37FBED7` FOREIGN KEY (`shareTrade_id`) REFERENCES `share_trade` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_request`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transfer_request` WRITE;
/*!40000 ALTER TABLE `transfer_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_request` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `userLogs`
--

DROP TABLE IF EXISTS `userLogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `userLogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `event` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `message` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `key` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `oldValue` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `newValue` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_F6C01113A76ED395` (`user_id`),
  CONSTRAINT `FK_F6C01113A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4445 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `userLogs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `userLogs` WRITE;
/*!40000 ALTER TABLE `userLogs` DISABLE KEYS */;
INSERT INTO `userLogs` VALUES
(4431,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2021-07-05 12:48:43','2021-07-05 12:48:43',NULL,NULL),
(4432,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2021-08-05 16:16:10','2021-08-05 16:16:10',NULL,NULL),
(4433,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2022-01-18 11:15:53','2022-01-18 11:15:53',NULL,NULL),
(4434,43,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2023-04-04 16:10:16','2023-04-04 16:10:16',NULL,NULL),
(4435,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2024-08-12 12:54:47','2024-08-12 12:54:47',NULL,NULL),
(4436,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2025-04-28 10:52:24','2025-04-28 10:52:24',NULL,NULL),
(4437,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2025-05-30 14:33:22','2025-05-30 14:33:22','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(4438,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2025-06-13 10:13:20','2025-06-13 10:13:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(4439,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2026-03-31 09:27:20','2026-03-31 09:27:20','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(4440,43,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2026-04-22 15:30:40','2026-04-22 15:30:40','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(4441,43,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2026-04-22 15:59:33','2026-04-22 15:59:33','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(4442,1,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2026-04-29 15:29:00','2026-04-29 15:29:00','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(4443,43,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2026-04-29 15:44:30','2026-04-29 15:44:30','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(4444,43,'2','security.interactive_login','You logged in on %timestamp%',NULL,NULL,NULL,'2026-06-09 16:54:35','2026-06-09 16:54:35','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk');
/*!40000 ALTER TABLE `userLogs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_assessment`
--

DROP TABLE IF EXISTS `user_assessment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_assessment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `expiry` datetime DEFAULT NULL,
  `complete` tinyint(1) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  `questionType` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5035FB9CCCFA12B8` (`profile_id`),
  KEY `IDX_5035FB9C3174800F` (`createdBy_id`),
  KEY `IDX_5035FB9C65FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_38176A813174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_38176A8165FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_38176A81CCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `onboarding_profile` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_assessment`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_assessment` WRITE;
/*!40000 ALTER TABLE `user_assessment` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_assessment` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_categorisation`
--

DROP TABLE IF EXISTS `user_categorisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_categorisation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `notes` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `verifiedBy_id` int(11) DEFAULT NULL,
  `createdBy_id` int(11) DEFAULT NULL,
  `updatedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1D429C20CCFA12B8` (`profile_id`),
  KEY `IDX_1D429C20291AAFE6` (`verifiedBy_id`),
  KEY `IDX_1D429C203174800F` (`createdBy_id`),
  KEY `IDX_1D429C2065FF1AEC` (`updatedBy_id`),
  CONSTRAINT `FK_A73B6EEF291AAFE6` FOREIGN KEY (`verifiedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A73B6EEF3174800F` FOREIGN KEY (`createdBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A73B6EEF65FF1AEC` FOREIGN KEY (`updatedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A73B6EEFCCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `onboarding_profile` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_categorisation`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_categorisation` WRITE;
/*!40000 ALTER TABLE `user_categorisation` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_categorisation` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_client`
--

DROP TABLE IF EXISTS `user_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `client_id` varchar(32) NOT NULL COMMENT '(DC2Type:string)',
  `alias` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `description` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A2161F6819EB6921` (`client_id`),
  KEY `IDX_A2161F68A76ED395` (`user_id`),
  CONSTRAINT `FK_A2161F6819EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_client` (`identifier`) ON DELETE CASCADE,
  CONSTRAINT `FK_A2161F68A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_client`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_client` WRITE;
/*!40000 ALTER TABLE `user_client` DISABLE KEYS */;
INSERT INTO `user_client` VALUES
(1,1,'904c1b4d9a15529ed70ff5e686345a9f',NULL,NULL,0,'2021-02-02 17:32:33','2021-02-02 17:32:33','admin@crowdtek.co.uk',NULL);
/*!40000 ALTER TABLE `user_client` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_cus_fields`
--

DROP TABLE IF EXISTS `user_cus_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_cus_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `fieldKey` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `fieldValue` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  KEY `IDX_FC6CDA75A76ED395` (`user_id`),
  CONSTRAINT `FK_FC6CDA75A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_cus_fields`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_cus_fields` WRITE;
/*!40000 ALTER TABLE `user_cus_fields` DISABLE KEYS */;
INSERT INTO `user_cus_fields` VALUES
(1,1,'referral_link','ACrowdtek1',NULL,'2019-12-09 16:43:48','2019-12-09 16:43:48','admin@crowdtek.co.uk','admin@crowdtek.co.uk'),
(2,43,'referral_link','BCharlton43',NULL,'2019-12-09 17:21:30','2019-12-09 17:21:30','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk'),
(3,44,'referral_link','HBird44',NULL,'2019-12-10 10:55:12','2019-12-10 10:55:12','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com'),
(4,53,'referral_link','JWeston53',NULL,'2019-12-10 11:03:41','2019-12-10 11:03:41','jim.autotest@crowdtek.co.uk','jim.autotest@crowdtek.co.uk');
/*!40000 ALTER TABLE `user_cus_fields` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_docs`
--

DROP TABLE IF EXISTS `user_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2BD6677EC33F7837` (`document_id`),
  KEY `IDX_2BD6677EA76ED395` (`user_id`),
  CONSTRAINT `FK_2BD6677EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_2BD6677EC33F7837` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_docs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_docs` WRITE;
/*!40000 ALTER TABLE `user_docs` DISABLE KEYS */;
INSERT INTO `user_docs` VALUES
(1,1,1,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(2,2,2,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(3,3,3,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(4,14,16,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(5,29,33,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(6,43,34,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(7,43,35,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(8,44,36,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(9,44,37,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(10,45,38,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(11,45,39,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(12,NULL,40,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(13,NULL,41,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(14,53,42,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(15,53,43,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(16,NULL,44,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(17,NULL,45,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL);
/*!40000 ALTER TABLE `user_docs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_investors`
--

DROP TABLE IF EXISTS `user_investors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_investors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cxbWorthInvestor` tinyint(1) DEFAULT NULL,
  `cxbSophisticatedInvestor` tinyint(1) DEFAULT NULL,
  `cxbRestrictedUser` tinyint(1) DEFAULT NULL,
  `cxbLtdCompInvestor` tinyint(1) DEFAULT NULL,
  `alwaysGoUp` varchar(255) DEFAULT 'No' COMMENT '(DC2Type:string)',
  `incomeEveryMonth` varchar(255) DEFAULT 'No' COMMENT '(DC2Type:string)',
  `neverExit` varchar(255) DEFAULT 'No' COMMENT '(DC2Type:string)',
  `poiFileId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `wordsOfOwn` varchar(1000) DEFAULT NULL COMMENT '(DC2Type:string)',
  `corporateInvestor` tinyint(1) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_investors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_investors` WRITE;
/*!40000 ALTER TABLE `user_investors` DISABLE KEYS */;
INSERT INTO `user_investors` VALUES
(1,NULL,NULL,NULL,NULL,NULL,'10000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(2,NULL,NULL,NULL,NULL,NULL,'10000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(3,NULL,NULL,NULL,NULL,NULL,'10000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(4,NULL,NULL,NULL,NULL,NULL,'10000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(5,NULL,NULL,NULL,NULL,NULL,'20000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL),
(10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL),
(11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL),
(15,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL),
(16,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(17,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(19,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL),
(21,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL),
(22,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(23,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(24,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(25,NULL,NULL,NULL,NULL,NULL,'10000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(26,NULL,NULL,NULL,NULL,NULL,'20000',NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(27,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(28,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(29,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL),
(30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL),
(31,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(32,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(33,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(34,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL),
(35,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL),
(36,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(37,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(38,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(39,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:14','2019-12-09 12:23:14',NULL,NULL),
(40,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:14','2019-12-09 12:23:14',NULL,NULL),
(41,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(42,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(43,0,1,0,0,NULL,'10000',NULL,'4',NULL,0,NULL,'2019-12-09 12:23:15','2024-08-12 12:58:03',NULL,'admin@crowdtek.co.uk'),
(44,0,0,1,0,NULL,'10000',NULL,'4',NULL,0,NULL,'2019-12-09 12:23:15','2024-08-12 12:57:34',NULL,'admin@crowdtek.co.uk'),
(45,0,0,1,0,NULL,'10000',NULL,'4',NULL,0,NULL,'2019-12-09 12:23:15','2024-08-12 12:56:31',NULL,'admin@crowdtek.co.uk'),
(46,0,0,1,NULL,NULL,'10000',NULL,'4',NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(47,0,0,1,NULL,NULL,'10000',NULL,'4',NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(48,0,0,1,NULL,NULL,'10000',NULL,'4',NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(49,0,0,1,NULL,NULL,'10000',NULL,'4',NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(50,0,0,1,NULL,NULL,'10000',NULL,'4',NULL,NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(51,0,0,1,0,NULL,'10000',NULL,'4',NULL,0,NULL,'2019-12-09 12:23:15','2024-08-12 12:56:00',NULL,'admin@crowdtek.co.uk'),
(52,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL),
(53,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2019-12-09 12:23:19','2019-12-09 12:23:19',NULL,NULL);
/*!40000 ALTER TABLE `user_investors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_security`
--

DROP TABLE IF EXISTS `user_security`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mfaTotp` tinyint(1) NOT NULL DEFAULT 0,
  `totpKey` varchar(511) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `mfaEmail` tinyint(1) NOT NULL DEFAULT 1,
  `emailAuthCode` int(11) DEFAULT NULL,
  `mfaPreference` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_security`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_security` WRITE;
/*!40000 ALTER TABLE `user_security` DISABLE KEYS */;
INSERT INTO `user_security` VALUES
(1,0,NULL,NULL,'2020-11-10 17:30:57','2020-11-10 17:30:57',NULL,NULL,0,NULL,NULL),
(2,0,NULL,NULL,'2020-11-10 17:31:42','2020-11-10 17:31:42',NULL,NULL,0,NULL,NULL),
(3,0,NULL,NULL,'2021-02-02 17:01:56','2021-02-02 17:33:51',NULL,NULL,1,180057,NULL);
/*!40000 ALTER TABLE `user_security` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_status_log`
--

DROP TABLE IF EXISTS `user_status_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `occuredAt` datetime NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `transitionedBy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F637FE8B4A8EA82E` (`transitionedBy_id`),
  KEY `IDX_F637FE8BA76ED395` (`user_id`),
  CONSTRAINT `FK_F637FE8B4A8EA82E` FOREIGN KEY (`transitionedBy_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_F637FE8BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_status_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_status_log` WRITE;
/*!40000 ALTER TABLE `user_status_log` DISABLE KEYS */;
INSERT INTO `user_status_log` VALUES
(1,1,NULL,'pending','2019-12-09 12:22:58','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(2,2,NULL,'pending','2019-12-09 12:22:58','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(3,3,NULL,'pending','2019-12-09 12:22:59','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(4,4,NULL,'pending','2019-12-09 12:23:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(5,5,NULL,'pending','2019-12-09 12:23:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(6,6,NULL,'pending','2019-12-09 12:23:01','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(7,7,NULL,'pending','2019-12-09 12:23:01','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(8,8,NULL,'pending','2019-12-09 12:23:01','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(9,9,NULL,'pending','2019-12-09 12:23:02','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(10,10,NULL,'pending','2019-12-09 12:23:02','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(11,11,NULL,'pending','2019-12-09 12:23:03','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(12,12,NULL,'pending','2019-12-09 12:23:03','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(13,13,NULL,'pending','2019-12-09 12:23:03','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(14,14,NULL,'pending','2019-12-09 12:23:04','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(15,15,NULL,'pending','2019-12-09 12:23:04','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(16,16,NULL,'pending','2019-12-09 12:23:05','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(17,17,NULL,'pending','2019-12-09 12:23:05','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(18,18,NULL,'pending','2019-12-09 12:23:05','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(19,19,NULL,'pending','2019-12-09 12:23:06','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(20,20,NULL,'pending','2019-12-09 12:23:06','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(21,21,NULL,'pending','2019-12-09 12:23:07','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(22,22,NULL,'pending','2019-12-09 12:23:07','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(23,23,NULL,'pending','2019-12-09 12:23:08','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(24,24,NULL,'pending','2019-12-09 12:23:08','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(25,25,NULL,'pending','2019-12-09 12:23:09','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(26,26,NULL,'pending','2019-12-09 12:23:09','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(27,27,NULL,'pending','2019-12-09 12:23:09','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(28,28,NULL,'pending','2019-12-09 12:23:09','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(29,29,NULL,'pending','2019-12-09 12:23:09','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(30,30,NULL,'pending','2019-12-09 12:23:10','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(31,31,NULL,'pending','2019-12-09 12:23:10','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(32,32,NULL,'pending','2019-12-09 12:23:11','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(33,33,NULL,'pending','2019-12-09 12:23:11','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(34,34,NULL,'pending','2019-12-09 12:23:11','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(35,35,NULL,'pending','2019-12-09 12:23:12','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(36,36,NULL,'pending','2019-12-09 12:23:12','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(37,37,NULL,'pending','2019-12-09 12:23:13','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(38,38,NULL,'pending','2019-12-09 12:23:13','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(39,39,NULL,'pending','2019-12-09 12:23:13','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(40,40,NULL,'pending','2019-12-09 12:23:14','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(41,41,NULL,'pending','2019-12-09 12:23:14','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(42,42,NULL,'pending','2019-12-09 12:23:15','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(43,43,NULL,'pending','2019-12-09 12:23:15','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(44,44,NULL,'pending','2019-12-09 12:23:15','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(45,45,NULL,'pending','2019-12-09 12:23:16','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(46,46,NULL,'pending','2019-12-09 12:23:16','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(47,47,NULL,'pending','2019-12-09 12:23:17','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(48,48,NULL,'pending','2019-12-09 12:23:17','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(49,49,NULL,'pending','2019-12-09 12:23:17','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(50,50,NULL,'pending','2019-12-09 12:23:18','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(51,51,NULL,'pending','2023-12-09 12:23:18','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(52,52,NULL,'pending','2019-12-09 12:23:19','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(53,53,NULL,'pending','2019-12-09 12:23:19','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(64,1,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(65,2,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(66,3,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(67,34,NULL,'active','2019-12-09 12:23:11','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(68,35,NULL,'active','2016-12-01 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(69,36,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(70,39,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(71,40,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(72,41,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(73,42,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(74,43,NULL,'active','2016-12-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(75,44,NULL,'active','2017-10-07 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(76,45,NULL,'active','2017-10-07 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(77,46,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(78,47,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(79,48,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(80,49,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(81,50,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(82,51,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(83,52,NULL,'active','2018-06-06 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(84,53,NULL,'active','2019-06-02 00:00:00','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL),
(95,22,NULL,'closed','2019-12-09 12:23:06','2025-10-22 15:30:30','2025-10-22 15:30:30',NULL);
/*!40000 ALTER TABLE `user_status_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` int(11) DEFAULT NULL,
  `investor_id` int(11) DEFAULT NULL,
  `contego_score_id` int(11) DEFAULT NULL,
  `wallet_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `image_id` int(11) DEFAULT NULL,
  `username` varchar(180) NOT NULL COMMENT '(DC2Type:string)',
  `username_canonical` varchar(180) NOT NULL COMMENT '(DC2Type:string)',
  `email` varchar(180) NOT NULL COMMENT '(DC2Type:string)',
  `email_canonical` varchar(180) NOT NULL COMMENT '(DC2Type:string)',
  `enabled` tinyint(1) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `last_login` datetime DEFAULT NULL,
  `confirmation_token` varchar(180) DEFAULT NULL COMMENT '(DC2Type:string)',
  `password_requested_at` datetime DEFAULT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `ob_mifid` int(11) DEFAULT 0,
  `ob_step` int(11) DEFAULT 0,
  `firstname` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `lastname` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `gender` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `type` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `lastLoginAt` datetime DEFAULT NULL,
  `setPasswdExpiry` tinyint(1) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `honoricPrefix` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `honoricSuffix` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `jobTitle` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `location` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `nationality` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `mobile` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `phone1` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `phone2` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `birthCountry` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `birthDate` datetime DEFAULT NULL,
  `birthPlace` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `drivingLicenseNo` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `passportNumber` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `passportCountry` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `passportExpiry` datetime DEFAULT NULL,
  `incomeRange` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `mangoPayUserId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `mangoPayWalletId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `visibility` int(11) NOT NULL DEFAULT 0,
  `isVIP` int(11) NOT NULL DEFAULT 0,
  `occupation` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `additionalType` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `additionalName` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `affiliateCode` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `biography` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `externalReferenceId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `referralCode` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `sector` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `tagline` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `taxId` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `timezone` datetime DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `term_service_accepted` tinyint(1) DEFAULT 0,
  `gdpr_accepted` int(11) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `security_id` int(11) DEFAULT NULL,
  `managedBy_id` int(11) DEFAULT NULL,
  `walletUserVersion` int(11) NOT NULL DEFAULT 0,
  `kycProfile_id` int(11) DEFAULT NULL,
  `onboardingProfile_id` int(11) DEFAULT NULL,
  `scaEnrolledAt` datetime DEFAULT NULL,
  `scaStatus` varchar(255) NOT NULL DEFAULT 'inactive',
  `bankAccountsSyncedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E992FC23A8` (`username_canonical`),
  UNIQUE KEY `UNIQ_1483A5E9A0D96FBF` (`email_canonical`),
  UNIQUE KEY `UNIQ_1483A5E9F85E0677` (`username`),
  UNIQUE KEY `UNIQ_1483A5E9C05FB297` (`confirmation_token`),
  UNIQUE KEY `UNIQ_1483A5E96BF700BD` (`status_id`),
  UNIQUE KEY `UNIQ_1483A5E99AE528DA` (`investor_id`),
  UNIQUE KEY `UNIQ_1483A5E9E45FA424` (`contego_score_id`),
  UNIQUE KEY `UNIQ_1483A5E9712520F3` (`wallet_id`),
  UNIQUE KEY `UNIQ_1483A5E9979B1AD6` (`company_id`),
  UNIQUE KEY `UNIQ_1483A5E93DA5256D` (`image_id`),
  UNIQUE KEY `UNIQ_1483A5E96DBE4214` (`security_id`),
  UNIQUE KEY `UNIQ_1483A5E9F6B6113D` (`kycProfile_id`),
  UNIQUE KEY `UNIQ_1483A5E9982B20F` (`onboardingProfile_id`),
  KEY `IDX_1483A5E9E5853CE4` (`managedBy_id`),
  CONSTRAINT `FK_1483A5E93DA5256D` FOREIGN KEY (`image_id`) REFERENCES `documents` (`id`),
  CONSTRAINT `FK_1483A5E96BF700BD` FOREIGN KEY (`status_id`) REFERENCES `users_statuses` (`id`),
  CONSTRAINT `FK_1483A5E96DBE4214` FOREIGN KEY (`security_id`) REFERENCES `user_security` (`id`),
  CONSTRAINT `FK_1483A5E9712520F3` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`),
  CONSTRAINT `FK_1483A5E9979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `FK_1483A5E9982B20F` FOREIGN KEY (`onboardingProfile_id`) REFERENCES `onboarding_profile` (`id`),
  CONSTRAINT `FK_1483A5E99AE528DA` FOREIGN KEY (`investor_id`) REFERENCES `user_investors` (`id`),
  CONSTRAINT `FK_1483A5E9E45FA424` FOREIGN KEY (`contego_score_id`) REFERENCES `contego_score` (`id`),
  CONSTRAINT `FK_1483A5E9E5853CE4` FOREIGN KEY (`managedBy_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1483A5E9F6B6113D` FOREIGN KEY (`kycProfile_id`) REFERENCES `kyc_profile` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,1,1,3,1,1,NULL,'admin@crowdtek.co.uk','admin@crowdtek.co.uk','admin@helpmewithit.com','admin@helpmewithit.com',1,'$2y$13$Oc8v3n1n2kTbBRAaA6aXrOj9Z.5VqFyv.GPFNl4KCHWcSDxMKqo/q','2026-04-29 15:29:00',NULL,NULL,'[\"ROLE_SUPER_ADMIN\"]',0,7,'Admin','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','Admin','GB','GB','+447958886611',NULL,NULL,'GB','1975-10-04 00:00:00','GB','8095622','3875604','GB',NULL,'1','user_m_01HW3CCXCYF1W0QNYE3KXVNRRQ','wlt_m_01HW3CMGAM5NJJ01RM8TQ6H8BS',0,1,'3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2019-12-09 12:22:58','2026-04-29 15:29:00',1,NULL,1,NULL,NULL,NULL,'inactive',NULL),
(2,2,2,4,NULL,2,NULL,'keesh@crowdtek.co.uk','keesh@crowdtek.co.uk','keesh@helpmewithit.com','keesh@helpmewithit.com',1,'$2y$13$/9HQZiNKi6.e.ivXUUnTIOJoeTgv8exfZ8M3i8Ne07kIW/K35bV72',NULL,NULL,NULL,'[\"ROLE_ADMIN\"]',0,7,'Keesh','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','Admin','GB','GB','+447958886611',NULL,NULL,'GB','1972-12-01 00:00:00','GB','1491263','7290245','GB',NULL,'6','21782631','21782632',0,1,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2019-12-09 12:22:58','2025-05-30 14:33:27',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(3,3,3,5,NULL,3,NULL,'sohail@helpmewithit.com','sohail@helpmewithit.com','sohail@helpmewithit.com','sohail@helpmewithit.com',1,'$2y$13$ALNAW8f//rOrKBmwjE2C1eiOq75iYpYZ08UubEFDE/NJaWa6tgy86',NULL,NULL,NULL,'[\"ROLE_ADMIN\"]',0,7,'Sohail','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','Admin','GB','GB','+447958886611',NULL,NULL,'GB','1978-05-07 00:00:00','GB','7311681','6994306','GB',NULL,'3','21578452','21578453',0,1,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2019-12-09 12:22:59','2025-05-30 14:33:30',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(4,4,5,NULL,NULL,4,NULL,'Userfake@test.com','userfake@test.com','jordy65@pouros.org','jordy65@pouros.org',1,'$2y$13$DK4kpTjYAO1HJkJZ3JS/x.oM9FoPMJjtZiDlgTpYeXoc01xM/JoHq',NULL,NULL,NULL,'[]',0,0,'Fritz','Strosin','Female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1989-05-05 20:55:23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(5,5,6,NULL,NULL,5,NULL,'sdaniel@nolan.com','sdaniel@nolan.com','borer.diana@gmail.com','borer.diana@gmail.com',1,'$2y$13$bqcgkiyRKsD1hKzFp1MlSuNlS3kIelRkNN2EJCZkzNNCM8TavaJCC',NULL,NULL,NULL,'[]',0,0,'Jackson','Parisian',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1984-01-19 09:25:46',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(6,6,7,NULL,NULL,6,NULL,'kamryn.dietrich@hotmail.com','kamryn.dietrich@hotmail.com','melissa83@leannon.com','melissa83@leannon.com',1,'$2y$13$Bs4rMhnW6vVsytQVYtDb5upixdXr6lb75Y3PG17MeDKCC0kIKSDWG',NULL,NULL,NULL,'[]',0,0,'Renee','Doyle',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1994-01-14 14:27:34',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(7,7,8,NULL,NULL,7,NULL,'terry.delaney@gmail.com','terry.delaney@gmail.com','emilia.stark@gmail.com','emilia.stark@gmail.com',1,'$2y$13$mQ8hYa4yxtJFl69YBqg1NeXFw2015m39QuU5/qfRUm9/Xc/7gMQFW',NULL,NULL,NULL,'[]',0,0,'Mallory','Schaefer',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1999-05-31 16:34:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(8,8,9,NULL,NULL,8,NULL,'bauch.dan@mueller.info','bauch.dan@mueller.info','tparker@durgan.com','tparker@durgan.com',1,'$2y$13$pZgnQ0l.kqXFU3j7gFk9SOM91iT36gSnQMY0bpI7YaFxzksQu0WiC',NULL,NULL,NULL,'[]',0,0,'Norma','Kunze',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1993-08-31 06:03:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(9,9,10,NULL,NULL,9,NULL,'whettinger@gmail.com','whettinger@gmail.com','victoria29@gmail.com','victoria29@gmail.com',1,'$2y$13$SolzQjq1loJ8FtQkJdg/aeBmq8kf.gUE0Sd1oQB3vxeNye/cTfZiq',NULL,NULL,NULL,'[]',0,0,'Rupert','Wilkinson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'2006-11-15 12:10:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(10,10,11,NULL,NULL,10,NULL,'kale64@weissnat.com','kale64@weissnat.com','ukirlin@grant.com','ukirlin@grant.com',1,'$2y$13$tdzRIY5WXZ8V2gM2T0Rw5uX7Lyl378ddBOKJtoT/G/Vxu.wSvT0fS',NULL,NULL,NULL,'[]',0,0,'Edison','Hackett',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1971-08-04 02:53:28',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(11,11,12,NULL,NULL,11,NULL,'judge89@bahringer.com','judge89@bahringer.com','theodora33@west.com','theodora33@west.com',1,'$2y$13$mLF4dIQtIUsQGwJTbx2Wxea4Fr2yj0pwOEdXSnIy5VGTZTL9DW75i',NULL,NULL,NULL,'[]',0,0,'Remington','Cole',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1975-02-21 03:55:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(12,12,13,NULL,NULL,12,NULL,'ena37@damore.info','ena37@damore.info','jocelyn.schuppe@yahoo.com','jocelyn.schuppe@yahoo.com',1,'$2y$13$V4CnqVa9jRVETa0PyYcVGu/x5CNXyrPCZrWextxkrf4qTsoF7Jj7e',NULL,NULL,NULL,'[]',0,0,'Addie','Hills',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1972-02-26 11:28:32',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(13,13,14,NULL,NULL,13,NULL,'plarkin@gmail.com','plarkin@gmail.com','griffin.hills@ferry.com','griffin.hills@ferry.com',1,'$2y$13$YnrgTj7BMChOx1Ob/5zrM.BY8njLi.22REzMH0c.R0pThq29ncd3S',NULL,NULL,NULL,'[]',0,0,'Hertha','Kilback',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1978-12-25 21:16:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(14,14,15,NULL,NULL,14,NULL,'investor_user1@test.com','investor_user1@test.com','investornatural_user1@test.co','investornatural_user1@test.co',1,'$2y$13$D.UnxIgBYgGOHZ9qtAlObeSo.XJcjP0P8PhDiLgWRpSrBXl10IQxC',NULL,NULL,NULL,'[]',0,0,'Investor','Natural1_MangoPay','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1990-09-06 06:56:45',NULL,NULL,NULL,'GB',NULL,NULL,'18465155',NULL,1,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(15,15,16,NULL,NULL,15,NULL,'investor_user2@test.com','investor_user2@test.com','investornatural_user2@test.co','investornatural_user2@test.co',1,'$2y$13$kOD8Zo1vCRB.s/zotbke1eRQCetquTGXYMSlkjRrFMyDv.iXTau9i',NULL,NULL,NULL,'[]',0,0,'Investor','Natural2_MangoPay','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1986-11-30 13:01:59',NULL,NULL,NULL,'GB',NULL,NULL,'18465502','19076900',1,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(16,16,17,NULL,NULL,16,NULL,'owner_user1@test.com','owner_user1@test.com','ownerlegal_user1@test.co','ownerlegal_user1@test.co',1,'$2y$13$5mVjVQ64sRENA2kL64SIou/Ui0CQENDkPc.K9Xxo./9EBMAJVXZq6',NULL,NULL,NULL,'[]',0,0,'owner1','Legal1','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1993-06-13 01:05:09',NULL,NULL,NULL,'GB',NULL,NULL,'18465323',NULL,2,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(17,17,18,NULL,NULL,17,NULL,'mangopay_wallet_creator@test.com','mangopay_wallet_creator@test.com','mangopay@test.co','mangopay@test.co',1,'$2y$13$KlVDO/Nt4ZWffBttJVlK1uA7giCiVtrf1b2znd0PGqe1vu2z1rIiO',NULL,NULL,NULL,'[]',0,0,'mango','pay','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1972-09-15 06:55:17',NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(18,18,4,NULL,NULL,18,NULL,'user15Investor@test.com','user15investor@test.com','sabina.mueller@botsford.com','sabina.mueller@botsford.com',1,'$2y$13$W13z7IT8QrpbNKAGz0PYU.RnhQWoSgdY0xTeymi7GgIyp27VNSnga',NULL,NULL,NULL,'[]',0,0,'Jaycee','Tremblay','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1973-11-08 02:57:25',NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,2,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(19,20,19,6,4,19,NULL,'bc_investor1@test.com','bc_investor1@test.com','bc_investor1@test.com','bc_investor1@test.com',1,'$2y$13$3fjs0Ig3KfgQQYLFU5nHBuUrR96SlBx.hbnrXy1aUBCsVxVykpOMC',NULL,NULL,NULL,'[]',0,0,'Abdul','Quitzon','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB','GB','+447958886611',NULL,NULL,'GB','2003-09-03 03:58:01','GB',NULL,NULL,'GB',NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(20,21,20,NULL,NULL,20,NULL,'bc_investor2@test.com','bc_investor2@test.com','bc_investor2@test.com','bc_investor2@test.com',1,'$2y$13$muH66jS3LzCgMh5FpZMva.ahJm5WhCvrau1NwQTAdsWW8jOEX4PIK',NULL,NULL,NULL,'[]',0,0,'Korbin','Mohr','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1996-12-14 00:50:32',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(21,22,21,7,NULL,21,NULL,'bc_investor3@test.com','bc_investor3@test.com','bc_investor3@test.com','bc_investor3@test.com',1,'$2y$13$bbORhAEt5m00ulX/rcbuz.3IZmJbCX9mk8vPXJRZFmSjl0Fb5fFIq',NULL,NULL,NULL,'[]',0,0,'Luna','Brown','Male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1985-01-14 20:05:54',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(22,19,22,NULL,NULL,22,NULL,'blockeduser@test.com','blockeduser@test.com','blockeduser@test.com','blockeduser@test.com',0,'$2y$13$NZXAux2HTQuz1jmqV.yiC.U18/2/sdZlG7juTTribHqEqrn.ME6M.',NULL,NULL,NULL,'[]',0,0,'Blocked User','Blocked','Female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1971-08-17 02:33:49',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(23,23,23,8,NULL,23,NULL,'will_be_blockeduser@test.com','will_be_blockeduser@test.com','will_be_blockeduser@test.com','will_be_blockeduser@test.com',1,'$2y$13$yqgy7Cskykb.cgwV6g4ujuGw9g2B89Zt3F4Sm45XRlcZ/vAuW4XuK',NULL,NULL,NULL,'[]',0,0,'Will be blocked User','Blocked','Female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1995-05-14 17:21:54',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(24,24,24,9,NULL,24,NULL,'VIP@User.com','vip@user.com','VIP@User.com','vip@user.com',1,'$2y$13$APUipLQFLKitLeJaGBdi3u6mf5Ycd.VKZCHWGwdp6pmfX/vKKStT2',NULL,NULL,NULL,'[]',0,0,'VIP User','Friesen','Female',NULL,NULL,NULL,NULL,'Mr','','VIP','GB','GB','+447954446611',NULL,NULL,'GB',NULL,'GB','3824180','5926830','GB','2021-11-04 06:43:45','1','45673','40539',0,1,'3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(25,25,25,NULL,NULL,25,NULL,'public_user1@gmail.com','public_user1@gmail.com','public_user1@gmail.com','public_user1@gmail.com',0,'Password123!',NULL,NULL,NULL,'[]',0,0,'Percival','Friesen','<randomElement([\"M\",\"F\")]>',NULL,NULL,NULL,NULL,'Mrs','Msc','Job Title 2','GB','AS','+447958886611',NULL,NULL,'SO',NULL,'HR','4923059','7068202','GB',NULL,'2','32733','81709',0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(26,26,26,NULL,NULL,26,NULL,'public_user2@gmail.com','public_user2@gmail.com','public_user2@gmail.com','public_user2@gmail.com',0,'Password123!',NULL,NULL,NULL,'[]',0,0,'Boris','Hintz','<randomElement([\"M\",\"F\")]>',NULL,NULL,NULL,NULL,'Mr','Bsc','Job Title 3','GB','IN','+447958886611',NULL,NULL,'TW',NULL,'GU','6748554','3653809','GB',NULL,'4','46160','88872',0,1,'3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(27,27,27,NULL,NULL,27,NULL,'FIONA_COOPER@test.com','fiona_cooper@test.com','FIONA_COOPER@test.com','fiona_cooper@test.com',1,'$2y$13$JPByXAL492SwhnO4X4CQl..N6aA84fsRNI.kye3G3F5V5uLBbvX2C',NULL,NULL,NULL,'[]',0,0,'FIONA','COOPER','F',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1976-08-10 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1','Contego Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(28,28,28,1,NULL,28,NULL,'JENNIFER_STONE@test.com','jennifer_stone@test.com','JENNIFER_STONE@test.com','jennifer_stone@test.com',1,'$2y$13$X8MDJ9FXCzzfObzCd5WC/OECbnrRpgZIgf6VEu6.vUHKu8Ozc8ux2',NULL,NULL,NULL,'[]',0,0,'JENNIFER','STONE','F',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1976-08-10 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1','Contego Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(29,29,29,NULL,NULL,29,NULL,'DUNCAN_ROSS_BOWEN@test.com','duncan_ross_bowen@test.com','DUNCAN_ROSS_BOWEN@test.com','duncan_ross_bowen@test.com',1,'$2y$13$92EBI.kIpJmQXsCHouk1g.Pu0GafM32TUUT5Brpk20GnrT/YaovHC',NULL,NULL,NULL,'[]',0,0,'DUNCAN','BOWEN','M',NULL,NULL,NULL,'ROSS',NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1985-03-14 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1','Contego Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(30,30,30,NULL,NULL,30,NULL,'ANTHONY_J_COOPER@test.com','anthony_j_cooper@test.com','ANTHONY_J_COOPER@test.com','anthony_j_cooper@test.com',1,'$2y$13$/4HqsnBPrK2P84vCH0sNceB4sU.D6LwjPZgW615337YghoiohFtdG',NULL,NULL,NULL,'[]',0,0,'ANTHONY','COOPER','M',NULL,NULL,NULL,'J',NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1948-11-01 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1','Contego Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(31,31,31,NULL,NULL,31,NULL,'KEITH_WILKINSON@test.com','keith_wilkinson@test.com','KEITH_WILKINSON@test.com','keith_wilkinson@test.com',1,'$2y$13$nnk/L9mi2eZ0G/dWLPyTIuR4wr5oSivgNbAB.pf6iDfhfIRggOlt2',NULL,NULL,NULL,'[]',0,0,'KEITH','WILKINSON','M',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1972-12-01 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1','Contego Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(32,32,32,2,NULL,32,NULL,'FIONA_STONE@test.com','fiona_stone@test.com','FIONA_STONE@test.com','fiona_stone@test.com',1,'$2y$13$Z3VKPou/qYlCgFMhR2WQS.w6cU.eN21Z8XTyHn0QkOFpFR1XVsllK',NULL,NULL,NULL,'[]',0,0,'FIONA','STONE','F',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'GB',NULL,NULL,NULL,NULL,'1976-08-10 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1','Contego Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(33,33,33,NULL,NULL,33,NULL,'email-not-verified@crowdtek.co.uk','email-not-verified@crowdtek.co.uk','email-not-verified@crowdtek.co.uk','email-not-verified@crowdtek.co.uk',1,'$2y$13$hS4E5qeCzFBBGgMYtICeU.47ZXOTQRqfODDGWrpLrkFqkPd4QpwIi',NULL,NULL,NULL,'[]',0,0,'email-not-verified','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','email-not-verified','GB','GB','+447958886611',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(34,34,34,NULL,NULL,34,NULL,'Email-verified@crowdtek.co.uk','email-verified@crowdtek.co.uk','Email-verified@crowdtek.co.uk','email-verified@crowdtek.co.uk',1,'$2y$13$wYKcxFm2U09/fh91W2SyK.w3nD6WYkjLDBbO10fm10imnp7y7f0gq',NULL,NULL,NULL,'[]',0,5,'Email-verified','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','Email-verified','GB','GB','+447958886611',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(35,35,35,NULL,NULL,35,NULL,'registration-complete@crowdtek.co.uk','registration-complete@crowdtek.co.uk','registration-complete@crowdtek.co.uk','registration-complete@crowdtek.co.uk',1,'$2y$13$wQPclal1RjqCOUawxkrwC.gMEsTw0NHYlFMR59/l0SYZEb7SVLz2W',NULL,NULL,NULL,'[]',0,0,'registration-complete','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','registration-complete','GB','GB','+447958886611',NULL,NULL,'GB','1975-10-04 00:00:00','GB','3053358','7351238','GB','2022-04-05 11:51:37','6','28287','19115',0,0,'3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(36,36,36,NULL,NULL,36,NULL,'approved-user@crowdtek.co.uk','approved-user@crowdtek.co.uk','approved-user@crowdtek.co.uk','approved-user@crowdtek.co.uk',1,'$2y$13$1VyNO32YxZKntiu/VF/kouKpQVoZod5zvkGbKsuFqv59OCmSSgWza',NULL,NULL,NULL,'[]',0,0,'approved-user','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','approved-user','GB','GB','+447958886611',NULL,NULL,'GB','1944-10-04 00:00:00','GB','4950766','3104593','GB','2024-12-06 04:59:37','2','42193','27969',0,0,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:12','2019-12-09 12:23:12',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(37,37,37,10,NULL,37,NULL,'company-verified@crowdtek.co.uk','company-verified@crowdtek.co.uk','company-verified@crowdtek.co.uk','company-verified@crowdtek.co.uk',1,'$2y$13$pVVjGdKtsRA/34xfxmbZ7OdMs/oKCUBP9N5R99QAcjd/g6wagzhBy',NULL,NULL,NULL,'[]',0,0,'user204','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','user204','GB','GB','+447958886611',NULL,NULL,NULL,'1975-10-04 00:00:00',NULL,NULL,'2211692','GB',NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(38,38,38,NULL,NULL,38,NULL,'company-min-verified@crowdtek.co.uk','company-min-verified@crowdtek.co.uk','company-min-verified@crowdtek.co.uk','company-min-verified@crowdtek.co.uk',1,'$2y$13$ibCCvZfj8hg81U3oNppkLu36Pi3IcPcq7xwbMfl2bRnIbNdgzkbM.',NULL,NULL,NULL,'[]',0,0,'company-min-verified@crowdtek.co.uk','Crowdtek','Male',NULL,NULL,NULL,NULL,'Mr','','company-min-verified@crowdtek.co.uk','GB','GB','+447958886611',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(39,39,39,11,NULL,39,NULL,'irfan@yielders.co.uk','irfan@yielders.co.uk','irfan@yielders.co.uk','irfan@yielders.co.uk',1,'$2y$13$ErLXJ1wBh6TOBmN2bGFC9eH8W0J7T9rwdZ4hj7/ita02BfyEVxY1u',NULL,NULL,NULL,'[\"ROLE_ADMIN\"]',0,7,'Irfan','Khan','Male',NULL,NULL,NULL,NULL,'Mr','','Admin','GB','GB','+447958886611',NULL,NULL,'GB','1975-10-04 00:00:00','GB',NULL,NULL,NULL,NULL,'1','23592994','23592997',0,1,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2019-12-09 12:23:13','2025-05-30 14:33:33',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(40,40,40,12,NULL,40,NULL,'marwa@yielders.co.uk','marwa@yielders.co.uk','marwa@yielders.co.uk','marwa@yielders.co.uk',1,'$2y$13$iaVyDNop.XIkgCl1OgmxbOR4/Ls.HcTUh9hsoBLnu9ufkTOyJEDui',NULL,NULL,NULL,'[\"ROLE_ADMIN\"]',0,7,'marwa','yielders','Female',NULL,NULL,NULL,NULL,'Mrs','','Admin','GB','GB','+447958886611',NULL,NULL,'GB','1972-12-01 00:00:00','GB',NULL,NULL,NULL,NULL,'4','21782639','21782644',0,1,'6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2019-12-09 12:23:14','2025-05-30 14:33:35',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(41,41,41,13,NULL,41,NULL,'zeeshan@yielders.co.uk','zeeshan@yielders.co.uk','zeeshan@yielders.co.uk','zeeshan@yielders.co.uk',1,'$2y$13$e6tLUMuJtF4n3SUaVoE4T.HJai/0aFhaWUVeobG13WvkZXmXx05nS',NULL,NULL,NULL,'[\"ROLE_ADMIN\"]',0,7,'Zee','yielders','Male',NULL,NULL,NULL,NULL,'Mr','','Admin','GB','GB','+447958886611',NULL,NULL,'GB','1972-12-01 00:00:00','GB',NULL,NULL,NULL,NULL,NULL,'23542232','23542233',0,1,'3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2019-12-09 12:23:14','2025-05-30 14:33:37',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(42,42,42,NULL,NULL,42,NULL,'stampduty@yielders.co.uk','stampduty@yielders.co.uk','stampduty@yielders.co.uk','stampduty@yielders.co.uk',1,'$2y$13$/NhU6mscj0rOv8sC/qbBjOq.G2bxFqDCn.g1kxCQXvAmj3uCIRCP2',NULL,NULL,NULL,'[]',0,0,'stampduty','yielders','Male',NULL,NULL,NULL,NULL,'Mr','','stamp duty','GB','GB',NULL,NULL,NULL,NULL,'1972-12-01 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,'user_m_01HW3D5DZHQKZ11CH9B4BR9K70','wlt_m_01HW3D90QQQXZ5KEB5X6PJYXN9',0,1,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(43,43,43,14,NULL,43,NULL,'ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk','ben.autotest@crowdtek.co.uk',1,'$2y$13$lJcXx41t4oApRlWQGg5t3O0EMhIuGN47mA06.qSZ5rysIGGj28rwK','2026-06-09 16:54:35',NULL,NULL,'[]',0,0,'Ben','Charlton','MALE',NULL,NULL,NULL,NULL,'Mr',NULL,'Refrigeration mechanic','GB','GB','+447969291087',NULL,NULL,'GB','1978-05-07 00:00:00','GB','2976706','7689302','GB',NULL,'2','user_m_01HW3FB1XG2KGBYMCKKV3Z1F7V','wlt_m_01HW3FBRBZF8ZMEF8WHPRA21NZ',0,0,'3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2019-12-09 12:23:15','2026-06-09 16:54:35',2,NULL,1,NULL,4,NULL,'active',NULL),
(44,44,44,15,NULL,44,NULL,'holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com','holly.autotest@helpmewithit.com',1,'$2y$13$.vFRAfSvuzEb3L6RaKjvXuBpXxiIVBeJrY3BCC9vSeJ7ndmJcm/My','2019-12-10 13:41:49',NULL,NULL,'[]',0,0,'Holly','Bird','FEMALE',NULL,NULL,NULL,NULL,'Mrs',NULL,'Singer','GB','GB','+447958886611',NULL,NULL,'GB','1978-05-07 00:00:00','GB','3071229','4818512','GB',NULL,'6','user_m_01HW3FKFC90176MJWG07KNDXEG','wlt_m_01HW3FKZR6PBD5PX8818D5GP04',0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2019-12-09 12:23:15','2025-06-13 10:15:57',NULL,NULL,0,NULL,3,NULL,'active',NULL),
(45,45,45,NULL,NULL,45,NULL,'ed.autotest@red.com','ed.autotest@red.com','ed.autotest@red.com','ed.autotest@red.com',1,'$2y$13$i2NknwL0TwbGTdO4MEcBDuvu9dWkOfBPaMthk2VIVFiliYlnD8AYi',NULL,NULL,NULL,'[]',0,0,'Edward','Haynes','MALE',NULL,NULL,NULL,NULL,'Mr',NULL,'Man','GB','GB','+447958886611',NULL,NULL,'GB','1978-05-07 00:00:00','GB','1749292','8186239','GB',NULL,'1','user_m_01HW3G4VDF6KE99EZNDQ6FV5BJ','wlt_m_01HW3G5FMS1H4AGGARZ6ETTXQD',0,0,'4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2019-12-09 12:23:16','2025-06-13 10:15:50',NULL,NULL,0,NULL,2,NULL,'active',NULL),
(46,46,52,NULL,NULL,46,NULL,'yorran_2sverified@cb.co.uk','yorran_2sverified@cb.co.uk','yorran_2sverified@cb.co.uk','yorran_2sverified@cb.co.uk',1,'$2y$13$diByz3fZ1BxmAZ07yabWA.x0OrEa9F2dh83Q7YOWdB33ub1oM0BX2',NULL,NULL,NULL,'[]',0,2,'Yorran','Davies',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:16','2019-12-09 12:23:16',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(47,47,46,NULL,NULL,47,NULL,'henley_3declaredqs@cb.co.uk','henley_3declaredqs@cb.co.uk','henley_3declaredqs@cb.co.uk','henley_3declaredqs@cb.co.uk',1,'$2y$13$NxhRuTCTA47LGIZBWWC/yuV8PDEMRk9X9LM2cdO7Z6mmKVWAmLEr.',NULL,NULL,NULL,'[]',0,3,'Jane','Henley',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(48,48,47,NULL,NULL,48,NULL,'bryson_3declaredqf@cb.co.uk','bryson_3declaredqf@cb.co.uk','bryson_3declaredqf@cb.co.uk','bryson_3declaredqf@cb.co.uk',1,'$2y$13$kBiU9Rzp9VzjRRm5ic955.vxNlYraoVIUE5tPfh.oaXywq2/95gwy',NULL,NULL,NULL,'[]',0,3,'Ilyin','Bryson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(49,49,48,NULL,NULL,49,NULL,'patton_4user@cb.co.uk','patton_4user@cb.co.uk','patton_4user@cb.co.uk','patton_4user@cb.co.uk',1,'$2y$13$B0TVARhmB7eB4uf3u6qUFeOcv283ScWBSDteGjka2Be8QCQEyXGPG',NULL,NULL,NULL,'[]',0,4,'Harriet','Patton',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:17','2019-12-09 12:23:17',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(50,50,49,NULL,NULL,50,NULL,'sonnet_4busi@cb.co.uk','sonnet_4busi@cb.co.uk','sonnet_4busi@cb.co.uk','sonnet_4busi@cb.co.uk',1,'$2y$13$hRHTY67ZdAHpS6z8jAMCLOtwClVhnZZkvKts2GhHsRY9tcxEF3kva',NULL,NULL,NULL,'[]',0,4,'Jeremy','Sonnet',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:18','2019-12-09 12:23:18',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(51,51,50,NULL,NULL,51,NULL,'hamlin_5complied@cb.co.uk','hamlin_5complied@cb.co.uk','hamlin_5complied@cb.co.uk','hamlin_5complied@cb.co.uk',1,'$2y$13$ONCemoumDxQqCIGDWqscd.06Ylt3XLHVXSCuNrUtUS7o1.Tu4xlNe',NULL,NULL,NULL,'[]',0,5,'Sally','Hamlin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2023-12-09 12:23:18','2023-12-09 12:23:18',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(52,52,53,NULL,NULL,52,NULL,'yalta_1signupd@cb.co.uk','yalta_1signupd@cb.co.uk','yalta_1signupd@cb.co.uk','yalta_1signupd@cb.co.uk',1,'$2y$13$YLld2w22xlXndjxlgWncT./RAxxXDiHsSAbzcbuEXRV7PtI8PCHBy',NULL,NULL,NULL,'[]',0,1,'Tom','Yalta',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2019-12-09 12:23:19','2019-12-09 12:23:19',NULL,NULL,0,NULL,NULL,NULL,'inactive',NULL),
(53,53,51,16,NULL,53,NULL,'jim.autotest@crowdtek.co.uk','jim.autotest@crowdtek.co.uk','jim.autotest@crowdtek.co.uk','jim.autotest@crowdtek.co.uk',1,'$2y$13$4r8iwEpQE84pwCXlkwK2M.4RN/rd0RYlDZ7PE772HJLEZNy.ZbSxK','2021-02-02 17:33:50',NULL,NULL,'[]',0,0,'Jim','Weston','MALE',NULL,NULL,NULL,NULL,'Mr',NULL,'University Lecturer','GB','GB','+445555624000',NULL,NULL,'GB','1989-03-02 00:00:00','GB','8265686','3366029','GB',NULL,'6','user_m_01HW3FDNVC6EG3QWBESED863WC','wlt_m_01HW3FF80J5F76J2SS02DQMS7H',0,1,'2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2019-12-09 12:23:19','2025-06-13 10:15:14',3,NULL,0,NULL,1,NULL,'active',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users_communications`
--

DROP TABLE IF EXISTS `users_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `content` longtext NOT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5297C2FBA76ED395` (`user_id`),
  CONSTRAINT `FK_5297C2FBA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_communications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users_communications` WRITE;
/*!40000 ALTER TABLE `users_communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_communications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users_statuses`
--

DROP TABLE IF EXISTS `users_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emailNotVerifiedOn` datetime DEFAULT NULL,
  `isEmailNotVerifed` tinyint(1) NOT NULL,
  `emailValidatedOn` datetime DEFAULT NULL,
  `isEmailValidated` tinyint(1) NOT NULL,
  `regCompletedOn` datetime DEFAULT NULL,
  `isRegCompleted` tinyint(1) NOT NULL,
  `approvedOn` datetime DEFAULT NULL,
  `isApproved` tinyint(1) NOT NULL,
  `blockedOn` datetime DEFAULT NULL,
  `isBlocked` tinyint(1) NOT NULL,
  `isKycApproved` tinyint(1) NOT NULL,
  `kycStatusOn` datetime DEFAULT NULL,
  `acctMgmtStatusOn` tinyint(1) DEFAULT NULL,
  `mangopayRegistrationOn` datetime DEFAULT NULL,
  `lifecycleStatus` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `lifecycleStatusComment` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_statuses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users_statuses` WRITE;
/*!40000 ALTER TABLE `users_statuses` DISABLE KEYS */;
INSERT INTO `users_statuses` VALUES
(1,'2019-12-09 12:22:58',0,'2016-12-02 00:00:00',1,'2016-12-03 00:00:00',1,'2019-12-09 12:22:58',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(2,'2019-12-09 12:22:58',0,'2016-12-02 00:00:00',1,'2016-12-03 00:00:00',1,'2019-12-09 12:22:58',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(3,'2019-12-09 12:22:58',0,'2016-12-02 00:00:00',1,'2016-12-04 00:00:00',1,'2019-12-09 12:22:58',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(4,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:00','2019-12-09 12:23:00',NULL,NULL),
(5,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(6,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(7,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:01','2019-12-09 12:23:01',NULL,NULL),
(8,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL),
(9,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:02','2019-12-09 12:23:02',NULL,NULL),
(10,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(11,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(12,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:03','2019-12-09 12:23:03',NULL,NULL),
(13,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL),
(14,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:04','2019-12-09 12:23:04',NULL,NULL),
(15,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(16,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(17,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:05','2019-12-09 12:23:05',NULL,NULL),
(18,'2019-12-09 12:23:00',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(19,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,'2019-12-09 12:23:06',1,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(20,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL),
(21,'2019-12-09 12:23:06',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL),
(22,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:07','2019-12-09 12:23:07',NULL,NULL),
(23,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(24,'2019-12-09 12:23:06',1,NULL,0,NULL,0,'2019-12-09 12:23:06',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:08','2019-12-09 12:23:08',NULL,NULL),
(25,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(26,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(27,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(28,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:09','2019-12-09 12:23:09',NULL,NULL),
(29,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL),
(30,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:10','2019-12-09 12:23:10',NULL,NULL),
(31,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(32,'2019-12-09 12:23:09',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(33,'2019-12-09 12:23:11',1,NULL,0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(34,'2019-12-09 12:23:11',0,'2019-12-09 12:23:11',1,NULL,1,NULL,1,NULL,0,0,NULL,NULL,NULL,'email_verified',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(35,'2019-12-09 12:23:11',1,'2016-12-01 00:00:00',1,'2019-12-09 12:23:11',1,NULL,0,NULL,0,0,NULL,NULL,NULL,'registration_complete',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(36,'2019-12-09 12:23:11',1,'2016-12-02 00:00:00',1,'2016-12-03 00:00:00',1,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(37,'2019-12-09 12:23:11',1,NULL,1,NULL,0,'2019-12-09 12:23:11',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(38,'2019-12-09 12:23:11',1,NULL,1,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:11','2019-12-09 12:23:11',NULL,NULL),
(39,'2019-12-09 12:23:13',0,'2016-12-02 00:00:00',1,'2016-12-03 00:00:00',1,'2019-12-09 12:23:13',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(40,'2019-12-09 12:23:13',0,'2016-12-02 00:00:00',1,'2016-12-03 00:00:00',1,'2019-12-09 12:23:13',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(41,'2019-12-09 12:23:13',0,'2016-12-02 00:00:00',1,'2016-12-04 00:00:00',1,'2019-12-09 12:23:13',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(42,'2019-12-09 12:23:13',0,'2016-12-02 00:00:00',1,'2016-12-04 00:00:00',1,NULL,1,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:13','2019-12-09 12:23:13',NULL,NULL),
(43,'2019-12-09 12:23:15',0,'2016-12-02 00:00:00',1,'2016-12-04 00:00:00',1,'2019-12-09 12:23:15',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(44,'2019-12-09 12:23:15',0,'2017-10-07 00:00:00',1,'2017-10-10 00:00:00',1,'2019-12-09 12:23:15',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(45,'2019-12-09 12:23:15',0,'2017-10-07 00:00:00',1,'2017-10-10 00:00:00',1,NULL,1,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(46,'2019-12-09 12:23:15',0,'2018-06-06 00:00:00',1,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(47,'2019-12-09 12:23:15',0,'2018-06-06 00:00:00',1,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(48,'2019-12-09 12:23:15',0,'2018-06-06 00:00:00',1,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(49,'2019-12-09 12:23:15',0,'2018-06-06 00:00:00',1,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(50,'2019-12-09 12:23:15',0,'2018-06-06 00:00:00',1,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(51,'2019-12-09 12:23:15',0,'2018-06-06 00:00:00',1,'2024-08-12 13:49:51',1,'2024-08-12 13:49:54',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:15','2024-08-12 13:49:54',NULL,'admin@crowdtek.co.uk'),
(52,'2019-12-09 12:23:15',1,'2018-06-06 00:00:00',0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'email_not_verified',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL),
(53,'2019-12-09 12:23:15',0,'2019-06-02 00:00:00',1,'2016-06-02 00:00:00',1,'2019-12-09 12:23:15',1,NULL,0,0,NULL,NULL,NULL,'approved',NULL,NULL,'2019-12-09 12:23:15','2019-12-09 12:23:15',NULL,NULL);
/*!40000 ALTER TABLE `users_statuses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency` varchar(255) NOT NULL COMMENT '(DC2Type:string)',
  `free_balance` decimal(10,0) DEFAULT NULL,
  `balance` decimal(10,0) DEFAULT NULL,
  `committed_balance` decimal(10,0) DEFAULT NULL,
  `createdById` int(11) DEFAULT 0,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `createdBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  `updatedBy` varchar(255) DEFAULT NULL COMMENT '(DC2Type:string)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES
(1,'GBP',500,1000,500,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(2,'GBP',1000,2000,500,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(3,'GBP',2000,3000,500,NULL,'2019-12-09 12:22:58','2019-12-09 12:22:58',NULL,NULL),
(4,'GBP',500,1000,500,NULL,'2019-12-09 12:23:06','2019-12-09 12:23:06',NULL,NULL);
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `webhook_event`
--

DROP TABLE IF EXISTS `webhook_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventType` varchar(255) NOT NULL,
  `resourceId` varchar(255) NOT NULL,
  `fingerprint` varchar(255) NOT NULL,
  `lastReceived` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_event`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `webhook_event` WRITE;
/*!40000 ALTER TABLE `webhook_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_event` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Final view structure for view `getAssetData`
--

/*!50001 DROP VIEW IF EXISTS `getAssetData`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getAssetData` AS select 1 AS `id`,1 AS `name`,1 AS `additionalType`,1 AS `alternateName`,1 AS `briefDescription`,1 AS `companyNumber`,1 AS `creditScore`,1 AS `detailedDesc`,1 AS `displayName`,1 AS `facebookUri`,1 AS `foundingDate`,1 AS `foundingLocation`,1 AS `legalName`,1 AS `linkedinUri`,1 AS `youtubeUri`,1 AS `twitterUri`,1 AS `location`,1 AS `logo`,1 AS `orgEmail`,1 AS `orgWebsite`,1 AS `sector`,1 AS `taxId`,1 AS `telephone`,1 AS `fundingGoal`,1 AS `amountOfShares`,1 AS `setupFee`,1 AS `adminFee`,1 AS `managementFee`,1 AS `profitShare`,1 AS `stampDutyUser`,1 AS `assetType`,1 AS `investmentTerm`,1 AS `grossRentalReturnPA`,1 AS `netRentalReturnPA`,1 AS `grossCapitalAppreciation`,1 AS `netCapitalAppreciation`,1 AS `netCapitalAppreciationYield`,1 AS `gross_yield`,1 AS `pointsOfInterest`,1 AS `blockedForSale`,1 AS `pricePerShare`,1 AS `visibility`,1 AS `mangoPayUserId`,1 AS `mangoPayWalletId`,1 AS `additional_wallet`,1 AS `createdById`,1 AS `createdAt`,1 AS `updatedAt`,1 AS `createdBy`,1 AS `updatedBy`,1 AS `contactPoint_id`,1 AS `assetStatus_id`,1 AS `draftOn`,1 AS `isDraft`,1 AS `archivedOn`,1 AS `isArchived`,1 AS `cancelledOn`,1 AS `isCancelled`,1 AS `submittedOn`,1 AS `isSubmitted`,1 AS `rejectedOn`,1 AS `isRejected`,1 AS `publishedOn`,1 AS `isPublished`,1 AS `lifecycleStatus`,1 AS `status.createdById`,1 AS `status.createdAt`,1 AS `status.updatedAt`,1 AS `status.createdBy`,1 AS `status.updatedBy` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getCapitalRepayments`
--

/*!50001 DROP VIEW IF EXISTS `getCapitalRepayments`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getCapitalRepayments` AS select 1 AS `inv_id`,1 AS `capitalRepaid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getContegoLog`
--

/*!50001 DROP VIEW IF EXISTS `getContegoLog`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getContegoLog` AS select 1 AS `id`,1 AS `profile_name`,1 AS `rag`,1 AS `kyc_score`,1 AS `kyc_type`,1 AS `ext_reference_id`,1 AS `pdf_report_url`,1 AS `user`,1 AS `createdAt` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getDivestments`
--

/*!50001 DROP VIEW IF EXISTS `getDivestments`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getDivestments` AS select 1 AS `inv_id`,1 AS `divested_amount`,1 AS `divested_shares`,1 AS `divestment_trades` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getHoldingData`
--

/*!50001 DROP VIEW IF EXISTS `getHoldingData`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getHoldingData` AS select 1 AS `id`,1 AS `asset_id`,1 AS `name`,1 AS `user_id`,1 AS `email`,1 AS `investment_id`,1 AS `investmest_shareamount`,1 AS `investment_name`,1 AS `transaction_id`,1 AS `share_amount`,1 AS `createdById`,1 AS `createdAt`,1 AS `updatedAt`,1 AS `createdBy`,1 AS `updatedBy` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getInvestmentData`
--

/*!50001 DROP VIEW IF EXISTS `getInvestmentData`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getInvestmentData` AS select 1 AS `id`,1 AS `user_id`,1 AS `off_id`,1 AS `assetId`,1 AS `Offering Name`,1 AS `visibility`,1 AS `for_sale`,1 AS `name`,1 AS `investmentValue`,1 AS `numberOfShares`,1 AS `currency`,1 AS `interestRate`,1 AS `term`,1 AS `orgPricePerShare`,1 AS `PricePerShare`,1 AS `share_amount`,1 AS `transaction_id`,1 AS `type`,1 AS `comments`,1 AS `createdById`,1 AS `createdAt`,1 AS `updatedAt`,1 AS `createdBy`,1 AS `updatedBy`,1 AS `investmentStatus_id`,1 AS `openOn`,1 AS `isOpen`,1 AS `rejectedOn`,1 AS `isRejected`,1 AS `approvedOn`,1 AS `isApproved`,1 AS `withdrawnOn`,1 AS `isWithdrawn`,1 AS `settledOn`,1 AS `isSettled`,1 AS `lifecycleStatus`,1 AS `signedUpWithRefCode`,1 AS `referersUsername`,1 AS `status.createdById`,1 AS `status.createdAt`,1 AS `status.updatedAt`,1 AS `status.createdBy`,1 AS `status.updatedBy`,1 AS `capitalRepaid`,1 AS `isRetention`,1 AS `cxbWorthInvestor`,1 AS `cxbSophisticatedInvestor`,1 AS `cxbRestrictedUser`,1 AS `corporateInvestor`,1 AS `estimatedStampDuty` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getInvestmentPayouts`
--

/*!50001 DROP VIEW IF EXISTS `getInvestmentPayouts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getInvestmentPayouts` AS select 1 AS `investment_id`,1 AS `offering_id`,1 AS `asset_id`,1 AS `asset_name`,1 AS `investor_id`,1 AS `investment_value`,1 AS `no_of_shares`,1 AS `org_price_per_share`,1 AS `price_per_share`,1 AS `share_amount`,1 AS `investment_created_at`,1 AS `investment_created_by`,1 AS `investment_status`,1 AS `dueDate`,1 AS `payoutAmount`,1 AS `fee`,1 AS `payout_created_at`,1 AS `payout_created_by`,1 AS `payout_owner_object`,1 AS `payout_transaction_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getLastActivity`
--

/*!50001 DROP VIEW IF EXISTS `getLastActivity`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getLastActivity` AS select 1 AS `id`,1 AS `action`,1 AS `logged_at`,1 AS `object_id`,1 AS `object_class`,1 AS `version`,1 AS `data`,1 AS `username` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getOfferingData`
--

/*!50001 DROP VIEW IF EXISTS `getOfferingData`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getOfferingData` AS select 1 AS `id`,1 AS `asset_id`,1 AS `inv_id`,1 AS `offeringType`,1 AS `name`,1 AS `additionalType`,1 AS `category`,1 AS `dealDescription`,1 AS `creditScore`,1 AS `fundingGoal`,1 AS `externalCommitments`,1 AS `isFeatured`,1 AS `isSecondaryMrkt`,1 AS `loanToValue`,1 AS `valuation`,1 AS `equityOffered`,1 AS `noOfShares`,1 AS `pricePerShare`,1 AS `debtInterestRate`,1 AS `county`,1 AS `debtTerm`,1 AS `netRentProjected`,1 AS `grossRentProjected`,1 AS `grossProjectReturn`,1 AS `offeringTerm`,1 AS `openDate`,1 AS `closeDate`,1 AS `minCommitUser`,1 AS `maxCommitUser`,1 AS `maxOverFunding`,1 AS `primaryOfferingId`,1 AS `comments`,1 AS `visibility`,1 AS `additional_type`,1 AS `currency`,1 AS `createdById`,1 AS `createdAt`,1 AS `updatedAt`,1 AS `createdBy`,1 AS `updatedBy`,1 AS `offeringStatus_id`,1 AS `discr`,1 AS `draftedOn`,1 AS `isDraft`,1 AS `archivedOn`,1 AS `isArchived`,1 AS `cancelledOn`,1 AS `isCancelled`,1 AS `submittedOn`,1 AS `isSubmitted`,1 AS `rejectedOn`,1 AS `isRejected`,1 AS `approvedOn`,1 AS `isApproved`,1 AS `publishedOn`,1 AS `isPublished`,1 AS `restrictedOn`,1 AS `isRestricted`,1 AS `closedOn`,1 AS `isClosed`,1 AS `settledOn`,1 AS `isSettled`,1 AS `lifecycleStatus`,1 AS `status.createdById`,1 AS `status.createdAt`,1 AS `status.updatedAt`,1 AS `status.createdBy`,1 AS `status.updatedBy` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getShareHoldings`
--

/*!50001 DROP VIEW IF EXISTS `getShareHoldings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getShareHoldings` AS select 1 AS `asset`,1 AS `assetId`,1 AS `user`,1 AS `userId`,1 AS `currentHolding`,1 AS `originalHolding`,1 AS `divestedHolding`,1 AS `divestmentTrades`,1 AS `capitalRepayments` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getShareRegister`
--

/*!50001 DROP VIEW IF EXISTS `getShareRegister`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getShareRegister` AS select 1 AS `id`,1 AS `Share No.`,1 AS `Investment Type`,1 AS `SPV No.`,1 AS `Account No.`,1 AS `User Email`,1 AS `Username`,1 AS `Title`,1 AS `firstName`,1 AS `lastName`,1 AS `address1`,1 AS `address2`,1 AS `city`,1 AS `region`,1 AS `postCode`,1 AS `country`,1 AS `User Registered`,1 AS `assetId`,1 AS `Asset Name`,1 AS `Asset Street Address`,1 AS `Asset City`,1 AS `Asset Region`,1 AS `Asset Postcode`,1 AS `Asset Country`,1 AS `Number Of Shares`,1 AS `pricePerShare`,1 AS `Share Capital`,1 AS `Seller Profile Id`,1 AS `Seller Username`,1 AS `Seller Email`,1 AS `Seller Title`,1 AS `Seller Firstname`,1 AS `Seller Lastname`,1 AS `Seller Address1`,1 AS `Seller City`,1 AS `Seller Region`,1 AS `Seller Postcode`,1 AS `Seller Country`,1 AS `createdAt`,1 AS `settledOn`,1 AS `Original Investment`,1 AS `Amount Divested`,1 AS `Shares Divested`,1 AS `Current Holding`,1 AS `Current Share Holding`,1 AS `MP Debited Wallet Id`,1 AS `MP Credited Wallet Id`,1 AS `Transaction Id`,1 AS `User Company Name`,1 AS `User Company Registration`,1 AS `User Company Reg Address1`,1 AS `User Company PostCode`,1 AS `User Company Approved On`,1 AS `Capital Repaid`,1 AS `isRetention`,1 AS `cxbWorthInvestor`,1 AS `cxbSophisticatedInvestor`,1 AS `cxbRestrictedUser`,1 AS `corporateInvestor` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getShareTrades`
--

/*!50001 DROP VIEW IF EXISTS `getShareTrades`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getShareTrades` AS select 1 AS `investment`,1 AS `asset`,1 AS `buyer`,1 AS `seller`,1 AS `numberOfShares`,1 AS `investedOn`,1 AS `settledOn`,1 AS `assetId`,1 AS `buyerId`,1 AS `sellerId` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getTransactionData`
--

/*!50001 DROP VIEW IF EXISTS `getTransactionData`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getTransactionData` AS select 1 AS `id`,1 AS `user_id`,1 AS `currency`,1 AS `payment_status`,1 AS `createdById`,1 AS `createdAt`,1 AS `updatedAt`,1 AS `createdBy`,1 AS `updatedBy`,1 AS `inv_id`,1 AS `creditor_id`,1 AS `debitor_id`,1 AS `debited_wallet_id`,1 AS `credited_wallet_id`,1 AS `offering_id`,1 AS `share_amount`,1 AS `value_amount`,1 AS `fee_amount`,1 AS `trans_type`,1 AS `comments`,1 AS `external_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getUserData`
--

/*!50001 DROP VIEW IF EXISTS `getUserData`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getUserData` AS select 1 AS `id`,1 AS `username`,1 AS `email`,1 AS `enabled`,1 AS `last_login`,1 AS `joinDate`,1 AS `firstname`,1 AS `lastname`,1 AS `gender`,1 AS `type`,1 AS `middlename`,1 AS `honoricPrefix`,1 AS `honoricSuffix`,1 AS `company_id`,1 AS `jobTitle`,1 AS `location`,1 AS `address1`,1 AS `address2`,1 AS `address3`,1 AS `city`,1 AS `region`,1 AS `postCode`,1 AS `country`,1 AS `nationality`,1 AS `mobile`,1 AS `phone1`,1 AS `phone2`,1 AS `birthCountry`,1 AS `birthDate`,1 AS `birthPlace`,1 AS `drivingLicenseNo`,1 AS `passportNumber`,1 AS `passportCountry`,1 AS `passportExpiry`,1 AS `incomeRange`,1 AS `mangoPayUserId`,1 AS `mangoPayWalletId`,1 AS `isVIP`,1 AS `occupation`,1 AS `additionalType`,1 AS `additionalName`,1 AS `affiliateCode`,1 AS `biography`,1 AS `externalReferenceId`,1 AS `referralCode`,1 AS `sector`,1 AS `tagline`,1 AS `taxId`,1 AS `timezone`,1 AS `website`,1 AS `term_service_accepted`,1 AS `gdpr_accepted`,1 AS `linkedinId`,1 AS `twitterId`,1 AS `facebookId`,1 AS `createdAt`,1 AS `updatedAt`,1 AS `cxbWorthInvestor`,1 AS `cxbSophisticatedInvestor`,1 AS `cxbRestrictedUser`,1 AS `cxbLtdCompInvestor`,1 AS `alwaysGoUp`,1 AS `incomeEveryMonth`,1 AS `neverExit`,1 AS `poiFileId`,1 AS `wordsOfOwn`,1 AS `corporateInvestor`,1 AS `isEmailValidated`,1 AS `isKycApproved`,1 AS `isApproved`,1 AS `isRegCompleted`,1 AS `isBlocked`,1 AS `salesforceId`,1 AS `question3`,1 AS `referralLink`,1 AS `fatca`,1 AS `companyApprovedOn`,1 AS `questionnaire_passed`,1 AS `questionnaire_attempts` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `getUserResetStatus`
--

/*!50001 DROP VIEW IF EXISTS `getUserResetStatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `getUserResetStatus` AS select 1 AS `id`,1 AS `email`,1 AS `last_login`,1 AS `confirmation_token`,1 AS `password_requested_at`,1 AS `lastLoginAt`,1 AS `setPasswdExpiry` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `payoutsReport`
--

/*!50001 DROP VIEW IF EXISTS `payoutsReport`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `payoutsReport` AS select 1 AS `id`,1 AS `payoutAmount`,1 AS `dueDate`,1 AS `payoutType`,1 AS `currency`,1 AS `fee`,1 AS `shareholding`,1 AS `createdAt`,1 AS `createdBy`,1 AS `updatedAt`,1 AS `updatedBy`,1 AS `creditedUserId`,1 AS `creditedUser`,1 AS `assetId`,1 AS `assetSpv`,1 AS `assetName`,1 AS `investmentId` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `prefundingRetention`
--

/*!50001 DROP VIEW IF EXISTS `prefundingRetention`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `prefundingRetention` AS select 1 AS `inv_id`,1 AS `prefundingId` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `shareRegister`
--

/*!50001 DROP VIEW IF EXISTS `shareRegister`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `shareRegister` AS select 1 AS `id`,1 AS `type`,1 AS `transactionId`,1 AS `SPV`,1 AS `assetName`,1 AS `assetId`,1 AS `pricePerShare`,1 AS `numberOfShares`,1 AS `calculatedInvestmentValue`,1 AS `recordedInvestmentValue`,1 AS `Amount Divested`,1 AS `Shares Divested`,1 AS `Current Holding`,1 AS `Current Share Holding`,1 AS `capitalRepaid`,1 AS `isRetention`,1 AS `createdAt`,1 AS `settledOn`,1 AS `buyerId`,1 AS `buyerEmail`,1 AS `buyerUsername`,1 AS `buyerTitle`,1 AS `buyerFirstname`,1 AS `buyerLastname`,1 AS `buyerAddressLine1`,1 AS `buyerAddressLine2`,1 AS `buyerAddressCity`,1 AS `buyerAddressRegion`,1 AS `buyerAddressPostCode`,1 AS `buyerAddressCountry`,1 AS `buyerCreatedAt`,1 AS `sellerId`,1 AS `sellerUsername`,1 AS `sellerEmail`,1 AS `sellerTitle`,1 AS `sellerFirstname`,1 AS `sellerLastname`,1 AS `sellerAddressLine2`,1 AS `sellerAddressCity`,1 AS `sellerAddressRegion`,1 AS `sellerAddressPostcode`,1 AS `sellerAddressCountry`,1 AS `buyerCompanyName`,1 AS `buyerCompanyRegNumber`,1 AS `buyerCompanyRegAddress1`,1 AS `buyerCompanyPostCode`,1 AS `buyerCompanyApprovedOn`,1 AS `cxbWorthInvestor`,1 AS `cxbSophisticatedInvestor`,1 AS `cxbRestrictedUser`,1 AS `corporateInvestor`,1 AS `estimatedStampDuty` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-09 17:32:56
