package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.*;

/**
 * ILS Indexing with customizations specific to Marmot.  Handles processing
 * - print items
 * - econtent items stored within Sierra
 * - order items
 *
 * Pika
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class MarmotRecordProcessor extends IIIRecordProcessor {
	public MarmotRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && !isEContent){
					getPrintIlsItem(groupedWork, recordInfo, record, itemField);
				}
			}
		}
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-dowju";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		return available;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		boolean suppressed = false;
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield != null){
			String icode2 = icode2Subfield.getData().toLowerCase().trim();
			Subfield locationCodeSubfield = curItem.getSubfield(locationSubfieldIndicator);
			if (locationCodeSubfield != null) {
				String locationCode = locationCodeSubfield.getData().trim();

				suppressed = icode2.equals("n") || icode2.equals("x") || locationCode.equals("zzzz") || icode2.equals("q") || icode2.equals("z") || icode2.equals("y") || icode2.equals("a");
			}
		}
		if (suppressed){
			return suppressed;
		}else{
			return super.isItemSuppressed(curItem);
		}
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				boolean isHoopla = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}else if (sourceType.equals("hoopla")){
								isHoopla = true;
							}
						}else{
							String source = itemField.getSubfield(eContentSubfieldIndicator).getData().trim();
							if (source.equalsIgnoreCase("overdrive")){
								isOverDrive = true;
							}else if (source.equalsIgnoreCase("hoopla")){
								isHoopla = true;
							}
						}
					}
				}
				if (!isOverDrive && !isHoopla && isEContent){
					RecordInfo eContentRecord = getEContentIlsRecord(groupedWork, record, identifier, itemField);
					if (eContentRecord != null) {
						unsuppressedEcontentRecords.add(eContentRecord);
					}
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	@Override
	protected void loadEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {
		String protectionType = econtentItem.geteContentProtectionType();
		switch (protectionType) {
			case "external":
				String iType = econtentItem.getITypeCode();
				if (iType != null) {
					String translatedFormat = translateValue("econtent_itype_format", iType, econtentRecord.getRecordIdentifier());
					String translatedFormatCategory = translateValue("econtent_itype_format_category", iType, econtentRecord.getRecordIdentifier());
					String translatedFormatBoost = translateValue("econtent_itype_format_boost", iType, econtentRecord.getRecordIdentifier());
					econtentItem.setFormat(translatedFormat);
					econtentItem.setFormatCategory(translatedFormatCategory);
					econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
				} else {
					logger.warn("Did not get a iType for external eContent " + econtentRecord.getFullIdentifier());
				}
				break;
			default:
				logger.warn("Unknown protection type " + protectionType);
				break;
		}
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return false;
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return itemInfo.getStatusCode().equals("o") || itemInfo.getStatusCode().equals("h") || itemInfo.getStatusCode().equals("u");
	}
}
