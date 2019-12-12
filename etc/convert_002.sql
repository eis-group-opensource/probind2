#
# Convert from version 2.1
#
# When     Who What
# ======================================================================
# 20191206 Alex version 2.2 now
#
ALTER TABLE records MODIFY COLUMN comment varchar(64);
ALTER TABLE records ADD COLUMN    modified_by varchar(32);
#
# Add-on to allow local specifics as extra zones (will be added to the very end of config)
#
ALTER TABLE servers ADD COLUMN    extra_config text;
ALTER TABLE servers ADD COLUMN    bind_version varchar(8);
ALTER TABLE zones   MODIFY COLUMN master varchar(128);
#
# It do not used for MASTER and SLAVE zones - MASTER is determined by empty 'masters' and 
# SLAVE can be determined if zone type is not forward|stub|static and masters is not empty
#
ALTER TABLE zones   ADD COLUMN    zone_type varchar(8) NOT NULL DEFAULT '';
ALTER TABLE zones   ADD COLUMN    modified_by varchar(32);
# TYPES  (MASTER or SLAVE), STUB, STATIC (static stub), FORWARD (FORWARD). Any other - means SLAVE or MASTER.
ALTER TABLE zones   MODIFY COLUMN options text;
#
DROP TABLE IF EXISTS version;
CREATE TABLE version (
  id ENUM('1') not null default '1',
  version varchar(8) not null default '2.2',
  PRIMARY KEY (`id`)
);
INSERT INTO version () VALUES();

