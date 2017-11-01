/*

	MUST BE LOADED AFTER THE JQUERY PLUGIN

*/


// Some global trackers
var switching = new Array(16); // index is output ID and content of that index is mapped input ID
var hack_dontTrigger = false;
var outputIDclicked;
var inputs = {};
var outputs = {};
var projectors = {};
var dialog;
var confirmPower;
var timer;
var timerInterval = 40000; //milliseconds between page refreshes
var shutterTimer;
var codeMirrorRef;
var hash;
var iBoot = {
		power: 'unknown',
		error: false,
		initial: true
};
var pulsateProperties = {
		left: {
			enabled: false,
			targets: '#projector_left .shutter, #projector_left_status'
		},
		right: {
			enabled: false,
			targets: '#projector_right .shutter, #projector_right_status'
		},
		highlightColor: '#c24949',
		normalColor: '#cccccc'
}
var projectorStatsConfig = {
		good: '#4fb132',
		warning: '#e47a2d',
		bad: '#e95200',
		crazy: '#d32424',
		percentageSuffix: '%',
		celciusSuffix: '&deg;C'
}

// Preload some images
var imgs = new Array(
				'css/smoothness/images/ui-bg_glass_75_e6e6e6_1x400.png', 
				'css/smoothness/images/ui-bg_glass_75_dadada_1x400.png',
				'css/smoothness/images/ui-bg_glass_65_ffffff_1x400.png',
				'css/smoothness/images/ui-icons_222222_256x240.png',
				'css/smoothness/images/ui-icons_454545_256x240.png',
				'css/smoothness/images/ui-icons_888888_256x240.png'
			);
for (img in imgs){
    (new Image).src = imgs[img];
}


// A function that will capitalize the first letter in every word of a string
String.prototype.capitalize = function() {
	return this.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
}


// A function that will strip html from any string (or at least tell the browser to do it, and hope for the best)
function stripTags(html) {
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || "";
}


// A function that will let us change the value of the `switching` array and at the same time maintain its dependencies
function setSwitching(_output, _input) {

	// Set the tracker
	switching[_output] = _input;
	
	// Check the presets
	checkPresets();
}


// A function that will enable and disable the switching presets based on the current switching state
function checkPresets() {
	// Update the presets buttons
	$('.switch-data').each(function (i) {
		var preset = $(this).attr('class').split(' ');
		var isCorrectlySwitched = true;
		
		for (i=0; i<preset.length; i++) {
			if (preset[i].indexOf('switch_') != 0) {
				continue;
			}
			var command = preset[i].split('_');
			var output = command[1];
			var input = command[2];
			
			if (switching[output] != input) {
				isCorrectlySwitched = false;
			}
		}
		
		// Disable/enable the button
		var buttonState = (isCorrectlySwitched) ? "disable" : "enable";
		$("button", this).button(buttonState);
	});
}


// A function that will place the thumbnails at the correct place in the matrix grid
function placeThumbs() {
	$("#matrix_control > button").each(function (index) {
		var outputNum = $(this).attr('id').split("_")[1]
		var that = this;
		$("#thumb_" + outputNum).position({
			of: $(that),
			my: "center bottom",
			at: "center bottom-10"
		});
	});
}


// A function that will disable and enable the matrix grid interface
// (str) _what = "show" or "hide"
// (obj) _switching = [{output: (str|int) output, input: (str|int) input}]
function toggleMatrixGrid(_what, _switching) {
	
	if (_what == "hide") {
		inputPopupActive = true;
		$("#matrix_control > button").button("disable");
		$(".thumbs").addClass("ui-state-disabled");
	} else if (_what == "show") {
		inputPopupActive = false;
		//$("#matrix_control > button").button("enable");
		$("#matrix_control > button").each(function (i) {
			var id = $(this).attr('id').split("_")[1];
			
			// Check if this is a projector, and if that projector is currently busy. If so, don't enable it (we'll do it from ajax.js)
			if (_switching != undefined) {
				for (i=0; i<_switching.length; i++) {
					if (id == _switching[i].output) {
						var projectorIndex = $.inArray(parseInt(_switching[i].output), projectorsConfig.monitorID);
						if (projectorIndex == -1) {
							continue;
						} else if (projectorsConfig.busy[projectorIndex]) {
							return;
						}
					}
				}
			}
			
			// Go ahead and re-enable if the output is enabled in the config
			if (outputs[id].enabled) {
				$(this).button("enable");
				$("#thumb_" + id).removeClass("ui-state-disabled");
			}
		});
	} else {
		console.log("Unknown parameter passed to toggleMatrixGrid(): " + _what);
	}
}


// A function that will disable and enable the basic projector control interface
// (str) _what = "show" or "hide"
function toggleProjectorControl(_what) {
	if (_what == "hide") {
		if ($("#projector_detail_control").is(":visible")) {
			return; // already hidden
		}
		
		$(".projector_control_details button").button("disable");
		//$("#button_both_blackout button, #button_both_normal button").button("disable");
		$(".projector_control_details").css({opacity: 0.5});
		$("#button_both_blackout, #button_both_normal, #common_buttons_text").hide();
		$("#projector_control .common button").first().parent().animate({"margin-left":"373px"}, 250);
	} else if (_what == "show") {
		helper_projectorInterface(projectorsConfig.name[0]);
		helper_projectorInterface(projectorsConfig.name[1]);
		$(".projector_control_details").css({opacity: ''});
		$("#button_both_blackout, #button_both_normal, #common_buttons_text").show();
		$("#projector_control .common button").first().parent().animate({"margin-left":""}, 250);
		
		// Re-enable the common blackout/normal buttons
		helper_commonButtons();
		
	} else {
		console.log("Unknown parameter passed to toggleProjectorControl(): " + _what);
	}
}


// A function that will continuously pulsate a specified number of objects
// Relies heavily on the global pulsateProperties object
// (string) _what = left|right, used to specify which group of objects to handle
// (bool) _forced = true|false, used to force start of the animation (ie setting the global tracker to true)
function pulsate(_what, _forced) {

	// Start the process if needed
	if (_forced !== undefined) {
		pulsateProperties[_what].enabled = true;
	}
	
	// If we're a go-ahead, animate
	if (pulsateProperties[_what].enabled) {
		if (!$(pulsateProperties[_what].targets).is(':animated')) {
			$(pulsateProperties[_what].targets).each(function(i) {
				var properties;
				if ($(this).is('button')) {
					$(this).css({
						borderTopColor: pulsateProperties.highlightColor,
						borderRightColor: pulsateProperties.highlightColor,
						borderBottomColor: pulsateProperties.highlightColor,
						borderLeftColor: pulsateProperties.highlightColor,
						color: pulsateProperties.highlightColor
					});
					
					properties = [
							{
								borderTopColor: pulsateProperties.highlightColor,
								borderRightColor: pulsateProperties.highlightColor,
								borderBottomColor: pulsateProperties.highlightColor,
								borderLeftColor: pulsateProperties.highlightColor,
								color: pulsateProperties.highlightColor
							}, 
							{
								borderTopColor: pulsateProperties.normalColor,
								borderRightColor: pulsateProperties.normalColor,
								borderBottomColor: pulsateProperties.normalColor,
								borderLeftColor: pulsateProperties.normalColor,
								color: pulsateProperties.normalColor
							}];
				} else {
					properties = [{opacity:0.2}, {opacity:1}];
				}
				
				$(this)
				 .animate(properties[1], 1200, 'linear')
				 .animate(properties[0], 1200, 'linear', function () {
					pulsate(_what);
				 });
			});
		}
		
	// If not, abort
	} else {
		pulsateStop(_what);
	}
}


// A function that will help cancel a running pulsate() function
// (string) _what = left|right, used to specify which group of objects to handle
function pulsateStop(_what) {
	pulsateProperties[_what].enabled = false;
	$(pulsateProperties[_what].targets).stop(true, true);
	$(pulsateProperties[_what].targets).css("opacity", '');
	$(pulsateProperties[_what].targets).css("borderTopColor", '');
	$(pulsateProperties[_what].targets).css("borderRightColor", '');
	$(pulsateProperties[_what].targets).css("borderBottomColor", '');
	$(pulsateProperties[_what].targets).css("borderLeftColor", '');
	$(pulsateProperties[_what].targets).css("color", '');
}


// A sync timer that will keep triggering and updating the page
function syncTimer() {
	
	// Load matrix switching state and projector states
	loadMatrixState();
	loadProjectors(['projector_left', 'projector_right']);
	loadiBoot();
	
	// Re-set the timer (pipe to `timer` in case we need to kill it)
	timer = setTimeout(
				function(){
					syncTimer();
				}, 
				timerInterval
			);
}



// Initialize when all content has loaded properly
$(document).ready(function() {

	//if (window.location.hash) {
	//	hash = window.location.hash;
	//	window.location.replace(window.location.origin + window.location.pathname);
	//}
	//console.log(window.location);

	
	// Create our error dialog handler
	dialog = $("#dialog").dialog({
				width: 430,
				height: 240,
				autoOpen: false,
				buttons: {
					"OK": function() {
						$(this).dialog("close");
					}
				}
			});
	

	// Create the confirmation dialog
	confirmPower = $("#confirmPower").dialog({
				width: 370,
				height: 200,
				autoOpen: false, 
				buttons: {
					OK: function() {
						projectorPower($(this).data('projector'), "off");
						$(this).dialog("close");
					},
					Cancel: function() {
						$(this).dialog("close");
					}
				}
			});

	
	// bugfix, force jQuery to familiarize itself with the offset of our first tab content
	$('#content > div').each(function(i) {
		var width = $('#content').width();
		var height = $('#content').height();
		$(this).css('left', width*i);
		$(this).css('top', -(height*i));
	});


	// Navigation buttons on the very top of the page
	$('li a').click(function() {
		
		// Check if this is the active element. If so, return
		if ($(this).hasClass('active_li')) {
			return false;
		}
		
		// Add class 'active_li' to the clicked element
		$('li a').removeClass('active_li');
		$(this).addClass('active_li');
		
		// Find the distance the tab contents should travel
		var id = $(this).attr('href');
		var offset = $(id).position().left;
		
		// Animate each div sliding sideways
		var animationTime = 500;
		$('#content > div').each(function(i) {
			
			// Calculate offset
			var left = $(this).position().left;
			
			// Fade opacity down and up as we slide. This will create a "swing" (quadratic?) fade that starts and 
			// finishes at 100% opacity, via 40% opacity midway through the sliding animation.
			$(this).fadeTo(animationTime/4, 0.4, "swing", function() {
				$(this).fadeTo(animationTime/4, 1, "swing");
			});
			
			// Slide the div sideways to match new location
			$(this).animate({
				left: left - offset
			}, animationTime);
		});
		
		// return false to avoid browser fetching the request and setting the #id in the url
		return false;
	});
	
	
	// Initialize the matrix switch grid
	$("#matrix_control > button").button().click(function(e) {
	
		/* THIS APPEARS TO HAVE BEEN FIXED WITH CSS. LEAVING JUST IN CASE, FOR COMPATIBILITY */
		// Huge hack. There's a problem with double triggering in certain situations.
		// If the following flag has been set, ignore this event and reset the flag
		if (hack_dontTrigger) {
			hack_dontTrigger = false;
			return false;
		}
		
		// Disable default event handling
		e.preventDefault();
		
		// Prevent some browers from lingering focus on the button after press (I'm looking at you, Firefox)
		$(this).blur();
		
		// What's the ID of this button?
		// Global tracker - will be used again later once the input is clicked
		outputIDclicked = $(this).attr('id').split("_")[1]; 
		
		// Pop up the list of inputs
		toggleMatrixGrid("hide");
		$("#input_panel > h2 > span").text(stripTags(outputs[outputIDclicked].name));
		$("#input_panel").show();
		
		// highlight the currently selected input
		var current = switching[outputIDclicked];
		$("#input_panel > button").removeClass('ui-state-hover-special');
		$("#input_" + current).addClass('ui-state-hover-special');
	});
	
	// Initialize the input popup grid
	$("#input_panel > button").button().click(function(e) {
		// Disable default event handling
		e.preventDefault();
		
		// Prevent some browers from lingering focus on the button after press (I'm looking at you, Firefox)
		$(this).blur();
		
		// Fetch the ID of this button
		var inputID = $(this).attr('id').split("_")[1];
		
		// Check if we were already switched to this source - if so, abort
		if (switching[outputIDclicked] == inputID) {
			$("#input_panel").hide();
			toggleMatrixGrid("show");
			return;
		}
		
		// Send the ajax call and close the popup
		var dataObj = [{
			output: outputIDclicked,
			input: inputID
		}];
		saveMatrixState(dataObj);
		$("#input_panel").hide();
		toggleMatrixGrid("show", dataObj);
	});
	
	// Initialize the projector detail control window
	$("#projector_control .detail_buttons button").button().click(function(e) {
		// Disable default event handling
		e.preventDefault();
		
		// Prevent some browers from lingering focus on the button after press (I'm looking at you, Firefox)
		$(this).blur();
		
		// Show the detailed projector control window
		var outText = $("span", this).html();
		$("#projector_detail_control h2").html(outText);
		$(".center_td").html(outText);
		toggleProjectorControl("hide"); // hide the basic interface
		
		// Fade the window in
		//$("#projector_detail_control").show(); 
		$("#projector_detail_control").stop();
		$("#projector_detail_control").fadeIn("fast");
		
		// Hide/show some buttons in the detailed control window
		if (outText.toLowerCase().indexOf('shift') > -1) {
			$(".vertical_arrow").removeClass("disabled");
			$(".th_top").html("Up");
			$(".th_right").html("Right");
			$(".th_bottom").html("Down");
			$(".th_left").html("Left");
		} else {
			$(".vertical_arrow").addClass("disabled");
			$(".th_top, .th_bottom").html("&nbsp;");
			$(".th_right").html("Increase");
			$(".th_left").html("Decrease");
		}

		// Hide buttons for projectors with power off
		//console.log(projectorsConfig);
		for (i=0; i<2; i++) {
			//console.log(projectorsConfig.name[i].power);
			console.log(".projector_detail_control ." + projectorsConfig.name[i] + " .horizontal_arrow");
			if (projectors[projectorsConfig.name[i]].power == "off") {
				$("#projector_detail_control ." + projectorsConfig.name[i] + " .horizontal_arrow").addClass("disabled");
				$("#projector_detail_control ." + projectorsConfig.name[i] + " .vertical_arrow").addClass("disabled");
			} else if (projectors[projectorsConfig.name[i]].power == "on") {
				$("#projector_detail_control ." + projectorsConfig.name[i] + " .horizontal_arrow").removeClass("disabled");
				$("#projector_detail_control ." + projectorsConfig.name[i] + " .vertical_arrow").removeClass("disabled");
			}
		}
		
		// Highlight this button
		$("#projector_control .common button").removeClass("ui-state-hover-special");
		$(this).addClass("ui-state-hover-special");
	});
	
	// Initialize the blackout/normal buttons that apply to both projectors (down in the common/detail area)
	$("#button_both_normal button, #button_both_blackout button").button().click(function(e) {
		// Disable default event handling
		e.preventDefault();
		
		// Prevent some browers from lingering focus on the button after press (I'm looking at you, Firefox)
		$(this).blur();
		
		var command = ($(this).closest('div').attr('id') == 'button_both_blackout') ? 'on' : 'off';
		
		// Send the call
		projectorShutters(projectorsConfig.name[0], command);
		projectorShutters(projectorsConfig.name[1], command);
	});
	
	// Sort out our close button for the input and projector control popup
	$("#input_panel div.ui-state-default, #projector_detail_control div.ui-state-default").hover(
		function(){ $(this).removeClass('ui-state-hover'); }, 
		function(){ $(this).addClass('ui-state-hover'); }
	);
	$("#input_panel div.ui-state-default").click(function() {
		$("#input_panel").hide();
		toggleMatrixGrid("show");
	});
	$("#projector_detail_control > div.ui-state-default").click(function() {
		$("#projector_detail_control").hide();
		toggleProjectorControl("show");
	});
	
	$(".projector_control_table img").hover(
		function() { /*$(this).css("opacity", 0.5);*/ $(this).addClass("ui-state-hover-special"); },
		function() { /*$(this).css("opacity", "");*/ $(this).removeClass("ui-state-hover-special"); }
	);
	$(".projector_control_table img").click(function() {
		
		// Figure out which button was pressed and what that means
		var commandRaw = $(this).attr('class').split(' ')[1].split('_');
		var command = $("#projector_control .common .ui-state-hover-special").first().text().toLowerCase();
		var value = commandRaw[1];
		var projector = $(this).closest('.projector_control_table').attr('class').split(' ')[1];
		
		// Parse the command value based on the text of the buttons (not great, but will do for now)
		if (command.indexOf('zoom') > -1) {
			command = 'zoom';
		} else if (command.indexOf('focus') > -1) {
			command = 'focus';
		} else if (command.indexOf('shift') > -1) {
			command = (commandRaw[0] == 'horizontal') ? 'hshift' : 'vshift';	
		} else {
			command = "unknown";
			console.log("`.projector_control_table img` click() failed...");
		}
		
		// Send the command to the projectors
		projectorDetailControl(projector, command, value);
	});
	
	
	// Bind mouse-up events to detect click outside the input popup or projector detail control windows (if you want to close them quickly)
	$(document).mouseup(function (e){
		var containers = [
				{
					name: "input_panel",
					ref: $("#input_panel"),
					call: "toggleMatrixGrid"
				},
				{
					name: "projector_detail_control",
					ref: $("#projector_detail_control"),
					call: "toggleProjectorControl"
				}
		];
		
		for (i=0; i<containers.length; i++) {
			var container = containers[i].ref;
		
			// If the input popup isn't visible, get out of here
			if (!container.is(":visible") ) {
				continue;
			}

			// Only trigger on elements that sit outside of the input popup
			if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0 // ... nor a descendant of the container
				//&& !$(e.target).hasClass('detail_buttons') && !$(e.target).parent().hasClass('detail_buttons') && !$(e.target).parent().parent().hasClass('detail_buttons')) // ... nor a button in the .detail_buttons class
				&& $(e.target).parents(".detail_buttons").length === 0) // ... nor a button decendant of the .detail_buttons class
			{
				// Hide the inputs and re-enable the matrix grid
				if (containers[i].name == "projector_detail_control") {
					container.stop();
					container.fadeOut("fast");
				} else {
					container.hide();
				}
				//toggleMatrixGrid("show");
				window[containers[i].call]("show"); // call the function described in `containers`
				
				/* THIS APPEARS TO HAVE BEEN FIXED WITH CSS. LEAVING JUST IN CASE, FOR COMPATIBILITY */
				// Huge hack. The <span> element in the button will also trigger on this call if clicked (Although they are disabled).
				// Set a flag to notify the buttons not to trigger for the next click
				if ($(e.target).hasClass('.ui-button') || $(e.target).parents('.ui-button').length > 0) {
					hack_dontTrigger = true;
				}
			}
		}
	});
	
	
	// Projector control buttons
	$(".power, .shutter").button().click(function(e) {
		// Disable default event handling
		e.preventDefault();
		
		// Prevent some browers from lingering focus on the button after press (I'm looking at you, Firefox)
		$(this).blur();
		
		// Send the call
		var projectorName = $(this).parent().parent().parent().attr("id");
		if ($(this).attr("class").indexOf("power") != -1) {
			var command = (projectors[projectorName].power == "on") ? 'off' : 'on';
			if (command == "off") {
				confirmPower.data('projector', projectorName).dialog("open");
			} else {
				projectorPower(projectorName, command);
			}
			
		} else {
			var command = (projectors[projectorName].shutter == "on") ? 'off' : 'on';
			projectorShutters(projectorName, command);
		}
	});
	
	
	// Context menu for the projector buttons in the matrix grid
	$.contextMenu({
		selector: '#output_'+ projectorsConfig.monitorID[0] +', #output_'+ projectorsConfig.monitorID[1], 
		callback: function(key, options) {
			var owner = $(options.$trigger).attr('id').split('_')[1];
			var projectorName = outputs[owner].projectorID;
				
			if (key == "power_on") {
				projectorPower(projectorName, 'on');
			} else if (key == "power_off") {
				confirmPower.data('projector', projectorName).dialog("open");
			} else if (key == "shutter_on") {
				projectorShutters(projectorName, 'on');
			} else if (key == "shutter_off") {
				projectorShutters(projectorName, 'off');
			}
		},
		items: {
			"power_on": {name: "Power on", icon: "power", disabled:function(key, options) {
				var owner = $(options.$trigger).attr('id').split('_')[1];
				var projectorName = outputs[owner].projectorID;
				return (projectors[projectorName].power == "on") ? true : false;
			}},
			"power_off": {name: "Power off", icon: "power", disabled:function(key, options) {
				var owner = $(options.$trigger).attr('id').split('_')[1];
				var projectorName = outputs[owner].projectorID;
				return (projectors[projectorName].power == "on") ? false : true;
			}},
			"shutter_on": {name: "Blackout", icon: "shutter", disabled:function(key, options) {
				var owner = $(options.$trigger).attr('id').split('_')[1];
				var projectorName = outputs[owner].projectorID;
				
				if (projectors[projectorName].power != "on") {return true;};
				
				return (projectors[projectorName].shutter == "on") ? true : false;
			}},
			"shutter_off": {name: "Normal", icon: "shutter", disabled:function(key, options) {
				var owner = $(options.$trigger).attr('id').split('_')[1];
				var projectorName = outputs[owner].projectorID;
				
				if (projectors[projectorName].power != "on") {return true;};
				
				return (projectors[projectorName].shutter == "on") ? false : true;
			}},
		}
    });
    
    
    // Create some UI Progressbars on the projector page (we'll use them for graph display)
    $(".progressbar").progressbar({
		value: false,
		change: function() {
		
			var span = $("span", this).first();
			var value = $(this).progressbar("option", "value");
			var background = "";
			var text = "";
			
			// Rules for the filter bar
			if (span.hasClass("filter"))  {
				text = projectorStatsConfig.percentageSuffix;
			
				if (value > 60) {
					background = projectorStatsConfig.good;
				} else if (value > 30) {
					background = projectorStatsConfig.warning;
				} else if (value > 10) {
					background = projectorStatsConfig.bad;
				} else {
					background = projectorStatsConfig.crazy;
				}
			
			// Rules for the temperature bars	
			} else {
				text = projectorStatsConfig.celciusSuffix;
				
				if (value < 30) {
					background = projectorStatsConfig.good;
				} else if (value < 50) {
					background = projectorStatsConfig.warning;
				} else if (value < 60) {
					background = projectorStatsConfig.bad;
				} else {
					background = projectorStatsConfig.crazy;
				}
			}
			
			// Set the background color
			$(".ui-progressbar-value", this).css({
					'background': background
			});
			
			// Set the text value
			$(".ui-progressbar-value, span", this).html(value + text);
		}
	});
	
	
	// Create the buttons for the presets
	$('#presets button').button().click(function () {
		var preset = $(this).parents('span').attr('class').split(' ');
		var dataObj = [];
		
		// Loop through the classes associated with this button
		for (i=0; i<preset.length; i++) {
		
			// Make sure the current class is a switching command
			if (preset[i].indexOf('switch_') != 0) {
				continue;
			}
			
			// Break the command up in monitor and input, then add to dataObj
			var command = preset[i].split('_');
			var output = command[1];
			var input = command[2];
			dataObj.push({'output':output, 'input':input});
		}
		
		// Send the command
		saveMatrixState(dataObj);
	});
	
	
	// Create the buttons for the iBoot page
	$('#iboot button').button().click(function () {
		var command = $(this).parents('span').attr('id').split('_')[1];
		iBootPower(command);
	});
	helper_iboot();

	
	// Initialize the codemirror plugin
	codeMirrorRef = CodeMirror.fromTextArea(document.getElementById('php_output'), {
		lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 4,
        indentWithTabs: true
	});
	
	// On every editor change, send the value to PHP and save it
	codeMirrorRef.on("changes", function() {
		var editorValue = codeMirrorRef.getValue();
		saveConfig(editorValue);
	});
    
	
	// Place the input thumbs for each button in the matrix grid
	placeThumbs();
	
		
	// Load matrix switching state and projector states. And keep loading them periodically
	syncTimer();


	// Load the config file
	loadConfig();
});