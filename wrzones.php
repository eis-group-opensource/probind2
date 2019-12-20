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
case 'masterzone':
case 'slavezone':
case 'Update':
	perform_zone_update($INPUT_VARS);
	$INPUT_VARS['zone'] = $INPUT_VARS['id'];
	print right_frame($INPUT_VARS);
	break;
case 'addrrbutton':
case 'Add RR':
	$info = get_zone($INPUT_VARS['id']);
	print sprintf($add_form, $info['domain'], $INPUT_VARS['id'], type_menu("type", ''));
	break;
case 'addrrform':
	print add_record($INPUT_VARS);
	$info = get_zone($INPUT_VARS['zone']);
	print sprintf($add_form, $info['domain'], $INPUT_VARS['zone'], type_menu("type", ''));
	if (!$INPUT_VARS['mode'])
		$INPUT_VARS['mode'] = 'view';
	print right_frame($INPUT_VARS);
	break;
case 'rrform':
	if ($res = perform_rr_action($INPUT_VARS)) {
		print "$html_top.$res\n";
		exit();
	}

	print right_frame($INPUT_VARS);
	break;
case 'Delete Zone?':
	$info = get_zone($INPUT_VARS['id']);
	header("Location: delzone.php?frame=delzone&domain=".$info['domain']);
	break;
case 'Details':
case 'Options':
	$info = get_zone($INPUT_VARS['id']);
	header("Location: zonedetails.php?domain=".$info['domain']);
	break;

}
?>
