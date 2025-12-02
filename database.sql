/*
SQLyog Ultimate v10.00 Beta1
MySQL - 5.5.5-10.4.27-MariaDB : Database - cw1841
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`cw1841` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `cw1841`;

/*Table structure for table `admins` */

DROP TABLE IF EXISTS `admins`;

CREATE TABLE `admins` (
  `AdminID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `AccessLevel` int(11) DEFAULT 1,
  PRIMARY KEY (`AdminID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `admins` */

insert  into `admins`(`AdminID`,`UserID`,`AccessLevel`) values (1,1,1);

/*Table structure for table `comments` */

DROP TABLE IF EXISTS `comments`;

CREATE TABLE `comments` (
  `CommentID` int(11) NOT NULL AUTO_INCREMENT,
  `PostID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Content` text NOT NULL,
  `CommentDate` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`CommentID`),
  KEY `idx_comments_post` (`PostID`),
  KEY `idx_comments_user` (`UserID`),
  KEY `idx_comments_date` (`CommentDate`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `comments` */

insert  into `comments`(`CommentID`,`PostID`,`UserID`,`Content`,`CommentDate`) values (1,2,1,'ok','2025-11-12 08:30:00'),(2,2,1,'hello','2025-11-12 08:30:00');

/*Table structure for table `modules` */

DROP TABLE IF EXISTS `modules`;

CREATE TABLE `modules` (
  `ModuleID` int(11) NOT NULL AUTO_INCREMENT,
  `ModuleName` varchar(100) NOT NULL,
  PRIMARY KEY (`ModuleID`),
  UNIQUE KEY `ModuleName` (`ModuleName`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `modules` */

insert  into `modules`(`ModuleID`,`ModuleName`) values (1,'COMP1649 Database Fundamentals'),(2,'COMP1841 Web Programming 1'),(3,'MATH1179 Mathematics for Computer Science');

/*Table structure for table `posts` */

DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `PostID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `ModuleID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Content` text DEFAULT NULL,
  `ImagePath` varchar(255) DEFAULT NULL,
  `PostDate` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`PostID`),
  KEY `idx_posts_user` (`UserID`),
  KEY `idx_posts_module` (`ModuleID`),
  KEY `idx_posts_date` (`PostDate`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `posts` */

insert  into `posts`(`PostID`,`UserID`,`ModuleID`,`Title`,`Content`,`ImagePath`,`PostDate`) values (1,2,2,'CONTACT USING PROPOSERS','Help',NULL,'2025-11-12 00:00:00'),(2,7,2,'PHP PDO Connection Issue','I am encountering an error when trying to connect the database using PHP PDO. Can anyone help?','uploads/Screenshot_2025-11-04_221840_1764422209.png','2025-10-23 15:52:00');

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `Position` varchar(50) DEFAULT NULL,
  `Avatar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`UserID`,`Username`,`Password`,`Name`,`DateOfBirth`,`PhoneNumber`,`Email`,`Position`,`Avatar`) values (1,'admin','admin123','Admin','2005-04-23','','admin@gmail.com','Admin',NULL),(2,'user1','123456','user1',NULL,NULL,'lbx2qwe@gmail.com','N/A',NULL),(3,'user2','123456','hao',NULL,NULL,'haovo2342005@gmail.com','N/A',NULL),(4,'haovo','123456','vo tran xuan hao',NULL,NULL,'lbxqwe@gmail.com','student',NULL),(5,'thinhe','123456','le phu thinh',NULL,NULL,'thinhe09022005@gmail.com','N/A',NULL),(6,'student1','456','haovo',NULL,NULL,'lbxqee@gmail.com','student',NULL),(7,'studentA','123456','John Smith',NULL,NULL,'studentA@greenwich.ac.uk','N/A',NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
