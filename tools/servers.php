<HTML>
<HEAD>
<TITLE>DNS Servers</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>DNS server descriptions</H1>
 <TD align="right"><A HREF="../manual.html#servers">Help</A></TD>
</TR>
</TABLE>
<HR><P>

<?
require('../inc/lib.inc');

$add_form = '
<FORM action="servers.php" method="post">
<INPUT type="hidden" name="action" value="Add">
<TABLE>
<TR align=left>
 <TH colspan=2>Name</TH>
 <TH>IP number</TH>
</TR><TR align=left>
 <TD colspan=2><INPUT type="text" name="name" size=24 maxlength=64></TD>
 <TD><INPUT type="text" name="ipno" size=15 maxlength=15></TD>
</TR><TR align=left>
 <TH>Type</TH>
 <TH>Updates</TH>
 <TH>NS records</TH>
</TR><TR align=left>
 <TD><SELECT name="type"><OPTION>Master</OPTION>
  <OPTION SELECTED>Slave</OPTION></SELECT></TD>
 <TD><SELECT name="push"><OPTION SELECTED>Skip</OPTION>
  <OPTION>Update</OPTION></SELECT></TD>
 <TD><SELECT name="mkrec"><OPTION SELECTED>Skip</OPTION>
  <OPTION>NS record</OPTION></SELECT></TD>
</TR><TR align=left>
 <TH colspan=3>Directory on the server containing the zone files</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="zonedir" SIZE=70 MAXLENGTH=255 value="'."$DEFAULT_DIR".'"></TD>
</TR><TR align=left>
 <TH colspan=3>File on this server containing the named.conf template</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="template" SIZE=70 MAXLENGTH=255></TD>
</TR><TR align=left>
 <TH colspan=3>Script used to push data to the server</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="script" SIZE=70 MAXLENGTH=255 value="'."$DEFAULT_PUSH".'"></TD>
</TR><TR align=left>
 <TH>Description</TH>
</TR><TR>
 <TD colspan=3><TEXTAREA name="description" COLS=70 ROWS=12></TEXTAREA></TD>
</TR><TR>
 <TD><INPUT type="reset"></TD>
 <TD></TD>
 <TD><INPUT type="submit" value="Add this DNS server"></TD>
</TR></TABLE>
</FORM>
';


$update_form = '
<FORM action="servers.php" method="post">
<INPUT type="hidden" name="action" value="Update">
<INPUT type="hidden" name="server" value="%d">
<TABLE>
<TR align=left>
 <TH colspan=2>Name</TH>
 <TH>IP number</TH>
</TR><TR align=left>
 <TD colspan=2><INPUT type="text" name="name" value="%s" size=24 maxlength=64></TD>
 <TD><INPUT type="text" name="ipno" value="%s" size=15 maxlength=15></TD>
</TR><TR align=left>
 <TH>Type</TH>
 <TH>Update</TH>
 <TH>NS record</TH>
</TR><TR align=left>
 <TD>%s</TD>
 <TD>%s</TD>
 <TD>%s</TD>
</TR><TR align=left>
 <TH colspan=3>Directory on the server containing the zone files</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="zonedir" value="%s" SIZE=70 MAXLENGTH=255></TD>
</TR><TR align=left>
 <TH colspan=3>File on this server containing the named.conf template</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="template" value="%s" SIZE=70 MAXLENGTH=255></TD>
</TR><TR align=left>
 <TH colspan=3>Script used to push data to the server</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="script" value="%s" SIZE=70 MAXLENGTH=255></TD>
</TR><TR align=left>
 <TH>Description</TH>
</TR><TR>
 <TD colspan=3><TEXTAREA name="description" COLS=70 ROWS=12>%s</TEXTAREA></TD>
</TR><TR>
 <TD><INPUT type="reset"></TD>
 <TD><INPUT type="submit" name="subaction" value="Delete"></TD>
 <TD><INPUT type="submit" name="subaction" value="Update"></TD>
</TR></TABLE>
</FORM>
';

$confirm_delete_form = '
<FORM action="servers.php" method="post">
<INPUT type="hidden" name="server" value="%d">
<INPUT type="hidden" name="name" value="%s">
<INPUT type="hidden" name="action" value="delete">
<INPUT type="hidden" name="subaction" value="realdelete">
Do you really want to delete this DNS server from the database?<P>
<B>%s</B><P>
<INPUT type="submit" value="Really Delete";
</FORM>
';

function mk_select($name, $array, $presel)
{
	$result = "<SELECT name=\"$name\">\n";
	for ($i=0; $i<count($array); $i++) {
		$result .= "<OPTION";
		if ($i == $presel)
			$result .= " SELECTED";
		$result .= ">$array[$i]</OPTION>\n";
	}
	$result .= "</SELECT>\n";
	return $result;
}

function valid_path($path)
{
	$bare = rtrim(ltrim($path));
	return strlen($bare) && ereg("^/", $bare);
}

function valid_server($name, $ipno, $type, $push, $zonedir, $template, $script)
{
	global $TOP;
	if (!strlen(ltrim(rtrim($name))))
		$warns .= "You must specify a hostname<BR>\n";
	if (!valid_ip($ipno))
		$warns .= "You must specify a valid IP number<BR>\n";
	if ($type != 'M' && $type != 'S')
		$warns .= "The server type must be 'M' or 'S'<BR\n";
	if (strlen($zonedir) && !valid_path($zonedir))
		$warns .= "The zone directory is not a valid path<BR>\n";
	if (strlen($template) && !preg_match("/^[\w.-_]+$/", $template))
		$warns .= "The template is not a valid filename<BR>\n";
	if (strlen($template) && !file_exists("$TOP/etc/$template"))
		$warns .= "'$TOP/etc/$template' does not exist<BR>\n";
	if (strlen($script) && !preg_match("/^[-\w._]+$/", $script))
		$warns .= "The script is not a valid filename<BR>\n";
	if (strlen($script) && !file_exists("$TOP/sbin/$script"))
		$warns .= "'$TOP/sbin/$script' does not exist<BR>\n";
	if ($push && (!valid_path($zonedir) || !$template || !$script))
		$warns .= "You must specify a zone directory, a template file and a 'push' script for an updateable server<BR>\n";
	return $warns;
}

# Return a string of error messages if the input is unsuitable to
# go into the database, otherwise insert it and return 0.
function add_servers($input)
{
	$name = chop(ltrim($input['name']));
	$ipno = $input['ipno'];
	$type = ($input['type'] == 'Master' ? 'M' : 'S');
	$push = ($input['push'] == 'Skip' ? 0 : 1);
	$mkrec = ($input['mkrec'] == 'Skip' ? 0 : 1);
	$zonedir = $input['zonedir'];
	$template = $input['template'];
	$script = $input['script'];
	$descr = $input['description'];
	$warnings = valid_server($name, $ipno, $type, $push, $zonedir, $template, $script);
	if (strlen($warnings)) 
		return $warnings;
	$query = "SELECT * FROM servers WHERE hostname = '$name'";
	$rid = sql_query($query);
	$count = mysql_num_rows($rid);
	mysql_free_result($rid);
	if ($count)
		return "$name already exists in the database.<BR>\n";
	$query = "INSERT INTO servers (hostname, ipno, type, pushupdates, mknsrec, zonedir, template, script, descr) VALUES ('$name', '$ipno', '$type', $push, $mkrec, '$zonedir', '$template', '$script', '$descr')";
	sql_query($query);
	return 0;
}

# Return a HTML form with the current makeup of the server
function mk_update_form($server)
{
	global $update_form;
	$query = "SELECT id, hostname, ipno, type, pushupdates, mknsrec, zonedir, template, script, descr FROM servers WHERE id = $server";
	$rid = sql_query($query);
	if ($row = mysql_fetch_array($rid)) {
		$id = $row['id'];
		$name = $row['hostname'];
		$ipno = $row['ipno'];
		$type = (int)($row['type'] == 'S');
		$push = $row['pushupdates'];
		$mkrec = $row['mknsrec'];
		$zonedir = $row['zonedir'];
		$template = $row['template'];
		$script = $row['script'];
		$descr = $row['descr'];
		$result .= sprintf($update_form,
			$id, $name, $ipno, 
			mk_select("type", array("Master", "Slave"), $type), 
			mk_select("push", array("Skip", "Update"), $push), 
			mk_select("mkrec", array("Skip", "NS record"), $mkrec),
			$zonedir, $template, $script, $descr);
	} else {
		$result = "No such server in the database: $server.<P>\n";
	}
	mysql_free_result($rid);
	return $result;
}

function browse_servers()
{
	$query = "SELECT id, hostname, ipno, type, pushupdates, mknsrec FROM servers ORDER BY hostname";
	$rid = sql_query($query);
	$result = "<FORM action=\"servers.php\" method=\"post\">
<INPUT type=\"hidden\" name=\"action\" value=\"addform\">
<TABLE><TR align=left>
 <TH>Name</TH>
 <TH>IP number</TH>
 <TH>Type</TH>
 <TH>Update</TH>
 <TH>NS record</TH>
</TR>\n";
	while ($server = mysql_fetch_array($rid)) {
		$id = $server['id'];
		$name = $server['hostname'];
		$ipno = $server['ipno'];
		$type = ($server['type'] == 'M' ? "Master" : "Slave");
		$push = ($server['pushupdates'] ? "Yes" : "No");
		$mkrec = ($server['mknsrec'] ? "Yes" : "No");
		$result .= "<TR>
 <TD><A HREF=\"servers.php?action=detailedview&server=$id\">$name</A></TD>
 <TD>$ipno</TD>
 <TD>$type</TD>
 <TD>$push</TD>
 <TD>$mkrec</TD>
</TR>\n";
	}
	$result .= "<TR><TD><INPUT type=\"submit\" value=\"Add another server\"></TD></TR>\n";
	$result .= "</TABLE>\n</FORM>\n";
	mysql_free_result($rid);
	return $result;
}

function update_servers($input)
{
	global $confirm_delete_form;
	$id = $input['server'];
	$name = $input['name'];
	$ipno = $input['ipno'];
	$type = ($input['type'] == 'Master' ? 'M' : 'S');
	$push = ($input['push'] == 'Skip' ? 0 : 1);
	$mkrec = ($input['mkrec'] == 'Skip' ? 0 : 1);
	$zonedir = $input['zonedir'];
	$template = $input['template'];
	$script = $input['script'];
	$descr = $input['description'];
	switch (strtolower($input['subaction'])) {
	case 'delete':
		return sprintf($confirm_delete_form, $id, $name, $name);
		break;
	case 'realdelete':
		$query = "DELETE FROM servers WHERE id = $id";
		$rid = sql_query($query);
		return "Deleted the '$name' server.<P>\n";
	case 'update':
		if ($warns = valid_server($name, $ipno, $type, $push, $zonedir, $template, $script))
			return $warns;
		$query = "UPDATE servers SET hostname = '$name', ipno = '$ipno', type = '$type', pushupdates = $push, mknsrec = $mkrec, zonedir = '$zonedir', template = '$template', script = '$script', descr = '$descr' WHERE id = $id";
		$rid = sql_query($query);
		$count = mysql_affected_rows();
		break;
	default:
		return "INTERNAL ERROR<P>\n";
	}
	return mk_update_form($input['server']);
}

#
# MAIN
#
get_input();
switch (strtolower($INPUT_VARS['action'])) {
case 'browse':
	print browse_servers();
	break;
case 'detailedview':
	print mk_update_form($INPUT_VARS['server']);
	break;
case 'add':
	if ($warns = add_servers($INPUT_VARS)) 
		print $warns;
	else
		print $add_form;
	break;
case 'addform':
	print $add_form;
	break;
case 'update':
case 'delete':
	print update_servers($INPUT_VARS);
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
