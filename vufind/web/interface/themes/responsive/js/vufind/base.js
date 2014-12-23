var VuFind = (function(){
	$(document).ready(function(){
		VuFind.initializeModalDialogs();
		VuFind.setupFieldSetToggles();
		VuFind.initCarousels();

		$("#modalDialog").modal({show:false});

		var panels = $('.panel');
		panels.on('show.bs.collapse', function () {
			$(this).addClass('active');
		});

		panels.on('hide.bs.collapse', function () {
			$(this).removeClass('active');
		});
	});
	/**
	 * Created by mark on 1/14/14.
	 */
	return {
		changePageSize: function(){
			var url = window.location.href;
			if (url.match(/[&?]pagesize=\d+/)) {
				url = url.replace(/pagesize=\d+/, "pagesize=" + $("#pagesize").val());
			} else {
				if (url.indexOf("?", 0) > 0){
					url = url+ "&pagesize=" + $("#pagesize").val();
				}else{
					url = url+ "?pagesize=" + $("#pagesize").val();
				}
			}
			window.location.href = url;
		},

		closeLightbox: function(callback){
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				modalDialog.modal('hide');
				if (callback != undefined){
					var closeLightboxListener = modalDialog.on('hidden.bs.modal', function (e) {
						modalDialog.off('hidden.bs.modal');
						callback();
					});
				}
			}
		},

		initCarousels:function(){
			var jcarousel = $('.jcarousel');

			jcarousel.on('jcarousel:reload jcarousel:create', function () {
				var element = $(this);
				var width = element.innerWidth();
				var itemWidth = width;
				if (width >= 600) {
					itemWidth = width / 4;
				}else if (width >= 400) {
					itemWidth = width / 3;
				}else if (width >= 300) {
					itemWidth = width / 2;
				}

				element.jcarousel('items').css('width', Math.floor(itemWidth) + 'px');
			})
			.jcarousel({
				wrap: 'circular'
			});

			$('.jcarousel-control-prev')
					.jcarouselControl({
						target: '-=1'
					});

			$('.jcarousel-control-next')
					.jcarouselControl({
						target: '+=1'
					});

			$('.jcarousel-pagination')
					.on('jcarouselpagination:active', 'a', function() {
						$(this).addClass('active');
					})
					.on('jcarouselpagination:inactive', 'a', function() {
						$(this).removeClass('active');
					})
					.on('click', function(e) {
						e.preventDefault();
					})
					.jcarouselPagination({
						perPage: 1,
						item: function(page) {
							return '<a href="#' + page + '">' + page + '</a>';
						}
					});
		},

		initializeModalDialogs: function() {
			$(".modalDialogTrigger").each(function(){
				$(this).click(function(){
					var trigger = $(this);
					var dialogTitle = trigger.attr("title") ? trigger.attr("title") : trigger.data("title");
					var dialogDestination = trigger.attr("href");
					$("#myModalLabel").text(dialogTitle);
					$(".modal-body").load(dialogDestination);
					$(".extraModalButton").hide();
					$("#modalDialog").modal("show");
					return false;
				});
			});
		},

		getQuerystringParameters: function(){
			var vars = [];
			var q = document.URL.split('?')[1];
			if(q != undefined){
				q = q.split('&');
				for(var i = 0; i < q.length; i++){
					var hash = q[i].split('=');
					vars[hash[0]] = hash[1];
				}
			}
			return vars;
		},

		getSelectedTitles: function(){
			var selectedTitles = $("input.titleSelect:checked ").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedTitles.length == 0){
				var ret = confirm('You have not selected any items, process all items?');
				if (ret == true){
					var titleSelect = $("input.titleSelect");
					titleSelect.attr('checked', 'checked');
					selectedTitles = titleSelect.map(function() {
						return $(this).attr('name') + "=" + $(this).val();
					}).get().join("&");
				}
			}
			return selectedTitles;
		},

		pwdToText: function(fieldId){
			var elem = document.getElementById(fieldId);
			var input = document.createElement('input');
			input.id = elem.id;
			input.name = elem.name;
			input.value = elem.value;
			input.size = elem.size;
			input.onfocus = elem.onfocus;
			input.onblur = elem.onblur;
			input.className = elem.className;
			if (elem.type == 'text' ){
				input.type = 'password';
			} else {
				input.type = 'text';
			}

			elem.parentNode.replaceChild(input, elem);
			return input;
		},

		setupFieldSetToggles: function (){
			$('legend.collapsible').each(function(){
				$(this).siblings().hide();
				$(this).addClass("collapsed");
				$(this).click(function() {
					$(this).toggleClass("expanded");
					$(this).toggleClass("collapsed");
					$(this).siblings().slideToggle();
					return false;
				});
			});
		},

		showMessage: function(title, body, autoClose, refreshAfterClose){
			// if autoclose is set as number greater than 1 autoClose will be the custom timeout interval in milliseconds, otherwise
			//     autoclose is treated as an on/off switch. Default timeout interval of 3 seconds.
			if (autoClose == undefined){
				autoClose = false;
			}
			if (refreshAfterClose == undefined){
				refreshAfterClose = false;
			}
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html('');
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
			if (autoClose && refreshAfterClose){
				setTimeout(function(){
					location.reload(true);
				}
				, autoClose > 1 ? autoClose : 3000);
			}else if (autoClose) {
				setTimeout(function(){
					VuFind.closeLightbox();
				}
				, autoClose > 1 ? autoClose : 3000);
			}
		},

		showMessageWithButtons: function(title, body, buttons){
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html(buttons);
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
		},

		toggleHiddenElementWithButton: function(button){
			var hiddenElementName = $(button).data('hidden_element');
			var hiddenElement = $(hiddenElementName);
			hiddenElement.val($(button).hasClass('active') ? '1' : '0');
			return false;
		},

		showElementInPopup: function(title, elementId){
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				VuFind.closeLightbox(function(){VuFind.showElementInPopup(title, elementId)});
			}else{
				$(".modal-title").html(title);
				var elementText = $(elementId).html();
				$(".modal-body").html(elementText);
				var modalDialog = $("#modalDialog");
				modalDialog.modal('show');
				return false;
			}
		},

		showLocationHoursAndMap: function(){
			var selectedId = $("#selectLibrary").find(":selected").val();
			$(".locationInfo").hide();
			$("#locationAddress" + selectedId).show();
			return false;
		},

		toggleCheckboxes: function (checkboxSelector, toggleSelector){
			var toggle = $(toggleSelector);
			var value = toggle.prop('checked');
			$(checkboxSelector).prop('checked', value);
		},

		submitOnEnter: function(event, formToSubmit){
			if (event.keyCode == 13){
				$(formToSubmit).submit();
			}
		}

	}

}(VuFind || {}));

jQuery.validator.addMethod("multiemail", function (value, element) {
	if (this.optional(element)) {
		return true;
	}
	var emails = value.split(/[,;]/),
			valid = true;
	for (var i = 0, limit = emails.length; i < limit; i++) {
		value = emails[i];
		valid = valid && jQuery.validator.methods.email.call(this, value, element);
	}
	return valid;
}, "Invalid email format: please use a comma to separate multiple email addresses.");