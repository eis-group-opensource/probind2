#!/usr/bin/perl
#
# 1. Copy all files into the ../HOSTS/host dir. Create it if necessary
# 2. Copy files onto the place
# 3. 
# restart the server.

sub usage()
{
	print "$0 dest-host remote-dir\n";
	exit(1);
}

#
# MAIN
#
print STDERR "PUSH\n";
$< = $>;
$( = $);
usage() if (scalar(@ARGV) < 2);
$ARGV[0] =~ /([\w.\-_]+)/;
$HOST = $1;
$ARGV[1] =~ /([\w.\-_\/+]+)/;
$BIND_DIR = $1;
%ENV = (
	'PATH', '/bin:/usr/bin',
	'HOME', '/root',
);
$| = 1;

print STDERR "dir = $BIND_DIR\n";
print STDERR "host = $HOST\n";
opendir(SRC, ".") || die("$!: opendir('.')\n");
@files = grep { -f && -r } readdir(SRC);
closedir(SRC);

foreach $file (@files) {
	$file =~ /(.*)/;
	push @safefiles, $1;
	$deadlist++ if ($file eq "deadlist");
}

print STDERR "files = ".join(', ', @safefiles)."\n";
if (scalar(@safefiles) && !fork()) {
	# Child process
	$cmd = "/usr/bin/mv ".join(" ", @safefiles)." $BIND_DIR";
print STDERR "$cmd\n";
  	exec($cmd);
	exit(0);
}
wait();

$cmd = "/usr/local/sbin/rndc reconfig || exit 1";
$cmd .= "; cd $BIND_DIR; mkdir DELETED 2>/dev/null; cat deadlist | xargs -i mv {} DELETED" if ($deadlist);
print STDERR "$cmd\n";
exec($cmd);
