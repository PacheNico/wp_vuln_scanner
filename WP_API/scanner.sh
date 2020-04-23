#!/bin/bash
cd /tmp/testfolder/
for file in *.zip ; do
echo ${file}
unzip "${file}" "*.php"
done
