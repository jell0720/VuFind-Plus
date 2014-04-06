var VuFind = (function(){
	$(document).ready(function(){
		VuFind.initializeModalDialogs();
		VuFind.setupFieldSetToggles();

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
		closeLightbox: function(){
			var modalDialog = $("#modalDialog");
			if (modalDialog.is(":visible")){
				modalDialog.modal('hide');
			}
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
			$('legend.collapsible').each(function(index){
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

		loadEContentHelpTopic: function (){
			var selectedDevice = $("#device :selected").val();
			var selectedFormat = $("#format :selected").val();

			if (selectedDevice != "selectone" && selectedFormat != "selectone"){
				$.getJSON(
						Globals.path + '/Help/AJAX?method=getHelpTopic&device=' + selectedDevice + '&format=' +selectedFormat,
						function (data){
							$("#stepByStepInstructions").show()
							$("#helpInstructions").html(data.helpText);
						}
				);
			}
		},

		showMessage: function(title, body, autoClose){
			if (autoClose == undefined){
				autoClose = false;
			}
			$("#myModalLabel").html(title);
			$(".modal-body").html(body);
			$('.modal-buttons').html('');
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
			if (autoClose){
				setTimeout("closeLightbox();", 3000);
			}
		},

		toggleHiddenElementWithButton: function(button){
			var hiddenElementName = $(button).data('hidden_element');
			var hiddenElement = $(hiddenElementName);
			hiddenElement.val($(button).hasClass('active') ? '1' : '0');
			return false;
		},

		showElementInPopup: function(title, elementId){
			VuFind.closeLightbox();
			$("#modal-title").html(title);
			var elementText = $(elementId).html();
			$(".modal-body").html(elementText);
			var modalDialog = $("#modalDialog");
			modalDialog.modal('show');
		},

		showLocationHoursAndMap: function(){
			var selectedId = $("#selectLibrary").find(":selected").val();
			$(".locationInfo").hide();
			$("#locationAddress" + selectedId).show();
			return false;
		}

	}

}(VuFind || {}));