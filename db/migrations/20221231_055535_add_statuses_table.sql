drop table if exists statuses;

CREATE TABLE statuses (
StID smallint unsigned NOT NULL,
StText varchar(20) NOT NULL,
StAbbreviation varchar(5) NOT NULL,
PRIMARY KEY (StID)
);

insert into statuses (StID, StAbbreviation, StText)
values
(0, '?', 'Unknown'),
(1, '1', 'New (1)'),
(2, '2', 'New (2)'),
(3, '3', 'Learning (3)'),
(4, '4', 'Learning (4)'),
(5, '5', 'Learned'),
(99, 'WKn', 'Well Known'),
(98, 'Ign', 'Ignored');
