<?
require 'inc/lib.inc';

$html_top = '
<HTML>
<HEAD>
<TITLE>Add a zone</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</HEAD>
<BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">
<TABLE width="100%">
<TR>
 <TD align=left><H1>Adding a zone</H1></TD>
 <TH align=right><A HREF="manual.html#add">Help</A></TH>
</TR>
</TABLE>
<HR><P>
';

$html_bottom = '
</BODY>
</HTML>
';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Add Zone</TITLE>
</HEAD>
<FRAMESET rows="12,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder
="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAME src="addzone.php?frame=addzone" name="main" noresize scrolling=auto fr
ameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
</FRAMESET>
</HTML>
';

$start_form = "
<FORM method=\"post\" action=\"addzone.php\">
<INPUT type=hidden name=\"type\" value=\"master\">
<TABLE>
<TR><TD>Domain name</TD>
    <TD><TEXTAREA name=\"newdomain\" rows=8 cols=44></TEXTAREA></TD>
    <TD>Enter one or more names of domains to add to the database, each on
    a separate line. You will be able to edit zone options later.</TD></TR>
<TR><TD colspan=2 align=center><INPUT type=submit value=\"Add Master Domain(s)\"></TD>
</TABLE>
</FORM>
<P><HR><P>

<FORM method=\"post\" action=\"addzone.php\">
<TABLE>
<TR><TD>Domain type:</TD>
    <TD>
    <SELECT name=\"type\">
  	 <option value=\"slave\" selected>slave</option>
  	 <option value=\"forward\">forward</option>
 	 <option value=\"stub\">stub</option>
   	 <option value=\"static\">static stub</option>
    </SELECT>
    </TD>
</TR>
<TR><TD>Domain name</TD>
    <TD><INPUT name=\"newdomain\" size=32></TD>
</TR>
<TR><TD>Master servers</TD>
    <TD><INPUT name=\"newmaster\" size=32></TD>
</TR>
<TR>
    <TD>(forwarders for forward)</TD>
    <TD><INPUT name=\"newmaster2\" size=32></TD>
</TR>
<TR>
    <TD>You can edit options later</TD>
    <TD><INPUT name=\"newmaster3\" size=32></TD>
</TR>
<TR>
    <TD></TD>
    <TD><INPUT name=\"newmaster4\" size=32></TD>
</TR>
<TR><TD colspan=2 align=center><INPUT type=submit value=\"Add Slave|Forward|STUB Domain\"></TD>
</TR>
</TABLE>
</FORM>
";

function validate_domain($domain)
{
	$warnings = "";
	if (!strlen($domain))
		$warnings .= "<LI>You didn't specify a new domain name.\n";
	if (!valid_domain($domain))
		$warnings .= "<LI>'$domain' is not a valid domain name, or the domain already exists in the database.\n";
	return $warnings;
}

function validate_master($master)
{
	$warnings = "";
	if (!$master)
		$warnings .= "<LI>You must specify a master for this domain.\n";
	if (valid_domain($master)) {
		if (!($tmp = gethostbyname($master)))
			$warnings .= "<LI>'$master' is an unknown hostname.\n";
		else
			$master = $tmp;
	} elseif (strlen($master) && !valid_ip($master))
		$warnings .= "<LI>'$master' is neither a valid IP number, nor an existing domain name.\n";
	return $warnings;
}

function add_master_domain($input)
{
	$domains = explode("\n", $input['newdomain']);
	while(list($d, $line) = each($domains)) {
	    $domain = trim(ltrim($line));
	    $warnings = validate_domain($domain);
	    if (strlen($warnings)) {
		    $result .= "The '$domain' domain was not created, for the following reasons:<P><UL>\n$warnings</UL>\n";
	    } else {
		    $id = add_domain($domain, '');
		    $res1   .= fill_in_domain($id, 1);
			$result .= "<HR><P>Domain '<A HREF=\"brzones.php?frame=records&zone=$id\">$domain</A>' successfully added.<P>\n";
			if ($res1) {
				$result .= "<HR>\n<H3>Records found in other domains which should be moved into the new domain</H3>\n";
			    $result .= "<br><FORM action=\"addzone.php\"><INPUT type=\"HIDDEN\" name=\"id\" value=\"$id\"><INPUT type=\"HIDDEN\" name=\"type\" value=\"fill\">\n";
				$result .= "<INPUT type=\"submit\" value=\"Move records into the new zone\"> <INPUT type=\"submit\" value=\"Cancel\" name=\"Cancel\"></FORM>\n";
				$result .= $res1."<HR>\n";
			}
		}
    }
    return $result;
}

function add_slave_domain($input, $type)
{
	$domain = $input['newdomain'];
	$master = $input['newmaster'];
    $master2 = $input['newmaster2'];
    $master3 = $input['newmaster3'];
    $master4 = $input['newmaster4'];
	$warnings = validate_domain($domain);
	$warnings .= validate_master($master);
	if ( "$master2" != "" ) {
		$warnings .= validate_master($master2);
	}
	if ( "$master3" != "" ) {
		$warnings .= validate_master($master3);
	}
	if ( "$master4" != "" ) {
		$warnings .= validate_master($master2);
	}

	# Enough validation, lets do it.
	if (strlen($warnings)) {
		$result .= "The domain was not created, for the following reasons:<P><UL>\n$warnings</UL>\n";
	} else {
		if ( "$master2" != "" )
			$master = $master.";".$master2;
		if ( "$master3" != "" )
			$master = $master.";".$master3;
		if ( "$master4" != "" )
			$master = $master.";".$master4;
		$id = add_domain($domain, $master, $type );
		$result .= "<HR><P>Domain '<A HREF=\"brzones.php?frame=records&zone=$id\">$domain</A>' successfully added.<P>\n";
	}
	$result .= "<hr><p>\n";
	return $result;
}

function fill_master_domain($input)
{
	$id = $input['id'];
	return fill_in_domain($id, 0);
}

#
# MAIN
#

get_input();

switch ($INPUT_VARS['type']) {
case 'master':
	print $html_top.add_master_domain($INPUT_VARS).$html_bottom;
	break;
case 'slave':
	print $html_top.add_slave_domain($INPUT_VARS).$html_bottom;
	break;
case 'stub':
	print $html_top.add_slave_domain($INPUT_VARS,'stub').$html_bottom;
	break;
case 'forward':
	print $html_top.add_slave_domain($INPUT_VARS,'forward').$html_bottom;
	break;
case 'static':
	print $html_top.add_slave_domain($INPUT_VARS,'static').$html_bottom;
	break;
case 'fill':
    if ( !$INPUT_VARS['Cancel']) {
		print $html_top.fill_master_domain($INPUT_VARS).$html_bottom;
		break;
	}
default:
	if ($INPUT_VARS['frame'] == 'addzone') {
		print $html_top.$start_form.$html_bottom;
	} else {
		print $start_frame;
	}
}

?>
