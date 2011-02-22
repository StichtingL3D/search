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

$query = '';
if (!empty($_GET['q'])) {
	$query = $_GET['q'];
	$query = preg_replace('|[^a-zA-Z0-9 ]|', '', $query);
	
	// log
	@file_put_contents('./keywords.log', date('Y/m/d', time())."\t".$query.NL, FILE_APPEND);
	
	// worlds
	$worlds['mysql']['host'] = 'localhost';
	$worlds['mysql']['user'] = 'aw';
	$worlds['mysql']['pass'] = base64_decode('dGE1ZXNXdT1hP2F4dV9fcQ==');
	
	$worlds['worldservers'] = array(
		'school1', 'l3d', 'intern', 'school2', 'speciaal',
	);
	$worlds['resultsName'] = array();
	$worlds['resultsKeywords'] = array();
	foreach ($worlds['worldservers'] as $worldserver) {
		$worlds['mysql']['name'] = 'aw_w'.$worldserver;
		$worlds['mysql']['handle'] = new mysqli($worlds['mysql']['host'], $worlds['mysql']['user'], $worlds['mysql']['pass'], $worlds['mysql']['name']);
		$worlds['mysql']['arg']['q'] = $worlds['mysql']['handle']->real_escape_string($query);
		$worlds['mysql']['query'] = "SELECT ID, Name FROM aws_world WHERE Name LIKE '%".$worlds['mysql']['arg']['q']."%';";
		$worlds['resultsName'] = array_merge($worlds['resultsName'], sqlGetResults($worlds['mysql']['query'], $worlds['mysql']['handle']));
		$worlds['mysql']['query'] = "SELECT Name, Value FROM aws_world, aws_attrib WHERE aws_world.ID = aws_attrib.World AND aws_attrib.ID = '58' AND Value LIKE '%".$worlds['mysql']['arg']['q']."%';";
		$worlds['resultsKeywords'] = array_merge($worlds['resultsKeywords'], sqlGetResults($worlds['mysql']['query'], $worlds['mysql']['handle']));
	}
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
		font-family: Verdana;
		font-size: small;
		font-weight: normal;
	}
	a {
		text-decoration: none;
		color: #00E;
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
	</style>
</head>
<body>
	<h1><!--L3Daw -->zoeken</h1>
	<form method="GET">
		<input type="text" name="q" value="<?php echo $query; ?>">
		<input type="submit" value="Zoeken">
	</form>
	<p>bijvoorbeeld:
		<a href="?q=vision">vision</a>,
		<a href="?q=kunst">kunst</a>,
		<a href="?q=leren">leren</a>,
		<a href="?q=robot">robot</a>
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
