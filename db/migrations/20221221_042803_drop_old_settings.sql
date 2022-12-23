-- Drop all of the old settings, not using them, except for this one:
delete from settings where stkey != 'currenttext';
