<? require 'inc/lib.inc';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Add Zone</TITLE>
</HEAD>
<FRAMESET rows="12,*" frameborder="0" border="2" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder ="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAMESET cols="70,30" frameborder="0" border="0" framespacing="0" noresize>
    <FRAME src="update.php?frame=update" name="main"  scrolling=auto frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
    <FRAME src="view.php?" name="VIEW" scrolling=auto frameborder="2" border="2" framespacing="2" marginheight="0" marginwidth="10">
  </FRAMESET>
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
<HR>
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
	$UPDATE_LOG_NAME= date("YmdHis").".log";
	$UPDATE_LOG = "$LOG_DIR/$UPDATE_LOG_NAME";
}

$query = "SELECT id, domain, master, zonefile FROM zones WHERE updated AND domain != 'TEMPLATE' ORDER BY domain";
$rid1 = sql_query($query);

$query = "SELECT domain, zonefile FROM deleted_domains";
$rid2 = sql_query($query);

if ($count = mysql_num_rows($rid1)) {
	print "Found $count domains which have been updated since\n";
	print "the last time this database was synchronized with\n";
	print "the DNS servers.<br>\n";
} 
if ($count = mysql_num_rows($rid2)) {
	print "Found $count domains which have been deleted from\n";
	print "the database since the last synchronization<br>.\n";
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
print "</UL><HR><P>\n";

$query = "SELECT hostname, type, pushupdates, script, zonedir FROM servers";
$rid = sql_query($query);
while ($row = mysql_fetch_array($rid)) {
	$server = $row['hostname'];
	$type = $row['type'];
	$update = $row['pushupdates'];
	$script = $row['script'];
	$zonedir = $row['zonedir'];
	if ($update) {
		# This is a master server
		chdir("$HOST_DIR/$server") || die("$!: $HOST_DIR/$server<P>\n");
		$cmd = "TOP=$TOP $BIN/mknamed.conf $server named.conf ";
		passthru("$cmd >> $UPDATE_LOG 2>& 1", $ret);
		if ($ret != 0) {
		    print "<LI><A  TARGET=\"VIEW\" href=\"view.php?file=$server/named.conf\"><FONT color=RED>mknamed.conf failure</FONT></A>\n";
		    $error = 1;
		}
		else
		    print "<LI><A  TARGET=\"VIEW\" href=\"view.php?file=$server/named.conf\"><FONT color=GREEN>named.conf updated</FONT></A>\n";
				
			
		if ($type == 'M') {
			if (mysql_num_rows($rid2)) {
			    mysql_data_seek($rid2, 0) || die("Something wrong, data seek 2");
			    while ($trash = mysql_fetch_array($rid2)) {
			    	$zonefile = $trash['zonefile'];
				$domain = $trash['domain'];
				
				$cmd = "mkdir -p DELETED;mv '$zonefile' DELETED/. && echo '$zonefile' >> deleted_files";
				exec($cmd);
				print "<LI><A TARGET=\"VIEW\" href=\"view.php?file=$server/DELETED/$zonefile\">$domain deleted</A>\n";
			    }
			}
			
			if (mysql_num_rows($rid1)) {
			//----------------------------------------------------//
			    mysql_data_seek($rid1, 0) || die("Something wrong, data seek 1");
			    while ($zone = mysql_fetch_array($rid1)) {
				$domain = $zone['domain'];
				if ($zone['master'])
					adjust_serial($zone['id']); # We don't make zone files for slaves
				else 
					$domains[] = $zone['domain'];
				$zonefile = $zone['zonefile'];
				if (count($domains) >= 64) {
					$cmd =  "TOP=$TOP $BIN/mkzonefile -d $HOST_DIR/$server -u ".join(" ", $domains);
					passthru("$cmd 2>&1 >>$UPDATE_LOG", $ret);					
					$domains = array();
					if ($ret) {
				    	    $error = 1;
				    	    print "<H3><FONT color=RED>Error in mkzonefile, see LOGS</FONT></H3>\n";
			    	    	}
				}
				print "<LI><A TARGET=\"VIEW\" href=\"view.php?file=$server/$zonefile\">$domain</A> updated\n";
			    }
			    if (count($domains)) {
				$cmd =  "TOP=$TOP $BIN/mkzonefile -d $HOST_DIR/$server -u ".join(" ", $domains);
				passthru("$cmd 2>&1 >>$UPDATE_LOG", $ret);
				if ($ret) {
				    $error = 1;
				    print "<H3><FONT color=RED>Error in mkzonefile, see LOGS</FONT></H3>\n";
			    	}
			    }
			}
			
			
			//----------------------------------------------------//

			
		}
		
		if (! $error) {
		    $cmd = "TOP=$TOP $SBIN/$script $server $zonedir >> $UPDATE_LOG 2>&1";
		    exec($cmd, $out, $ret);
		    if ($ret != 0 && $ret != 22) {
			print "<HR><FONT color=RED>SCRIPT FAILURE,see logs below. Do not forget to make 'Bulk update' when will fix the problem.</FONT><HR>\n";
			$error = 1;
		    }
		}
		else {
		    print "<br>PUSH for server $name skipped due to <FONT color=RED>error</FONT><br>\n";
    	    	}
		if ($error) 
		    print "<br><FONT color=RED>ERROR</FONT> in update $server as a ".($type=='M' ? "master" : "slave")."<HR>\n";
		else
		    print "<br>Updated <A TARGET=\"VIEW\" href=\"$HOST_URL/$server/\">$server</A> as a ".($type=='M' ? "master" : "slave")."<HR>\n";
	} else {
		print "Skipped $server.<P>\n";
	}

}

mysql_free_result($rid);
mysql_free_result($rid1);
mysql_free_result($rid2);

if (!$error) {
	patient_enter_crit('INTERNAL1', 'DOMAIN');
	$query = "DELETE FROM deleted_domains";
	$rid = sql_query($query);
	leave_crit('DOMAIN');
};
# !!!
leave_crit('PUSH');
if ($LOG_DIR) {
    if  ($error)
	    $err_text="&error=1";
	else
	    $err_text="";
		
	print "<H3>Log file: <A TARGET=\"VIEW\" href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME$err_text\">$UPDATE_LOG</A></H3>\n";
	print "<SCRIPT>open('view.php?base=LOGS&file=$UPDATE_LOG_NAME$err_text',\"VIEW\");</SCRIPT>\n";
};

print "<P>Done.<P><HR>\n".$html_bottom;

?>


