<?php
	
	// Require the configuration file
	require_once("php/config.php");
	
	// Check if we should be using a custom display order
	$uniqueOrder = array_unique($config['outputs_displayOrder']);
	$useCustomOrder = (count($uniqueOrder) == count($config['outputs'])) ? true : false;
	
	// Figure out what our projectors are called
	$projectors = array();
	$tracker = 0;
	for ($i=0; $i<count($config['outputs']); $i++) {
		$ref = ($useCustomOrder) ? $config['outputs_displayOrder'][$i] : $i;
		if ($config['outputs'][$ref]['isProjector']) {
			$projectors[$tracker] = $config['outputs'][$ref];
			$projectors[$tracker]['ref'] = $ref;
			$tracker++;
		}
	}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Video Switcher Controlpanel</title>
	<link rel="stylesheet" type="text/css" media="all" href="css/smoothness/jquery-ui-1.10.4.custom.css" />
	<link rel="stylesheet" type="text/css" media="all" href="js/context-menu/jquery.contextMenu.css" />
	<link rel="stylesheet" type="text/css" media="all" href="css/main.css" />
	<link rel="stylesheet" type="text/css" media="all" href="codemirror/lib/codemirror.css">	
	<script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>
	<script type="text/javascript" src="js/context-menu/jquery.contextMenu.js"></script>
	<script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript" src="js/ajax.js"></script>
	
	<script src="codemirror/lib/codemirror.js"></script>
	<script src="codemirror/mode/php/php.js"></script>
	<script src="codemirror/mode/htmlmixed/htmlmixed.js"></script>
	<script src="codemirror/mode/xml/xml.js"></script>
	<script src="codemirror/mode/javascript/javascript.js"></script>
	<script src="codemirror/mode/clike/clike.js"></script>
	
	<script type="text/javascript">
		// Written by PHP to avoid waiting for the first ajax infusion
		var projectorsConfig = {
				monitorID: [<?php echo $projectors[0]['ref'] .", ". $projectors[1]['ref']; ?>],
				name: [<?php echo "'". $projectors[0]['projectorID'] ."', '". $projectors[1]['projectorID'] ."'"; ?>],
				busy: [false, false]
		}
	</script>
</head>

<body>
	<div id="menu">
		<ul>
			<li><a class="active_li" href="#matrix_control">Matrix control</a></li>
			<li><a href="#presets">Matrix presets</a></li>
			<li><a href="#projector_control">Projector control</a></li>
			<li><a href="#iboot">iBoot device</a></li>
			<li><a href="#config">Configuration</a></li>
		</ul>
	</div>
	<div id="content" class="ui-corner-bottom">
		<div id="matrix_control" class="tab_content">
			<h1>Matrix switching state</h1>
			<p>Using the buttons below, you can route any input to any number of outputs. 
			Click the buttons symbolising the ouputs to view a list of available inputs to choose from.
			<!--<br /><br /><a style="text-decoration:none;" href="../controlpanel_old" target="_blank">&#8680; Click here to access the 8x8 matrix interface (for relay switching)</a>-->
			</p>
			
			<?php
				
				// Check if we should be using a custom display order
				$uniqueOrder = array_unique($config['outputs_displayOrder']);
				$useCustomOrder = (count($uniqueOrder) == count($config['outputs'])) ? true : false;
				
				// Loop through all the outputs and create a button for each
				for ($i=0; $i<count($config['outputs']); $i++) {
					
					// If we're half way, insert a line break (only works if no one screwed up the config)
					if ($i == count($config['outputs'])/2) {
						echo "<hr />\n";
					}
					
					// Insert the button
					$ref = ($useCustomOrder) ? $config['outputs_displayOrder'][$i] : $i;
					echo ($config['outputs'][$ref]['enabled']) ? "<button id=\"output_". $ref ."\">" : "<button id=\"output_". $ref ."\" disabled=\"disabled\">";
					echo "<span>". $config['outputs'][$ref]['name'] ."</span>";
					echo "</button>\n";
				}
				
				// Output one box for each input-thumbnail (we'll position this overlapping with the output box)
				for ($i=0; $i<count($config['inputs']); $i++) {
					$enabled = ($config['outputs'][$i]['enabled']) ? "" : " ui-state-disabled";
					
					echo "<div id=\"thumb_". $i ."\" class=\"thumbs ui-corner-all". $enabled ."\"><span class=\"header\">Source</span><span class=\"content\">...</span></div>\n";
				}
			?>
			
			<div id="projector_left_status" class="projector_status">...</div>
			<div id="projector_right_status" class="projector_status">...</div>
			
			<div id="input_panel">
				<h2>Choose an input for monitor <span>test</span>:</h2>
				<?php
					
					// Check if we should be using a custom display order
					$uniqueOrder = array_unique($config['inputs_displayOrder']);
					$useCustomOrder = (count($uniqueOrder) == count($config['inputs'])) ? true : false;
					
					// Print the input boxes
					for ($i=0; $i<count($config['inputs']); $i++) {
						$ref = ($useCustomOrder) ? $config['inputs_displayOrder'][$i] : $i;
						
						// If we're half way, insert a line break (only works if no one screwed up the config)
						if ($i == count($config['inputs'])/2) {
							echo "<hr />\n";
						}
						
						echo ($config['inputs'][$ref]['enabled']) ? "<button id=\"input_". $ref ."\">" : "<button id=\"input_". $ref ."\" disabled=\"disabled\">";
						echo "<span>". $config['inputs'][$ref]['name'] ."</span>";
						echo "</button>\n";
					}
				
				?>
				<div class="ui-state-default ui-state-hover ui-corner-all">
					<span class="ui-icon ui-icon-closethick"></span>
				</div>
			</div>
		</div>
		<div id="presets" class="tab_content">
			<h1>Matrix switching presets</h1>
			<p>With the buttons below you can quickly set the matrix switch to a handful of pre-defined states. Disabled buttons indicate that the current state matches that particular preset.</p>
			<div class="padding">
				<?php 
					foreach ($config['presets'] as $preset) {
						$switching = "switch-data";
						foreach ($preset['switching'] as $out=>$in) {
							$switching .= " switch_". $out ."_". $in;
						}
						echo '<span class="'. trim($switching) .'">' ."<button>". $preset['name'] ."</button></span>";
					}
				?>
			</div>
		</div>
		<div id="projector_control" class="tab_content">
			<h1>Projector control</h1>
			<p>In the two columns below you will find basic operational controls and statistics for the projectors. The buttons in the bottom panel will let you modify some of the advanced features, like lens shift, focus, and zoom.</p>
			
			<?php for ($i=0; $i<2; $i++) {; ?>
			<div id="<?php echo $projectors[$i]['projectorID']; ?>" class="<?php echo ($i==0) ? "left" : "right"; ?> projector_control_details">
			 	<h2 class="ui-corner-top"><?php echo strip_tags($projectors[$i]['name']); ?></h2>
			 	<div class="ui-corner-bottom">
					<span class="projector_model">...</span>
					<div class="left2">
						<span>...</span>
						<!--<h3>Power:</h3>--><button class="power">...</button>
					</div>
					<div class="right2">
						<span>...</span>
						<!--<h3>Blackout:</h3>--><button class="shutter">...</button>
					</div>
					<hr />
					<br /><br />
					<h4>Self check:</h4><span class="self_check">...</span><br />
					<h4>Lamp runtime:</h4><span class="lamp_runtime">...</span><br />
					<h4>Total runtime:</h4><span class="total_runtime">...</span>
					
					<br /><br />
					
					<h4>Filter remaining:</h4><div class="progressbar"><span class="filter">...</span></div><br />
					<h4>Lamp temperature:</h4><div class="progressbar"><span class="lamp_temp">...</span></div><br />
					<h4>Optics temperature:</h4><div class="progressbar"><span class="optics_temp">...</span></div><br />
					<h4>Ambient temperature:</h4><div class="progressbar"><span class="ambient_temp">...</span></div><br />
				</div>	
			</div>
			<?php }; ?>
			
			<hr class="clear" />
			
			<div class="common">
				<h2 class="ui-corner-top">Detailed projector control</h2>
			 	<div class="ui-corner-bottom">
					<div class="left2 detail_buttons"><button>Lens shift</button></div>
					<div class="right2 detail_buttons"><button>Focus</button></div>
					<div class="right2 detail_buttons"><button>Zoom</button></div>
					<div id="button_both_blackout" class="left3 common_buttons"><button>Enable blackout</button></div>
					<div id="button_both_normal" class="right2 common_buttons"><button>Disable blackout</button></div>
					<div id="common_buttons_text" class="right2">...</div>
				</div>
			</div>
			<div id="projector_detail_control">
				<h2>...</h2>
				<div class="ui-state-default ui-state-hover ui-corner-all">
					<span class="ui-icon ui-icon-closethick"></span>
				</div>
				<?php for ($i=0; $i<2; $i++) {; ?>
				<div class="<?php echo ($i==0) ? "left" : "right"; ?>">
					<table class="projector_control_table <?php echo $projectors[$i]['projectorID']; ?>">
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td class="th_top">&nbsp;</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td><img class="ui-corner-all vertical_inc3 vertical_arrow" src="pics/arrows_triple_north.png" alt="Increase 3x" title="Increase 3x" /></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td><img class="ui-corner-all vertical_inc2 vertical_arrow" src="pics/arrows_double_north.png" alt="Increase 2x" title="Increase 2x" /></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td><img class="ui-corner-all vertical_inc1 vertical_arrow" src="pics/arrows_single_north.png" alt="Increase 2x" title="Increase 2x" /></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td class="th_left"></td>
							<td><img class="ui-corner-all horizontal_dec3 horizontal_arrow" src="pics/arrows_triple_west.png" alt="Increase 3x" title="Decrease 3x" /></td>
							<td><img class="ui-corner-all horizontal_dec2 horizontal_arrow" src="pics/arrows_double_west.png" alt="Increase 3x" title="Decrease 2x" /></td>
							<td><img class="ui-corner-all horizontal_dec1 horizontal_arrow" src="pics/arrows_single_west.png" alt="Increase 3x" title="Decrease 1x" /></td>
							<td class="center_td">...</td>
							<td><img class="ui-corner-all horizontal_inc1 horizontal_arrow" src="pics/arrows_single_east.png" alt="Increase 3x" title="Increase 1x" /></td>
							<td><img class="ui-corner-all horizontal_inc2 horizontal_arrow" src="pics/arrows_double_east.png" alt="Increase 3x" title="Increase 2x" /></td>
							<td><img class="ui-corner-all horizontal_inc3 horizontal_arrow" src="pics/arrows_triple_east.png" alt="Increase 3x" title="Increase 3x" /></td>
							<td class="th_right"></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td><img class="ui-corner-all vertical_dec1 vertical_arrow" src="pics/arrows_single_south.png" alt="Increase 1x" title="Decrease 1x" /></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td><img class="ui-corner-all vertical_dec2 vertical_arrow" src="pics/arrows_double_south.png" alt="Increase 2x" title="Decrease 2x" /></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td><img class="ui-corner-all vertical_dec3 vertical_arrow" src="pics/arrows_triple_south.png" alt="Increase 3x" title="Decrease 3x" /></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td class="th_bottom">&nbsp;</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</table>
				</div>
				<?php }; ?>
			</div>
		</div>
		<div id="iboot" class="tab_content">
			<h1>iBoot device (stopwatch power supply)</h1>
			<p>The iBoot device sits between the stopwatch display and the mains power, enabling you to turn it on and off using the buttons below.</p>
			<div class="padding">
				<span class="error_text">...</span>
				<span id="iboot_on"><button>Turn power on</button></span>
				<span id="iboot_off"><button>Turn power off</button></span>
			</div>
		</div>
		<div id="config" class="tab_content">
			<h1>Controlpanel settings</h1>
			<p>Use the editor below to make changes to the config.php file. Keep in mind that errors in the code structure will prevent PHP from compiling the file properly.
			<br />For the changes to take effect (besides what you see in the editor itself), you will need to reload the controlpanel page.</p>
			<textarea id="php_output"><?php
				
					// Print the config file
					//highlight_file('php/config.php');
					readfile('php/config.php');
				
				?>
			</textarea>
			<span id="save_status" class="green">&nbsp;</span>
		</div>
	</div>
	<div id="dialog" title="Title">
		<p>Content</p>
	</div>
	<div id="confirmPower" title="Are you sure?">
		<p>Are you sure you want to power this projector off?</p>
	</div>
</body>
</html>