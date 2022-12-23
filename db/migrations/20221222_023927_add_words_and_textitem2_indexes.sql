-- Indexes used when refreshing textstatscache.

create index WoTextLC on words (WoTextLC);

create index WoStatusChanged on words (WoStatusChanged);

create index Ti2LgID on textitems2 (Ti2LgID);
