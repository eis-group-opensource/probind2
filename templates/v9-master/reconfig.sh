#!/bin/sh
if [ -f deleted_files ]
then
    test -d DELETED || mkdir -p DELETED
	for i in `cat deleted_files`
	do
	    test -f $i && mv -f $i DELETED/ && echo $i moved into DELETED folder
	done
	rm -f deleted_files
fi
echo /usr/local/sbin/rndc reconfig 
/usr/local/sbin/rndc reconfig
if [ $? = 0 ]
then
 echo Server reconfigured
 exit 0
else
 echo Reconfigure failed
 exit 1
fi
