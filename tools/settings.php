<HTML>
<HEAD>
<TITLE>ProBIND Settings</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>ProBIND Settings</H1></TD>
 <TD align="right"><A HREF="../manual.html#settings">Help</A></TD>
</TR>
</TABLE>
<HR><P>

<?
require('../inc/lib.inc');

$preamble = '
NB: When you change these settings, the change is only reflected in
domains which are pushed onto the DNS servers after the update. If
you want to make the change apply to all domains in the database, use
the bulk update function, then push updates.<BR><HR><BR>
';

$settings_list['default_external_dns'] = '<B>DEFAULT EXTERNAL DNS:</B><BR>
The default DNS server to use for the external consistency checks. E.g.
ns1.uplink-isp.net.';
$settings_list['default_origin'] = '<B>Default MNAME:</B><BR>The origin 
of domains managed in this database, as published in the SOA records.
This would usually be the hostname of the master DNS server, e.g.
ns1.mydomain.net.';
$settings_list['default_ptr_domain'] = '<B>DEFAULT PTR DOMAIN:</B><BR>PTR 
records are automatically put in the zone files for each A record in 
the database. This setting controls which domain they belong to, e.g.
mydomain.net. Enter a value of NONE to disable this feature.';
$settings_list['hostmaster'] = '<B>Default RNAME:</B><BR>The mailbox to publish 
in SOA records. If you have a "hostmaster" alias which forwards to the
DNS staff, put "hostmaster" in here. Remember to use a "." in stead
of the "@", e.g. hostmaster.mydomain.net.';

function browse_settings()
{
	global $settings_list, $preamble;
	$query = "SELECT name, value FROM blackboard ORDER BY name";
	$rid = sql_query($query);
	$result = $preamble;
	$result .= "<FORM action=\"settings.php\" method=\"post\">
<INPUT type=\"hidden\" name=\"action\" value=\"update\">
<TABLE>\n";
	while ($setting = mysql_fetch_array($rid)) {
		$name = $setting['name'];
		$value = $setting['value'];
		$text = $settings_list[$name];
		if ($text) {
			$result .= "<TR>
 <TD>$text</TD>
 <TD valign=\"top\"><INPUT type=\"text\" name=\"$name\" value=\"$value\" SIZE=40 MAXLENGTH=255></TD>
</TR>\n";
			$seen[$name] = 1;
		}
	}
	reset($settings_list);
	$value = '';
	while ($setting = each($settings_list)) {
		$name = $setting[0];
		$text = $settings_list[$name];
		if (!$seen[$name])
			$result .= "<TR>
 <TD>$text</TD>
 <TD valign=\"top\"><INPUT type=\"text\" name=\"$name\" value=\"$value\" SIZE=40 MAXLENGTH=255></TD>
</TR>\n";
	}
	$result .= "<TR><TD></TD><TD><INPUT type=\"submit\" value=\"Update settings\"></TD></TR></TABLE>\n</FORM>\n";
	mysql_free_result($rid);
	if (strlen($locks = list_locks()))
		$result .= $locks;
	return $result;
}

function update_settings($input)
{
	global $settings_list;
	reset($settings_list);
	$warnings = "";
	while ($setting = each($settings_list)) {
		$name = $setting[0];
		$value = strtr($input[$name], "@", ".");
		if ($value) {
			$sql[] = "DELETE FROM blackboard WHERE name = '$name'";
			$sql[] = "INSERT INTO blackboard (name, value) VALUES ('$name', '$value')";
		} else 
			$warnings .= "<LI>You must specify a value for <B>$name</B>\n";
	}
	if (strlen($warnings)) 
		return "<UL>$warnings</UL>\n";
	while ($q = each($sql)) {
		sql_query($q[1]);
	}
	return browse_settings();
}

function break_lock($input)
{
	$lock = $input['lockname'];
	$query = "DELETE FROM blackboard WHERE name = 'PUSHLOCK'";
	leave_crit($lock);
	return browse_settings();
}

#
# MAIN
#
get_input();
switch (strtolower($INPUT_VARS['action'])) {
case 'update':
	print update_settings($INPUT_VARS);
	break;
case '':
case 'browse':
	print browse_settings();
	break;
case 'breaklock':
	print break_lock($INPUT_VARS);
	break;
default:
	while ($var = each($INPUT_VARS)) {
		print "$var[0] = $var[1]<BR>\n";
	}
	print "action = '".$INPUT_VARS['action']."' => default<P>\n";
}

?>

</BODY>
</HTML>
