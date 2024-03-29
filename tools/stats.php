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
$rid = sql_query("SELECT version from version WHERE id = 1");
if ( $rid ) {
	$row = mysql_fetch_array($rid);
	$version = $row['version'];
};
if ( ! $version || version_compare($version, "2.2") < 0 ) {
print "<H1><FONT color=RED>LOW database version: $version - upgrade DB to the version 2.2</H1></BODY></HTML>";
exit;
};

adjust_serials();
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
$rid = sql_query("SELECT COUNT(id) FROM zones WHERE (master IS NULL OR master = '') AND domain != 'TEMPLATE'");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Authoritative domains</TD><TD align=right>$count</TD></TR>\n";
$rid = sql_query("SELECT COUNT(id) FROM zones WHERE master");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Slave/FWD/STUB domains</TD><TD align=right>$count</TD></TR>\n";
$rid = sql_query("SELECT COUNT(records.id) FROM records, zones WHERE zones.domain != 'TEMPLATE' AND records.zone = zones.id ");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Resource records</TD><TD align=right>$count</TD></TR>\n";
print "</TABLE></TD><TD>\n";

$rid = sql_query("SELECT id, hostname, ipno, type, pushupdates, mknsrec, state, bind_version FROM servers ORDER BY hostname");
print "<TABLE border cellpadding=4><TR><TH colspan=8>Managed DNS Servers</TH></TR>\n";
print "<TR><TH>Server</TH><TH>Ip number</TH><TH>Type</TH><TH>Update?</TH><TH>NS record?</TH><TH>state</TH><TH>Version</TH><TH>test</TH></TR>\n";
while ($row = mysql_fetch_array($rid)) {
	$id = $row['id'];
	$type = ($row['type'] == 'M' ? 'Master' : 'Slave');
	$push = ($row['pushupdates'] ? 'Yes' : 'No');
	$mkrec = ($row['mknsrec'] ? 'Yes' : 'No');
	$state = $row['state'];
	$id = $row['id'];
	$hostname = $row['hostname'];
	$ipno = $row['ipno'];
	$bind_version = $row['bind_version'];
	$B = "";
	if ($push == 'No')
	    $state = 'OK';
	switch ($state) {
			case 'OK':
				$T = "<B>OK</B>";
				$B = " bgcolor=lightgreen";
			 	break;
			case 'OUT':
				$T = "<B>need update</B>";
				$B = " bgcolor=yellow";
				break;
			case 'CHG':
				$T = "<B>need push</B>";
				$B = " bgcolor=yellow";
				break;
			case 'CFG':
				$T = "<B>need reconfig</B>";
				$B = " bgcolor=yellow";
				break;
			case 'ERR':
				$T = "<FONT COLOR=WHITE><BLINK>Update error</BLINK></FONT>";
				$B = " bgcolor=red";
				break;
			default:  $T = $state;break;
	}
	print "<TR><TD><A HREF=\"servers.php?action=detailedview&server=$id\">$hostname</A></TD><TD>$ipno</TD><TD align=center>$type</TD><TD align=center>$push</TD><TD align=center>$mkrec</TD><TD align=CENTER $B>$T</TD><TD>$bind_version</TD><TD align=center><A href=\"../test.php?id=$id\"><img src=\"../images/greenbutton.gif\" border=0 high=16 width=24></A></TD></TR>\n";
}
print "</TABLE></TD></TR></TABLE><HR><P></BODY></HTML>\n";

?>
