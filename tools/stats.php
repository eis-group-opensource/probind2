<HTML>
<HEAD>
<TITLE>Statistics</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#9999cc" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>Statistics</H1></TD>
 <TD align="right"><A HREF="../manual.html#stats">Help</A></TD>
</TR>
</TABLE>
<HR><P>

<? 

require '../inc/lib.inc';

if ($warns = database_state())
	print "The database is not in an operational state. The following problems exist:<P><UL>$warns</UL><P>\n";

$rid = sql_query("SELECT domain, id, zonefile FROM zones WHERE updated AND domain != 'TEMPLATE' ORDER BY zonefile");
$count = mysql_num_rows($rid);
if ($count) {
	print "<P>The database contains changes to $count domains.<P><UL>";
	while ($row = mysql_fetch_row($rid)) {
		print "<LI><A HREF=\"../brzones.php?frame=records&zone=$row[1]\">$row[0]</A>\n";
	}
	print "</UL>\n";
	$update++;
}
mysql_free_result($rid);
$rid = sql_query("SELECT domain FROM deleted_domains");
$count = mysql_num_rows($rid);
if ($count) {
	print "<P>The following domains have been deleted from the database.<P><UL>\n";
	while ($row = mysql_fetch_row($rid)) {
		print "<LI>$row[0]\n";
	}
	print "</UL><HR>\n";
	$update++;
}
mysql_free_result($rid);
if ($update)
	print "These changes have not been pushed out to the actual DNS servers. Click the 'Update' button above to execute the changes to these zones:<P><HR><P>\n";

# Bragging ...
print "<TABLE width=\"100%\"><TR><TD>\n";
print "<TABLE border cellpadding=4><TR><TH>Statistic</TH><TH>Count</TH></TR>\n";
$rid = sql_query("SELECT COUNT(id) FROM zones WHERE (master IS NULL OR NOT master) AND domain != 'TEMPLATE'");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Authoritative domains</TD><TD align=right>$count</TD></TR>\n";
$rid = sql_query("SELECT COUNT(id) FROM zones WHERE master");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Slave domains</TD><TD align=right>$count</TD></TR>\n";
$rid = sql_query("SELECT COUNT(records.id) FROM records, zones WHERE zones.domain != 'TEMPLATE' AND records.zone = zones.id ");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Resource records</TD><TD align=right>$count</TD></TR>\n";
print "</TABLE></TD><TD>\n";

$rid = sql_query("SELECT id, hostname, ipno, type, pushupdates, mknsrec FROM servers ORDER BY hostname");
print "<TABLE border cellpadding=4><TR><TH colspan=5>Managed DNS Servers</TH></TR>\n";
print "<TR><TH>Server</TH><TH>Ip number</TH><TH>Type</TH><TH>Update?</TH><TH>NS record?</TH></TR>\n";
while ($row = mysql_fetch_row($rid)) {
	$type = ($row[3] == 'M' ? 'Master' : 'Slave');
	$push = ($row[4] ? 'Yes' : 'No');
	$mkrec = ($row[5] ? 'Yes' : 'No');
	print "<TR><TD><A HREF=\"servers.php?action=detailedview&server=$row[0]\">$row[1]</A></TD><TD>$row[2]</TD><TD align=center>$type</TD><TD align=center>$push</TD><TD align=center>$mkrec</TD></TR>\n";
}

print "</TABLE></TD></TR></TABLE><HR><P></BODY></HTML>\n";

?>
