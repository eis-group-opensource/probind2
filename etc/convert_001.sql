#
# Convert from version 2.0
#
# When     Who What
# ======================================================================
# 20030110 Alex First Version

ALTER TABLE zones
	ADD COLUMN disabled INT(1) DEFAULT '0' AFTER updated;

ALTER TABLE records
	ADD COLUMN disabled INT(1) DEFAULT '0' AFTER genptr;
