package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Pattern;

/**
 * Processes item records for use within solr
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/4/13
 * Time: 10:44 AM
 */
public class PrintItemSolrProcessor {
	private Set<String> librarySystems;
	private Set<String> librarySubdomains;
	private Set<String> locations;
	private Set<String> barcodes;
	private Set<String> iTypes;
	private HashMap<String, LinkedHashSet<String>> iTypesBySystem;
	private Set<String> locationCodes;
	private HashMap<String, LinkedHashSet<String>> locationsCodesBySystem;
	private Set<String> timeSinceAdded;
	private HashMap<String, LinkedHashSet<String>> timeSinceAddedBySystem;
	private HashMap<String, LinkedHashSet<String>> timeSinceAddedByLocation;
	private Set<String> availableAt;
	private LinkedHashSet<String> availabilityToggleGlobal;
	private HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation;
	private LinkedHashSet<String> localCallNumbers;
	private HashMap<String, HashMap<String, Long>> sortableCallNumbersByLibraryAndLocation; //HashMap(library/location code, HashMap(call number, times used)
	private LinkedHashSet<String> usableByPTypes;
	private boolean manuallySuppressed;
	private boolean allItemsSuppressed;
	private float popularity;
	private DataField itemField;
	private Logger logger;
	private MarcProcessor marcProcessor;
	Pattern digitPattern = Pattern.compile("^\\d+$");
	private static Date indexDate = new Date();
	private Long bibDaysSinceAdded;

	public PrintItemSolrProcessor(Logger logger, MarcProcessor marcProcessor, Set<String> librarySystems, Set<String> librarySubdomains, Set<String> locations, Set<String> barcodes, Set<String> iTypes, HashMap<String, LinkedHashSet<String>> iTypesBySystem, Set<String> locationCodes, HashMap<String, LinkedHashSet<String>> locationsCodesBySystem, Long bibDaysSinceAdded, Set<String> timeSinceAdded, HashMap<String, LinkedHashSet<String>> timeSinceAddedBySystem, HashMap<String, LinkedHashSet<String>> timeSinceAddedByLocation, Set<String> availableAt, LinkedHashSet<String> availabilityToggleGlobal, HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation, LinkedHashSet<String> usableByPTypes, LinkedHashSet<String> localCallNumbers, HashMap<String, HashMap<String, Long>> sortableCallNumbersByLibraryAndLocation, boolean manuallySuppressed, boolean allItemsSuppressed, float popularity, DataField itemField) {
		this.logger = logger;
		this.marcProcessor = marcProcessor;
		this.librarySystems = librarySystems;
		this.librarySubdomains = librarySubdomains;
		this.locations = locations;
		this.barcodes = barcodes;
		this.iTypes = iTypes;
		this.iTypesBySystem = iTypesBySystem;
		this.locationCodes = locationCodes;
		this.locationsCodesBySystem = locationsCodesBySystem;
		this.bibDaysSinceAdded = bibDaysSinceAdded;
		this.timeSinceAdded = timeSinceAdded;
		this.timeSinceAddedBySystem = timeSinceAddedBySystem;
		this.timeSinceAddedByLocation = timeSinceAddedByLocation;
		this.availableAt = availableAt;
		this.availabilityToggleGlobal = availabilityToggleGlobal;
		this.availableAtBySystemOrLocation = availableAtBySystemOrLocation;
		this.usableByPTypes = usableByPTypes;
		this.localCallNumbers = localCallNumbers;
		this.sortableCallNumbersByLibraryAndLocation = sortableCallNumbersByLibraryAndLocation;
		this.manuallySuppressed = manuallySuppressed;
		this.allItemsSuppressed = allItemsSuppressed;
		this.popularity = popularity;
		this.itemField = itemField;
	}

	public Set<String> getTimeSinceAdded() {
		return timeSinceAdded;
	}

	public boolean isAllItemsSuppressed() {
		return allItemsSuppressed;
	}

	public float getPopularity() {
		return popularity;
	}

	public PrintItemSolrProcessor invoke() {
		boolean itemSuppressed = false;
		if (itemField.getSubfield(marcProcessor.getLocationSubfield()) == null) {
			logger.debug("Did not find location code for item ");
		} else {
			String locationCode = itemField.getSubfield(marcProcessor.getLocationSubfield()).getData().trim();

			//logger.debug("Processing locationCode " + locationCode);
			// Figure out which location and library this item belongs to.
			LocationIndexingInfo locationIndexingInfo = marcProcessor.getLocationIndexingInfo(locationCode);
			LibraryIndexingInfo libraryIndexingInfo;
			if (locationIndexingInfo == null) {
				libraryIndexingInfo = marcProcessor.getLibraryIndexingInfoByCode(locationCode);
				if (libraryIndexingInfo != null){
					logger.debug("Warning, did not find location info for location " + locationCode);
				} else{
					logger.warn("Warning, did not find location info or library info for location " + locationCode);
				}
			} else {
				libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(locationIndexingInfo.getLibraryId());
			}

			//Determine suppression
			if (locationCode.equalsIgnoreCase("zzzz")) {
				// logger.debug("suppressing item because location code is zzzz");
				itemSuppressed = true;
			}
			if (locationIndexingInfo != null && locationIndexingInfo.isSuppressHoldings()){
				itemSuppressed = true;
			}


			if (marcProcessor.isUseItemBasedCallNumbers()){
				String callNumber = getLocalCallNumber();

				if (callNumber.length() > 0){
					//logger.debug("Processing call number " + callNumber + " for location code " + locationCode);
					localCallNumbers.add(callNumber);
					if (libraryIndexingInfo != null){
						//Add sortable call number to array
						String scopeName = libraryIndexingInfo.getSubdomain();
						addSortableCallNumber(callNumber, scopeName);
					}
					if (locationIndexingInfo != null){
						//Add sortable call number to array
						String scopeName = locationIndexingInfo.getCode();
						addSortableCallNumber(callNumber, scopeName);
					}
				}
			}

			// Load availability (local, system, marmot)
			Subfield statusSubfield = itemField.getSubfield(marcProcessor.getStatusSubfield());
			Subfield dueDateField = itemField.getSubfield(marcProcessor.getDueDateSubfield());
			Subfield icode2Subfield = itemField.getSubfield(marcProcessor.getICode2Subfield());
			boolean available = false;
			if (marcProcessor.isGetAvailabilityFromMarc()){
				if (statusSubfield != null) {
					String status = statusSubfield.getData();
					String dueDate = dueDateField == null ? "" : dueDateField.getData().replaceAll("\\D", "").trim();

					String availableStatus = marcProcessor.getAvailableStatusCodes();
					if (availableStatus.indexOf(status.charAt(0)) >= 0) {
						if (dueDate.length() == 0) {
							if (marcProcessor.isUseICode2Suppression()){
								if (icode2Subfield != null) {
									String icode2 = icode2Subfield.getData().toLowerCase().trim();
									if (icode2.equals("n") || icode2.equals("x")) {
										//logger.debug("Suppressing item because icode2 is " + icode2);
										itemSuppressed = true;
									} else {
										available = true;
									}
								}
							}else{
								available = true;
							}
						}
					}
				}
			}else{
				//Check icode2 to see if the item is suppressed
				String icode2 = icode2Subfield.getData().toLowerCase().trim();
				if (icode2.equals("n") || icode2.equals("x")) {
					//logger.debug("Suppressing item because icode2 is " + icode2);
					itemSuppressed = true;
				}
				if (itemField.getSubfield('b') != null){
					String barcode = itemField.getSubfield('b').getData();
					available = marcProcessor.isBarcodeAvailable(barcode);
				}
			}

			if (!itemSuppressed) {
				// Map library system (institution)
				if (libraryIndexingInfo != null) {
					librarySystems.add(libraryIndexingInfo.getFacetLabel());
					librarySubdomains.add(libraryIndexingInfo.getSubdomain());
				}

				// Map location (building)
				if (locationIndexingInfo != null) {
					locations.add(locationIndexingInfo.getFacetLabel());
				}
				// Check for extra locations
				LinkedHashSet<String> extraLocations = marcProcessor.getExtraLocations(locationCode);
				if (extraLocations.size() > 0) {
					locations.addAll(extraLocations);
				}

				// Barcodes
				@SuppressWarnings("unchecked")
				List<Subfield> barcodeFields = itemField.getSubfields(marcProcessor.getBarcodeSubfield());
				for (Subfield curSubfield : barcodeFields) {
					String barcode = curSubfield.getData();
					if (digitPattern.matcher(barcode).matches()) {
						barcodes.add(barcode);
					}
				}

				//Get number of times the title has been checked out
				Subfield totalCheckoutsField = itemField.getSubfield(marcProcessor.getTotalCheckoutSubfield());
				int totalCheckouts = 0;
				if (totalCheckoutsField != null){
					totalCheckouts = Integer.parseInt(totalCheckoutsField.getData());
				}
				Subfield ytdCheckoutsField = itemField.getSubfield(marcProcessor.getYtdCheckoutSubfield());
				int ytdCheckouts = 0;
				if (ytdCheckoutsField != null){
					ytdCheckouts = Integer.parseInt(ytdCheckoutsField.getData());
				}
				Subfield lastYearCheckoutsField = itemField.getSubfield(marcProcessor.getLastYearCheckoutSubfield());
				int lastYearCheckouts = 0;
				if (lastYearCheckoutsField != null){
					lastYearCheckouts = Integer.parseInt(lastYearCheckoutsField.getData());
				}
				double itemPopularity = ytdCheckouts + .5 * (lastYearCheckouts) + .1 * (totalCheckouts - lastYearCheckouts - ytdCheckouts);
				//logger.debug("Popularity for item " + itemPopularity + " ytdCheckouts=" + ytdCheckouts + " lastYearCheckouts=" + lastYearCheckouts + " totalCheckouts=" + totalCheckouts);
				popularity += itemPopularity;

				// Map iTypes
				Subfield iTypeSubfield = itemField.getSubfield(marcProcessor.getiTypeSubfield());
				String iType = "0";
				if (iTypeSubfield != null) {
					iType = processItemIcode(iTypes, iTypesBySystem, libraryIndexingInfo, iTypeSubfield);
				}

				// Get Location Codes
				locationCodes.add(locationCode);
				// Get Location Codes By System
				if (libraryIndexingInfo != null) {
					LinkedHashSet<String> detailedLocationVals = locationsCodesBySystem.get(libraryIndexingInfo.getSubdomain());
					if (detailedLocationVals == null) {
						detailedLocationVals = new LinkedHashSet<String>();
						locationsCodesBySystem.put(libraryIndexingInfo.getSubdomain(), detailedLocationVals);
					}
					detailedLocationVals.add(locationCode);
				}

				// Get Location Codes By Location
				if (locationIndexingInfo != null) {
					LinkedHashSet<String> detailedLocationVals = locationsCodesBySystem.get(locationIndexingInfo.getCode());
					if (detailedLocationVals == null) {
						detailedLocationVals = new LinkedHashSet<String>();
						locationsCodesBySystem.put(locationIndexingInfo.getCode(), detailedLocationVals);
					}
					detailedLocationVals.add(locationCode);
				}

				// Map time since added (library & location)
				char dateCreatedSubfield = marcProcessor.getDateCreatedSubfield();
				Subfield dateAddedField = itemField.getSubfield(dateCreatedSubfield);
				if (dateAddedField != null) {
					timeSinceAdded = processItemDateAdded(timeSinceAdded, timeSinceAddedBySystem, timeSinceAddedByLocation, locationIndexingInfo, libraryIndexingInfo, dateAddedField);
				}

				// Add availability
				if (!itemSuppressed && !manuallySuppressed) {
					processItemAvailability(availableAt, availabilityToggleGlobal, availableAtBySystemOrLocation, usableByPTypes, locationCode, locationIndexingInfo, libraryIndexingInfo, available, iType);
				}
			} else {
				logger.debug("Item is suppressed.");
			}
		}
		if (!itemSuppressed) {
			allItemsSuppressed = false;
		}
		return this;
	}

	private void addSortableCallNumber(String callNumber, String scopeName) {
		HashMap<String, Long> sortableCallNumbers = sortableCallNumbersByLibraryAndLocation.get(scopeName);
		if (sortableCallNumbers == null){
			sortableCallNumbers = new HashMap<String, Long>();
			sortableCallNumbersByLibraryAndLocation.put(scopeName, sortableCallNumbers);
		}
		Long timesFound = sortableCallNumbers.get(callNumber);
		if (timesFound == null){
			timesFound = 0L;
		}
		timesFound++;
		sortableCallNumbers.put(callNumber, timesFound);
	}

	private String getLocalCallNumber() {
		StringBuilder callNumber = new StringBuilder();

		Subfield callNumberFieldS = itemField.getSubfield('s');
		if (callNumberFieldS != null){
			callNumber.append(callNumberFieldS.getData().trim());
		}
		Subfield callNumberFieldA = itemField.getSubfield('a');
		if (callNumberFieldA != null){
			callNumber.append(" ").append(callNumberFieldA.getData().trim());
		}
		Subfield callNumberFieldR = itemField.getSubfield('r');
		if (callNumberFieldR != null){
			callNumber.append(" ").append(callNumberFieldR.getData().trim());
		}
		return callNumber.toString().trim();
	}

	private String processItemIcode(Set<String> iTypes, HashMap<String, LinkedHashSet<String>> iTypesBySystem, LibraryIndexingInfo libraryIndexingInfo, Subfield iTypeSubfield) {
		String iType;
		iType = iTypeSubfield.getData();
		iTypes.add(iType);
		if (libraryIndexingInfo != null) {
			LinkedHashSet<String> iTypesBySystemVals;
			if (iTypesBySystem
					.containsKey(libraryIndexingInfo.getSubdomain())) {
				iTypesBySystemVals = iTypesBySystem.get(libraryIndexingInfo.getSubdomain());
			} else {
				iTypesBySystemVals = new LinkedHashSet<String>();
				iTypesBySystem.put(libraryIndexingInfo.getSubdomain(), iTypesBySystemVals);
			}

			iTypesBySystemVals.add(iType);
		}
		return iType;
	}

	private Set<String> processItemDateAdded(Set<String> timeSinceAdded, HashMap<String, LinkedHashSet<String>> timeSinceAddedBySystem, HashMap<String, LinkedHashSet<String>> timeSinceAddedByLocation, LocationIndexingInfo locationIndexingInfo, LibraryIndexingInfo libraryIndexingInfo, Subfield dateAddedField) {
		String dateAddedStr = dateAddedField.getData();
		try {
			SimpleDateFormat dateAddedFormatter = marcProcessor.getDateAddedFormatter();
			Date itemDateAdded = dateAddedFormatter.parse(dateAddedStr);
			Long itemDaysSinceAdded = getDaysSinceAdded(itemDateAdded);
			LinkedHashSet<String> itemTimeSinceAdded = getTimeSinceAddedForDate(itemDateAdded);

			if (this.bibDaysSinceAdded == null || bibDaysSinceAdded > itemDaysSinceAdded){
				bibDaysSinceAdded = itemDaysSinceAdded;
			}
			if (itemTimeSinceAdded.size() > timeSinceAdded.size()) {
				timeSinceAdded = itemTimeSinceAdded;
			}
			// Check library specific time since added
			if (libraryIndexingInfo != null) {
				LinkedHashSet<String> timeSinceAddedBySystemVals = timeSinceAddedBySystem.get(libraryIndexingInfo.getSubdomain());
				if (timeSinceAddedBySystemVals == null || itemTimeSinceAdded.size() > timeSinceAddedBySystemVals.size()) {
					timeSinceAddedBySystem.put(libraryIndexingInfo.getSubdomain(), itemTimeSinceAdded);
				}
			}
			// Check location specific time since added
			if (locationIndexingInfo != null) {
				LinkedHashSet<String> timeSinceAddedByLocationVals = timeSinceAddedByLocation.get(locationIndexingInfo.getCode());
				if (timeSinceAddedByLocationVals == null || itemTimeSinceAdded.size() > timeSinceAddedByLocationVals.size()) {
					timeSinceAddedByLocation.put(locationIndexingInfo.getCode(), itemTimeSinceAdded);
				}
			}
		} catch (ParseException e) {
			logger.error("Error processing date added", e);
		}
		return timeSinceAdded;
	}

	private void processItemAvailability(Set<String> availableAt, LinkedHashSet<String> availabilityToggleGlobal, HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation, LinkedHashSet<String> usableByPTypes, String locationCode, LocationIndexingInfo locationIndexingInfo, LibraryIndexingInfo libraryIndexingInfo, boolean available, String iType) {
		if (available) {
			availabilityToggleGlobal.add("Available Now");
			availableAtBySystemOrLocation.put("marmot", availabilityToggleGlobal);
		}
		// logger.debug("item is available at " + locationCode);
		// Loop through all libraries
		for (String curSubdomain : marcProcessor.getLibrarySubdomains()) {
			LinkedHashSet<String> existingAvailability = availableAtBySystemOrLocation
					.get(curSubdomain);
			if (existingAvailability != null
					&& existingAvailability.size() == 2) {
				continue;
			}
			LinkedHashSet<String> libraryAvailability = new LinkedHashSet<String>();
			libraryAvailability.add("Entire Collection");
			if (available) {
				if (libraryIndexingInfo != null && libraryIndexingInfo.getSubdomain().equalsIgnoreCase(curSubdomain)) {
					libraryAvailability.add("Available Now");
				}
			}
			if (existingAvailability == null || libraryAvailability.size() > existingAvailability.size()) {
				availableAtBySystemOrLocation.put(curSubdomain, libraryAvailability);
			}
		}

		// Loop through all locations
		for (String curCode : marcProcessor.getLocationCodes()) {
			LinkedHashSet<String> existingAvailability = availableAtBySystemOrLocation .get(curCode);
			if (existingAvailability != null && existingAvailability.size() == 2) {
				// Can't get better availability
				continue;
			}
			LinkedHashSet<String> locationAvailability = new LinkedHashSet<String>();
			locationAvailability.add("Entire Collection");
			if (available) {
				if (locationIndexingInfo != null
						&& locationIndexingInfo.getCode().equalsIgnoreCase(curCode)) {
					locationAvailability.add("Available Now");
					availableAt.add(locationIndexingInfo.getFacetLabel());
				}
			}
			if (existingAvailability == null
					|| locationAvailability.size() > existingAvailability.size()) {
				availableAtBySystemOrLocation.put(curCode, locationAvailability);
			}
		}

		LinkedHashSet<String> itemUsableByPTypes = marcProcessor
				.getCompatiblePTypes(iType, locationCode);
		usableByPTypes.addAll(itemUsableByPTypes);
	}

	public Long getDaysSinceAdded(Date curDate){
		return (indexDate.getTime() - curDate.getTime()) / (1000 * 60 * 60 * 24);
	}
	public LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
		Long timeDifferenceDays = getDaysSinceAdded(curDate);
		// System.out.println("Time Difference Days: " + timeDifferenceDays);
		LinkedHashSet<String> result = new LinkedHashSet<String>();
		if (timeDifferenceDays <= 1) {
			result.add("Day");
		}
		if (timeDifferenceDays <= 7) {
			result.add("Week");
		}
		if (timeDifferenceDays <= 30) {
			result.add("Month");
		}
		if (timeDifferenceDays <= 60) {
			result.add("2 Months");
		}
		if (timeDifferenceDays <= 90) {
			result.add("Quarter");
		}
		if (timeDifferenceDays <= 180) {
			result.add("Six Months");
		}
		if (timeDifferenceDays <= 365) {
			result.add("Year");
		}
		return result;
	}

	public Long getBibDaysSinceAdded() {
		return bibDaysSinceAdded;
	}
}
