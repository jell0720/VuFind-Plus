#!/bin/sh
# set local configuration for starting Solr and then start solr
#Replace {servername} with your server name and save in sites/{servername} as {servername.sh} 
export VUFIND_HOME=/usr/local/vufind-plus/sites/opac.marmot.org
export JETTY_HOME=/usr/local/vufind-plus/sites/default/solr/jetty
export SOLR_HOME=/data/vufind-plus/opac.marmot.org/solr
export JETTY_PORT=8080
#Max memory should be at least the size of all solr indexes combined.
#export JAVA_OPTIONS="-server -Xms2g -Xmx26g -XX:+UseParallelGC -XX:NewRatio=5"
export JAVA_OPTIONS="-server -Xms2g -Xmx40g -XX:+UseG1GC"
export JETTY_LOG=/var/log/vufind-plus/opac.marmot.org/jetty

exec /usr/local/vufind-plus/sites/default/vufind.sh $1 $2
