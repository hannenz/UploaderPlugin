--
-- MySQL 5.1.49
-- Fri, 27 Jan 2012 13:02:14 +0000
--

CREATE TABLE `uploads` (
   `id` int(11) not null auto_increment,
   `created` datetime,
   `modified` datetime,
   `filename` varchar(255),
   `name` varchar(255),
   `size` int(11),
   `type` varchar(100),
   `pos` int(11),
   `model` varchar(100),
   `foreign_key` int(11),
   `alias` varchar(255),
   `session_id` varchar(255),
   `title` varchar(200),
   `description` mediumtext,
   `poster` varchar(255),
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1711;
