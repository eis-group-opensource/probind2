<? require 'inc/lib.inc';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Add Zone</TITLE>
</HEAD>
<FRAMESET rows="12,60%,*" frameborder="5" border="2" framespacing="0">
      <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder ="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
      <FRAME src="update.php?frame=MAIN"  name="MAIN"    scrolling=auto frameborder="5" border="3" framespacing="3" marginheight="0" marginwidth="10">
      <FRAME src="update.php?frame=BLANK" name="VIEW"    scrolling=auto frameborder="3" border="3" framespacing="3" marginheight="3" marginwidth="10">
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
 <TD align=left><H3>Pushing DNS updates to the servers</H3></TD>
 <TH align=right><A HREF="manual.html#push">Help</A></TH>
</TR>
</TABLE>
<HR>
';

$html_top1 = '
<HTML><HEAD>
<TITLE>Update LOGS</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="dddd99" background="images/BG-shadowtop.gif">
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



function main_update_menu($input)
{
	global $TWO_STEP_UPDATE;
	global $HOST_URL;
	
	adjust_serials();
	$rid = sql_query("SELECT id FROM zones WHERE updated");
	$zones = mysql_num_rows($rid);
	mysql_free_result($rid);
	
	$rid = sql_query("SELECT id,hostname,ipno,type,state FROM servers WHERE pushupdates = 1");
	print "<FORM TARGET=\"VIEW\" action=\"update.php\"><INPUT type=\"HIDDEN\" name=\"frame\" value=\"VIEW\">\n";
	$res .= "<TABLE BORDER=2 width=\"70%\">\n";
	$res .= "<TR><TH>name</TH><TH>ip address</TH><TH>type</TH><TH>state</TH><TH>Do not apply</TH><TH>View</TH><TH>Test</TH></TR>\n";
	$gen_c  = "";
	$push_c = "";
	$conf_c = "";
	$appl_c = "CHECKED";
	while ( list($id,$hostname, $ipno, $type, $state) = mysql_fetch_row($rid)) {
		
		$T = $state;
		$B = "";
		$skip_c = "";
		switch ($state) {
			case 'OK':  
				$T = "<B>OK</B>";
			 	break;
			case 'OUT': 
				$T = "<B>need update</B>"; 
				$B = " bgcolor=yellow";
				$gen_c="CHECKED";
				break;
			case 'CHG': 
				$T = "<B>need push</B>";   
				$B = " bgcolor=yellow"; 
				$push_c = "CHECKED";
				break;
			case 'CFG': 
				$T = "<B>need reconfig</B>"; 
				$B = " bgcolor=yellow";
				$cfg_c = "CHECKED";
				break;
			case 'ERR': 
				$T = "<FONT COLOR=WHITE><BLINK>Update error</BLINK></FONT>";
				$B = " bgcolor=red";
				$skip_c = "CHECKED";
				break;
			default:  break;
		}
		 
		
		$res .= "<TR>\n";
		$res .= "\t<TD>$hostname</TD>\n";
		$res .= "\t<TD>$ipno</TD>\n";
		$res .= "\t<TD align=center>$type</TD>\n";
		$res .= "\t<TD $B>$T</TD>\n";
		$res .= "\t<TD align=center><INPUT TYPE=\"CHECKBOX\" $skip_c name=\"skip_$id\"></TD>\n";
		$res .= "\t<TD align=center>";
		    $res .= "<A TARGET=\"VIEW\" href=\"view.php?file=$hostname/named.conf\">named.conf</A>,";
			$res .= "<A TARGET=\"VIEW\" href=\"$HOST_URL/$hostname\">files</A>";
			$res .= "</TD>\n";
		$res .= "\t<TD align=center><A TARGET=\"VIEW\" href=\"test.php?id=$id\"><img src=\"images/greenbutton.gif\" border=0 high=16 width=24></A></TD>\n";
		$res .= "</TR>\n";
	}
	$res .= "</TABLE>\n";
	
	if ($gen_c)
	    $push_c = "CHECKED";
	
	if ($TWO_STEP_UPDATE && ($gen_c || $push_c))
	    $conf_c = "";
		
	print "<TABLE width=\"70%\"  border=\"1\">\n";
	print "\t<TD>Generate files ($zones): <INPUT type=CHECKBOX name=\"gen\" $gen_c></TD>\n";
	print "\t<TD>Push files: <INPUT type=CHECKBOX name=\"push\" $push_c></TD>\n";
	print "\t<TD>Reconfig server: <INPUT type=CHECKBOX name=\"conf\" $conf_c></TD>\n";
	print "</TR>\n";
	print "<TR><TD colspan=3 align=center>";
	print "<INPUT type=submit value=\"START UPDATE\" class=\"button\" onmouseover=\"this.className='buttonwarning'\" onmouseout=\"this.className='button'\">\n";
	print "</TD></TR>\n";
	print "</TABLE>\n";
	print $res;
	print "</FORM>\n";		
	
	exit;
}

function generate_files($input)
{
	global $UPDATE_LOG;
	global $A_LOG;
	global $A_LOGE;
	global $HOST_DIR;
	global $TOP;
	global $BIN;
	$query = "SELECT id, hostname, type, pushupdates, zonedir FROM servers WHERE state = 'OUT' OR state = 'ERR'";
	$rid = sql_query($query);

	$query = "SELECT id, domain, master, zonefile FROM zones WHERE updated AND domain != 'TEMPLATE' ORDER BY domain";
	$rid1 = sql_query($query);

	$query = "SELECT domain, zonefile FROM deleted_domains";
	$rid2 = sql_query($query);

	while (list($servid, $server, $type, $update, $zonedir) = mysql_fetch_row($rid)) {
		print "<H4>Updating $server ";
		if ($type == 'M')
			print "(as master)</H4>\n";
		else
			print "(as slave)</H4>\n";
		
		# This server need real file update
		chdir("$HOST_DIR/$server") || die("$!: $HOST_DIR/$server<P>\n");
		$cmd = "TOP=$TOP $BIN/mknamed.conf $server named.conf ";
		passthru("$cmd >> $UPDATE_LOG 2>& 1", $ret);
		if ($ret != 0) {
		    print "<A  TARGET=\"VIEW\" href=\"view.php?file=$server/named.conf\"><FONT color=RED>mknamed.conf</FONT></A> failure, see $A_LOGE<BR>\n";
		    $error = 1;
		}
		else
		    print "<A  TARGET=\"VIEW\" href=\"view.php?file=$server/named.conf\"><FONT color=GREEN>named.conf</FONT></A> updated, see $A_LOG<BR>\n";
				
			
		if ($type == 'M') {
			print "<UL>\n";
			
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
				    	    print "<LI><FONT color=RED>Error in mkzonefile, see $A_LOG</FONT>\n";
			    	    	}
				}
				print "<LI><A TARGET=\"VIEW\" href=\"view.php?file=$server/$zonefile\">$domain</A> updated\n";
			    }
			    if (count($domains)) {
				$cmd =  "TOP=$TOP $BIN/mkzonefile -d $HOST_DIR/$server -u ".join(" ", $domains);
				passthru("$cmd 2>&1 >>$UPDATE_LOG", $ret);
				if ($ret) {
				    $error = 1;
				    print "<H3><FONT color=RED>Error in mkzonefile, see $A_LOG</FONT></H3>\n";
			    	}
			    }
			}
			print "</UL>\n";
			
			
			//----------------------------------------------------//

			
		}
		
		if ($error)
			$text = "<FONT color=RED>ERROR:</FONT>";
		else
			$text = "<FONT color=RED>UPDATED:</FONT>";
		
		print "$text<A TARGET=\"VIEW\" href=\"$HOST_URL/$server/\">$server</A><HR>\n";
		if ($error) {
			sql_query("UPDATE servers SET state='ERR' WHERE id = $servid");
			break;
		}
		else {
			sql_query("UPDATE servers SET state='CHG' WHERE id = $servid");
		}
	};


	mysql_free_result($rid);	
	mysql_free_result($rid1);
	mysql_free_result($rid2);

	if (!$error) {
		patient_enter_crit('INTERNAL1', 'DOMAIN');
		updates_completed();
		$query = "DELETE FROM deleted_domains";
		$rid = sql_query($query);
		leave_crit('DOMAIN');
	};

    if  ($error)
	    $err_text="&error=1";
	else
	    $err_text="";
		
	print "<H3>Log file: <A TARGET=\"VIEW\" href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME$err_text\">$UPDATE_LOG</A></H3>\n";
}

function run_scripts($input, $push, $conf)
{
	print "run scripts push = $push conf=$conf\n";
	return "";
}

#
# MAIN
#

get_input();

#
# 1. Update serial numbers if necessary. Decision about generating zone will be done
# by comparing server serial id and zone zerial id
# gen=1 - generate zones; sync = 1 - syncronyze3 zones, conf = 1 - configure zones,
# id_%d = 1 - server id for the operation (id_ALL=1 means ALL servers)
#
# If no operation is specified, serials are updated and overall view is presented
#
$gen   = $INPUT_VARS['gen'];
$push  = $INPUT_VARS['push'];
$conf  = $INPUT_VARS['conf'];
$frame = $INPUT_VARS['frame'];


#
# Set up frame structure
#
if (!$frame) {
	print $start_frame;
	exit();
}

if ($frame == 'MAIN' ) {
	print $html_top;
	print main_update_menu($INPUT_VARS);
	exit;
}

print $html_top1;
if ($frame == 'BLANK') {
    print $html_bottom;
	exit;
}

if ( !$LOG_DIR || !opendir($LOG_DIR) ) {
	die("<H3><FONT color=\"red\">Can not open log directory: $LOG_DIR</FONT></H3>\n");
};
closedir();
$UPDATE_LOG_NAME= date("YmdHis").".log";
$UPDATE_LOG = "$LOG_DIR/$UPDATE_LOG_NAME";
$A_LOG="<A TARGET=\"VIEW\" href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME\">LOG</A>";
$A_LOGE="<A TARGET=\"VIEW\" href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME&error=1\">LOG</A>";

# !!!
if ($user = patient_enter_crit($REMOTE_USER, 'PUSH')) {
	print sprintf($push_in_progress, ucfirst($user));
	exit();
}

if ($gen) {
	$err = generate_files($INPUT_VARS);
	if ($err) {
		print "<H3><FONT color=RED>Update interrupted due to the error</FONT></H3>\n";
		print "$err <br>\n";
	}
}

if ( !$err && $push) {
	$err = run_scripts($INPUT_VARS, $push, $conf);
	if ($err) {
		print "<H3><FONT color=RED>Update interrupted due to the error</FONT></H3>\n";
		print "$err <br>\n";
	}
}


if ($err) {
	print "<H3><FONT color=RED>Errors, see logs ";
	print "<A TARGET=\"VIEW\" href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME\">here</A></FONT></H3>\n";
}
else {
	print "<H3><FONT color=GREEN>Completed, see logs ";
	print "<A href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME\">here</A></FONT></H3>\n";

};
leave_crit('PUSH');
print "<SCRIPT>open('update.php?frame=MAIN','MAIN');</SCRIPT><HR>\n";
print $html_bottom;
?>


