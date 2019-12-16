-- Adminer 4.7.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `openskos`;
CREATE DATABASE `openskos` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `openskos`;

DROP TABLE IF EXISTS `job`;
CREATE TABLE `job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `set_uri` varchar(100) DEFAULT NULL,
  `task` varchar(100) DEFAULT NULL,
  `parameters` text,
  `created` datetime DEFAULT NULL,
  `started` datetime DEFAULT NULL,
  `finished` datetime DEFAULT NULL,
  `status` enum('SUCCESS','ERROR') DEFAULT NULL,
  `info` text,
  PRIMARY KEY (`id`),
  KEY `task` (`task`),
  KEY `finished` (`finished`),
  KEY `fk_job_user` (`user`),
  CONSTRAINT `fk_job_user` FOREIGN KEY (`user`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `max_numeric_notation`;
CREATE TABLE `max_numeric_notation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(45) DEFAULT NULL,
  `max_numeric_notation` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `namespace`;
CREATE TABLE `namespace` (
  `prefix` varchar(25) NOT NULL COMMENT '			',
  `uri` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `notations`;
CREATE TABLE `notations` (
  `notation` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`notation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `search_profiles`;
CREATE TABLE `search_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `searchOptions` blob,
  `creatorUserId` int(11) DEFAULT NULL,
  `tenant` char(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_search_profile_user` (`creatorUserId`),
  KEY `fk_search_profile_tenant` (`tenant`),
  CONSTRAINT `fk_search_profile_tenant` FOREIGN KEY (`tenant`) REFERENCES `tenant` (`code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_search_profile_user` FOREIGN KEY (`creatorUserId`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `password` char(32) NOT NULL,
  `tenant` char(10) NOT NULL,
  `apikey` varchar(100) DEFAULT NULL,
  `active` char(1) NOT NULL DEFAULT 'Y',
  `type` enum('editor','api','both') NOT NULL DEFAULT 'both',
  `role` varchar(25) NOT NULL DEFAULT 'guest',
  `searchOptions` blob,
  `conceptsSelection` blob,
  `defaultSearchProfileIds` varchar(255) DEFAULT NULL,
  `disableSearchProfileChanging` tinyint(1) DEFAULT NULL,
  `uri` text,
  `enableSkosXl` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`email`,`tenant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2019-11-18 15:10:04