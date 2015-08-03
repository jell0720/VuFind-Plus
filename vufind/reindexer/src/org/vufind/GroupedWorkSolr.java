package org.vufind;

import com.sun.istack.internal.NotNull;
import org.apache.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;

import java.util.*;

/**
 * A representation of the grouped record as it will be added to Solr.
 *
 * Pika
 * User: Mark Noble
 * Date: 11/25/13
 * Time: 3:19 PM
 */
public class GroupedWorkSolr {
	private String id;

	private HashMap<String, RecordInfo> relatedRecords = new HashMap<>();

	private String acceleratedReaderInterestLevel;
	private String acceleratedReaderReadingLevel;
	private String acceleratedReaderPointValue;
	private String allFields = "";
	private HashSet<String> alternateIds = new HashSet<>();
	private String authAuthor;
	private String author;
	private String authorLetter;
	private HashSet<String> authorAdditional = new HashSet<>();
	private String authorDisplay;
	private HashSet<String> author2 = new HashSet<>();
	private HashSet<String> authAuthor2 = new HashSet<>();
	private HashSet<String> author2Role = new HashSet<>();
	private HashSet<String> awards = new HashSet<>();
	private HashSet<String> barcodes = new HashSet<>();
	private HashSet<String> bisacSubjects = new HashSet<>();
	private String callNumberA;
	private String callNumberFirst;
	private String callNumberSubject;
	private HashSet<String> contents = new HashSet<>();
	private HashSet<String> dateSpans = new HashSet<>();
	private HashSet<String> description = new HashSet<>();
	private String displayDescription = "";
	private String displayDescriptionFormat = "";
	private String displayTitle;
	private Long earliestPublicationDate = null;
	private HashSet<String> econtentDevices = new HashSet<>();
	private HashSet<String> editions = new HashSet<>();
	private HashSet<String> eras = new HashSet<>();
	private HashSet<String> fullTitles = new HashSet<>();
	private HashSet<String> genres = new HashSet<>();
	private HashSet<String> genreFacets = new HashSet<>();
	private HashSet<String> geographic = new HashSet<>();
	private HashSet<String> geographicFacets = new HashSet<>();
	private String groupingCategory;
	private HashSet<String> isbns = new HashSet<>();
	private HashSet<String> issns = new HashSet<>();
	private HashSet<String> keywords = new HashSet<>();
	private HashSet<String> languages = new HashSet<>();
	private Long languageBoost = 1L;
	private Long languageBoostSpanish = 1L;
	private HashSet<String> lccns = new HashSet<>();
	private HashSet<String> lcSubjects = new HashSet<>();
	private String lexileScore = "-1";
	private String lexileCode = "";
	private HashMap<String, Integer> literaryFormFull = new HashMap<>();
	private HashMap<String, Integer> literaryForm = new HashMap<>();
	private HashSet<String> mpaaRatings = new HashSet<>();
	private Long numHoldings = 0L;
	private HashSet<String> oclcs = new HashSet<>();
	private HashSet<String> physicals = new HashSet<>();
	private double popularity;
	private HashSet<String> publishers = new HashSet<>();
	private HashSet<String> publicationDates = new HashSet<>();
	private float rating = 2.5f;
	private HashSet<String> series = new HashSet<>();
	private HashSet<String> series2 = new HashSet<>();
	private String subTitle;
	private HashSet<String> targetAudienceFull = new HashSet<>();
	private HashSet<String> targetAudience = new HashSet<>();
	private String title;
	private HashSet<String> titleAlt = new HashSet<>();
	private HashSet<String> titleOld = new HashSet<>();
	private HashSet<String> titleNew = new HashSet<>();
	private String titleSort;
	private HashSet<String> topics = new HashSet<>();
	private HashSet<String> topicFacets = new HashSet<>();
	private HashSet<String> upcs = new HashSet<>();

	private Logger logger;
	private GroupedWorkIndexer groupedWorkIndexer;
	private HashSet<String> systemLists = new HashSet<>();

	public GroupedWorkSolr(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		this.logger = logger;
		this.groupedWorkIndexer = groupedWorkIndexer;
	}

	public SolrInputDocument getSolrDocument(int availableAtBoostValue, int ownedByBoostValue) {
		SolrInputDocument doc = new SolrInputDocument();
		//Main identification
		doc.addField("id", id);
		doc.addField("alternate_ids", alternateIds);
		doc.addField("recordtype", "grouped_work");
		//Ownership and location
		doc.addField("owning_library", getAllOwningLibraries());
		doc.addField("owning_location", getAllOwningLocations());
		
		//Title and variations
		String fullTitle = title;
		if (subTitle != null){
			fullTitle += " " + subTitle;
		}
		doc.addField("title", fullTitle);
		doc.addField("title_display", displayTitle);
		doc.addField("title_sub", subTitle);
		doc.addField("title_short", title);
		doc.addField("title_full", fullTitles);
		doc.addField("title_sort", titleSort);
		doc.addField("title_alt", titleAlt);
		doc.addField("title_old", titleOld);
		doc.addField("title_new", titleNew);

		//author and variations
		doc.addField("auth_author", authAuthor);
		doc.addField("author", author);
		doc.addField("author-letter", authorLetter);
		doc.addField("auth_author2", authAuthor2);
		doc.addField("author2", author2);
		doc.addField("author2-role", author2Role);
		doc.addField("author_additional", authorAdditional);
		doc.addField("author_display", authorDisplay);
		//format
		doc.addField("grouping_category", groupingCategory);
		doc.addField("format_boost", getTotalFormatBoost());

		//language related fields
		doc.addField("language", languages);
		doc.addField("language_boost", languageBoost);
		doc.addField("language_boost_es", languageBoostSpanish);
		//Publication related fields
		doc.addField("publisher", publishers);
		doc.addField("publishDate", publicationDates);
		//Sorting will use the earliest date published
		doc.addField("publishDateSort", earliestPublicationDate);

		//faceting and refined searching
		doc.addField("physical", physicals);
		doc.addField("edition", editions);
		doc.addField("dateSpan", dateSpans);
		doc.addField("series", series);
		doc.addField("series2", series2);
		doc.addField("topic", topics);
		doc.addField("topic_facet", topicFacets);
		doc.addField("lc_subject", lcSubjects);
		doc.addField("bisac_subject", bisacSubjects);
		doc.addField("genre", genres);
		doc.addField("genre_facet", genreFacets);
		doc.addField("geographic", geographic);
		doc.addField("geographic_facet", geographicFacets);
		doc.addField("era", eras);
		checkDefaultValue(literaryFormFull, "Not Coded");
		checkInconsistentLiteraryFormsFull();
		doc.addField("literary_form_full", literaryFormFull.keySet());
		checkDefaultValue(literaryForm, "Not Coded");
		checkInconsistentLiteraryForms();
		doc.addField("literary_form", literaryForm.keySet());
		checkDefaultValue(targetAudienceFull, "Unknown");
		doc.addField("target_audience_full", targetAudienceFull);
		checkDefaultValue(targetAudience, "Unknown");
		doc.addField("target_audience", targetAudience);
		doc.addField("system_list", systemLists);
		//Date added to catalog
		Date dateAdded = getDateAdded();
		doc.addField("date_added", dateAdded);

		if (dateAdded == null){
			//Determine date added based on publication date
			if (earliestPublicationDate != null){
				//Return number of days since the given year
				Calendar publicationDate = GregorianCalendar.getInstance();
				publicationDate.set(earliestPublicationDate.intValue(), Calendar.DECEMBER, 31);

				long indexTime = Util.getIndexDate().getTime();
				long publicationTime = publicationDate.getTime().getTime();
				long bibDaysSinceAdded = (indexTime - publicationTime) / (long)(1000 * 60 * 60 * 24);
				doc.addField("days_since_added", Long.toString(bibDaysSinceAdded));
				doc.addField("time_since_added", Util.getTimeSinceAddedForDate(publicationDate.getTime()));
			}else{
				doc.addField("days_since_added", Long.toString(Integer.MAX_VALUE));
			}
		}else{
			doc.addField("days_since_added", Util.getDaysSinceAddedForDate(dateAdded));
			doc.addField("time_since_added", Util.getTimeSinceAddedForDate(dateAdded));
		}

		doc.addField("barcode", barcodes);
		//Awards and ratings
		doc.addField("mpaa_rating", mpaaRatings);
		doc.addField("awards_facet", awards);
		if (lexileScore.length() == 0){
			doc.addField("lexile_score", -1);
		}else{
			doc.addField("lexile_score", lexileScore);
		}
		doc.addField("lexile_code", lexileCode);
		doc.addField("accelerated_reader_interest_level", acceleratedReaderInterestLevel);
		doc.addField("accelerated_reader_reading_level", acceleratedReaderReadingLevel);
		doc.addField("accelerated_reader_point_value", acceleratedReaderPointValue);
		//EContent fields
		doc.addField("econtent_device", econtentDevices);

		HashSet<String> eContentSources = getAllEContentSources();
		keywords.addAll(eContentSources);

		doc.addField("table_of_contents", contents);
		//broad search terms
		// TODO: determine if we still need all fields?
		doc.addField("all_fields", allFields);
		//TODO: change keywords to be more like old version?
		doc.addField("keywords", Util.getCRSeparatedStringFromSet(keywords));
		//identifiers
		doc.addField("lccn", lccns);
		doc.addField("oclc", oclcs);
		doc.addField("isbn", isbns);
		doc.addField("issn", issns);
		doc.addField("upc", upcs);
		//call numbers
		doc.addField("callnumber-a", callNumberA);
		doc.addField("callnumber-first", callNumberFirst);
		doc.addField("callnumber-subject", callNumberSubject);
		//relevance determiners
		doc.addField("popularity", Long.toString((long)popularity));
		doc.addField("num_holdings", numHoldings);
		//vufind enrichment
		doc.addField("rating", rating);
		doc.addField("rating_facet", getRatingFacet(rating));
		doc.addField("description", Util.getCRSeparatedString(description));
		doc.addField("display_description", displayDescription);

		//Save information from scopes
		addScopedFieldsToDocument(availableAtBoostValue, ownedByBoostValue, doc);

		return doc;
	}

	private Long getTotalFormatBoost() {
		long formatBoost = 0;
		for (RecordInfo curRecord : relatedRecords.values()){
			formatBoost += curRecord.getFormatBoost();
		}
		if (formatBoost == 0){
			formatBoost = 1;
		}
		return formatBoost;
	}

	private HashSet<String> getAllEContentSources() {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()){
			values.addAll(curRecord.getAllEContentSources());
		}
		return values;
	}

	private Date getDateAdded() {
		Date earliestDate = null;
		for (RecordInfo curRecord : relatedRecords.values()) {
			for (ItemInfo curItem : curRecord.getRelatedItems()) {
				if (curItem.getDateAdded() != null) {
					if (earliestDate == null || curItem.getDateAdded().before(earliestDate)) {
						earliestDate = curItem.getDateAdded();
					}
				}
			}
		}
		return earliestDate;
	}

	private HashSet<String> getAllOwningLocations() {
		HashSet<String> owningLibraryValues = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()){
			owningLibraryValues.addAll(curRecord.getAllOwningLocations());
		}
		return owningLibraryValues;
	}

	private HashSet<String> getAllOwningLibraries() {
		HashSet<String> owningLibraryValues = new HashSet<>();
		for (RecordInfo curRecord : relatedRecords.values()){
			owningLibraryValues.addAll(curRecord.getAllOwningLibraries());
		}
		return owningLibraryValues;
	}

	protected void addScopedFieldsToDocument(int availableAtBoostValue, int ownedByBoostValue, SolrInputDocument doc) {
		//Load information based on scopes.  This has some pretty severe performance implications since we potentially
		//have a lot of scopes and a lot of items & records.
		for (RecordInfo curRecord : relatedRecords.values()){
			doc.addField("record_details", curRecord.getDetails());
			for (ItemInfo curItem : curRecord.getRelatedItems()){
				doc.addField("item_details", curItem.getDetails());
				for (String curScopeName : curItem.getScopingInfo().keySet()){
					ScopingInfo curScope = curItem.getScopingInfo().get(curScopeName);
					doc.addField("scoping_details_" + curScopeName, curScope.getScopingDetails());
					//TODO: Add scoped fields for owning branches, owning locations, and available at
					//if we do that, we don't need to filter within PHP
					addUniqueFieldValue(doc, "scope_has_related_records", curScopeName);
					addUniqueFieldValue(doc, "format_" + curScopeName, curItem.getFormat());
					addUniqueFieldValues(doc, "format_" + curScopeName, curRecord.getFormats());
					addUniqueFieldValue(doc, "format_category_" + curScopeName, curItem.getFormatCategory());
					addUniqueFieldValues(doc, "format_category_" + curScopeName, curRecord.getFormatCategories());
					addUniqueFieldValue(doc, "detailed_location_" + curScopeName, curItem.getShelfLocation());
					if (curScope.isLocallyOwned()) {
						addUniqueFieldValue(doc, "detailed_location_" + curScopeName, curItem.getCollection());
					}
					if (curScope.isAvailable() && curScope.isLocallyOwned() && !curItem.isEContent()) {
						addUniqueFieldValue(doc, "available_at", curScope.getScope().getFacetLabel());
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Available Now");
					}
					if (curItem.isEContent() && curScope.getScope().isLibraryScope()) {
						addUniqueFieldValue(doc, "available_at", curScope.getScope().getFacetLabel() + " Online");
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Available Now");
					}

					//TODO: Different toggles for library and location?
					if (curScope.isLocallyOwned() && curScope.getScope().isLocationScope()){
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Entire Collection");
					}
					if (curScope.isLibraryOwned() && curScope.getScope().isLibraryScope()){
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Entire Collection");
					}

					/*if (curScope.isLocallyOwned()) {
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Library Collection");
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Branch Collection");
						updateMaxValueField(doc, "lib_boost_" + curScopeName, ownedByBoostValue);
					}else if (curScope.isLibraryOwned()) {
						addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Library Collection");
						updateMaxValueField(doc, "lib_boost_" + curScopeName, ownedByBoostValue);
					}*/

					//Date Added To Catalog needs to be the earliest date added for the catalog.
					if (curScope.isLocallyOwned() || curScope.isLibraryOwned()) {
						Date dateAdded = curItem.getDateAdded();
						long daysSinceAdded;
						//See if we need to override based on publication date if not provided.
						//Should be set by individual driver though.
						if (dateAdded == null){
							if (earliestPublicationDate != null){
								//Return number of days since the given year
								Calendar publicationDate = GregorianCalendar.getInstance();
								publicationDate.set(earliestPublicationDate.intValue(), Calendar.DECEMBER, 31);

								long indexTime = Util.getIndexDate().getTime();
								long publicationTime = publicationDate.getTime().getTime();
								daysSinceAdded = (indexTime - publicationTime) / (long)(1000 * 60 * 60 * 24);
							}else{
								daysSinceAdded = Integer.MAX_VALUE;
							}
						}else{
							daysSinceAdded = Util.getDaysSinceAddedForDate(curItem.getDateAdded());
						}

						updateMaxValueField(doc, "local_days_since_added_" + curScopeName, (int)daysSinceAdded);
					}

					if (curScope.isAvailable()){
						if (curScope.isLocallyOwned()) {
							addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Available Now");
							addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Available Locally");
							updateMaxValueField(doc, "lib_boost_" + curScopeName, availableAtBoostValue);
						} else if (curScope.isLibraryOwned()) {
							addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Available Locally");
						} else{
							addUniqueFieldValue(doc, "availability_toggle_" + curScopeName, "Available Anywhere");
						}
					}

					addUniqueFieldValue(doc, "itype_" + curScopeName, curItem.getIType());
					if (curItem.isEContent()) {
						addUniqueFieldValue(doc, "econtent_source_" + curScopeName, curItem.geteContentSource());
						addUniqueFieldValue(doc, "econtent_protection_type_" + curScopeName, curItem.geteContentProtectionType());
					}
					if (curScope.isLocallyOwned() || curScope.isLibraryOwned()) {
						addUniqueFieldValue(doc, "local_callnumber_" + curScopeName, curItem.getCallNumber());
						setSingleValuedFieldValue(doc, "callnumber_sort_" + curScopeName, curItem.getSortableCallNumber());
					}
				}
			}
		}

		//Now that we know the latest number of days added for each scope, we can set the time since added facet
		for (Scope scope : groupedWorkIndexer.getScopes()){
			SolrInputField field = doc.getField("local_days_since_added_" + scope.getScopeName());
			if (field != null){
				Integer daysSinceAdded = (Integer)field.getFirstValue();
				doc.addField("local_time_since_added_" + scope.getScopeName(), Util.getTimeSinceAdded(daysSinceAdded));
			}
		}

		/*for (Scope scope : groupedWorkIndexer.getScopes()){
			HashSet<RecordInfo> scopedRecords = new HashSet<>();
			HashSet<ItemInfo> scopedItems = new HashSet<>();
			loadRelatedRecordsAndItemsForScope(scope, scopedRecords, scopedItems);
			if (scopedRecords.size() > 0) {
				String scopeName = scope.getScopeName();
				scopesWithRecords.add(scopeName);
				doc.addField("format_" + scopeName, getScopedFormats(scopedRecords));
				doc.addField("format_category_" + scopeName, getScopedFormatCategories(scopedRecords));
				HashSet<String> detailedLocations = getScopedShelfLocations(scopedItems);
				if (detailedLocations.size() > 0) {
					doc.addField("detailed_location_" + scopeName, detailedLocations);
				}

				doc.addField("collection_" + scopeName, getCollections(scopedItems, scope));
				
				if (hasAvailableItemsWithinScope(scopedItems, scope)){
					availableFacets.add(scope.getFacetLabel());
				}

				doc.addField("availability_toggle_" + scopeName, getAvailabilityToggle(scopedItems, scope));
				doc.addField("availability_by_format_" + scopeName, getAvailabilityToggleByFormat(scopedItems, scope));

				if (isLocallyOwned(scopedItems, scope)){
					doc.addField("lib_boost_" + scopeName, availableAtBoostValue);
				}else{
					doc.addField("lib_boost_" + scopeName, ownedByBoostValue);
				}

				Date localDateAdded = getLocalDateAdded(scopedItems, scope);
				if (localDateAdded != null) {
					doc.addField("local_time_since_added_" + scopeName, Util.getTimeSinceAddedForDate(localDateAdded));
					doc.addField("local_days_since_added_" + scopeName, Util.getDaysSinceAddedForDate(localDateAdded));
				}

				doc.addField("itype_" + scopeName, getLocalITypes(scopedItems, scope));
				doc.addField("econtent_source_" + scopeName, getLocalEContentSources(scopedItems, scope));
				doc.addField("econtent_protection_type_" + scopeName, getLocalEContentProtectionTypes(scopedItems, scope));
				doc.addField("local_callnumber_" + scopeName, getLocalCallNumbers(scopedItems, scope));
				doc.addField("callnumber_sort_" + scopeName, getSortableLocalCallNumber(scopedItems, scope));
			}
		}
		doc.addField("scope_has_related_records", scopesWithRecords);
		doc.addField("available_at", availableFacets);*/
	}

	/**
	 * Update a field that can only contain a single value.  Ignores any subsequent after the first.
	 *
	 * @param doc         The document to be updated
	 * @param fieldName   The field name to update
	 * @param value       The value to set if no value already exists
	 */
	private void setSingleValuedFieldValue(SolrInputDocument doc, String fieldName, String value) {
		Object curValue = doc.getFieldValue(fieldName);
		if (curValue == null){
			doc.addField(fieldName, value);
		}
	}

	private void updateMaxValueField(SolrInputDocument doc, String fieldName, int value) {
		Object curValue = doc.getFieldValue(fieldName);
		if (curValue == null){
			doc.addField(fieldName, value);
		}else{
			if ((Integer)curValue < value){
				doc.setField(fieldName, value);
			}
		}
	}

	private void addUniqueFieldValue(SolrInputDocument doc, String fieldName, String value){
		if (value == null) return;
		Collection<Object> fieldValues = doc.getFieldValues(fieldName);
		if (fieldValues == null || !fieldValues.contains(value)){
			doc.addField(fieldName, value);
		}
	}

	private void addUniqueFieldValues(SolrInputDocument doc, String fieldName, Collection<String> values){
		if (values.size() == 0) return;
		Collection<Object> fieldValues = doc.getFieldValues(fieldName);
		if (fieldValues == null){
			doc.addField(fieldName, values);
		}else{
			for (String value : values){
				if (!fieldValues.contains(value)){
					doc.addField(fieldName, value);
				}
			}
		}
	}

	private boolean isLocallyOwned(HashSet<ItemInfo> scopedItems, Scope scope) {
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)){
				return true;
			}
		}
		return false;
	}

	private boolean isLibraryOwned(HashSet<ItemInfo> scopedItems, Scope scope) {
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLibraryOwned(scope)){
				return true;
			}
		}
		return false;
	}

	private Date getLocalDateAdded(HashSet<ItemInfo> scopedItems, Scope scope) {
		Date earliestDate = null;
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope) && curItem.getDateAdded() != null) {
				if (earliestDate == null || curItem.getDateAdded().before(earliestDate)){
					earliestDate = curItem.getDateAdded();
				}
			}
		}
		return earliestDate;
	}

	private String getSortableLocalCallNumber(HashSet<ItemInfo> scopedItems, Scope scope) {
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)) {
				if (curItem.getSortableCallNumber() != null && curItem.getSortableCallNumber().length() > 0){
					return curItem.getSortableCallNumber();
				}
			}
		}
		return "";
	}

	private HashSet<String> getLocalCallNumbers(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)) {
				values.add(curItem.getCallNumber());
			}
		}
		return values;
	}

	private HashSet<String> getLocalEContentProtectionTypes(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)) {
				values.add(curItem.geteContentProtectionType());
			}
		}
		return values;
	}

	private HashSet<String> getLocalEContentSources(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)) {
				values.add(curItem.geteContentSource());
			}
		}
		return values;
	}

	private HashSet<String> getLocalITypes(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)) {
				values.add(curItem.getIType());
			}
		}
		return values;
	}

	private HashSet<String> getAvailabilityToggleByFormat(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> availabilityToggle = new HashSet<>();
		for (ItemInfo curItem : scopedItems) {
			if (curItem.isLocallyOwned(scope)) {
				availabilityToggle.add(curItem.getFormat() + "_local");
				availabilityToggle.add(curItem.getFormatCategory() + "_local");
			}
			if (curItem.isLocallyAvailableInScope(scope)) {
				availabilityToggle.add(curItem.getFormat() + "_available");
				availabilityToggle.add(curItem.getFormatCategory() + "_available");
			}
		}
		return availabilityToggle;
	}

	private HashSet<String> getAvailabilityToggle(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> availabilityToggle = new HashSet<>();
		for (ItemInfo curItem : scopedItems) {
			if (curItem.isLocallyOwned(scope)) {
				availabilityToggle.add("Entire Collection");
			}
			if (curItem.isLocallyAvailableInScope(scope)) {
				availabilityToggle.add("Available Now");
			}
		}
		return availabilityToggle;
	}

	private boolean hasAvailableItemsWithinScope(HashSet<ItemInfo> scopedItems, Scope scope) {
		for (ItemInfo curItem : scopedItems){
			if (curItem.isAvailableInScope(scope)){
				return true;
			}
		}
		return false;
	}

	private HashSet<String> getScopedShelfLocations(HashSet<ItemInfo> scopedItems) {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curRecord : scopedItems){
			values.add(curRecord.getShelfLocation());
		}
		return values;
	}

	private HashSet<String> getScopedFormatCategories(HashSet<RecordInfo> scopedRecords) {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : scopedRecords){
			values.addAll(curRecord.getAllFormatCategories());
		}
		return values;
	}

	private HashSet<String> getScopedFormats(HashSet<RecordInfo> scopedRecords) {
		HashSet<String> values = new HashSet<>();
		for (RecordInfo curRecord : scopedRecords){
			values.addAll(curRecord.getAllFormats());
		}
		return values;
	}

	public HashSet<String> getRelatedRecordDetails(HashSet<RecordInfo> scopedRecords) {
		HashSet<String> relatedRecordDetails = new HashSet<>();
		for (RecordInfo curRecord : scopedRecords){
			relatedRecordDetails.add(curRecord.getDetails());
		}
		return relatedRecordDetails;
	}

	public HashSet<String> getRelatedItemDetails(HashSet<ItemInfo> scopedItems, Scope scope) {
		HashSet<String> relatedItemDetails = new HashSet<>();
		for (ItemInfo curItem : scopedItems){
			//relatedItemDetails.add(curItem.getDetails(scope));
		}
		return relatedItemDetails;
	}
	
	public HashSet<String> getCollections(HashSet<ItemInfo> scopedItems, Scope scope){
		HashSet<String> relatedCollections = new HashSet<>();
		for (ItemInfo curItem : scopedItems){
			if (curItem.isLocallyOwned(scope)) {
				relatedCollections.add(curItem.getCollection());
			}
		}
		return relatedCollections;
	}


	private void loadRelatedRecordsAndItemsForScope(Scope curScope, HashSet<RecordInfo> scopedRecords, HashSet<ItemInfo> scopedItems) {
		for (RecordInfo curRecord : relatedRecords.values()){
			boolean recordIsValid = false;
			for (ItemInfo curItem : curRecord.getRelatedItems()){
				if (curItem.isValidForScope(curScope)){
					scopedItems.add(curItem);
					recordIsValid = true;
				}
			}
			if (recordIsValid) {
				scopedRecords.add(curRecord);
			}
		}
	}

	private void checkInconsistentLiteraryForms() {
		if (literaryForm.size() > 1){
			if (literaryForm.containsKey("Unknown")){
				//We got unknown and something else, remove the unknown
				literaryForm.remove("Unknown");
			}
			if (literaryForm.size() >= 2){
				//Hmm, we got both fiction and non-fiction
				Integer numFictionIndicators = literaryForm.get("Fiction");
				if (numFictionIndicators == null){
					numFictionIndicators = 0;
				}
				Integer numNonFictionIndicators = literaryForm.get("Non Fiction");
				if (numNonFictionIndicators == null){
					numNonFictionIndicators = 0;
				}
				if (numFictionIndicators.equals(numNonFictionIndicators)){
					//Houston we have a problem.
					//logger.warn("Found inconsistent literary forms for grouped work " + id + " both fiction and non fiction had the same amount of usage.  Defaulting to neither.");
					literaryForm.clear();
					literaryForm.put("Unknown", 1);
					groupedWorkIndexer.addWorkWithInvalidLiteraryForms(id);
				}else if (numFictionIndicators.compareTo(numNonFictionIndicators) > 0){
					logger.debug("Popularity dictates that Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Non Fiction");
				}else if (numFictionIndicators.compareTo(numNonFictionIndicators) > 0){
					logger.debug("Popularity dictates that Non Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Fiction");
				}
			}
		}
	}

	private void checkInconsistentLiteraryFormsFull() {
		if (literaryFormFull.size() > 1){
			if (literaryFormFull.containsKey("Unknown")){
				//We got unknown and something else, remove the unknown
				literaryFormFull.remove("Unknown");
			}
			if (literaryFormFull.size() >= 2){
				//Hmm, we got multiple forms.  Check to see if there are inconsistent forms
				// i.e. Fiction and Non-Fiction are incompatible, but Novels and Fiction could be mixed
				int maxUsage = 0;
				HashSet<String> highestUsageLiteraryForms = new HashSet<>();
				for (String literaryForm : literaryFormFull.keySet()){
					int curUsage = literaryFormFull.get(literaryForm);
					if (curUsage > maxUsage){
						highestUsageLiteraryForms.clear();
						highestUsageLiteraryForms.add(literaryForm);
						maxUsage = curUsage;
					}else if (curUsage == maxUsage){
						highestUsageLiteraryForms.add(literaryForm);
					}
				}
				if (highestUsageLiteraryForms.size() > 1){
					//Check to see if the highest usage literary forms are inconsistent
					if (hasInconsistentLiteraryForms(highestUsageLiteraryForms)){
						//Ugh, we have inconsistent literary forms and can't make an educated guess as to which is correct.
						literaryFormFull.clear();
						literaryFormFull.put("Unknown", 1);
						groupedWorkIndexer.addWorkWithInvalidLiteraryForms(id);
					}
				}else{
					removeInconsistentFullLiteraryForms(literaryFormFull, highestUsageLiteraryForms);
				}
			}
		}
	}

	private void removeInconsistentFullLiteraryForms(HashMap<String, Integer> literaryFormFull, HashSet<String> highestUsageLiteraryForms) {
		boolean firstLiteraryFormIsNonFiction = nonFictionFullLiteraryForms.contains(highestUsageLiteraryForms.iterator().next());
		boolean changeMade = true;
		while (changeMade){
			changeMade = false;
			for (String curLiteraryForm : literaryFormFull.keySet()){
				if (firstLiteraryFormIsNonFiction != nonFictionFullLiteraryForms.contains(curLiteraryForm)){
					logger.debug(curLiteraryForm + " got voted off the island for grouped work " + id + " because it was inconsistent with other full literary forms.");
					literaryFormFull.remove(curLiteraryForm);
					changeMade = true;
					break;
				}
			}
		}
	}

	static ArrayList<String> nonFictionFullLiteraryForms = new ArrayList<>();
	static{
		nonFictionFullLiteraryForms.add("Non Fiction");
		nonFictionFullLiteraryForms.add("Essays");
		nonFictionFullLiteraryForms.add("Letters");
		nonFictionFullLiteraryForms.add("Speeches");
	}
	private boolean hasInconsistentLiteraryForms(HashSet<String> highestUsageLiteraryForms) {
		boolean firstLiteraryFormIsNonFiction = false;
		int numFormsChecked = 0;
		for (String curLiteraryForm : highestUsageLiteraryForms){
			if (numFormsChecked == 0){
				firstLiteraryFormIsNonFiction = nonFictionFullLiteraryForms.contains(curLiteraryForm);
			}else{
				if (firstLiteraryFormIsNonFiction != nonFictionFullLiteraryForms.contains(curLiteraryForm)){
					return true;
				}
			}
			numFormsChecked++;
		}
		return false;
	}

	private void checkDefaultValue(HashSet<String> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.contains(defaultValue) && valuesCollection.size() > 1){
			valuesCollection.remove(defaultValue);
		}else if (valuesCollection.size() == 0){
			valuesCollection.add(defaultValue);
		}
	}

	private void checkDefaultValue(HashMap<String, Integer> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.containsKey(defaultValue) && valuesCollection.size() > 1){
			valuesCollection.remove(defaultValue);
		}else if (valuesCollection.size() == 0){
			valuesCollection.put(defaultValue, 1);
		}
	}

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	public void setTitle(String title) {
		if (title != null){
			//TODO: determine if the title should be changed or always use the first one?
			this.title = title.replace("&", "and");
			keywords.add(title);
		}
	}

	public void setDisplayTitle(String newTitle){
		if (newTitle == null){
			return;
		}
		newTitle = Util.trimTrailingPunctuation(newTitle.replace("&", "and"));
		//Strip out anything in brackets unless that would cause us to show nothing
		String tmpTitle = newTitle.replaceAll("\\[.*?\\]", "").trim();
		if (tmpTitle.length() > 0){
			newTitle = tmpTitle;
		}
		//Remove common formats
		tmpTitle = newTitle.replaceAll("(?i)((?:a )?graphic novel|audio cd|book club kit)$", "").trim();
		if (tmpTitle.length() > 0){
			newTitle = tmpTitle;
		}

		if (this.displayTitle == null || newTitle.length() > this.displayTitle.length()){
			this.displayTitle = newTitle;
		}
	}

	public void setSubTitle(String subTitle) {
		if (subTitle != null){
			//TODO: determine if the subtitle should be changed?
			this.subTitle = subTitle;
			keywords.add(subTitle);
		}
	}
	public void setSortableTitle(String sortableTitle) {
		if (sortableTitle != null){
			this.titleSort = sortableTitle;
		}
	}

	public void addFullTitles(Set<String> fullTitles){
		this.fullTitles.addAll(fullTitles);
	}

	public void addFullTitle(String title) {
		this.fullTitles.add(title);
	}

	public void addAlternateTitles(Set<String> altTitles){
		this.titleAlt.addAll(altTitles);
	}

	public void addOldTitles(Set<String> oldTitles){
		this.titleOld.addAll(oldTitles);
	}

	public void addNewTitles(Set<String> newTitles){
		this.titleNew.addAll(newTitles);
	}

	public void setAuthor(String author) {
		this.author = author;
		keywords.add(author);
	}

	public void setAuthorDisplay(String newAuthor){
		this.authorDisplay = Util.trimTrailingPunctuation(newAuthor);
	}

	public void setAuthAuthor(String author) {
		this.authAuthor = author;
		keywords.add(author);
	}

	public void addOclcNumbers(Set<String> oclcs) {
		this.oclcs.addAll(oclcs);
	}
	public void addIsbn(String isbn) {
		isbns.add(isbn);
	}
	public HashSet<String> getIsbns() {
		return isbns;
	}
	public void addIssns(Set<String> issns) {
		this.issns.addAll(issns);
	}
	public void addUpc(String upc) {
		upcs.add(upc);
	}

	public void addAlternateId(String alternateId) {
		this.alternateIds.add(alternateId);
	}

	public void setGroupingCategory(String groupingCategory) {
		this.groupingCategory = groupingCategory;
	}

	public void setAuthorLetter(String authorLetter) {
		this.authorLetter = authorLetter;
	}

	public void addAuthAuthor2(Set<String> fieldList) {
		this.authAuthor2.addAll(fieldList);
	}

	public void addAuthor2(Set<String> fieldList) {
		this.author2.addAll(fieldList);
	}

	public void addAuthor2Role(Set<String> fieldList) {
		this.author2Role.addAll(fieldList);
	}

	public void addAuthorAdditional(Set<String> fieldList) {
		this.authorAdditional.addAll(fieldList);
	}

	public void addHoldings(int recordHoldings) {
		this.numHoldings += recordHoldings;
	}

	public void addPopularity(double itemPopularity) {
		this.popularity += itemPopularity;
	}

	public void addTopic(Set<String> fieldList) {
		this.topics.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addTopicFacet(Set<String> fieldList) {
		this.topicFacets.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addSeries(Set<String> fieldList) {
		for(String curField : fieldList){
			if (!curField.equalsIgnoreCase("none")){
				this.series.add(Util.trimTrailingPunctuation(curField));
			}
		}
	}

	public void addSeries(String series) {
		if (series != null && !series.equalsIgnoreCase("none")){
			this.series.add(Util.trimTrailingPunctuation(series));
		}
	}

	public void addSeries2(Set<String> fieldList) {
		this.series2.addAll(fieldList);
	}

	public void addPhysical(Set<String> fieldList) {
		this.physicals.addAll(fieldList);
	}

	public void addDateSpan(Set<String> fieldList) {
		this.dateSpans.addAll(fieldList);
	}

	public void addEditions(Set<String> fieldList) {
		this.editions.addAll(fieldList);
	}

	public void addContents(Set<String> fieldList) {
		this.contents.addAll(fieldList);
	}

	public void addGenre(Set<String> fieldList) {
		this.genres.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addGenreFacet(Set<String> fieldList) {
		this.genreFacets.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addGeographic(Set<String> fieldList) {
		this.geographic.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addGeographicFacet(Set<String> fieldList) {
		this.geographicFacets.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addEra(Set<String> fieldList) {
		this.eras.addAll(fieldList);
	}

	public void setLanguageBoost(Long languageBoost) {
		if (languageBoost > this.languageBoost){
			this.languageBoost = languageBoost;
		}
	}

	public void setLanguageBoostSpanish(Long languageBoostSpanish) {
		if (languageBoostSpanish > this.languageBoostSpanish){
			this.languageBoostSpanish = languageBoostSpanish;
		}
	}

	public void setLanguages(HashSet<String> languages) {
		this.languages.addAll(languages);
	}

	public void addPublishers(Set<String> publishers) {
		this.publishers.addAll(publishers);
	}

	public void addPublisher(String publisher){
		this.publishers.add(publisher);
	}

	public void addPublicationDates(Set<String> publicationDate) {
		for (String pubDate : publicationDate){
			addPublicationDate(pubDate);
		}
	}

	public void addPublicationDate(String publicationDate){
		String cleanDate = Util.cleanDate(publicationDate);
		if (cleanDate != null){
			this.publicationDates.add(cleanDate);
			//Convert the date to a long and see if it is before the current date
			Long pubDateLong = Long.parseLong(cleanDate);
			if (earliestPublicationDate == null || pubDateLong < earliestPublicationDate){
				earliestPublicationDate = pubDateLong;
			}
		}
	}

	public void addLiteraryForms(HashSet<String> literaryForms) {
		for (String curLiteraryForm : literaryForms){
			this.addLiteraryForm(curLiteraryForm);
		}
	}

	public void addLiteraryForms(HashMap<String, Integer> literaryForms) {
		for (String curLiteraryForm : literaryForms.keySet()){
			this.addLiteraryForm(curLiteraryForm, literaryForms.get(curLiteraryForm));
		}
	}

	public void addLiteraryForm(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (this.literaryForm.containsKey(literaryForm)){
			Integer numMatches = this.literaryForm.get(literaryForm);
			this.literaryForm.put(literaryForm, numMatches + count);
		}else{
			this.literaryForm.put(literaryForm, count);
		}
	}

	public void addLiteraryForm(String literaryForm) {
		addLiteraryForm(literaryForm, 1);
	}

	public void addLiteraryFormsFull(HashMap<String, Integer> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull.keySet()){
			this.addLiteraryFormFull(curLiteraryForm, literaryFormsFull.get(curLiteraryForm));
		}
	}

	public void addLiteraryFormsFull(HashSet<String> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull){
			this.addLiteraryFormFull(curLiteraryForm);
		}
	}

	public void addLiteraryFormFull(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (this.literaryFormFull.containsKey(literaryForm)){
			Integer numMatches = this.literaryFormFull.get(literaryForm);
			this.literaryFormFull.put(literaryForm, numMatches + count);
		}else{
			this.literaryFormFull.put(literaryForm, count);
		}
	}

	public void addLiteraryFormFull(String literaryForm) {
		this.addLiteraryFormFull(literaryForm, 1);
	}

	public void addTargetAudiences(HashSet<String> target_audience) {
		targetAudience.addAll(target_audience);
	}

	public void addTargetAudience(String target_audience) {
		targetAudience.add(target_audience);
	}

	public void addTargetAudiencesFull(HashSet<String> target_audience_full) {
		targetAudienceFull.addAll(target_audience_full);
	}

	public void addTargetAudienceFull(String target_audience) {
		targetAudienceFull.add(target_audience);
	}

	private Set<String> getRatingFacet(Float rating) {
		Set<String> ratingFacet = new HashSet<>();
		if (rating >= 4.75) {
			ratingFacet.add("fiveStar");
		}
		if (rating >= 4) {
			ratingFacet.add("fourStar");
		}
		if (rating >= 3) {
			ratingFacet.add("threeStar");
		}
		if (rating >= 2) {
			ratingFacet.add("twoStar");
		}
		if (rating >= 0.0001) {
			ratingFacet.add("oneStar");
		}
		if (ratingFacet.size() == 0){
			ratingFacet.add("Unrated");
		}
		return ratingFacet;
	}

	public void addMpaaRating(String mpaaRating) {
		this.mpaaRatings.add(mpaaRating);
	}

	public void addBarcodes(Set<String> barcodeList) {
		this.barcodes.addAll(barcodeList);
	}

	public void setRating(float rating) {
		this.rating = rating;
	}

	public void setLexileScore(String lexileScore) {
		this.lexileScore = lexileScore;
	}

	public void setLexileCode(String lexileCode) {
		this.lexileCode = lexileCode;
	}

	public void addAwards(Set<String> awards) {
		this.awards.addAll(awards);
	}

	public void setAcceleratedReaderInterestLevel(String acceleratedReaderInterestLevel) {
		if (acceleratedReaderInterestLevel != null){
			this.acceleratedReaderInterestLevel = acceleratedReaderInterestLevel;
		}
	}

	public void setAcceleratedReaderReadingLevel(String acceleratedReaderReadingLevel) {
		if (acceleratedReaderReadingLevel != null){
			this.acceleratedReaderReadingLevel = acceleratedReaderReadingLevel;
		}
	}

	public void setAcceleratedReaderPointValue(String acceleratedReaderPointValue) {
		if (acceleratedReaderPointValue != null){
			this.acceleratedReaderPointValue = acceleratedReaderPointValue;
		}
	}

	public void addAllFields(String fields){
		allFields += " " + fields;
	}

	public void setCallNumberA(String callNumber) {
		if (callNumber != null && callNumberA == null){
			this.callNumberA = callNumber;
		}
	}
	public void setCallNumberFirst(String callNumber) {
		if (callNumber != null && callNumberFirst == null){
			this.callNumberFirst = callNumber;
		}
	}
	public void setCallNumberSubject(String callNumber) {
		if (callNumber != null && callNumberSubject == null){
			this.callNumberSubject = callNumber;
		}
	}

	public void addEContentDevices(HashSet<String> devices){
		this.econtentDevices.addAll(devices);
	}

	public void addKeywords(String keywords){
		this.keywords.add(keywords);
	}

	public void addDescription(String description, @NotNull String recordFormat){
		if (description == null || description.length() == 0){
			return;
		}
		this.description.add(description);
		if (this.displayDescription.length() == 0){
			this.displayDescription = description;
			this.displayDescriptionFormat = recordFormat;
		}else{
			//Only overwrite if we get a better format
			if (recordFormat.equals("Book") || recordFormat.equals("eBook") ){
				if (description.length() > this.displayDescription.length()){
					this.displayDescription = description;
					this.displayDescriptionFormat = recordFormat;
				}
			} else if (!displayDescriptionFormat.equals("Book") && !displayDescriptionFormat.equals("eBook")){
				if (description.length() > this.displayDescription.length()) {
					this.displayDescription = description;
					this.displayDescriptionFormat = recordFormat;
				}
			}
		}
	}

	public RecordInfo addRelatedRecord(String source, String recordIdentifier){
		String recordIdentifierWithType = source + ":" + recordIdentifier;
		if (relatedRecords.containsKey(recordIdentifierWithType)){
			return relatedRecords.get(recordIdentifierWithType);
		}else {
			RecordInfo newRecord = new RecordInfo(source, recordIdentifier);
			relatedRecords.put(recordIdentifierWithType, newRecord);
			return newRecord;
		}
	}

	public void addLCSubjects(Set<String> lcSubjects) {
		this.lcSubjects.addAll(lcSubjects);
	}

	public void addBisacSubjects(Set<String> bisacSubjects) {
		this.bisacSubjects.addAll(bisacSubjects);
	}

	public void addSystemLists(Set<String> systemLists) {
		this.systemLists.addAll(systemLists);
	}

	public void removeRelatedRecord(RecordInfo recordInfo) {
		this.relatedRecords.remove(recordInfo.getFullIdentifier());
	}

	public void updateIndexingStats(TreeMap<String, ScopedIndexingStats> indexingStats) {
		//Update total works
		for (Scope scope: groupedWorkIndexer.getScopes()){
			HashSet<RecordInfo> relatedRecordsForScope = new HashSet<>();
			HashSet<ItemInfo> relatedItems = new HashSet<>();
			loadRelatedRecordsAndItemsForScope(scope, relatedRecordsForScope, relatedItems);
			if (relatedRecordsForScope.size() > 0){
				ScopedIndexingStats stats = indexingStats.get(scope.getScopeName());
				stats.numTotalWorks++;
				if (isLocallyOwned(relatedItems, scope) || isLibraryOwned(relatedItems, scope)){
					stats.numLocalWorks++;
				}
			}
		}
		//Update stats based on individual record processor
		for (RecordInfo curRecord : relatedRecords.values()){
			curRecord.updateIndexingStats(indexingStats);
		}
	}

}
