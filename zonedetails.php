<HTML>
<HEAD>
<TITLE>Domain details</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">

<?

require('inc/lib.inc');

# Convert the special mysql timestamp format to something more readable
function fmt_timestamp($timestamp)
{
	return substr($timestamp, 6, 2)
		."/".substr($timestamp, 4, 2)
		."/".substr($timestamp, 0, 4)
		." ".substr($timestamp, 8, 2)
		.":".substr($timestamp, 10, 2)
		." CET";
}

function update_description($domain, $descrip, $options)
{
	$query = "SELECT id FROM zones WHERE domain = '$domain'";
	$rid = sql_query($query);
	($zone = mysql_fetch_array($rid))
		or die("No such domain: $domain<P>\n");
	mysql_free_result($rid);
	$id = $zone['id'];
	$query = "DELETE FROM annotations WHERE zone = $id";
	$rid = sql_query($query);
	$query = "INSERT INTO annotations (zone, descr) VALUES ($id, '$descrip')";
	$rid = sql_query($query);
	$options = strtr($options, "'",'"');
	$rid = sql_query("UPDATE zones SET options='$options', updated=1 WHERE id=$id");
}

function domain_details($domain)
{
	$query = "SELECT id, mtime, ctime, options FROM zones WHERE domain = '$domain'";
	$rid = sql_query($query);
	($zone = mysql_fetch_array($rid))
		or die("No such domain: $domain<P>\n");
	mysql_free_result($rid);
	$mtime = fmt_timestamp($zone['mtime']);
	$ctime = fmt_timestamp($zone['ctime']);
	$id = $zone['id'];
	$options = htmlspecialchars($zone['options']);
	$result = "<H1>$domain</H1>\n";
	$result .= "<FORM action=\"zonedetails.php\" method=\"post\">\n";
	$result .= "<TABLE width=\"100%\" border><TR align=left><TH>Zone created in database</TH><TH>Last update in database</TH></TR>
<TR><TD>$ctime</TD><TD>$mtime</TD></TR>
<TR><TD colspan=2>Zone options (<b>no syntax check here!</b>): <INPUT type=text  name=\"options\" value=\"$options\" size=80 maxlenght=255></TD></TR>
</TABLE>
<P>When you create or modify a domain, please add a note to the domain
description. The note should contain the date, your initials and a few
words about what was done (and perhaps why). Please add new entries
at the top.
<P>
";
	$query = "SELECT descr from annotations WHERE zone = $id";
	$rid = sql_query($query);
	$result .= "
<INPUT type=\"hidden\" name=\"action\" value=\"textupdate\">
<INPUT type=\"hidden\" name=\"domain\" value=\"$domain\">
<TABLE width=\"99%\">
<TR><TD colspan=\"2\">
<TEXTAREA name=\"description\" rows=20 cols=55>\n";
	if ($annotation = mysql_fetch_array($rid)) {
		$result .= $annotation['descr'];
	}	
	$result .= "</TEXTAREA>
</TR><TR>
<TD><INPUT type=reset></TD>
<TD><INPUT type=submit name=submit value=Update></TD>
</TR></TABLE>
</FORM>
";
	mysql_free_result($rid);
	return $result;
}

#
# MAIN
#
get_input();

if ($domain = $INPUT_VARS['domain']) {
	if ($INPUT_VARS['action'] == "textupdate") {
		update_description($INPUT_VARS['domain'],
			htmlspecialchars($INPUT_VARS['description']), $INPUT_VARS['options']);
	}
	print domain_details($domain);
} else {
	print "No domain specified.<P>$QUERY_STRING<P>\n";
}

?>

</BODY>
</HTML>
