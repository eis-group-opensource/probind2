### ProBIND 2.2 created DB from scratch.
# Create the data structures needed for the dnsdb system
#
# When     Who What
# ======================================================================
# 20000622 FSJ First version
# 20021215 alex Version 2.0
# 20030110 alex Version 2.1
# 20191212 alex Version 2.2
# UNCOMMENT IT TO RECREATE SCHEMA
#-----------------------------------------
##DROP TABLE IF EXISTS zones, version, records, annotations, servers, deleted_domains, typesort, blackboard;
#-----------------------------------------
#
#
# For each domain served by our BIND servers, exactly one record
# must exist in the zones table. The update flag is supposed to be
# set by anyone updating a record in zones, or editing the set of
# associated records in the 'records' table.
#
CREATE TABLE zones (
# Unique zone ID
	id	INT(11)  NOT NULL AUTO_INCREMENT,
# Origin of this zone
# NB: the PTR RRs for zones under in-addr.arpa. are auto-generated
	domain	CHAR(100) NOT NULL,
# Serial number for the SOA record
	serial	INT(12),
# Refresh rate for the SOA record
	refresh INT(12),
# Retry interval for the SOA record
	retry INT(12),
# Expire period for the SOA record
	expire INT(12),
# If set, an IP number for an auth. master for this zone i.e, if 
# this column is non-null, then we are secondary for the zone
# Two IP delimited by ';' are allowed
	master	VARCHAR(128) NOT NULL DEFAULT '',
# If set, the basename of the file containing zone records
# Either master or zonefile must be set, but not both
	zonefile	CHAR(80) NOT NULL DEFAULT '',
# Zone options. $ACL will be replaced by the access list.
	options     TEXT,
# Zone record last modification time
	mtime	TIMESTAMP NOT NULL,
# Zone record creation time
	ctime	TIMESTAMP,
# Zone has been updated => increment serial on next dump-to-file
	updated	INT(1) DEFAULT '0',
# disabled bit
	disabled INT(1) DEFAULT '0',
# zone_type - if empty, MASTER or SLAVE, else 'forward', 'stub', 'static'
	zone_type VARCHAR(8) NOT NULL DEFAULT '',
# who modify zone
	modified_by VARCHAR(32),
	PRIMARY KEY (id)
);

#
# This is where we store the actual Resource Records for the domains.
# For each domain served authoritatively, exactly one SOA record must
# exist here. 
#
# NB: The NS records for our own DNS servers are automatically
# added when the zone files are generated, based on the contents of the
# 'servers' table. Thus NS records pointing to a known DNS server 
# are redundant.
#
CREATE TABLE records (
# Unique Resource Record ID
	id	INT(11) NOT NULL AUTO_INCREMENT,
# foreign key to the zones table
	zone	INT(11) NOT NULL,
# Origin of this record
	domain	CHAR(100) DEFAULT '' NOT NULL,
# This application only deals with the IN class, so we dont
# bother representing the RR class in the database
# Time To Live, must be non-null for SOA records
	ttl	CHAR(15),
# RR type, e.g. A, MX, SOA, CNAME or NS
# NB: PTR records are _not_ stored explicitly, the reverse-lookup
# zone files are generated automatically.
	type	CHAR(10) DEFAULT '' NOT NULL,
# Preference value for this MX record
	pref	CHAR(5),
# RR Data
	data	CHAR(255) DEFAULT '' NOT NULL,
# Comment
	comment  VARCHAR(64),
# Last modification time for this RR
	mtime	TIMESTAMP NOT NULL,
# Creation time for this RR
	ctime	TIMESTAMP,
# Should PTR be generated for this record
	genptr  INT(1),
# disabled?
	disabled INT(1) DEFAULT '0',
# Who modify record
        modified_by varchar(32),
	PRIMARY KEY (id)
);

#
# This table contains long annotations for zones or records. It is
# basically eyecandy for the web interface, and an aid for forgetful
# DNS admins, should any such exist (I don't remember meeting any').
#
CREATE TABLE annotations (
# Unique ID for a chunk of text
	zone	INT(11) NOT NULL,
# The actual text
	descr	TEXT NOT NULL,
	PRIMARY KEY (zone)
);

#
# This table must contain one record for each DNS/BIND server managed
# by this database.
#
CREATE TABLE servers (
# Unique Resource Record ID
	id		INT(11)  NOT NULL AUTO_INCREMENT,
# The hostname
	hostname	VARCHAR(200) NOT NULL,
# The IP number derived from the hostname 
	ipno		VARCHAR(15) NOT NULL,
# Either 'M' or 'S'
	type		CHAR(1) NOT NULL,
# If non-zero and non-null, do push updates from the database to the server
# This field was added to enable us to handle server aliases
	pushupdates	INT(1) NOT NULL,
# If non-zero, include this server when generating NS records for a domain
	mknsrec		INT(1) NOT NULL,
# Path to directory on the DNS server containing the zone files
	zonedir		VARCHAR(255) NOT NULL,
# Template directory; must contain named.conf anc can contain other files as well...
	template	VARCHAR(255) NOT NULL,
# Path to script that will push updates to this server
	script		VARCHAR(255) NOT NULL,
# Server options (additional)
	options         TEXT,
# Descriptive text
	descr		TEXT,
# Current status; can be 'OK', 
#                        'OUT' (out of date), 
#                        'CHG' (changed but not pushed yet)
#                        'CFG' (pushed but not reconfigured yet)
#                        'ERR' (error during last update)
#
	state           CHAR(5) DEFAULT 'OK',
# Extra config - applied before all zones.
	extra_config TEXT,
	bind_version varchar(8) DEFAULT NULL,
	PRIMARY KEY (id)
);

# This table tracks deleted domains until they have been cleaned up
# on the BIND servers.
CREATE TABLE deleted_domains(
# The domain name of the defunct domain
	domain		VARCHAR(100) NOT NULL,
# The zonefile associated with the defunct domain
	zonefile	VARCHAR(80) NOT NULL
);

# This table controls the record sorting order in the domain browser
CREATE TABLE typesort (
	type	CHAR(10) NOT NULL,
	ord	INT(2) NOT NULL
);
INSERT INTO typesort (type, ord) values ('SOA', 1);
INSERT INTO typesort (type, ord) values ('NS', 2);
INSERT INTO typesort (type, ord) values ('TXT', 3);
INSERT INTO typesort (type, ord) values ('HINFO', 4);
INSERT INTO typesort (type, ord) values ('MX', 5);
INSERT INTO typesort (type, ord) values ('A', 6);
INSERT INTO typesort (type, ord) values ('CNAME', 7);
INSERT INTO typesort (type, ord) values ('PTR', 8);
INSERT INTO typesort (type, ord) values ('SRV', 9);


# This table stores various management info. First (and so far only)
# use is to help limit the push functionality to one single user at a time.
CREATE TABLE blackboard (
	name  VARCHAR(32)   NOT NULL DEFAULT '',
	value VARCHAR(255)  NOT NULL DEFAULT '',
	ctime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
#
# Version table
#
CREATE TABLE version (
  id ENUM('1') not null default '1',
  version varchar(8) not null default '2.2',
  PRIMARY KEY (`id`)
);
INSERT INTO version () VALUES();

# Initialize the zones table with a TEMPLATE record
# By definition, this zone gets ID = 1
# The default refresh, retry, expire and minimum TTL are taken from
# the RIPE recommendations found at http://www.ripe.net/ripe/docs/ripe-203.html
INSERT INTO zones (domain, serial, refresh, retry, expire, options)
	VALUES ('TEMPLATE', 1, 86400, 7200, 3628800, 'allow-transfer{ $ACL };' );
SET SQL_SAFE_UPDATES = 0;
UPDATE zones SET ctime = mtime WHERE mtime is not NULL;
INSERT INTO records (zone, domain, ttl, type)
	VALUES (1, '@', 172800, 'SOA');
UPDATE records SET ctime = mtime WHERE mtime is not NULL;
INSERT INTO annotations (zone, descr)
	VALUES (1, "This is the template from which new master domains 
are initialized. It is not a 'REAL' domain, it is 
not pushed to the BIND servers, and you cannot 
delete it.");


