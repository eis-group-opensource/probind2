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
/usr/local/sbin/named-checkconf named.conf
if [ $? = 0 ]
then
 echo "Checkinmg named.conf - OK, named.conf.good renewed"
 cp named.conf named.conf.good
else
 echo "named-checkconf failed; restoring named.conf from named.conf.good"
 test -f named.conf.good && cp named.conf.good named.conf
 exit 1
fi
#
echo /usr/local/sbin/rndc -c rndc.conf reload
/usr/local/sbin/rndc -c rndc.conf reload
if [ $? = 0 ]
then
 echo Server reconfigured
 sleep 2
 test -f /var/adm/messages && tail /var/adm/messages
 test -f /var/log/messages && tail /var/log/messages
 exit 0
else
 echo Reconfigure failed
 test -f /var/adm/messages && tail /var/adm/messages
 test -f /var/log/messages && tail /var/log/messages
 exit 1
fi
