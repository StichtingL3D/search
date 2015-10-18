<?php

define('NL', "\n");
function _print_r($a) { echo '<pre>'; print_r($a); echo '</pre>'; }
function debug($var, $key) { echo '<div style="border: 3px solid red;"><strong>'.$key.':</strong> '.$var.'</div>'; }

function linkTo($title, $url, $query) {
	$title = highlight($title, $query);
	return '<li><a href="'.$url.'">'.$title.'</a></li>'.NL;
}
function highlight($text, $keyword) {
	$searchText = strtolower($text);
	$start = strpos($searchText, $keyword);
	#debug($start, '$start');
	if ($start !== false) {
		$end = $start + strlen($keyword) + strlen('<span class="highlight">');
		$text = substr_replace($text, '<span class="highlight">', $start, 0);
		$text = substr_replace($text, '</span>', $end, 0);
	}
	return $text;
}
function sqlGetResults($query, $handle) {
	$resultSet = $handle->query($query);
	$results = array();
	while($result = $resultSet->fetch_assoc()) {
		$results[] = $result;
	}
	return $results;
}

function skipHiddenWorlds($world_results, $worlds) {
	$new_results = array();
	foreach ($world_results as $key => $world) {
		$uni['mysql']['handle'] = new mysqli($worlds['mysql']['host'], $worlds['mysql']['user'], $worlds['mysql']['pass'], 'aw_universe');
		$uni['mysql']['query'] = "SELECT Name, Hidden FROM awu_license WHERE Name LIKE '".$world['Name']."';";
		$world_info = sqlGetResults($uni['mysql']['query'], $uni['mysql']['handle']);
		if ($world_info[0]['Hidden'] != 1) {
			$new_results[$key] = $world;
		}
	}
	
	return $new_results;
}

$query = '';
if (!empty($_GET['q'])) {
	$query = $_GET['q'];
	$query = preg_replace('|[^a-zA-Z0-9 ]|', '', $query);
	
	// log
	@file_put_contents('./keywords.log', date('Y/m/d', time())."\t".$query.NL, FILE_APPEND);
	
	// worlds
	$worlds['mysql']['host'] = 'localhost';
	$worlds['mysql']['user'] = 'aw';
	$worlds['mysql']['pass'] = '...';
	
	$worlds['worldservers'] = array(
		'school1', 'l3d', 'intern', 'school2', 'speciaal',
	);
	$worlds['resultsName'] = array();
	$worlds['resultsKeywords'] = array();
	foreach ($worlds['worldservers'] as $worldserver) {
		$worlds['mysql']['name'] = 'aw_w'.$worldserver;
		$worlds['mysql']['handle'] = new mysqli($worlds['mysql']['host'], $worlds['mysql']['user'], $worlds['mysql']['pass'], $worlds['mysql']['name']);
		$worlds['mysql']['arg']['q'] = $worlds['mysql']['handle']->real_escape_string($query);
		$worlds['mysql']['query'] = "SELECT ID, Name FROM aws_world WHERE Enabled = '1' AND Name LIKE '%".$worlds['mysql']['arg']['q']."%';";
		$worlds['resultsName'] = array_merge($worlds['resultsName'], sqlGetResults($worlds['mysql']['query'], $worlds['mysql']['handle']));
		$worlds['mysql']['query'] = "SELECT Name, Value FROM aws_world, aws_attrib WHERE aws_world.Enabled = '1' AND aws_world.ID = aws_attrib.World AND aws_attrib.ID = '58' AND Value LIKE '%".$worlds['mysql']['arg']['q']."%';";
		$worlds['resultsKeywords'] = array_merge($worlds['resultsKeywords'], sqlGetResults($worlds['mysql']['query'], $worlds['mysql']['handle']));
	}
	
	// don't show hidden worlds
	$worlds['resultsName'] = skipHiddenWorlds($worlds['resultsName'], $worlds);
	$worlds['resultsKeywords'] = skipHiddenWorlds($worlds['resultsKeywords'], $worlds);
	
	$worlds['prefixLink'] = 'http://tools.l3d.nl/teleport/';
	// urls of scheme 'teleport:' don't work in the tabs, only in the web sidebar
	
	// 3dwiki
	$wiki['searchTitle'] = 'http://www.3dwiki.nl/w/api.php?action=query&list=search&srsearch='.$query.'&srwhat=title&format=php';
	$wiki['searchText'] = 'http://www.3dwiki.nl/w/api.php?action=query&list=search&srsearch='.$query.'&srwhat=text&format=php';
	$wiki['resultsTitle'] = unserialize(file_get_contents($wiki['searchTitle']));
	$wiki['resultsText'] = unserialize(file_get_contents($wiki['searchText']));
	$wiki['prefixLink'] = 'http://www.3dwiki.nl/wiki/';
	
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>L3Daw zoeken</title>
	<style>
	body {
		margin: 0;
		padding-left: 5px;
		font-family: Verdana;
		font-size: small;
		font-weight: normal;
	}
	
	#header {
		margin-left: -5px;
		color: white;
		background-color: #AA122B;
	}
	#header h1, #header a, #header a:hover, #header a:active, #header p {
		color: white;
	}
	#header h1 {
		margin-top: 0;
		margin-bottom: 0;
	}
	#header a {
		display: block;
		padding: 0.25em;
		padding-left: 5px;
		text-decoration: none;
	}
	#header p {
		margin-top: -0.5em;
		padding-left: 5px;
		padding-bottom: 0.5em;
		text-indent: 55px;
		font-style: italic;
	}
	
	h2, h3, h4, h5, h6 {
		color: #AA122B;
	}
	a {
		color: #00E;
	}
	a:hover, a:active {
		text-decoration: underline;
		color: #AA122B;
	}
	
	ul {
		margin-left: 0;
		padding-left: 0;
		list-style-type: none;
	}
	li {
		margin: 0.25em 0;
		height: 1.25em;
		overflow: hidden;
	}
	.highlight {
		font-weight: bold;
	}
	
	form {
		margin-top: 0.5em;
		margin-bottom: 0.25em;
	}
	#intro {
		margin-top: 0.25em;
		color: #888;
		font-style: italic;
	}
	#intro a {
		text-decoration: none;
		color: #55E;
	}
	#intro:hover {
		color: black;
	}
	#intro:hover a {
		text-decoration: underline;
		color: #00E;
	}
	#intro a:hover, #intro a:active {
		color: #AA122B;
	}
	#query {
		width: 130px;
	}
	</style>
</head>
<body>
	<div id="header">
		<h1><a href="./">zoeken</a></h1>
		<p>in projecten en 3Dwiki</p>
	</div>
	<form method="GET">
		<input id="query" type="text" name="q" value="<?php echo $query; ?>">
		<input id="submit" type="submit" value="Zoeken">
	</form>
	<p id="intro">voorbeeld:
		<a href="?q=vision">vision</a>,
		<a href="?q=kunst">kunst</a>,
		<a href="?q=leren">leren</a>
	</p>
	</ul>
<?php
if (!empty($worlds['resultsName']) || !empty($worlds['resultsKeywords'])) {
	echo '<h2>Werelden</h2>'.NL;
	echo '<ul>'.NL;
	foreach ($worlds['resultsName'] as $result) {
		echo linkTo($result['Name'], $worlds['prefixLink'].$result['Name'], $query);
	}
	foreach ($worlds['resultsKeywords'] as $result) {
		echo linkTo($result['Name'].' - '.$result['Value'], $worlds['prefixLink'].$result['Name'], $query);
	}
	echo '</ul>'.NL;
}

if (!empty($wiki['resultsTitle']['query']['search']) || !empty($wiki['resultsText']['query']['search'])) {
	echo '<h2>3Dwiki</h2>'.NL;
	echo '<ul>'.NL;
	foreach ($wiki['resultsTitle']['query']['search'] as $result) {
		echo linkTo($result['title'], $wiki['prefixLink'].$result['title'], $query);
	}
	foreach ($wiki['resultsText']['query']['search'] as $result) {
		echo linkTo($result['title'], $wiki['prefixLink'].$result['title'], $query);
	}
	echo '</ul>'.NL;
}
?>
</body>
</html>
