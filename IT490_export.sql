-- MySQL dump 10.13  Distrib 8.0.41, for Linux (x86_64)
--
-- Host: localhost    Database: IT490
-- ------------------------------------------------------
-- Server version	8.0.41-0ubuntu0.24.04.1

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
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_key` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT ((now() + interval 1 day)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_key` (`session_key`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stocks`
--

DROP TABLE IF EXISTS `stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stocks` (
  `asset_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `price` decimal(20,8) DEFAULT NULL,
  `market_cap` decimal(20,8) DEFAULT NULL,
  `supply` decimal(20,8) DEFAULT NULL,
  `max_supply` decimal(20,8) DEFAULT NULL,
  `volume` decimal(20,8) DEFAULT NULL,
  `change_percent` decimal(10,4) DEFAULT NULL,
  `data` json NOT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stocks`
--

LOCK TABLES `stocks` WRITE;
/*!40000 ALTER TABLE `stocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'1','1@mail.com','$2y$10$oSKV9JEnnU7Ic3iiD5S7pujFao0nvhOXjxQmbRCTR3nqqJyuLgACq'),(2,'2','2@mail.com','$2y$10$4NDaJvunAO.sB5nIfA5EIui/okagwTUy815ONrU5NaEfGrrak.FUq'),(3,'3','3@mail.com','$2y$10$3AZZSVs09XU9jwRkLIgZIu/uHdMhmvfF6SH6T8Td82S8lAHksDkKi'),(4,'4','4@mail.com','$2y$10$9HVfd4.D8cPlhbB87oChpe8l9IZyJhDfprF1vVIlveslRbsLgO20i'),(5,'5','5@mail.com','$2y$10$K7diLyX1g8A9ShbUKbLbQO80/xeZneHLeXgxU3vfOoO4yBOUZLasa'),(6,'6','6@mail.com','$2y$10$BCr08P4CKgVvk2roDWX7RurqiPoaqXet8bBkXx10uDFWxbXmFB6xO'),(7,'7','7@mail.com','$2y$10$pM2wPWNG6AedHcpuDkYc0uhAgLTMEzMhSlXPjm/UP8YuL1xtF5Dju'),(8,'8','8@mail.com','$2y$10$U6qgVDlfwEXMjR1wQerUnOdxtGeFw7lb81rnWbls6q5hhciEi7ejW'),(9,'9','9@mail.com','$2y$10$E6kFkz59gWCTqHVTHoelJuAEiEuWJAdCs1DtUePcSsRy7e9.X57bS'),(10,'10','10@mail.com','$2y$10$uq/WkAVs01oeJ5hb9/72j.yl/s57qt/f4e5yxBT1fM1t47/PbEeAC'),(11,'11','11@mail.com','$2y$10$unLylLMyAZtm6.ZvERYeXOi.aB..F4ouxNQVRiPU96o5YhJqXgAFa'),(12,'12','12@mail.com','$2y$10$MqPEMHCS9HPqtlLS5GHSeupKlUjmImAZCiOw32MpXSm8cUoT52I9O'),(13,'13','13@mail.com','$2y$10$JFHg631gka1uqp.nrUnlHe4gqFchQ.LnztPH5Fjv9FEhJBY8h4k7u'),(14,'14','14@mail.com','$2y$10$qcOzlKzgIm2hPjNiagDx7egAiKXZi7XkaVVej6SV0UciKLJvynAYm'),(15,'15','15@mail.com','$2y$10$AafRXI6wetX85TqPDb7dpeYzT9JR3KQzGoaBP60SU6ZSJMmjd/kba'),(16,'test','test@mail.com','$2y$10$i/0Gock7dDRxTrooXRW7ouTFdUwFvkvRh4izc2wYq6hPVski82F.K'),(17,'test2','test2@mail.com','$2y$10$3OxutPD0iskps2.K/CuwMO7x2rn0I.4AeORhf9FbM8gw51GJr4iFe'),(18,'hello','hello@mail.com','$2y$10$evfjVnmfF.m3LAkRcavf4OdH07rhsSA2pBuG.jfePKcECUXzylDHC'),(19,'kris','iamtesting@gmail.com','$2y$10$krkHVYQDU2pYRf5Ei5Ff9OoP/TQaStFtzzvXqoGXtzrmd5m6uLUp2');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-10  0:32:15
