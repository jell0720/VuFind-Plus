VuFind.Admin = (function(){
	return {
		showReindexNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id, true);
			return false;
		},
		toggleReindexProcessInfo: function (id){
			$("#reindexEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},
		showReindexProcessNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id, true);
			return false;
		},

		showCronNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id, true);
			return false;
		},
		showCronProcessNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id, true);
			return false;
		},
		toggleCronProcessInfo: function (id){
			$("#cronEntry" + id).toggleClass("expanded collapsed");
			$("#processInfo" + id).toggle();
		},

		showOverDriveExtractNotes: function (id){
			VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getOverDriveExtractNotes&id=" + id, true);
			return false;
		}
	};
}(VuFind.Admin || {}));
