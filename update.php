<? require 'inc/lib.inc';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Add Zone</TITLE>
</HEAD>
<FRAMESET rows="12,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder ="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAME src="update.php?frame=update" name="main" noresize scrolling=auto frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
</FRAMESET>
</HTML>
';

$html_top = '
<HTML><HEAD>
<TITLE>Proventum Push DNS Updates</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="cccc99" background="images/BG-shadowleft.gif">
<TABLE width="100%">
<TR>
 <TD align=left><H1>Pushing DNS updates to the servers</H1><P>
 <TH align=right><A HREF="manual.html#push">Help</A></TH>
</TR>
</TABLE>
<HR><P>
';

$html_bottom = '
</BODY>
</HTML>
';

$stern_warning = "
<P><HR><P>
<B>WARNING:</B> You are about to copy modified data 
from this database to the actual DNS servers. The DNS servers
are what makes this company and our customers accesible to users 
everywhere on the Internet.
<P>
If you have screwed up the contents of the database, the end result
may be the partial or complete disruption of Internet services for
us and/or our customers. Real money may be lost, and real people
may get real upset.
<P>
By clicking on this button, you accept full legal, financial and
moral responsibility for the consequences of your action:
<P>
<CENTER>
<A HREF=\"update.php?frame=update&iamserious=true\">
<IMG SRC=\"images/wasp-warning.gif\" alt=\"Go ahead - do it!\">
</A>
</CENTER>
<P><HR><P>
</BODY></HTML>
"; 

$push_in_progress = "
<UL>
<B><BLINK>
%s is already running a push operation. The database is
locked until that process completes. Please be patient, as
a big update can take many minutes to complete.
</BLINK></B>
</UL>
<P>
If this condition persists, or you are otherwise convinced that
an error has occurred, then you can clear the lock condition
on the settings menu.
";


#
# MAIN
#

get_input();
if ($INPUT_VARS['frame'] == 'update')
	print $html_top;
else {
	print $start_frame;
	exit();
}
if ( $LOG_DIR ) {
	if ( !opendir($LOG_DIR) ) {
		die("<H3><FONT color=\"red\">Can not open log directory: $LOG_DIR</FONT></H3>\n");
	};
	closedir();
	$UPDATE_LOG = $LOG_DIR."/".date("YmdHis").".log";
}

$query = "SELECT id, domain, master FROM zones WHERE updated AND domain != 'TEMPLATE' ORDER BY domain";
$rid1 = sql_query($query);
$query = "SELECT domain, zonefile FROM deleted_domains";
$rid2 = sql_query($query);
if ($count = mysql_num_rows($rid1)) {
	print "Found $count domains which have been updated since\n";
	print "the last time this database was synchronized with\n";
	print "the DNS servers.<P>\n";
} 
if ($count = mysql_num_rows($rid2)) {
	print "Found $count domains which have been deleted from\n";
	print "the database since the last synchronization<P>.\n";
}
if (!mysql_num_rows($rid1) && !mysql_num_rows($rid2)) {
	print "Nothing to do.<P>\n".$html_bottom;
	exit();
} else {
	if ($INPUT_VARS['iamserious'] != 'true') {
		print $stern_warning;
		exit();
	}
}

# !!!
if ($user = patient_enter_crit($REMOTE_USER, 'PUSH')) {
	print sprintf($push_in_progress, ucfirst($user));
	exit();
}

print "<UL>\n";
exec("/bin/rm -rf $TMP/master $TMP/slave");
mkdir("$TMP/master", 0755) or die("<FONT COLOR=RED>SOMETHING WRONG! CHECK PERMISSIONS!<BR></FONT>"); 
mkdir("$TMP/slave", 0755)  or die("<FONT COLOR=RED>SOMETHING WRONG! CHECK PERMISSIONS!<BR></FONT>");
chdir("$TMP/master") or die("chdir($TMP/master) failed.<P>\n");
while ($zone = mysql_fetch_row($rid1)) {
	if ($zone[2])
		adjust_serial($zone[0]); # We don't make zone files for slaves
	else 
		$domains[] = $zone[1];
	if (count($domains) >= 64) {
		$cmd =  "$BIN/mkzonefile -u ".join(" ", $domains);
		exec("$cmd 2>&1 >>$UPDATE_LOG");
		$domains = array();
	}
	print "<LI>$zone[1] updated\n";
}
if (count($domains)) {
	$cmd =  "$BIN/mkzonefile -u ".join(" ", $domains);
	exec("$cmd 2>&1 >>$UPDATE_LOG");
}

while ($trash = mysql_fetch_row($rid2)) {
	print "<LI>$trash[0] deleted\n";
}
print "</UL><HR><P>\n";
mysql_free_result($rid1);
mysql_free_result($rid2);
$cmd = "$BIN/mkdeadlist -u 2>&1 >> $UPDATE_LOG";
exec("$cmd");

$query = "SELECT hostname, type, pushupdates, script, zonedir FROM servers";
$rid = sql_query($query);
while ($row = mysql_fetch_array($rid)) {
	$server = $row['hostname'];
	$type = $row['type'];
	$update = $row['pushupdates'];
	$script = $row['script'];
	$zonedir = $row['zonedir'];
	if ($update) {
		if ($type == 'M') {
			# This is a master server
			chdir("$TMP/master") || die("$!: $TMP/master<P>\n");
			$cmd = "$BIN/mknamed.conf $server >named.conf";
			exec($cmd);
			$cmd = "$SBIN/$script $server $zonedir>>$UPDATE_LOG 2>&1";
			exec($cmd, $out, $ret);
			if ($ret != 0) {
				print "<HR><FONT color=RED>SCRIPT FAILURE,see logs below. Do not forget to make 'Bulk update' when will fix the problem.</FONT><HR>\n";
				$error = 1;
			}
		} else {
			# This must be a slave server then
			chdir("$TMP/slave") || die("$!: $TMP/slave<P>\n");
			$cmd = "$BIN/mknamed.conf $server >named.conf";
			exec($cmd);
			$cmd = "$SBIN/$script $server $zonedir>>$UPDATE_LOG 2>&1";
			print "$cmd<br>\n";
			exec($cmd, $out, $ret);
			if ($ret != 0) {
				print "<HR><FONT color=RED>SCRIPT FAILURE,see logs below. Do not forget to make 'Bulk update' when problem wil be fixed.</FONT><HR>\n";
				$error = 1;
			}
		}
		print "Updated $server as a ".($type=='M' ? "master" : "slave")."<P>\n";
	} else {
		print "Skipped $server.<P>\n";
	}
}
mysql_free_result($rid);

# !!!
leave_crit('PUSH');
if ($LOG_DIR) {
	print "<H3>LOG file: $UPDATE_LOG</H3>\n<HR>\n<PRE>\n";
	if ($error) 
	    print "<FONT color=red>\n";
	passthru("/bin/cat $UPDATE_LOG");
	if ($error) 
	    print "</FONT>\n";
	print "<HR>\n</PRE>\n";
};

print "<P>Done.<P><HR>\n".$html_bottom;

?>


