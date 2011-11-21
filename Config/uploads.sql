CREATE TABLE `uploads` (
   `id` int(11) not null auto_increment,
   `created` datetime,
   `filename` varchar(255),
   `path` varchar(255),
   `filesize` int(11),
   `mime_type` varchar(100),
   `pos` int(11),
   `model` varchar(100),
   `foreign_key` int(11),
   `alias` varchar(255),
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=66;
