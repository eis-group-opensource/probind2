<?

require 'inc/brzones.inc';

#
# MAIN
#
get_input();

switch ($INPUT_VARS['frame']) {
case 'zones':
	if (! $SHOW_ALL ) {
		print domain_search_form($INPUT_VARS);
		exit();
	}
	$INPUT_VARS['formname'] = 'zonesearch';
	break;
case 'records':
	if ($warns = database_state())
		print "The database is not in an operational state. The following problems exist:<P><UL>$warns</UL><P>\n";

	print right_frame($INPUT_VARS);
	exit();
}

switch($INPUT_VARS['formname']) {

case 'zonesearch':
	print domain_search_form($INPUT_VARS);
	$str = $INPUT_VARS['lookfor'];
	print domain_list("%$str%", $INPUT_VARS['domtype'], "<A HREF=\"brzones.php?frame=records&zone=%s&mode=view\" target=\"right\">%s%s</A><BR>\n");
	print $html_close;
	break;
default:
	print $start_form;
	break;
}
?>
