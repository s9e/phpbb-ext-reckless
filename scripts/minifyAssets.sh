#!/bin/bash

last_modified()
{
	stat -c %Y "$1" 
}

compress_file()
{
	local ts=$(last_modified "$1");
	local brFile="$1.br";
	local gzFile="$1.gz";

	if [ ! -f "$gzFile" ] || [ "$ts" != $(last_modified "$gzFile") ];
	then
		zopfli -i100 "$1"
		touch -r "$1" "$gzFile"
	fi

	if [ ! -f "$brFile" ] || [ "$ts" != $(last_modified "$brFile") ];
	then
		brotli -f "$1"
	fi
}

compress_file '/tmp/1.html'