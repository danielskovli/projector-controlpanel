/*

	MUST BE LOADED AFTER/WITH THE JQUERY PLUGIN

*/


// Load Matrix switch state
function loadMatrixState() {

	// Load dataset from PHP
	$.ajax({
		url: 'php/parser-matrixStatus.php',
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				//error = true;
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while fetching the Matrix status from the server: <br /><br />" + data.error_reason);
				dialog.dialog("open");
				return;
			}

			// Update the Matrix grid
			
			// Loop through the items in the reply
			for (i=0; i<data.switching.length; i++) {
				
				// Update the text on the page
				$('#thumb_' + data.switching[i].output + ' .content').html(data.switching[i].in_name);
				
				// Update our global tracker
				//switching[i] = data.switching[i].input;
				setSwitching(i, data.switching[i].input);
			}			
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while fetching the Matrix status from the server: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
		}
	});
}


// Load configuration file
function loadConfig() {

	// Load dataset from PHP
	$.ajax({
		url: 'php/parser-configReader.php',
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while fetching the config file from the server: <br /><br />" + data.error_reason);
				dialog.dialog("open");
				return;
			}

			// Update the global trackers
			inputs = data.config.inputs;
			outputs = data.config.outputs;
			shutterTimer = data.config.shutter_timer;
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while fetching the config file from the server: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
		},
		
		// Request is complete, regardless of success/error
		complete: function (jqXHR, textStatus) {
		
			// Move some projector_status labels into place (because before this, we didn't which outputs were projectors)
			$("#projector_left_status, #projector_right_status").each(function (index) {
				var target = $(this).attr('id').replace("_status", "");
				
				// Loop through list of outputs and find the output that matches our ID
				for (i=0; i<outputs.length; i++) {
					if (outputs[i].projectorID == target) {
						target = "output_" + i;
					}
				}
				
				// Set the position
				$(this).position({
					of: $("#" + target),
					my: "center center",
					at: "center center-20"
				});
			});
		}
	});
}


// Save matrix state
// (object[]) _dataObj = [{output:outputID, input:inputID}, {...}]
function saveMatrixState(_dataObj) {
	
	// Loop through the items in _dataObj
	for (i=0; i<_dataObj.length; i++) {
		var input = _dataObj[i].input;
		var output = _dataObj[i].output;
		var projectorID = outputs[output].projectorID;
		
		// Check if we're already switched to this input. If so, abort
		if (switching[output] == input) {
			console.log("Input `"+ inputs[input].name +"` is already assigned to output `"+ outputs[output].name +"`. Skipping");
			continue;
		}
		
		// Update the global tracker
		//switching[output] = input;
		setSwitching(output, input);
		//console.log("Switching input `"+ input +"` to output `"+ output +"`");
		
		// Animate the output thumbnail if _dataObj contains only one set, otherwise just change the text
		// One set means user clicked the input box, more means we're using a preset or changing programatically
		if (_dataObj.length == 1) {
			$('#thumb_' + output).toggle({
				effect: "highlight",
				complete: function () {
					$('#thumb_' + output + ' .content').html(inputs[input].name);
					$(this).show();
				}
			});
			
		} else {
			$('#thumb_' + output + ' .content').html(inputs[input].name);
		}
		
		// If this is a projector (deal with shutters) and timeouts
		var projectorIndex = $.inArray(parseInt(output), projectorsConfig.monitorID);
		if (projectorIndex != -1) {
		
			// If the projector is currently busy, skip and display error
			if (projectorsConfig.busy[projectorIndex]) {
				dialog.dialog("option", "title", "Projector is busy");
				dialog.html("<strong>"+ projectorsConfig.name[projectorIndex] +"</strong> is still busy handling another request. Please try again shortly.<br /><br />Switching request cancelled.");
				dialog.dialog("open");
				continue;
			}
			
			// If the projector is not currently off or has it's shutter down
			if (projectors[projectorID].power == "on" && projectors[projectorID].shutter == "off") {
			
				// Enable the shutters
				projectorShutters(projectorID, "on", true);
				projectorsConfig.busy[projectorIndex] = true;
				
				// Disable the interface
				$("#output_"+ output).button("disable");
				$("#thumb_" + output).addClass("ui-state-disabled");
				$('#output_'+ output).contextMenu(false);
				$('#presets button').button("disable");
				
				// Let the user know we're switching
				var displayText = "Switching...";
				$("#"+ projectorID +"_status").html('<div class="progressbar"><span class="shutter">'+ displayText +'</span></div>');
				
				// If this is direct user input, display the progress bars
				if (_dataObj.length == 1) {
				
					$("#"+ projectorID +"_status .progressbar").progressbar({
						value: 0,
						complete: function() {

							// Clean up
							$("#"+ projectorID +"_status .progressbar").progressbar("destroy");
							
							// Reset the shutters
							projectorShutters(projectorID, "off", true);
							
							// Re-enable the interface
							$("#output_"+ output).button("enable");
							//$("#output_"+ output + " .thumbs").removeClass("ui-state-disabled");
							$("#thumb_" + output).removeClass("ui-state-disabled");
							$('#output_'+ output).contextMenu(true);
							projectorsConfig.busy[projectorIndex] = false;
							checkPresets();
							
							// Reset some projector related information, just in case something changed while we were sleeping
							helper_projectorInterface(projectorID);
						}
					});
					$("#"+ projectorID +"_status .ui-progressbar-value").html(displayText);
					helper_updateProgress(projectorID); // kicks off the progressbar
				
				// If not (aka API or preset), just set a timer to disable the shutters again later
				} else {

					// We need to wrap the timeout call in a function to create a distinct copy of the id and index variables
					(function(_id, _index, _output) {
						setTimeout(
							function(){
								projectorShutters(_id, "off", true);
								$("#output_"+ _output).button("enable");
								$("#thumb_" + _output).removeClass("ui-state-disabled");
								$('#output_'+ _output).contextMenu(true);
								projectorsConfig.busy[_index] = false;
								helper_projectorInterface(_id);
								checkPresets();
							}, 
							shutterTimer
						);
						
					})(projectorID, projectorIndex, output);
				}
			}
		}
		
		// Do the actual switching
		$.ajax({
			url: 'php/interaction-matrix.php?input='+ input +'&output='+ output,
			dataType: 'json',
			timeout: 10000,
			
			// Sucessfully loaded the PHP file
			success: function (data, textStatus, jqXHR) {
			
				// Check the reply for errors (if the user supplied some incorrect info, etc)
				if (data.error) {
					//error = true;
					dialog.dialog("option", "title", "Uh oh...");
					dialog.html("An error occurred while switching the displays: <br /><br />" + data.error_reason);
					dialog.dialog("open");
					
					// Get new matrix state
					loadMatrixState();					
				}
			},
			
			// Error loading the file (network, etc)
			error: function (jqXHR, textStatus, errorThrown) {
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while switching the displays: <br /><br />" + textStatus + ' -- ' + errorThrown);
				dialog.dialog("open");
			}
		});
	}
}


// Load projector status
// (string[]) _projectors = ['projector_left', ...]
// (int) _tracker is internal, and should not be used
function loadProjectors(_projectors, _tracker) {
	
	// Check the input. If we didn't get an array, convert what we got to a single-item array.
	// Don't care about data type. We'll catch fails on the reply from PHP
	if (!$.isArray(_projectors)) {
		_projectors = [_projectors];
	}
	
	// Prepare the tracker
	_tracker = 0;

	// Loop through each projector in the list
	for (i=0; i<_projectors.length; i++) {
	
		// Load dataset from PHP
		$.ajax({
			url: 'php/parser-projectorStatus.php?projector=' + _projectors[i],
			dataType: 'json',
			timeout: 10000,
			
			// Sucessfully loaded the PHP file
			success: function (data, textStatus, jqXHR) {
			
				// Check the reply for errors (if the user supplied some incorrect info, etc)
				if (data.error) {
					dialog.dialog("option", "title", "Uh oh...");
					dialog.html("An error occurred while fetching the projector status from the server: <br /><br />" + data.error_reason);
					dialog.dialog("open");
					return;
				}

				// Update the global tracker
				projectors[data.request_name] = data;
				_tracker++;
			},
			
			// Error loading the file (network, etc)
			error: function (jqXHR, textStatus, errorThrown) {
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while fetching the projector status from the server: <br /><br />" + textStatus + ' -- ' + errorThrown);
				dialog.dialog("open");
			},
			
			// Request is complete, regardless of success/error
			complete: function (jqXHR, textStatus) {
				
				// If this is the last reply (this function is mostly called consecutively, twice)
				if (_tracker == _projectors.length) {
					//console.log(projectors);
					
					$(".projector_control_details").each(function (i){
						var id = $(this).attr('id');
						helper_projectorInterface(id);
					});
				}
			}
		});
	}
}


// Turn the projectors on/off
// (string) _projector = projector ID
// (string) _command = on|off
function projectorPower(_projector, _command) {
	
	// Update the projectors object and GUI
	projectors[_projector].power = _command
	helper_projectorInterface(_projector);
	
	// Handle the common buttons
	helper_commonButtons();
	
	// Send the ajax call
	$.ajax({
		url: 'php/interaction-projector.php?projector='+ _projector +'&command='+ _command,
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				
				// Display error
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while sending commands to the projector: <br /><br />" + data.error_reason);
				dialog.dialog("open");
				
				// Request a new data object from the projector in question (since we hastily overwrote it before the ajax call)
				loadProjectors(_projector);
			}
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			
			// Display error
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while sending commands to the projector: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
			
			// Request a new data object from the projector in question
			loadProjectors(_projector);
		}
	});
}


// Enable/disable blackout in the projectors
// (string) _projector = projector ID
// (string) _command = on|off
// (bool) _noGUI = true|false, whether or not to involve the GUI callbacks.
function projectorShutters(_projector, _command, _noGUI) {
	
	// Update the projectors object and GUI
	projectors[_projector].shutter = _command
	if (!_noGUI) {
		helper_projectorInterface(_projector);
		helper_commonButtons();
	}
	
	// Translate the command to what PHP is expecting
	_command = (_command == 'on') ? 'blackout' : 'normal';
	
	// Send the ajax call
	$.ajax({
		url: 'php/interaction-projector.php?projector='+ _projector +'&command='+ _command,
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				
				// Display error
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while sending commands to the projector: <br /><br />" + data.error_reason);
				dialog.dialog("open");
				
				// Request a new data object from the projector in question (since we hastily overwrote it before the ajax call)
				loadProjectors(_projector);
				loadMatrixState();
			}
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			
			// Display error
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while sending commands to the projector: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
			
			// Request a new data object from the projector in question
			loadProjectors(_projector);
			loadMatrixState();
		}
	});
}


// Detail control for the projectors
// (string) _projector = projector ID
// (string) _command = focus|zoom|hshift|vshift
// (string) _value = inc1|inc2|inc3|dec1|dec2|dec3
function projectorDetailControl(_projector, _command, _value) {
	
	// Check that we got the information we needed
	if (_projector === undefined || _command === undefined || _value === undefined) {
		console.log("Incorrect arguments passed to projectorDetailControl(): `"+ _projector +"`, `"+ _command +"`, `"+ _value +"`");
	}
	
	// Send the ajax call
	$.ajax({
		url: 'php/interaction-projector.php?projector='+ _projector +'&command='+ _command +"&value="+ _value,
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				
				// Display error
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while sending commands to the projector: <br /><br />" + data.error_reason);
				dialog.dialog("open");
			}
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			
			// Display error
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while sending commands to the projector: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
		}
	});
}


// Get the current status of the iBoot device
function loadiBoot() {

	// Load dataset from PHP
	$.ajax({
		url: 'php/parser-ibootStatus.php',
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				//dialog.dialog("option", "title", "Uh oh...");
				//dialog.html("An error occurred while fetching the iBoot status from the server: <br /><br />" + data.error_reason);
				//dialog.dialog("open");
				
				iBoot.power = 'unknown';
				iBoot.error = true;
				return;
			}

			// Update the global tracker
			iBoot.power = data.status;
			iBoot.error = false;
			
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			//dialog.dialog("option", "title", "Uh oh...");
			//dialog.html("An error occurred while fetching the iBoot status from the server: <br /><br />" + textStatus + ' -- ' + errorThrown);
			//dialog.dialog("open");
			
			iBoot.power = 'unknown';
			iBoot.error = true;
		},
		
		// Request is complete, regardless of success/error
		complete: function (jqXHR, textStatus) {
			helper_iboot();
		}
	});
}


// Set the status of the iBoot device (on|off)
// (string) _command = on|off
function iBootPower(_command) {

	// Update the global tracker and interface
	iBoot.power = _command;
	helper_iboot();

	// Load dataset from PHP
	$.ajax({
		url: 'php/interaction-iboot.php?command=' + _command,
		dataType: 'json',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while setting the iBoot power state: <br /><br />" + data.error_reason);
				dialog.dialog("open");
				
				iBoot.power = 'unknown';
				iBoot.error = true;
				loadiBoot();
			}
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while setting the iBoot power state: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
			
			iBoot.power = 'unknown';
			iBoot.error = true;
			loadiBoot();
		}
	});
}


// A function that will save the PHP config file (as edited in the #config tab)
// (string) _content = the content to write to file
function saveConfig(_content) {
	$.ajax({
		url: 'php/interaction-saveConfig.php?authentic',
		type: 'POST',
		data: { content: _content },
		dataType: 'text',
		timeout: 10000,
		
		// Sucessfully loaded the PHP file
		success: function (data, textStatus, jqXHR) {
		
			// Check the reply for errors (if the user supplied some incorrect info, etc)
			if (data.error) {
				dialog.dialog("option", "title", "Uh oh...");
				dialog.html("An error occurred while saving the config file: <br /><br />" + data.error_reason);
				dialog.dialog("open");
				
				$('#save_status').stop();
				$('#save_status').css("opacity", 1);
				$('#save_status').html("Error saving changes");
				$('#save_status').removeClass('red green').addClass('red');
				$('#save_status').fadeTo(3000, 0);
			} else {
				$('#save_status').stop();
				$('#save_status').css("opacity", 1);
				$('#save_status').html("Changes saved");
				$('#save_status').removeClass('red green').addClass('green');
				$('#save_status').fadeTo(3000, 0);
			}
		},
		
		// Error loading the file (network, etc)
		error: function (jqXHR, textStatus, errorThrown) {
			dialog.dialog("option", "title", "Uh oh...");
			dialog.html("An error occurred while saving the config file: <br /><br />" + textStatus + ' -- ' + errorThrown);
			dialog.dialog("open");
			
			$('#save_status').stop();
			$('#save_status').css("opacity", 1);
			$('#save_status').html("Error saving changes");
			$('#save_status').removeClass('red green').addClass('red');
			$('#save_status').fadeTo(3000, 0);
		}
	});
}


// Helper function that will toggle various options based on projector state
// (string) _projector = projector ID
function helper_projectorInterface(_projector) {

	// If this projector is switching at the moment, re-run the process when switching is finished
	var projectorIndex = $.inArray(_projector, projectorsConfig.name);
	if (projectorsConfig.busy[projectorIndex]) {
		setTimeout(
			function(){
				helper_projectorInterface(_projector);
			}, 
			shutterTimer
		);
		return;
	}

	// Print values in the projector control tab
	$("#"+ _projector +" .projector_model").html("Panasonic " + projectors[_projector].projector_type);
	$("#"+ _projector +" .self_check").html(projectors[_projector].self_check.capitalize());
	$("#"+ _projector +" .lamp_runtime").html(projectors[_projector].lamp1_runtime + " hours");
	$("#"+ _projector +" .total_runtime").html(projectors[_projector].projector_runtime + " hours");
	
	// Set values for progress bars
	$("#"+ _projector +" .ambient_temp").parent().progressbar("value", parseInt(projectors[_projector].air_temp_ambient.celcius));
	$("#"+ _projector +" .optics_temp").parent().progressbar("value", parseInt(projectors[_projector].air_temp_optics.celcius));
	$("#"+ _projector +" .lamp_temp").parent().progressbar("value", parseInt(projectors[_projector].air_temp_lamp.celcius));
	$("#"+ _projector +" .filter").parent().progressbar("value", parseInt(projectors[_projector].filter_remaining));

	// Set the proper values for the common buttons on the projectors interface page
	helper_commonButtons();
	
	// Enable disable a `pulsate` effect on the shutter button and shutter text
	var leftRight = _projector.split('_')[1];
	if (projectors[_projector].shutter == "on") {
		pulsate(leftRight, true); // start or continue pulsate effect
	} else {
		pulsateStop(leftRight); // stop pulsating
	}

	// Toggle power buttons
	if (projectors[_projector].power == "on") {
		$("#"+ _projector +" .left2 span").html("Power is ON");
		$("#"+ _projector +" .power").button("option", "label", "Turn power off");
	} else {
		pulsateStop(leftRight); // stop the pulsate effect
		projectors[_projector].shutter = "off"; // shutters cant be on if the projector is off
		$("#"+ _projector +" .left2 span").html("Power is OFF");
		$("#"+ _projector +" .power").button("option", "label", "Turn power on");
	}
	
	// Toggle blackout buttons
	if (projectors[_projector].power == "off") {
		$("#"+ _projector +" .right2 span").html("Blackout is OFF");
		$("#"+ _projector +" .right2 span").addClass("disabled");
		$("#"+ _projector +" .shutter").button("option", "label", "Enable blackout");
		$("#"+ _projector +" .shutter").button("disable");
	} else {
		$("#"+ _projector +" .right2 span").removeClass("disabled");
		$("#"+ _projector +" .shutter").button("enable");
		
		if (projectors[_projector].shutter == "on") {
			$("#"+ _projector +" .right2 span").html("Blackout is ON");
			$("#"+ _projector +" .shutter").button("option", "label", "Disable blackout");
		} else {
			$("#"+ _projector +" .right2 span").html("Blackout is OFF");
			$("#"+ _projector +" .shutter").button("option", "label", "Enable blackout");
		}
	}
	
	// Some layout stuff
	var selfCheckClass = (projectors[_projector].self_check == "no errors") ? "green" : "red";
	$("#"+ _projector +" .self_check").removeClass("red green").addClass(selfCheckClass);

	// Some labels in the matrix grid
	if (projectors[_projector].power == "off") {
		$("#" + _projector + "_status").addClass("red");
		$("#" + _projector + "_status").html("Power is off");
	} else if (projectors[_projector].shutter == "on") {
		$("#" + _projector + "_status").addClass("red");
		$("#" + _projector + "_status").html("Blackout is active");
	} else {
		$("#" + _projector + "_status").removeClass("red");
		$("#" + _projector + "_status").html("&nbsp;");
	}
}


// Helper function that will update the progress bars for the projector shutters while switching input
// (string) _projector = projector ID this relates to
function helper_updateProgress(_projector) {
	var value = $("#"+ _projector +"_status .progressbar").progressbar("value");
	$("#"+ _projector +"_status .progressbar").progressbar("value", value + 1);
	if (value < 99) {
		setTimeout(
			function() {
				helper_updateProgress(_projector);
			}, 
			shutterTimer/100
		);
	}
}


// Helper function for updating the iBoot part of the main interface
function helper_iboot() {
	if (iBoot.initial) {
		iBoot.initial = false;
		$('#iboot button').button("disable");
		$('#iboot .error_text').html("Updating..");
	} else if (iBoot.error) {
		$('#iboot button').button("disable");
		$('#iboot .error_text').html("iBoot device is not reponding...");
		$('#iboot .error_text').addClass('red');
	} else {
		$('#iboot button').button("enable");
		$('#iboot_'+ iBoot.power +' button').button("disable");
		$('#iboot .error_text').html("");
	}
}


// Helper function that will set the correct state for the common blackout/normal buttons (based on the individual states
function helper_commonButtons() {

	// If either projector is off, disable both buttons
	if (projectors[projectorsConfig.name[0]].power == "off" && projectors[projectorsConfig.name[1]].power == "off") {
		$(".common_buttons button").button("disable");
		$(".detail_buttons button").button("disable");
		$('#common_buttons_text').html("Projectors are off");
		return;
	} else if (projectors[projectorsConfig.name[0]].power == "off" || projectors[projectorsConfig.name[1]].power == "off") {
		$(".common_buttons button").button("disable");
		$(".detail_buttons button").button("enable");
		$('#common_buttons_text').html("Projectors are in<br />different power states");
		return;
	} else {
		$(".common_buttons button").button("enable");
		$(".detail_buttons button").button("enable");
		$('#common_buttons_text').html("Click to toggle blackout<br />for both projectors");
	}
	
	// Selectively disable some buttons based on the shutters state for each projector
	if (projectors[projectorsConfig.name[0]].shutter == "on" && projectors[projectorsConfig.name[1]].shutter == "on") {
		$("#button_both_blackout button").button("disable");
	} else if (projectors[projectorsConfig.name[0]].shutter == "off" && projectors[projectorsConfig.name[1]].shutter == "off") {
		$("#button_both_normal button").button("disable");
	}
}