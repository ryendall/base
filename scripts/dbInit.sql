 CREATE TABLE `api_users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
);

insert into api_users (username,password) values ('testuser','testpass');

CREATE TABLE `commodity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` char(32),
  `name` varchar(255) DEFAULT NULL,
  `commodity_class_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `commodity_commodityclassid` (`class_id`)
);

CREATE TABLE `commodity_class` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` char(32),
  `name` varchar(255) DEFAULT NULL,
  `family_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `commodityclass_familyid` (`family_id`)
);

CREATE TABLE `family` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` char(32),
  `name` varchar(255) DEFAULT NULL,
  `segment_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `family_segmentid` (`segment_id`)
);

CREATE TABLE `segment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` char(32),
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`)
);

