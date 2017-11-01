<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Runsheet for focus groups</title>
	<script type="text/javascript" src="js/jquery-2.0.0.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			
			var projector_left = "10.148.192.47";
			var projector_right = "10.148.192.48";
			var gefen_switch = "10.148.192.53";
			
			var previous = "#bbb";
			var current = "#6ac352";
			var future = "#000"; 
		
			// Switch
			//'http://10.148.192.53/cgibin.shtml?a=s&o='. $gefencodes[$out] .'&i='. $gefencodes[$in]

			// Shutters on
			//'http://'. $projector_left .'/cgi-bin/proj_ctl.cgi?key=shutter_on&lang=e&osd=on'
			//'http://'. $projector_right .'/cgi-bin/proj_ctl.cgi?key=shutter_on&lang=e&osd=on'

			// Shutters off
			//'http://'. $projector_left .'/cgi-bin/proj_ctl.cgi?key=shutter_off&lang=e&osd=on'
			//'http://'. $projector_right .'/cgi-bin/proj_ctl.cgi?key=shutter_off&lang=e&osd=on'
			
			// OUT:
			// 0 = LEFT
			// 1 = RIGHT
			
			// IN:
			// 0+1 = Mocap 1
			// 4+5 = Mocap 3
			// 6+7 = Mocap 4
			// 8+9 = Mocap 5
			
			function get(url) {
				$.ajax({
				  url: url,
				  dataType: "json"
				});
			}


			// Bind some click-actions to the menu buttons
			$('a').click(function() {
			
				// The link's attribute id
				var id = parseInt($(this).attr("href").substring(1));


				/* SEND AJAX CALLS */
				// Mocap 5
				//if (id == 1 || id == 9 || id == 11 || id == 21) {
          //get('php/interaction-matrix.php?input=8&output=0');
          //get('php/interaction-matrix.php?input=9&output=1');
				
				// Mocap 1
				if (id == 3 || id == 9 || id == 11 || id == 21) {
          get('php/interaction-matrix.php?input=0&output=0');
          get('php/interaction-matrix.php?input=1&output=1');
				
				// Mocap 4 
				} else if (id == 5 || id == 13 || id == 17) {
          get('php/interaction-matrix.php?input=6&output=0');
          get('php/interaction-matrix.php?input=7&output=1');
				
				// Mocap 3
				} else if (id == 1 || id == 7 || id == 15 || id == 19) {
          get('php/interaction-matrix.php?input=4&output=0');
          get('php/interaction-matrix.php?input=5&output=1');
				
				// Laptop/spare input
				} else if (id == 0) {
					get('php/interaction-matrix.php?input=10&output=0');
          get('php/interaction-matrix.php?input=10&output=1');
				}
				
				// Blackout
				if ($(this).text() == "Blackout") {
					get('php/interaction-projector.php?projector=projector_left&command=blackout');
					get('php/interaction-projector.php?projector=projector_right&command=blackout');
				
				// Mono if needed
				} else if (id < 9) {
          get('php/interaction-projector.php?projector=projector_left&command=normal');
					get('php/interaction-projector.php?projector=projector_right&command=blackout');
					
				// Default to stereo if no other instructions
				} else {
					get('php/interaction-projector.php?projector=projector_left&command=normal');
					get('php/interaction-projector.php?projector=projector_right&command=normal');
				}
			
				// Set new color for text
				$( "li ol li a" ).each(function( index ) {
					var id2 = parseInt($(this).attr("href").substring(1));
					//alert(id2);
					
					// Previous states
					if (id2 < id) {
						$(this).css("color", previous);
					
					// Current state
					} else if (id2 == id) {
						$(this).css("color", current);
					
					// Future states
					} else {
						$(this).css("color", future);
					}
				});
					
					
				// Return false to avoid browser fetching the request and setting the #id in the url
				return false;
			});
		});
	</script>
	
	<style type="text/css">
		li {
			margin-top: 2px;
		}
		.header {
			margin-top: 15px;
		}
		#main {
			padding-left: 40px;
			padding-top: 20px;
		}
		a:link, a:visited, a:hover, a:active {
      color: #000;
      text-decoration: none;
		}
		a:hover {
      color: #5396b9;
		}
	</style>
</head>

<body>
	<div id="main">
		<h2>Runsheet for Focus Groups</h2>
		
		<ol style="font-weight: bold;">
			<li class="header">Introduction (MONO)</li>
			<li style="list-style-type:none; font-weight: normal;">
				<ol type="a">
          <li><a href="#0"><em>Laptop: Kim presentation</em></a></li>
					<li><a href="#1">Mocap3: Commercial &amp; research showreel (video)</a></li>
					<li><a href="#2"><em>Blackout</em></a></li>
					<li><a href="#3">Mocap1: Cortex demo</a></li>
					<!--<li><a href="#4"><em>Blackout</em></a></li>-->
					<li><a href="#5">Mocap4: Andy on grid</a></li>
					<!--<li><a href="#6"><em>Blackout</em></a></li>-->
					<li><a href="#7">Mocap3: Andy in forest</a></li>
					<li><a href="#8"><em>Blackout</em></a></li>
				</ol>
			</li>
			<li class="header" value="2">Post-production with motion capture (STEREO)</li>
			<li style="list-style-type:none; font-weight: normal;">
				<ol type="a">
					<li><a href="#9">Mocap1: Silver surfer, Ben's dancing man &amp; Crack-up head (video)</a></li>
					<li><a href="#10"><em>Blackout</em></a></li>
				</ol>
			</li>
			<li class="header" value="3">Post-production animation as scenography (STEREO)</li>
			<li style="list-style-type:none; font-weight: normal;">
				<ol type="a">
					<li><a href="#11">Mocap1: Magic carpet, Vic opera &amp; Torus (video)</a></li>
					<li><a href="#12"><em>Blackout</em></a></li>
				</ol>
			</li>
			<li class="header" value="4">Live interactive performance (STEREO)</li>
			<li style="list-style-type:none; font-weight: normal;">
				<ol type="a">
					<li><a href="#13">Mocap4: Blobs</a></li>
					<li><a href="#14"><em>Blackout</em></a></li>
					<li><a href="#15">Mocap3: Dress</a></li>
					<li><a href="#16"><em>Blackout</em></a></li>
					<li><a href="#17">Mocap4: Spikey</a></li>
					<li><a href="#18"><em>Blackout</em></a></li>
				</ol>
			</li>
			<li class="header" value="5">Dancer in a virtual scene (STEREO)</li>
			<li style="list-style-type:none; font-weight: normal;">
				<ol type="a">
					<li><a href="#19">Mocap3: Empty forest scene</a></li>
					<li><a href="#20"><em>Blackout</em></a></li>
					<li><a href="#21">Mocap1: Fronds (video)</a></li>
					<li><a href="#22"><em>Blackout</em></a></li>
				</ol>
			</li>
		</ol>
	</div>
</body>
</html>