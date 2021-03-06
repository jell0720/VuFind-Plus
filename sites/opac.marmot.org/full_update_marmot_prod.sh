#!/bin/bash
# Script handles all aspects of a full index including extracting data from other systems.
# Should be called once per day.  Will interrupt partial reindexing.
#
# At the end of the index will email users with the results.
EMAIL=root@mercury
PIKASERVER=opac.marmot.org
OUTPUT_FILE="/var/log/vufind-plus/${PIKASERVER}/full_update_output.log"

# Check for conflicting processes currently running
function checkConflictingProcesses() {
	#Check to see if the conflict exists.
	countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
	countConflictingProcesses=$((countConflictingProcesses-1))

	let numInitialConflicts=countConflictingProcesses
	#Wait until the conflict is gone.
	until ((${countConflictingProcesses} == 0)); do
		countConflictingProcesses=$(ps aux | grep -v sudo | grep -c "$1")
		countConflictingProcesses=$((countConflictingProcesses-1))
		#echo "Count of conflicting process" $1 $countConflictingProcesses
		sleep 300
	done
	#Return the number of conflicts we found initially.
	echo ${numInitialConflicts};
}

#truncate the output file so you don't spend a week debugging an error from a week ago!
: > $OUTPUT_FILE;

#Check for any conflicting processes that we shouldn't do a full index during.
checkConflictingProcesses "sierra_export.jar ${PIKASERVER}"
checkConflictingProcesses "overdrive_extract.jar ${PIKASERVER}"
checkConflictingProcesses "reindexer.jar ${PIKASERVER}"

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Extract from ILS
/root/cron/copySierraExport.sh >> ${OUTPUT_FILE}

#Extract from Hoopla
#cd /usr/local/vufind-plus/vufind/cron;./HOOPLA.sh ${PIKASERVER} >> ${OUTPUT_FILE}
cd /usr/local/vufind-plus/vufind/cron;./GetHooplaFromMarmot.sh >> ${OUTPUT_FILE}
# Grab cleaned, updated marc files from venus after they have been retrieved from Nashville

# Ebrary Marc Updates
#TODO: refactor CCU's ebrary destination
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh ccu_ebrary ebrary_ccu >> ${OUTPUT_FILE}
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh adams/ebrary ebrary/adams >> ${OUTPUT_FILE}

# CCU Ebsco Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh ebsco_ccu ebsco/ccu >> ${OUTPUT_FILE}

# CMC Ebsco Academic Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh cmc/ebsco ebsco/cmc >> ${OUTPUT_FILE}

# Western Oxford Reference Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/oxfordReference oxfordReference/western >> ${OUTPUT_FILE}

# Western Springer Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/springer springer/western >> ${OUTPUT_FILE}

# Western Kanopy Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh western/kanopy kanopy/western >> ${OUTPUT_FILE}

# Learning Express Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh budwerner/learning_express learning_express/steamboatsprings >> ${OUTPUT_FILE}

# OneClick digital Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh englewood/oneclickdigital oneclickdigital/englewood >> ${OUTPUT_FILE}

# Colorado State Gov Docs Marc Updates
/usr/local/vufind-plus/sites/opac.marmot.org/moveFullExport.sh cologovdocs colorado_gov_docs >> ${OUTPUT_FILE}

#Lynda.com Marc Updates
# (EVLD, Vail)
#Extracts for sideloaded eContent; settings defined in config.pwd.ini [Sideload]
cd /usr/local/vufind-plus/vufind/cron; ./sideload.sh ${PIKASERVER}


#Extract Lexile Data
#cd /data/vufind-plus/; wget -N --no-verbose http://venus.marmot.org/lexileTitles.txt
cd /data/vufind-plus/; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/lexileTitles.txt http://venus.marmot.org/lexileTitles.txt

#Extract AR Data
#cd /data/vufind-plus/accelerated_reader; wget -N --no-verbose http://venus.marmot.org/RLI-ARDataTAB.txt
cd /data/vufind-plus/accelerated_reader; curl --remote-name --remote-time --silent --show-error --compressed --time-cond /data/vufind-plus/accelerated_reader/RLI-ARDataTAB.txt http://venus.marmot.org/RLI-ARDataTAB.txt

#Do a full extract from OverDrive just once a week to catch anything that doesn't
#get caught in the regular extract
DAYOFWEEK=$(date +"%u")
if [ "${DAYOFWEEK}" -eq 6 ];
then
	cd /usr/local/vufind-plus/vufind/overdrive_api_extract/
	nice -n -10 java -server -XX:+UseG1GC -jar overdrive_extract.jar ${PIKASERVER} fullReload >> ${OUTPUT_FILE}
fi

#Validate the export
cd /usr/local/vufind-plus/vufind/cron; java -server -XX:+UseG1GC -jar cron.jar ${PIKASERVER} ValidateMarcExport >> ${OUTPUT_FILE}

#Full Regroup
cd /usr/local/vufind-plus/vufind/record_grouping; java -server -XX:+UseG1GC -Xmx6G -jar record_grouping.jar ${PIKASERVER} fullRegroupingNoClear >> ${OUTPUT_FILE}

#TODO: Determine if we should do a partial update from the ILS and OverDrive before running the reindex to grab last minute changes

#Full Reindex
cd /usr/local/vufind-plus/vufind/reindexer; nice -n -3 java -server -XX:+UseG1GC -jar reindexer.jar ${PIKASERVER} fullReindex >> ${OUTPUT_FILE}

# Clean-up Solr Logs
find /usr/local/vufind-plus/sites/default/solr/jetty/logs -name "solr_log_*" -mtime +7 -delete
find /usr/local/vufind-plus/sites/default/solr/jetty/logs -name "solr_gc_log_*" -mtime +7 -delete

#Restart Solr
cd /usr/local/vufind-plus/sites/${PIKASERVER}; ./${PIKASERVER}.sh restart

#Email results
FILESIZE=$(stat -c%s ${OUTPUT_FILE})
if [[ ${FILESIZE} > 0 ]]
then
	# send mail
	mail -s "Full Extract and Reindexing - ${PIKASERVER}" $EMAIL < ${OUTPUT_FILE}
fi

