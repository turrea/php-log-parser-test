#!/bin/bash
tar -xvf astlog.tgz
pwd=`pwd`
echo "*/5 * * * * php $pwd/run.php $pwd/full.1" > crontab.txt