package org.marmot;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Types;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.zip.CRC32;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;

import org.apache.commons.codec.binary.Base64;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

public class ExtractOverDriveInfo {
	private static Logger logger = Logger.getLogger(ExtractOverDriveInfo.class);
	private Connection econtentConn;
	private OverDriveExtractLogEntry results;

	private Long lastExtractTime;
	private String lastUpdateTimeParam = "";
	
	//Overdrive API information
	private String clientSecret;
	private String clientKey;
	private String accountId;
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	private String overDriveProductsKey;
	private boolean forceMetaDataUpdate;
	private HashMap<Long, String> libToOverDriveAPIKeyMap = new HashMap<Long, String>();
	private HashMap<String, Long> overDriveFormatMap = new HashMap<String, Long>();
	
	private HashMap<String, OverDriveRecordInfo> overDriveTitles = new HashMap<String, OverDriveRecordInfo>();
	private HashMap<String, Long> advantageCollectionToLibMap = new HashMap<String, Long>();
	private HashMap<String, OverDriveDBInfo> databaseProducts = new HashMap<String, OverDriveDBInfo>();
	private HashMap<String, Long> existingLanguageIds = new HashMap<String, Long>();
	private HashMap<String, Long> existingSubjectIds = new HashMap<String, Long>();
	
	private PreparedStatement addProductStmt;
	private PreparedStatement updateProductStmt;
	private PreparedStatement deleteProductStmt;
	private PreparedStatement updateProductMetadataStmt;
	private PreparedStatement loadMetaDataStmt;
	private PreparedStatement addMetaDataStmt;
	private PreparedStatement updateMetaDataStmt;
	private PreparedStatement clearCreatorsStmt;
	private PreparedStatement addCreatorStmt;
	private PreparedStatement addLanguageStmt;
	private PreparedStatement clearLanguageRefStmt;
	private PreparedStatement addLanguageRefStmt;
	private PreparedStatement addSubjectStmt;
	private PreparedStatement clearSubjectRefStmt;
	private PreparedStatement addSubjectRefStmt;
	private PreparedStatement clearFormatsStmt;
	private PreparedStatement addFormatStmt;
	private PreparedStatement clearIdentifiersStmt;
	private PreparedStatement addIdentifierStmt;
	private PreparedStatement checkForExistingAvailabilityStmt;
	private PreparedStatement updateAvailabilityStmt;
	private PreparedStatement addAvailabilityStmt;
	private PreparedStatement deleteAvailabilityStmt;
	private PreparedStatement updateProductAvailabilityStmt;
	private boolean hadTimeoutsFromOverDrive;
	
	private CRC32 checksumCalculator = new CRC32();
	private boolean errorOnLastOverDriveCall;
	private boolean errorsWhileLoadingProducts;

	public void extractOverDriveInfo(Ini configIni, Connection vufindConn, Connection econtentConn, OverDriveExtractLogEntry logEntry, boolean doFullReload) {
		this.econtentConn = econtentConn;
		this.results = logEntry;

		try {
			addProductStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_products set overdriveid = ?, mediaType = ?, title = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateAdded = ?, dateUpdated = ?, lastMetadataCheck = 0, lastMetadataChange = 0, lastAvailabilityCheck = 0, lastAvailabilityChange = 0, rawData=?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateProductStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET mediaType = ?, title = ?, series = ?, primaryCreatorRole = ?, primaryCreatorName = ?, cover = ?, dateUpdated = ?, deleted = 0, rawData=? where id = ?");
			deleteProductStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET deleted = 1, dateDeleted = ? where id = ?");
			updateProductMetadataStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET lastMetadataCheck = ?, lastMetadataChange = ? where id = ?");
			loadMetaDataStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_metadata WHERE productId = ?");
			updateMetaDataStmt = econtentConn.prepareStatement("UPDATE overdrive_api_product_metadata set productId = ?, checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, starRating = ?, popularity =?, thumbnail=?, cover=?, rawData=? where id = ?");
			addMetaDataStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_metadata set productId = ?, checksum = ?, sortTitle = ?, publisher = ?, publishDate = ?, isPublicDomain = ?, isPublicPerformanceAllowed = ?, shortDescription = ?, fullDescription = ?, starRating = ?, popularity =?, thumbnail=?, cover=?, rawData=?");
			clearCreatorsStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_creators WHERE productId = ?");
			addCreatorStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_creators SET productId = ?, role = ?, name = ?, fileAs = ?");
			PreparedStatement loadLanguagesStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_languages");
			addLanguageStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_languages set code =?, name = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			clearLanguageRefStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_languages_ref where productId = ?");
			addLanguageRefStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_languages_ref set productId = ?, languageId = ?");
			PreparedStatement loadSubjectsStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_subjects");
			addSubjectStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_subjects set name = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			clearSubjectRefStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_subjects_ref where productId = ?");
			addSubjectRefStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_subjects_ref set productId = ?, subjectId = ?");
			clearFormatsStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_formats where productId = ?");
			addFormatStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_formats set productId = ?, textId = ?, numericId = ?, name = ?, fileName = ?, fileSize = ?, partCount = ?, sampleSource_1 = ?, sampleUrl_1 = ?, sampleSource_2 = ?, sampleUrl_2 = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			clearIdentifiersStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_identifiers where productId = ?");
			addIdentifierStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_identifiers set productId = ?, type = ?, value = ?");
			checkForExistingAvailabilityStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ? and libraryId = ?");
			updateAvailabilityStmt = econtentConn.prepareStatement("UPDATE overdrive_api_product_availability set available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ? WHERE id = ?");
			addAvailabilityStmt = econtentConn.prepareStatement("INSERT INTO overdrive_api_product_availability set productId = ?, libraryId = ?, available = ?, copiesOwned = ?, copiesAvailable = ?, numberOfHolds = ?");
			deleteAvailabilityStmt = econtentConn.prepareStatement("DELETE FROM overdrive_api_product_availability where id = ?");
			updateProductAvailabilityStmt = econtentConn.prepareStatement("UPDATE overdrive_api_products SET lastAvailabilityCheck = ?, lastAvailabilityChange = ? where id = ?");

			//Get the last time we extracted data from OverDrive
			if (!doFullReload){
				PreparedStatement getVariableStatement = vufindConn.prepareStatement("SELECT * FROM variables where name = 'last_overdrive_extract_time'");
				ResultSet lastExtractTimeRS = getVariableStatement.executeQuery();
				if (lastExtractTimeRS.next()){
					lastExtractTime = lastExtractTimeRS.getLong("value");
					Date lastExtractDate = new Date(lastExtractTime);
					SimpleDateFormat lastUpdateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
					logger.debug("Loading all records that have changed since " + lastUpdateFormat.format(lastExtractDate));
					lastUpdateTimeParam = "lastupdatetime=" + lastUpdateFormat.format(lastExtractDate);
					//Simple Date Format doesn't give us quite the right timezone format so adjust
					lastUpdateTimeParam = lastUpdateTimeParam.substring(0, lastUpdateTimeParam.length() - 2) + ":" + lastUpdateTimeParam.substring(lastUpdateTimeParam.length() - 2);
				}
			}
			//Update the last extract time
			Long extractStartTime = new Date().getTime();
			
			ResultSet loadLanguagesRS = loadLanguagesStmt.executeQuery();
			while (loadLanguagesRS.next()){
				existingLanguageIds.put(loadLanguagesRS.getString("code"), loadLanguagesRS.getLong("id"));
			}
			
			ResultSet loadSubjectsRS = loadSubjectsStmt.executeQuery();
			while (loadSubjectsRS.next()){
				existingSubjectIds.put(loadSubjectsRS.getString("name"), loadSubjectsRS.getLong("id"));
			}
			
			PreparedStatement advantageCollectionMapStmt = vufindConn.prepareStatement("SELECT libraryId, overdriveAdvantageName, overdriveAdvantageProductsKey FROM library where overdriveAdvantageName > ''");
			ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery();
			while (advantageCollectionMapRS.next()){
				advantageCollectionToLibMap.put(advantageCollectionMapRS.getString(2), advantageCollectionMapRS.getLong(1));
				libToOverDriveAPIKeyMap.put(advantageCollectionMapRS.getLong(1), advantageCollectionMapRS.getString(3));
			}
			
			//Load products from API 
			clientSecret = configIni.get("OverDrive", "clientSecret");
			clientKey = configIni.get("OverDrive", "clientKey");
			accountId = configIni.get("OverDrive", "accountId");
			
			overDriveProductsKey = configIni.get("OverDrive", "productsKey");
			if (overDriveProductsKey == null){
				logger.warn("Warning no products key provided for OverDrive");
			}
			String forceMetaDataUpdateStr = configIni.get("OverDrive", "forceMetaDataUpdate");
			forceMetaDataUpdate = forceMetaDataUpdateStr != null && Boolean.parseBoolean(forceMetaDataUpdateStr);
			
			overDriveFormatMap.put("ebook-epub-adobe", 410L);
			overDriveFormatMap.put("ebook-kindle", 420L);
			overDriveFormatMap.put("Microsoft eBook", 1L);
			overDriveFormatMap.put("audiobook-wma", 25L);
			overDriveFormatMap.put("audiobook-mp3", 425L);
			overDriveFormatMap.put("audiobook-overdrive", 625L);
			overDriveFormatMap.put("music-wma", 30L);
			overDriveFormatMap.put("video-wmv", 35L);
			overDriveFormatMap.put("ebook-pdf-adobe", 50L);
			overDriveFormatMap.put("Palm", 150L);
			overDriveFormatMap.put("Mobipocket eBook", 90L);
			overDriveFormatMap.put("Disney Online Book", 302L);
			overDriveFormatMap.put("ebook-pdf-open", 450L);
			overDriveFormatMap.put("ebook-epub-open", 810L);
			overDriveFormatMap.put("ebook-overdrive", 610L);
			overDriveFormatMap.put("video-streaming", 635L);
			overDriveFormatMap.put("periodicals-nook", 304L);
			overDriveFormatMap.put("ebook-mediado", 303L);
			
			if (clientSecret == null || clientKey == null || accountId == null || clientSecret.length() == 0 || clientKey.length() == 0 || accountId.length() == 0){
				logEntry.addNote("Did not find correct configuration in config.ini, not loading overdrive titles");
			}else{
				//Load products from database
				if (!loadProductsFromDatabase()){
					return;
				}
				
				//Load products from API
				if (!loadProductsFromAPI()){
					return;
				}
				
				//Update products in database
				updateDatabase();
			}

			//Mark the new last update time if we did not get errors loading products from the database
			if (errorsWhileLoadingProducts){
				logger.error("Not setting last extract time since there were problems extracting products from the API");
			}else{
				PreparedStatement updateExtractTime;
				if (lastExtractTime == null){
					updateExtractTime = vufindConn.prepareStatement("INSERT INTO variables set value = ?, name = 'last_overdrive_extract_time'");
				}else{
					updateExtractTime = vufindConn.prepareStatement("UPDATE variables set value = ? where name = 'last_overdrive_extract_time'");
				}
				updateExtractTime.setLong(1, extractStartTime);
				updateExtractTime.executeUpdate();
				logger.debug("Setting last extract time to " + lastExtractTime + " " + new Date(extractStartTime).toString());
			}
		} catch (SQLException e) {
		// handle any errors
			logger.error("Error initializing overdrive extraction", e);
			results.addNote("Error initializing overdrive extraction " + e.toString());
			results.incErrors();
			results.saveResults();
		}
	}
	
	private void updateDatabase() {
		int numProcessed = 0;
		for (String overDriveId : overDriveTitles.keySet()){
			OverDriveRecordInfo overDriveInfo = overDriveTitles.get(overDriveId);
			//Check to see if the title already exists within the database.
			if (databaseProducts.containsKey(overDriveId)){
				updateProductInDB(overDriveInfo, databaseProducts.get(overDriveId));
				databaseProducts.remove(overDriveId);
			}else{
				addProductToDB(overDriveInfo);
			}
			results.saveResults();
			numProcessed++;
			if (numProcessed % 100 == 0){
				logger.debug("Processed " + numProcessed + " products from the API");
			}
		}
		
		//Delete any products that no longer exist, but only if we aren't only loading changes and also
		//should not update if we had any timeouts loading products since those products would have been skipped.
		if (lastUpdateTimeParam.length() == 0 && !hadTimeoutsFromOverDrive){
			for (String overDriveId : databaseProducts.keySet()){
				OverDriveDBInfo overDriveDBInfo = databaseProducts.get(overDriveId);
				if (!overDriveDBInfo.isDeleted()){
					deleteProductInDB(databaseProducts.get(overDriveId));
				}
			}
		}
	}

	private void deleteProductInDB(OverDriveDBInfo overDriveDBInfo) {
		try {
			long curTime = new Date().getTime() / 1000;
			deleteProductStmt.setLong(1, curTime);
			deleteProductStmt.setLong(2, overDriveDBInfo.getDbId());
			deleteProductStmt.executeUpdate();
			results.incDeleted();
		} catch (SQLException e) {
			logger.error("Error deleting overdrive product " + overDriveDBInfo.getDbId(), e);
			results.addNote("Error deleting overdrive product " + overDriveDBInfo.getDbId() + e.toString());
			results.incErrors();
			results.saveResults();
		}
	}

	private void updateProductInDB(OverDriveRecordInfo overDriveInfo,
			OverDriveDBInfo overDriveDBInfo) {
		try {
			boolean updateMade = false;
			//Check to see if anything has changed.  If so, perform necessary updates. 
			if (!Util.compareStrings(overDriveInfo.getMediaType(), overDriveDBInfo.getMediaType()) || 
					!Util.compareStrings(overDriveInfo.getTitle(), overDriveDBInfo.getTitle()) || 
					!Util.compareStrings(overDriveInfo.getSeries(), overDriveDBInfo.getSeries()) ||
					!Util.compareStrings(overDriveInfo.getPrimaryCreatorRole(), overDriveDBInfo.getPrimaryCreatorRole()) ||
					!Util.compareStrings(overDriveInfo.getPrimaryCreatorName(), overDriveDBInfo.getPrimaryCreatorName()) ||
					!Util.compareStrings(overDriveInfo.getCoverImage(), overDriveDBInfo.getCover()) ||
					overDriveDBInfo.isDeleted() ||
					!overDriveDBInfo.hasRawData()
					){
				//Update the product in the database
				long curTime = new Date().getTime() / 1000;
				int curCol = 0;
				updateProductStmt.setString(++curCol, overDriveInfo.getMediaType());
				updateProductStmt.setString(++curCol, overDriveInfo.getTitle());
				updateProductStmt.setString(++curCol, overDriveInfo.getSeries());
				updateProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorRole());
				updateProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorName());
				updateProductStmt.setString(++curCol, overDriveInfo.getCoverImage());
				updateProductStmt.setLong(++curCol, curTime);
				updateProductStmt.setString(++curCol, overDriveInfo.getRawData());
				updateProductStmt.setLong(++curCol, overDriveDBInfo.getDbId());
				
				updateProductStmt.executeUpdate();
				updateMade = true;
			}
			
			boolean metadataChanged = updateOverDriveMetaData(overDriveInfo, overDriveDBInfo.getDbId(), overDriveDBInfo);
			boolean availabilityChanged = updateOverDriveAvailability(overDriveInfo, overDriveDBInfo.getDbId(), overDriveDBInfo);
			
			if (updateMade || availabilityChanged || metadataChanged){
				results.incUpdated();
			}else{
				results.incSkipped();
			}
			
		} catch (SQLException e) {
			logger.error("Error updating overdrive product " + overDriveInfo.getId(), e);
			results.addNote("Error updating overdrive product " + overDriveInfo.getId() + e.toString());
			results.incErrors();
			results.saveResults();
		}
		
	}

	private void addProductToDB(OverDriveRecordInfo overDriveInfo) {
		int curCol = 0;
		try {
			long curTime = new Date().getTime() / 1000;
			addProductStmt.setString(++curCol, overDriveInfo.getId());
			addProductStmt.setString(++curCol, overDriveInfo.getMediaType());
			addProductStmt.setString(++curCol, overDriveInfo.getTitle());
			addProductStmt.setString(++curCol, overDriveInfo.getSeries());
			addProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorRole());
			addProductStmt.setString(++curCol, overDriveInfo.getPrimaryCreatorName());
			addProductStmt.setString(++curCol, overDriveInfo.getCoverImage());
			addProductStmt.setLong(++curCol, curTime);
			addProductStmt.setLong(++curCol, curTime);
			addProductStmt.setString(++curCol, overDriveInfo.getRawData());
			addProductStmt.executeUpdate();
			
			ResultSet newIdRS = addProductStmt.getGeneratedKeys();
			newIdRS.next();
			long databaseId = newIdRS.getLong(1);

			results.incAdded();

			//Update metadata based information
			updateOverDriveMetaData(overDriveInfo, databaseId, null);
			updateOverDriveAvailability(overDriveInfo, databaseId, null);
			
		} catch (SQLException e) {
			logger.error("Error saving product " + overDriveInfo.getId() + " to the database", e);
			results.addNote("Error saving product " + overDriveInfo.getId() + " to the database " + e.toString());
			results.incErrors();
			results.saveResults();
		}
	}

	private boolean loadProductsFromDatabase() {
		try {
			PreparedStatement loadProductsStmt = econtentConn.prepareStatement("Select * from overdrive_api_products");
			ResultSet loadProductsRS = loadProductsStmt.executeQuery();
			while (loadProductsRS.next()){
				String overdriveId = loadProductsRS.getString("overdriveId").toLowerCase();
				OverDriveDBInfo curProduct = new OverDriveDBInfo();
				curProduct.setDbId(loadProductsRS.getLong("id"));
				curProduct.setMediaType(loadProductsRS.getString("mediaType"));
				curProduct.setSeries(loadProductsRS.getString("series"));
				curProduct.setTitle(loadProductsRS.getString("title"));
				curProduct.setPrimaryCreatorRole(loadProductsRS.getString("primaryCreatorRole"));
				curProduct.setPrimaryCreatorName(loadProductsRS.getString("primaryCreatorName"));
				curProduct.setCover(loadProductsRS.getString("cover"));
				curProduct.setLastAvailabilityCheck(loadProductsRS.getLong("lastAvailabilityCheck"));
				curProduct.setLastAvailabilityChange(loadProductsRS.getLong("lastAvailabilityChange"));
				curProduct.setLastMetadataCheck(loadProductsRS.getLong("lastMetadataCheck"));
				curProduct.setLastMetadataChange(loadProductsRS.getLong("lastMetadataChange"));
				curProduct.setDeleted(loadProductsRS.getLong("deleted") == 1);
				String rawData = loadProductsRS.getString("rawData");
				curProduct.setHasRawData(rawData != null && rawData.length() > 0);
				databaseProducts.put(overdriveId, curProduct);
			}
			return true;
		} catch (SQLException e) {
			logger.error("Error loading products from database", e);
			results.addNote("Error loading products from database " + e.toString());
			results.incErrors();
			results.saveResults();
			return false;
		}
		
	}
	private boolean loadProductsFromAPI() {
		JSONObject libraryInfo = callOverDriveURL("http://api.overdrive.com/v1/libraries/" + accountId);
		try {
			String libraryName = libraryInfo.getString("name");
			String mainProductUrl = libraryInfo.getJSONObject("links").getJSONObject("products").getString("href");
			if (lastUpdateTimeParam.length() > 0){
				if (mainProductUrl.contains("?")){
					mainProductUrl += "&" + lastUpdateTimeParam;
				}else{
					mainProductUrl += "?" + lastUpdateTimeParam;
				}
			}
			loadProductsFromUrl(libraryName, mainProductUrl);
			logger.debug("loaded " + overDriveTitles.size() + " overdrive titles in shared collection");
			//Get a list of advantage collections
			if (libraryInfo.getJSONObject("links").has("advantageAccounts")){
				JSONObject advantageInfo = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
				if (advantageInfo.has("advantageAccounts")){
					JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
					for (int i = 0; i < advantageAccounts.length(); i++){
						JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
						String advantageSelfUrl = curAdvantageAccount.getJSONObject("links").getJSONObject("self").getString("href");
						JSONObject advantageSelfInfo = callOverDriveURL(advantageSelfUrl);
						String advantageName = curAdvantageAccount.getString("name");
						String productUrl = advantageSelfInfo.getJSONObject("links").getJSONObject("products").getString("href");
						if (lastUpdateTimeParam.length() > 0){
							if (productUrl.contains("?")){
								productUrl += "&" + lastUpdateTimeParam;
							}else{
								productUrl += "?" + lastUpdateTimeParam;
							}
						}

						loadProductsFromUrl(advantageName, productUrl);
					}
				}else{
					results.addNote("The API indicate that the library has advantage accounts, but none were returned from " + libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					results.incErrors();
				}
				logger.debug("loaded " + overDriveTitles.size() + " overdrive titles in shared collection and advantage collections");
			}
			results.setNumProducts(overDriveTitles.size());
			return true;
		} catch (Exception e) {
			results.addNote("error loading information from OverDrive API " + e.toString());
			results.incErrors();
			logger.error("Error loading overdrive titles", e);
			return false;
		}
	}
	
	private void loadProductsFromUrl(String libraryName, String mainProductUrl) throws JSONException {
		JSONObject productInfo = callOverDriveURL(mainProductUrl);
		if (errorOnLastOverDriveCall){
			errorsWhileLoadingProducts = true;
		}
		if (productInfo == null){
			return;
		}
		long numProducts = productInfo.getLong("totalItems");
		//if (numProducts > 50) numProducts = 50;
		logger.debug(libraryName + " collection has " + numProducts + " products in it");
		results.addNote("Loading OverDrive information for " + libraryName);
		results.saveResults();
		long batchSize = 300;
		Long libraryId = getLibraryIdForOverDriveAccount(libraryName);
		for (int i = 0; i < numProducts; i += batchSize){
			logger.debug("Processing " + libraryName + " batch from " + i + " to " + (i + batchSize));
			String batchUrl = mainProductUrl;
			if (mainProductUrl.contains("?")){
				batchUrl += "&";
			} else{
				batchUrl += "?";
			}
			batchUrl += "offset=" + i + "&limit=" + batchSize;
			JSONObject productBatchInfo = callOverDriveURL(batchUrl);
			if (productBatchInfo != null){
				JSONArray products = productBatchInfo.getJSONArray("products");
				logger.debug(" Found " + products.length() + " products");
				for(int j = 0; j <products.length(); j++ ){
					JSONObject curProduct = products.getJSONObject(j);
					OverDriveRecordInfo curRecord = loadOverDriveRecordFromJSON(libraryName, curProduct);
					if (overDriveTitles.containsKey(curRecord.getId().toLowerCase())){
						OverDriveRecordInfo oldRecord = overDriveTitles.get(curRecord.getId().toLowerCase());
						oldRecord.getCollections().add(libraryId);
					}else{
						//logger.debug("Loading record " + curRecord.getId());
						overDriveTitles.put(curRecord.getId().toLowerCase(), curRecord);
					}
				}
			}
		}
	}
	
	private OverDriveRecordInfo loadOverDriveRecordFromJSON(String libraryName, JSONObject curProduct) throws JSONException {
		OverDriveRecordInfo curRecord = new OverDriveRecordInfo();
		curRecord.setId(curProduct.getString("id"));
		//logger.debug("Processing overdrive title " + curRecord.getId());
		curRecord.setTitle(curProduct.getString("title"));
		curRecord.setMediaType(curProduct.getString("mediaType"));
		if (curProduct.has("series")){
			curRecord.setSeries(curProduct.getString("series"));
		}
		if (curProduct.has("primaryCreator")){
			curRecord.setPrimaryCreatorName(curProduct.getJSONObject("primaryCreator").getString("name"));
			curRecord.setPrimaryCreatorRole(curProduct.getJSONObject("primaryCreator").getString("role"));
		}
		if (curProduct.has("formats")){
			for (int k = 0; k < curProduct.getJSONArray("formats").length(); k++){
				curRecord.getFormats().add(curProduct.getJSONArray("formats").getJSONObject(k).getString("id"));
			}
		}
		if (curProduct.has("images") && curProduct.getJSONObject("images").has("thumbnail")){
			String thumbnailUrl = curProduct.getJSONObject("images").getJSONObject("thumbnail").getString("href");
			curRecord.setCoverImage(thumbnailUrl);
		}
		curRecord.getCollections().add(getLibraryIdForOverDriveAccount(libraryName));
		curRecord.setRawData(curProduct.toString(2));
		return curRecord;
	}
	
	private Long getLibraryIdForOverDriveAccount(String libraryName) {
		if (advantageCollectionToLibMap.containsKey(libraryName)){
			return advantageCollectionToLibMap.get(libraryName);
		}
		return -1L;
	}
	
	private boolean updateOverDriveMetaData(OverDriveRecordInfo overDriveInfo, long databaseId, OverDriveDBInfo dbInfo) {
		//Check to see if we need to load metadata 
		long curTime = new Date().getTime() / 1000;
		//Don't need to load metadata if we already have metadata and the metadata was checked within the last 24 hours
		if ((dbInfo != null && dbInfo.getLastMetadataCheck() >= curTime - 24 * 60 * 60) && !forceMetaDataUpdate){
			return false;
		}
		
		//load metadata information for the product from the database
		OverDriveDBMetaData databaseMetaData = loadMetadataFromDatabase(databaseId);
		
		//Get the url to call for meta data information (based on the first owning collection)
		long firstCollection = overDriveInfo.getCollections().iterator().next();
		String apiKey;
		if (firstCollection == -1L){
			apiKey = overDriveProductsKey;
		}else{
			apiKey = libToOverDriveAPIKeyMap.get(firstCollection);
		}
		if (apiKey == null){
			logger.error("Unable to get api key for collection " + firstCollection);
		}
		String url = "http://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/metadata";
		JSONObject metaData = callOverDriveURL(url);
		if (metaData == null){
			logger.error("Could not load metadata from " + url);
			return false;
		}else{
			checksumCalculator.reset();
			checksumCalculator.update(metaData.toString().getBytes());
			long metadataChecksum = checksumCalculator.getValue();
			boolean updateMetaData = false;
			if (dbInfo == null || forceMetaDataUpdate){
				updateMetaData = true;
			}else{
				if (!databaseMetaData.hasRawData()){
					updateMetaData = true;
				}else if (metadataChecksum != databaseMetaData.getChecksum()){
					//The metadata has definitely changed.
					updateMetaData = true;
				}else if (dbInfo.getLastMetadataCheck() <= curTime - 14 * 24 * 60 * 60){
					//If it's been two weeks since we last updated, give a 20% chance of updating
					//Don't update everything at once to spread out the number of calls and reduce time.
					double randomNumber = Math.random() * 100;
					if (randomNumber <= 20.0){
						updateMetaData = true;
					}
				}
			}
			if (updateMetaData){
				try {
					int curCol = 0;
					PreparedStatement metaDataStatement = addMetaDataStmt;
					if (databaseMetaData.getId() != -1){
						metaDataStatement = updateMetaDataStmt;
					}
					metaDataStatement.setLong(++curCol, databaseId);
					metaDataStatement.setLong(++curCol, metadataChecksum);
					metaDataStatement.setString(++curCol, metaData.has("sortTitle") ? metaData.getString("sortTitle") : "");
					metaDataStatement.setString(++curCol, metaData.has("publisher") ? metaData.getString("publisher") : "");
					//Grab the textual version of publish date rather than the actual date
					if (metaData.has("publishDateText")){
						String publishDateText = metaData.getString("publishDateText");
						if (publishDateText.matches("\\d{2}/\\d{2}/\\d{4}")){
							publishDateText = publishDateText.substring(6, 10);
							metaDataStatement.setLong(++curCol, Long.parseLong(publishDateText));
						}else{
							metaDataStatement.setNull(++curCol, Types.INTEGER);
						}
					}else{
						metaDataStatement.setNull(++curCol, Types.INTEGER);
					}

					metaDataStatement.setBoolean(++curCol, metaData.has("isPublicDomain") && metaData.getBoolean("isPublicDomain"));
					metaDataStatement.setBoolean(++curCol, metaData.has("isPublicPerformanceAllowed") && metaData.getBoolean("isPublicPerformanceAllowed"));
					metaDataStatement.setString(++curCol, metaData.has("shortDescription") ? metaData.getString("shortDescription") : "");
					metaDataStatement.setString(++curCol, metaData.has("fullDescription") ? metaData.getString("fullDescription") : "");
					metaDataStatement.setDouble(++curCol, metaData.has("starRating") ? metaData.getDouble("starRating") : 0);
					metaDataStatement.setInt(++curCol, metaData.has("popularity") ? metaData.getInt("popularity") : 0);
					String thumbnail = "";
					String cover = "";
					if (metaData.has("images")){
						JSONObject imagesData = metaData.getJSONObject("images");
						if (imagesData.has("thumbnail")){
							thumbnail = imagesData.getJSONObject("thumbnail").getString("href");
						}
						if (imagesData.has("cover")){
							cover = imagesData.getJSONObject("cover").getString("href");
						}
					}
					metaDataStatement.setString(++curCol, thumbnail);
					metaDataStatement.setString(++curCol, cover);
					metaDataStatement.setString(++curCol, metaData.toString(2));

					if (databaseMetaData.getId() != -1){
						metaDataStatement.setLong(++curCol, databaseMetaData.getId());
					}
					metaDataStatement.executeUpdate();
					
					clearCreatorsStmt.setLong(1, databaseId);
					clearCreatorsStmt.executeUpdate();
					if (metaData.has("creators")){
						JSONArray contributors = metaData.getJSONArray("creators");
						for (int i = 0; i < contributors.length(); i++){
							JSONObject contributor = contributors.getJSONObject(i);
							addCreatorStmt.setLong(1, databaseId);
							addCreatorStmt.setString(2, contributor.getString("role"));
							addCreatorStmt.setString(3, contributor.getString("name"));
							addCreatorStmt.setString(4, contributor.getString("fileAs"));
							addCreatorStmt.executeUpdate();
						}
					}
					
					clearLanguageRefStmt.setLong(1, databaseId);
					clearLanguageRefStmt.executeUpdate();
					if (metaData.has("languages")){
						JSONArray languages = metaData.getJSONArray("languages");
						for (int i = 0; i < languages.length(); i++){
							JSONObject language = languages.getJSONObject(i);
							String code = language.getString("code");
							long languageId;
							if (existingLanguageIds.containsKey(code)){
								languageId = existingLanguageIds.get(code);
							}else{
								addLanguageStmt.setString(1, code);
								addLanguageStmt.setString(2, language.getString("name"));
								addLanguageStmt.executeUpdate();
								ResultSet keys = addLanguageStmt.getGeneratedKeys();
								keys.next();
								languageId = keys.getLong(1);
								existingLanguageIds.put(code, languageId);
							}
							addLanguageRefStmt.setLong(1, databaseId);
							addLanguageRefStmt.setLong(2, languageId);
							addLanguageRefStmt.executeUpdate();
						}
					}
					
					clearSubjectRefStmt.setLong(1, databaseId);
					clearSubjectRefStmt.executeUpdate();
					if (metaData.has("subjects")){
						JSONArray subjects = metaData.getJSONArray("subjects");
						for (int i = 0; i < subjects.length(); i++){
							JSONObject subject = subjects.getJSONObject(i);
							String curSubject = subject.getString("value");
							long subjectId;
							if (existingSubjectIds.containsKey(curSubject)){
								subjectId = existingSubjectIds.get(curSubject);
							}else{
								addSubjectStmt.setString(1, curSubject);
								addSubjectStmt.executeUpdate();
								ResultSet keys = addSubjectStmt.getGeneratedKeys();
								keys.next();
								subjectId = keys.getLong(1);
								existingSubjectIds.put(curSubject, subjectId);
							}
							addSubjectRefStmt.setLong(1, databaseId);
							addSubjectRefStmt.setLong(2, subjectId);
							addSubjectRefStmt.executeUpdate();
						}
					}
					
					clearFormatsStmt.setLong(1, databaseId);
					clearFormatsStmt.executeUpdate();
					clearIdentifiersStmt.setLong(1, databaseId);
					clearIdentifiersStmt.executeUpdate();
					if (metaData.has("formats")){
						JSONArray formats = metaData.getJSONArray("formats");
						HashSet<String> uniqueIdentifiers = new HashSet<String>();
						for (int i = 0; i < formats.length(); i++){
							JSONObject format = formats.getJSONObject(i);
							addFormatStmt.setLong(1, databaseId);
							String textFormat = format.getString("id");
							addFormatStmt.setString(2, textFormat);
							Long numericFormat = overDriveFormatMap.get(textFormat);
							if (numericFormat == null){
								logger.error("Could not find numeric format for format " + textFormat);
								results.addNote("Could not find numeric format for format " + textFormat);
								results.incErrors();
								System.out.println("Warning: new format for OverDrive found " + textFormat);
								continue;
							}
							addFormatStmt.setLong(3, numericFormat);
							addFormatStmt.setString(4, format.getString("name"));
							addFormatStmt.setString(5, format.has("filename") ? format.getString("fileName") : "");
							addFormatStmt.setLong(6, format.has("fileSize") ? format.getLong("fileSize") : 0L);
							addFormatStmt.setLong(7, format.has("partCount") ? format.getLong("partCount") : 0L);

							if (format.has("identifiers")){
								JSONArray identifiers = format.getJSONArray("identifiers");
								for (int j = 0; j < identifiers.length(); j++){
									JSONObject identifier = identifiers.getJSONObject(j);
									uniqueIdentifiers.add(identifier.getString("type") + ":" + identifier.getString("value"));
								}
							}
							//Default samples to null
							addFormatStmt.setString(8, null);
							addFormatStmt.setString(9, null);
							addFormatStmt.setString(10, null);
							addFormatStmt.setString(11, null);

							if (format.has("samples")){
								JSONArray samples = format.getJSONArray("samples");
								for (int j = 0; j < samples.length(); j++){
									JSONObject sample = samples.getJSONObject(j);
									if (j == 0){
										addFormatStmt.setString(8, sample.getString("source"));
										addFormatStmt.setString(9, sample.getString("url"));
									}else if (j == 1){
										addFormatStmt.setString(10, sample.getString("source"));
										addFormatStmt.setString(11, sample.getString("url"));
									}else{
										logger.warn("Record " + overDriveInfo.getId() + " had more than 2 samples for format " + format.getString("name"));
									}
								}
							}
							addFormatStmt.executeUpdate();
						}

						for (String curIdentifier : uniqueIdentifiers){
							addIdentifierStmt.setLong(1, databaseId);
							String[] identifierInfo = curIdentifier.split(":");
							addIdentifierStmt.setString(2, identifierInfo[0]);
							addIdentifierStmt.setString(3, identifierInfo[1]);
							addIdentifierStmt.executeUpdate();
						}
					}
					results.incMetadataChanges();
				} catch (Exception e) {
					logger.error("Error loading meta data for title ", e);
					results.addNote("Error loading meta data for title " + overDriveInfo.getId() + " " + e.toString());
					results.incErrors();
				}
			}
			try {
				updateProductMetadataStmt.setLong(1, curTime);
				if (updateMetaData){
					updateProductMetadataStmt.setLong(2, curTime);
				}else{
					updateProductMetadataStmt.setLong(2, dbInfo.getLastMetadataChange());
				}
				updateProductMetadataStmt.setLong(3, databaseId);
				updateProductMetadataStmt.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error updating product metadata summary ", e);
				results.addNote("Error updating product metadata summary " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
			return updateMetaData;
		}
	}
	
	private OverDriveDBMetaData loadMetadataFromDatabase(long databaseId) {
		OverDriveDBMetaData metaData = new OverDriveDBMetaData();
		try {
			loadMetaDataStmt.setLong(1, databaseId);
			ResultSet metaDataRS = loadMetaDataStmt.executeQuery();
			if (metaDataRS.next()){
				metaData.setId(metaDataRS.getLong("id"));
				metaData.setChecksum(metaDataRS.getLong("checksum"));
				String rawData = metaDataRS.getString("rawData");
				metaData.setHasRawData(rawData != null && rawData.length() > 0);
			}
		} catch (SQLException e) {
			logger.error("Error loading product metadata ", e);
			results.addNote("Error loading product metadata for " + databaseId + " " + e.toString());
			results.incErrors();
		}
		return metaData;
	}

	private boolean updateOverDriveAvailability(OverDriveRecordInfo overDriveInfo, long databaseId, OverDriveDBInfo dbInfo) {
		//Don't need to load availability if we already have availability and the availability was checked within the last hour
		long curTime = new Date().getTime() / 1000;
		if (dbInfo != null && dbInfo.getLastAvailabilityCheck() >= curTime - 1 * 60 * 60){
			return false;
		}

		//logger.debug("Loading availability, " + overDriveInfo.getId() + " is in " + overDriveInfo.getCollections().size() + " collections");

		boolean availabilityChanged = false;
		for (Long curCollection : overDriveInfo.getCollections()){
			try {
				//Get existing availability 
				checkForExistingAvailabilityStmt.setLong(1, databaseId);
				checkForExistingAvailabilityStmt.setLong(2, curCollection);
				
				ResultSet existingAvailabilityRS = checkForExistingAvailabilityStmt.executeQuery();
				boolean hasExistingAvailability = existingAvailabilityRS.next();
				
				String apiKey;
				if (curCollection == -1L){
					apiKey = overDriveProductsKey;
				}else{
					apiKey = libToOverDriveAPIKeyMap.get(curCollection);
				}
				if (apiKey == null){
					logger.error("Unable to get api key for collection " + curCollection);
					continue;
				}
				String url = "http://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/availability";
				JSONObject availability = callOverDriveURL(url);
				if (errorOnLastOverDriveCall){
					//We got an error calling the OverDrive API, do nothing.
					logger.error("Error loading API for product " + overDriveInfo.getId());
				}else if (availability == null){
					if (hasExistingAvailability){
						deleteAvailabilityStmt.setLong(1, existingAvailabilityRS.getLong("id"));
						deleteAvailabilityStmt.executeUpdate();
						availabilityChanged = true;
					}
				}else{
					//If availability is null, it isn't available for this collection
					try {
						boolean available = availability.has("available") && availability.getString("available").equals("true");
						int copiesOwned = availability.getInt("copiesOwned");
						int copiesAvailable = availability.getInt("copiesAvailable");
						int numberOfHolds = availability.getInt("numberOfHolds");
						if (hasExistingAvailability){
							//Check to see if the availability has changed
							if (available != existingAvailabilityRS.getBoolean("available") || 
									copiesOwned != existingAvailabilityRS.getInt("copiesOwned") || 
									copiesAvailable != existingAvailabilityRS.getInt("copiesAvailable") || 
									numberOfHolds != existingAvailabilityRS.getInt("numberOfHolds")
									){
								updateAvailabilityStmt.setBoolean(1, available);
								updateAvailabilityStmt.setInt(2, copiesOwned);
								updateAvailabilityStmt.setInt(3, copiesAvailable);
								updateAvailabilityStmt.setInt(4, numberOfHolds);
								updateAvailabilityStmt.setLong(5, existingAvailabilityRS.getLong("id"));
								updateAvailabilityStmt.executeUpdate();
								availabilityChanged = true;
							}
						}else{
							addAvailabilityStmt.setLong(1, databaseId);
							addAvailabilityStmt.setLong(2, curCollection);
							addAvailabilityStmt.setBoolean(3, available);
							addAvailabilityStmt.setInt(4, copiesOwned);
							addAvailabilityStmt.setInt(5, copiesAvailable);
							addAvailabilityStmt.setInt(6, numberOfHolds);
							addAvailabilityStmt.executeUpdate();
							availabilityChanged = true;
						}
					} catch (JSONException e) {
						logger.error("Error loading availability for title ", e);
						results.addNote("Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
						results.incErrors();
					}
				}
			} catch (SQLException e) {
				logger.error("Error loading availability for title ", e);
				results.addNote("Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
		//Update the product to indicate that we checked availability
		try {
			updateProductAvailabilityStmt.setLong(1, curTime);
			if (dbInfo == null || availabilityChanged){
				updateProductAvailabilityStmt.setLong(2, curTime);
				results.incAvailabilityChanges();
				results.saveResults();
			}else{
				updateProductAvailabilityStmt.setLong(2, dbInfo.getLastAvailabilityChange());
			}
			updateProductAvailabilityStmt.setLong(3, databaseId);
			updateProductAvailabilityStmt.executeUpdate();
		} catch (SQLException e) {
			logger.error("Error updating product availability status ", e);
			results.addNote("Error updating product availability status " + overDriveInfo.getId() + " " + e.toString());
			results.incErrors();
		}
		return availabilityChanged;
	}
	
	private JSONObject callOverDriveURL(String overdriveUrl) {
		errorOnLastOverDriveCall = false;
		int maxConnectTries = 1; //OverDrive states that calling multiple times won't improve responses, but will take longer
		//logger.debug("Calling overdrive URL " + overdriveUrl);
		for (int connectTry = 0 ; connectTry < maxConnectTries; connectTry++){
			if (connectToOverDriveAPI(connectTry != 0)){
				if (connectTry != 0){
					logger.debug("Connecting to " + overdriveUrl + " try " + (connectTry + 1));
				}
				//Connect to the API to get our token
				HttpURLConnection conn;
				try {
					URL emptyIndexURL = new URL(overdriveUrl);
					conn = (HttpURLConnection) emptyIndexURL.openConnection();
					if (conn instanceof HttpsURLConnection){
						HttpsURLConnection sslConn = (HttpsURLConnection)conn;
						sslConn.setHostnameVerifier(new HostnameVerifier() {
							
							@Override
							public boolean verify(String hostname, SSLSession session) {
								//Do not verify host names
								return true;
							}
						});
					}
					conn.setRequestMethod("GET");
					conn.setRequestProperty("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
					
					StringBuilder response = new StringBuilder();
					if (conn.getResponseCode() == 200) {
						// Get the response
						BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
						String line;
						while ((line = rd.readLine()) != null) {
							response.append(line);
						}
						//logger.debug("  Finished reading response");
						rd.close();
						return new JSONObject(response.toString());
					} else {
						logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive API try " + connectTry );
						// Get any errors
						BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
						String line;
						while ((line = rd.readLine()) != null) {
							response.append(line);
						}
						logger.debug("  Finished reading response");
						logger.debug(response.toString());
	
						rd.close();
						hadTimeoutsFromOverDrive = true;
					}
	
				} catch (Exception e) {
					logger.debug("Error loading data from overdrive API try " + connectTry, e );
					hadTimeoutsFromOverDrive = true;
				}
			}
			//Something went wrong, try to wait a second to see if the OverDrive API will catch up.
			/*try {
				Thread.sleep(1000);
			} catch (InterruptedException e) {
				logger.debug("Could not pause between tries " + connectTry);
			}*/
		}
		logger.error("Failed to call overdrive url " +overdriveUrl + " in " + maxConnectTries + " calls");
		results.addNote("Failed to call overdrive url " +overdriveUrl + " in " + maxConnectTries + " calls");
		results.saveResults();
		errorOnLastOverDriveCall = true;
		return null;
	}

	private boolean connectToOverDriveAPI(boolean getNewToken){
		//Check to see if we already have a valid token
		if (overDriveAPIToken != null && !getNewToken){
			if (overDriveAPIExpiration - new Date().getTime() > 0){
				//logger.debug("token is still valid");
				return true;
			}else{
				logger.debug("Token has exipred");
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn;
		try {
			URL emptyIndexURL = new URL("https://oauth.overdrive.com/token");
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {
					
					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
			//logger.debug("Client Key is " + clientSecret);
			String encoded = Base64.encodeBase64String((clientKey + ":" + clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic "+encoded);
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();
			
			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				overDriveAPIToken = parser.getString("access_token");
				overDriveAPITokenType = parser.getString("token_type");
				logger.debug("Token expires at " + parser.getLong("expires_in"));
				overDriveAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
				//logger.debug("OverDrive token is " + overDriveAPIToken);
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive authentication service" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response\r\n" + response);

				rd.close();
				return false;
			}

		} catch (Exception e) {
			logger.error("Error connecting to overdrive API", e );
			return false;
		}
		return true;
	}
}
