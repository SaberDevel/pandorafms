<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function mainAgentsModules() {
	global $config;
	// Load global vars
	require_once ("include/config.php");
	require_once ("include/functions_reporting.php");

	check_login ();
	// ACL Check
	if (! give_acl ($config['id_user'], 0, "AR")) {
		audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", 
		"Trying to access Agent view (Grouped)");
		require ("general/noaccess.php");
		exit;
	}

	// Update network modules for this group
	// Check for Network FLAG change request
	// Made it a subquery, much faster on both the database and server side
	if (isset ($_GET["update_netgroup"])) {
		$group = get_parameter_get ("update_netgroup", 0);
		if (give_acl ($config['id_user'], $group, "AW")) {
			$sql = sprintf ("UPDATE tagente_modulo SET `flag` = 1 WHERE `id_agente` = ANY(SELECT id_agente FROM tagente WHERE `id_grupo` = %d)",$group);
			process_sql ($sql);
		} else {
			audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to set flag for groups");
			require ("general/noaccess.php");
			exit;
		}
	}


	if ($config["realtimestats"] == 0){
		$updated_time = __('Last update'). " : ". print_timestamp (get_db_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
	} else {
		$updated_time = __("Updated at realtime");
	}

	$offset = get_parameter('offset', 0);
	$hor_offset = get_parameter('hor_offset', 0);
	$block = 20;

	// Header
	print_page_header (__("Agents/Modules"), "images/bricks.png", false, "", false, $updated_time );

	// Old style table, we need a lot of special formatting,don't use table function
	// Prepare old-style table

	$all_modules = get_agent_modules('', false, false, true, false);

	$modules_by_name = array();
	$name = '';
	$cont = 0;

	foreach($all_modules as $key => $module) {
		if($module == $name){
			$modules_by_name[$cont-1]['id'][] = $key;
		}
		else{
			$name = $module;
			$modules_by_name[$cont]['name'] = $name;
			$modules_by_name[$cont]['id'][] = $key;
			$cont ++;
		}
	}

	echo '<table cellpadding="1" cellspacing="4" cellspacing="0" border="0" style="background-color: #EEE;">';

	echo "<th width='140px'>".__("Agents")." \\ ".__("Modules")."</th>";	

	$agents = get_agents (array ('offset' => (int) $offset,
				'limit' => (int) $config['block_size']));
	$nagents = count($agents);
		
	if($hor_offset > 0) {
		$new_hor_offset = $hor_offset-$block;
		echo "<th width='20px' rowspan='".($nagents+1)."'><a href='index.php?sec=extensions&sec2=extensions/agents_modules&refr=0&hor_offset=".$new_hor_offset."&offset=".$offset."'>".print_image("images/darrowleft.png",true, array('title' => __('Previous modules')))."</a> </th>";
	}
		$nmodules = 0;
	foreach($modules_by_name as $module) {
		$nmodules++;
		
		if($nmodules <= $hor_offset || $nmodules > ($hor_offset+$block)) {
			continue;
		}
		$file_name = string2image(printTruncateText($module['name'],10, false, true, false, '...'), 90, 15, 3, 270, '#9EAC8B', 'FFF', 4, 0);
		echo "<th width='24px'>".print_image($file_name, true, array('title' => $module['name']))."</th>";
	}
				
		if(($hor_offset + $block) < $nmodules) {
			$new_hor_offset = $hor_offset+$block;
			echo "<th width='20px' rowspan='".($nagents+1)."'><a href='index.php?sec=extensions&sec2=extensions/agents_modules&refr=0&hor_offset=".$new_hor_offset."&offset=".$offset."'>".print_image("images/darrowright.png",true, array('title' => __('More modules')))."</a> </th>";
		}

	// Prepare pagination
	pagination ((int)count(get_agents ()));
	echo "<br>";

	foreach ($agents as $agent) {
		// Get stats for this group
		$data = get_agent_module_info($agent['id_agente']);

		// Calculate entire row color
		if ($data["monitor_alertsfired"] > 0){
			$rowcolor = '#ffa300';
			$textcolor = '#000';
		} elseif ($data["monitor_critical"] > 0) {
			$rowcolor = '#bc0000';
			$textcolor = '#FFF';
		} elseif ($data["monitor_warning"] > 0) {
			$rowcolor = '#f2ef00';
			$textcolor = '#000';
		} elseif ($data["monitor_unknown"] > 0) {
			$rowcolor = '#babdb6';
			$textcolor = '#000';
		} elseif ($data["monitor_normal"] > 0)  {
			$rowcolor = '#8ae234';
			$textcolor = '#000';
		} else {
			$rowcolor = '#babdb6';
			$textcolor = '#000';
		}
		
		echo "<tr style='height: 35px;'>";
		
		$file_name = string2image(printTruncateText($agent['nombre'],20, false, true, false, '...'), 140, 15, 3, 0, $rowcolor, $textcolor, 4, 0);
		echo "<td style='background-color: ".$rowcolor.";'>".print_image($file_name, true, array('title' => $agent['nombre']))."</td>";
		$agent_modules = get_agent_modules($agent['id_agente']);
		
		$nmodules = 0;
		
		foreach($modules_by_name as $module) {
			$nmodules++;
			
			if($nmodules <= $hor_offset || $nmodules > ($hor_offset+$block)) {
				continue;
			}
			
			$match = false;
			
			foreach($module['id'] as $module_id){
				if(!$match && array_key_exists($module_id,$agent_modules)) {
					$status = get_agentmodule_status($module_id);
					echo "<td style='text-align: center; background-color: #DDD;'>";
					switch($status){
						case 0:
							print_status_image ('module_ok.png', $module['name']." in ".$agent['nombre'].": ".__('NORMAL'), false, array('width' => '20px', 'height' => '20px'));
							break;
						case 1:
							print_status_image ('module_critical.png', $module['name']." in ".$agent['nombre'].": ".__('CRITICAL'), false, array('width' => '20px', 'height' => '20px'));
							break;
						case 2:
							print_status_image ('module_warning.png', $module['name']." in ".$agent['nombre'].": ".__('WARNING'), false, array('width' => '20px', 'height' => '20px'));
							break;
						case 3:
							print_status_image ('module_unknown.png', $module['name']." in ".$agent['nombre'].": ".__('UNKNOWN'), false, array('width' => '20px', 'height' => '20px'));
							break;
						case 4:
							print_status_image ('module_alertsfired.png', $module['name']." in ".$agent['nombre'].": ".__('ALERTS FIRED'), false, array('width' => '20px', 'height' => '20px'));
							break;
					}
					echo "</td>";
					$match = true;
				}		
			}
			
			if(!$match) {
				echo "<td style='background-color: #DDD;'></td>";
			}
		}
		
		echo "</tr>";
	}

	echo "</table>";
	
		echo "<br><br><p>" . __("The colours meaning:") .
		"<ul style='float: left;'>" .
		'<li style="clear: both;">
			<div style="float: left; background: #ffa300; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
			__("Orange cell when the module have fired alerts.") .
		'</li>' .
		'<li style="clear: both;">
			<div style="float: left; background: #cc0000; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
			__("Red cell when the module have critical state.") .
		'</li>' .
		'<li style="clear: both;">
			<div style="float: left; background: #fce94f; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
			__("Yellow cell when the module have warning state.") .
		'</li>' .
		'<li style="clear: both;">
			<div style="float: left; background: #babdb6; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
			__("Grey cell when the module have unknown state.") .
		'</li>' .
		'<li style="clear: both;">
			<div style="float: left; background: #8ae234; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
			__("Green cell when the module have normal state.") .
		'</li>' .
		"</ul>" .
	"</p>";
}

add_operation_menu_option(__("Agents/Modules view"), 'estado', '');
add_extension_main_function('mainAgentsModules');

?>

