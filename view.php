<? require 'inc/lib.inc';

$html_top = '

<HTML><HEAD>
<TITLE>File viewer</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="ccdd88" >
';

$html_bottom = '
</BODY>
</HTML>
';

#
# MAIN
#
get_input();

if ($INPUT_VARS['error'])
    $color='RED';
else
    $color = 'BLACK';

print $html_top;
$file = $INPUT_VARS['file'];
if ($INPUT_VARS['base'] == "LOGS") {
    $base = $LOG_DIR;
	$tbase = "Log file";
}
else {
    $base = $HOST_DIR;
	$tbase = "File";
}
	
if ($file) {
	if (preg_match('/\.\./', $file)) {
    	print "<H3>Incorrect file name $file</H3>\n";
	}

	print "<H3>$tbase: $file</H3>\n";
	print "<HR><PRE>\n";
    print "<FONT COLOR=$color>\n";
	print join("",file("$base/$file"));
	print "</FONT\n";
	print "</PRE>\n";
};
print $html_bottom;
?>

