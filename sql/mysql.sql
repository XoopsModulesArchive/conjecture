CREATE TABLE `conjec_comtopic` (
  `comtopic_id` int unsigned auto_increment,
  `type` varchar(30) NOT NULL DEFAULT '',
  `module_id` int unsigned NOT NULL DEFAULT 0,
  `item_id` int unsigned NOT NULL DEFAULT 0,
  `topic_id` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comtopic_id`),
  KEY `lookup` (`type`, `module_id`, `topic_id`)
) TYPE=MyISAM;

CREATE TABLE `conjec_modcatforum` (
  `module_id` int unsigned NOT NULL DEFAULT 0,
  `category_id` int unsigned NOT NULL DEFAULT 0,
  `forum_id` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`module_id`, `category_id`)
) TYPE=MyISAM;