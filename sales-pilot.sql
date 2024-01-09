-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: localhost    Database: sales_pilot
-- ------------------------------------------------------
-- Server version	8.0.34

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `business_records`
--

DROP TABLE IF EXISTS `business_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_records` (
  `id_business` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `BusinessName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ConfirmPassword` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Location` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `activation_code` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_business`,`Username`,`BusinessName`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_records`
--

LOCK TABLES `business_records` WRITE;
/*!40000 ALTER TABLE `business_records` DISABLE KEYS */;
INSERT INTO `business_records` VALUES (0,'Username','BusinessName','Password','ConfirmPassword','Phone','Email','Location','activation_code','0000-00-00 00:00:00',NULL),(12,'Koko Trading','Koko Trading Stores ','$2y$10$wj7k24yNOvYi3OavI7hCp.q1Jf9PkQrXFg.NUR9rzV3OIl2dopPhe','koko1234','+1 234 567 890','olphemie@hotmail.com','123 Main St CBD, Nairobi, Kenya','652d609a77efd','2023-10-16 19:11:06','uploads/6581f474e39c1.png'),(21,'Meta Trading','Meta Trading Plc','$2y$10$Ai8Z40YlpRQ1KYR1yCfQXOf6J9cS4.CUjmEBv5zix60erBoyOT.cC','meta1234','+1 234 567 890','fernandohowells@gmail.com','fernandohowells@gmail.com','657511beb0706','2023-12-10 04:17:50',NULL);
/*!40000 ALTER TABLE `business_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `id_category` int NOT NULL AUTO_INCREMENT,
  `categoryname` varchar(255) NOT NULL,
  PRIMARY KEY (`id_category`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Tech'),(2,'Fashion'),(3,'Food'),(4,'Home'),(5,'Health'),(6,'Automotives'),(7,'Sport'),(8,'Others'),(9,'Kids'),(10,'Office'),(11,'1'),(12,'2'),(13,'3'),(14,'4'),(15,'5'),(16,'6'),(17,'6'),(18,'7'),(19,'8'),(20,'9'),(21,'10'),(22,'1'),(23,'1'),(24,'2'),(25,'3'),(26,'4'),(27,'4'),(28,'4'),(29,'4'),(30,'4'),(31,'4'),(32,'4'),(33,'4'),(34,'4'),(35,'4'),(36,'4'),(37,'4'),(38,'4'),(39,'4'),(40,'4'),(41,'4'),(42,'4'),(43,'5'),(44,'10'),(45,'6'),(46,'7'),(47,'8'),(48,'9'),(49,'1'),(50,'1'),(51,'1'),(52,'1'),(53,'1'),(54,'1'),(55,'1'),(56,'1'),(57,'1'),(58,'1'),(59,'1'),(60,'1'),(61,'1'),(62,'1'),(63,'2'),(64,'3'),(65,'4'),(66,'5'),(67,'1'),(68,'6'),(69,'6'),(70,'7'),(71,'8'),(72,'9'),(73,'10'),(74,'2'),(75,'3'),(76,'4'),(77,'5'),(78,'10'),(79,'6'),(80,'7'),(81,'4'),(82,'7'),(83,'8'),(84,'9'),(85,'1'),(86,'1'),(87,'1'),(88,'2'),(89,'2');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `id_inventory` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Product` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Author` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SupplyQty` decimal(10,0) NOT NULL,
  `StockQty` decimal(10,0) NOT NULL,
  `CostPrice` decimal(10,2) NOT NULL,
  `SalesPrice` decimal(10,2) NOT NULL,
  `InventoryQty` decimal(10,0) GENERATED ALWAYS AS ((`StockQty` + `SupplyQty`)) STORED,
  `InventoryValue` decimal(10,2) GENERATED ALWAYS AS ((`InventoryQty` * `CostPrice`)) STORED,
  `TotalRevenue` decimal(10,2) GENERATED ALWAYS AS ((`InventoryQty` * `SalesPrice`)) STORED,
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `StockValue` decimal(10,2) GENERATED ALWAYS AS ((`StockQty` * `CostPrice`)) STORED,
  `SupplyValue` decimal(10,2) GENERATED ALWAYS AS ((`SupplyQty` * `CostPrice`)) STORED,
  `category_id` int NOT NULL,
  `DaysOfWeek` enum('Sun','Mon','Tue','Wed','Thu','Fri','Sat') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Month` enum('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `WeeklyStockQty` int GENERATED ALWAYS AS ((`InventoryQty` - `WeeklySalesQty`)) STORED,
  `DailyStockQty` int GENERATED ALWAYS AS ((`InventoryQty` - `DailySalesQty`)) STORED,
  `MonthlyStockQty` int GENERATED ALWAYS AS ((`InventoryQty` - `MonthlySalesQty`)) STORED,
  `YearlyStockQty` int GENERATED ALWAYS AS ((`InventoryQty` - `YearlySalesQty`)) STORED,
  `DailySalesQty` int DEFAULT '0',
  `WeeklySalesQty` int DEFAULT '0',
  `MonthlySalesQty` int DEFAULT '0',
  `YearlySalesQty` int DEFAULT '0',
  PRIMARY KEY (`id_inventory`,`id_user`,`Username`,`Product`),
  KEY `id_user_idx` (`id_user`),
  KEY `category_id_idx` (`category_id`),
  CONSTRAINT `category_id` FOREIGN KEY (`category_id`) REFERENCES `category` (`id_category`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `id_user` FOREIGN KEY (`id_user`) REFERENCES `business_records` (`id_business`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` (`id_inventory`, `id_user`, `Username`, `Product`, `Description`, `Author`, `SupplyQty`, `StockQty`, `CostPrice`, `SalesPrice`, `Date`, `category_id`, `DaysOfWeek`, `Month`, `DailySalesQty`, `WeeklySalesQty`, `MonthlySalesQty`, `YearlySalesQty`) VALUES (89,12,'Koko Trading','Samsung Galaxy','A10s','sammy',48,45,80.00,110.00,'2023-03-17 22:22:34',11,NULL,NULL,NULL,23,33,53),(90,12,'Koko Trading','Nike Sneakers','Nike Air Jordan','mike',26,34,40.00,55.00,'2023-04-17 22:23:44',12,NULL,NULL,NULL,25,35,45),(91,12,'Koko Trading','Palm Oil','vegetable oil','david',22,43,35.00,60.00,'2023-05-17 22:25:06',13,NULL,NULL,NULL,22,32,42),(92,12,'Koko Trading','Gas Cooker','LG Gas Cooker','temmy',54,32,20.00,30.00,'2023-06-17 22:26:48',14,NULL,NULL,NULL,36,46,56),(93,12,'Koko Trading','Black Seed Oil','organic oil','simba',35,45,25.00,40.00,'2023-07-17 22:28:04',15,NULL,NULL,NULL,25,25,25),(95,12,'Koko Trading','XR Chrome Wheels','26inch chrome wheels','james',30,50,90.00,120.00,'2023-08-17 23:08:06',17,NULL,NULL,NULL,35,45,55),(96,12,'Koko Trading','Golf Stick','Umbro Golf Stick','john',24,30,45.00,60.00,'2023-09-17 23:09:27',18,NULL,NULL,NULL,28,32,42),(97,12,'Koko Trading','Persian Rugs','Premium persian carpets','sasha',44,55,76.00,90.00,'2023-10-18 16:19:18',19,NULL,NULL,NULL,24,34,44),(98,12,'Koko Trading','Batmobile','Batman Souvenirs','joe',60,90,15.00,24.00,'2023-11-08 16:21:38',20,NULL,NULL,NULL,39,49,69),(99,12,'Koko Trading','HP Printer','HP inkjet printer','mark',20,40,100.00,120.00,'2023-11-18 16:22:56',21,NULL,NULL,NULL,40,80,130),(101,12,'Koko Trading','Iphone 14XR','Apple Iphone 14','fela',30,45,150.00,230.00,'2023-10-21 21:09:05',23,NULL,NULL,NULL,30,40,50),(102,12,'Koko Trading','Gucci Bag','Gucci Travel Bag','judith',55,25,75.00,90.00,'2023-09-21 21:22:37',24,NULL,NULL,NULL,22,42,62),(103,12,'Koko Trading','Tomatoes','Farm Fresh Tomatoes','femi',65,15,50.00,70.00,'2023-08-21 21:26:42',25,NULL,NULL,NULL,35,55,70),(104,12,'Koko Trading','Pressure Cooker','LG Presssure Cooker','Jones',35,20,400.00,520.00,'2023-07-26 20:34:23',42,NULL,NULL,NULL,28,38,48),(105,12,'Koko Trading','Black Soap','Organic soap','peter',50,50,300.00,450.00,'2023-06-27 23:37:24',43,NULL,NULL,NULL,38,75,90),(106,12,'Koko Trading','Laptop Bag','DELL laptop bag','matthew',30,45,150.00,190.00,'2023-05-28 00:17:28',44,NULL,NULL,NULL,30,40,60),(107,12,'Koko Trading','V6 Turbo Engine','Honda V6 Engine','paul',12,38,200.00,310.00,'2023-04-28 23:33:54',45,NULL,NULL,NULL,26,36,46),(108,12,'Koko Trading','Baseball bat','Nike baseball bat','moses',55,65,25.00,45.00,'2023-03-28 23:37:15',46,NULL,NULL,NULL,40,80,100),(109,12,'Koko Trading','Ultra HDTV','Samsung HDTV','isaac',20,35,200.00,350.00,'2023-02-28 23:44:02',47,NULL,NULL,NULL,25,35,45),(110,12,'Koko Trading','ElitePro Ball','Nike soccerball','mark',60,120,40.00,60.00,'2023-01-28 23:47:23',48,NULL,NULL,NULL,48,58,88),(114,12,'Koko Trading','Tecno Spark 10','tecno smartphone','jide',80,45,160.00,320.00,'2023-11-23 21:07:48',86,NULL,NULL,NULL,28,32,64),(115,12,'Koko Trading','Zara Sneakers','Zara Unisex Sneakers','tom',76,45,120.00,155.00,'2023-12-07 23:20:20',88,NULL,NULL,NULL,0,0,0);
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id_sales` int NOT NULL AUTO_INCREMENT,
  `id_business` int NOT NULL,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Product` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Author` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `InventoryQty` decimal(10,0) NOT NULL,
  `CostPrice` decimal(10,2) NOT NULL,
  `SalesPrice` decimal(10,2) NOT NULL,
  `InventoryValue` decimal(10,2) GENERATED ALWAYS AS ((`InventoryQty` * `CostPrice`)) STORED,
  `TotalRevenue` decimal(10,2) GENERATED ALWAYS AS ((`InventoryQty` * `SalesPrice`)) STORED,
  `GrossProfit` decimal(10,2) GENERATED ALWAYS AS ((`TotalRevenue` - `InventoryValue`)) STORED,
  `GrossProfitMargin` decimal(10,2) GENERATED ALWAYS AS ((((`TotalRevenue` - `InventoryValue`) / `TotalRevenue`) * 100)) STORED,
  `NetProfit` decimal(10,2) GENERATED ALWAYS AS ((`GrossProfit` - `YearlyExpenses`)) STORED,
  `NetProfitMargin` decimal(10,2) GENERATED ALWAYS AS ((((`GrossProfit` - `YearlyExpenses`) / `TotalRevenue`) * 100)) STORED,
  `ReturnOnInvestment` decimal(10,2) GENERATED ALWAYS AS (((`GrossProfit` / `InventoryValue`) * 100)) STORED,
  `InventoryToSalesRatio` decimal(10,2) GENERATED ALWAYS AS (((`NetProfit` / `InventoryValue`) * 100)) STORED,
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DailyExpenses` decimal(10,2) NOT NULL,
  `DailySalesQty` decimal(10,0) NOT NULL,
  `DailyRevenue` decimal(10,2) GENERATED ALWAYS AS ((`DailySalesQty` * `SalesPrice`)) STORED,
  `DailyProfit` decimal(10,2) GENERATED ALWAYS AS ((`DailyRevenue` - `DailyExpenses`)) STORED,
  `WeeklyExpenses` decimal(10,2) DEFAULT NULL,
  `WeeklySalesQty` decimal(10,2) DEFAULT NULL,
  `WeeklyRevenue` decimal(10,2) GENERATED ALWAYS AS ((`WeeklySalesQty` * `SalesPrice`)) STORED,
  `WeeklyProfit` decimal(10,2) GENERATED ALWAYS AS ((`WeeklyRevenue` - `WeeklyExpenses`)) STORED,
  `AverageWeeklySales` decimal(10,2) GENERATED ALWAYS AS ((`WeeklySalesQty` / 7)) STORED,
  `MonthlyExpenses` decimal(10,2) DEFAULT NULL,
  `MonthlySalesQty` decimal(10,2) DEFAULT NULL,
  `MonthlyRevenue` decimal(10,2) GENERATED ALWAYS AS ((`MonthlySalesQty` * `SalesPrice`)) STORED,
  `MonthlyProfit` decimal(10,2) GENERATED ALWAYS AS ((`MonthlyRevenue` - `MonthlyExpenses`)) STORED,
  `AverageMonthlySales` decimal(10,2) GENERATED ALWAYS AS ((`MonthlySalesQty` / 4)) STORED,
  `YearlyExpenses` decimal(10,2) DEFAULT NULL,
  `YearlySalesQty` decimal(10,2) DEFAULT NULL,
  `YearlyRevenue` decimal(10,2) GENERATED ALWAYS AS ((`YearlySalesQty` * `SalesPrice`)) STORED,
  `YearlyProfit` decimal(10,2) GENERATED ALWAYS AS ((`YearlyRevenue` - `YearlyExpenses`)) STORED,
  `AverageYearlySales` decimal(10,2) GENERATED ALWAYS AS ((`YearlySalesQty` / 12)) STORED,
  `sales_categoryid` int NOT NULL,
  `DaysOfWeek` enum('Sun','Mon','Tue','Wed','Thu','Fri','Sat') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Month` enum('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_sales`,`id_business`,`Username`,`Product`),
  KEY `id_user_idx` (`id_business`),
  KEY `sales_categoryid_idx` (`sales_categoryid`),
  CONSTRAINT `id_business` FOREIGN KEY (`id_business`) REFERENCES `business_records` (`id_business`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `sales_categoryid` FOREIGN KEY (`sales_categoryid`) REFERENCES `category` (`id_category`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` (`id_sales`, `id_business`, `Username`, `Product`, `Author`, `InventoryQty`, `CostPrice`, `SalesPrice`, `Date`, `DailyExpenses`, `DailySalesQty`, `WeeklyExpenses`, `WeeklySalesQty`, `MonthlyExpenses`, `MonthlySalesQty`, `YearlyExpenses`, `YearlySalesQty`, `sales_categoryid`, `DaysOfWeek`, `Month`) VALUES (9,12,'Koko Trading','Samsung Galaxy','sammy',73,180.00,210.00,'2023-10-29 22:26:06',270.00,13,450.00,23.00,2000.00,33.00,1500.00,53.00,62,'Sun','Oct'),(10,12,'Koko Trading','Nike Sneakers','mike',60,140.00,185.00,'2023-11-29 22:40:56',280.00,15,600.00,25.00,1100.00,35.00,1050.00,45.00,63,'Sun','Oct'),(11,12,'Koko Trading','Palm Oil','david',65,135.00,160.00,'2023-09-29 22:41:50',320.00,12,1200.00,22.00,1500.00,32.00,1430.00,42.00,64,'Sun','Oct'),(12,12,'Koko Trading','Gas Cooker','temmy',86,120.00,150.00,'2023-08-29 22:43:36',210.00,16,1500.00,36.00,3000.00,46.00,1780.00,56.00,65,'Sun','Oct'),(13,12,'Koko Trading','Black Seed Oil','simba',80,75.00,100.00,'2023-07-29 22:44:48',250.00,25,2000.00,25.00,600.00,25.00,2000.00,25.00,66,'Sun','Oct'),(14,12,'Koko Trading','Iphone 14XR','fela',75,150.00,230.00,'2023-06-10 13:39:37',450.00,15,2900.00,35.00,1700.00,45.00,1600.00,55.00,67,'Fri','Nov'),(15,12,'Koko Trading','XR Chrome Wheels','james',80,90.00,120.00,'2023-05-11 23:40:26',500.00,22,3700.00,28.00,1500.00,32.00,1300.00,42.00,68,'Sat','Nov'),(17,12,'Koko Trading','Golf Stick','john',54,145.00,160.00,'2023-04-11 23:45:02',200.00,14,2500.00,24.00,1900.00,34.00,1650.00,44.00,70,'Sat','Nov'),(18,12,'Koko Trading','Persian Rugs','sasha',99,176.00,190.00,'2023-03-11 23:46:56',280.00,19,4800.00,39.00,1700.00,49.00,1650.00,69.00,71,'Sat','Nov'),(19,12,'Koko Trading','Batmobile','joe',150,115.00,125.00,'2023-02-11 23:49:38',600.00,20,3750.00,40.00,1000.00,80.00,2200.00,130.00,72,'Sat','Nov'),(20,12,'Koko Trading','HP Printer','tim',60,100.00,120.00,'2023-01-11 23:52:15',500.00,10,2950.00,30.00,3500.00,40.00,2300.00,50.00,73,'Sat','Nov'),(21,12,'Koko Trading','Gucci Bag','brandon',80,75.00,90.00,'2023-10-11 23:55:17',800.00,12,4350.00,22.00,1100.00,42.00,2500.00,62.00,74,'Sat','Nov'),(22,12,'Koko Trading','Tomatoes','kim',30,150.00,170.00,'2023-09-11 23:57:44',250.00,15,2670.00,35.00,1500.00,55.00,2200.00,70.00,75,'Sat','Nov'),(23,12,'Koko Trading','Pressure Cooker','david',35,400.00,520.00,'2023-08-11 23:58:45',400.00,14,2100.00,28.00,2400.00,38.00,3100.00,48.00,76,'Sat','Nov'),(24,12,'Koko Trading','Black Soap','mike',100,300.00,350.00,'2023-07-12 00:00:20',600.00,18,3680.00,38.00,2100.00,76.00,2100.00,90.00,77,'Sun','Nov'),(25,12,'Koko Trading','Laptop Bag','james',45,150.00,190.00,'2023-06-12 00:02:11',250.00,15,3720.00,30.00,2200.00,40.00,3200.00,60.00,78,'Sun','Nov'),(26,12,'Koko Trading','V6 Turbo Engine','mark',30,200.00,310.00,'2023-05-12 00:03:14',400.00,12,5920.00,26.00,1500.00,36.00,2050.00,46.00,79,'Sun','Nov'),(27,12,'Koko Trading','Baseball bat','kray',120,125.00,145.00,'2023-04-12 00:04:22',360.00,20,1460.00,40.00,2500.00,80.00,2300.00,100.00,80,'Sun','Nov'),(30,12,'Koko Trading','Ultra HDTV','joe',55,200.00,350.00,'2023-03-12 01:50:51',620.00,15,1850.00,25.00,2800.00,35.00,2700.00,45.00,83,'Sun','Nov'),(31,12,'Koko Trading','ElitePro Ball','jim',180,140.00,180.00,'2023-02-12 01:52:54',300.00,18,2800.00,48.00,1800.00,58.00,2500.00,88.00,84,'Sun','Nov'),(32,12,'Koko Trading','Tecno Spark 10','jide',125,160.00,320.00,'2023-11-23 21:17:19',300.00,12,800.00,28.00,NULL,32.00,NULL,64.00,87,NULL,NULL),(33,12,'Koko Trading','Zara Sneakers','tom',121,120.00,155.00,'2023-12-07 23:37:18',400.00,12,920.00,32.00,NULL,NULL,NULL,NULL,89,NULL,NULL);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-12-23 16:39:51
