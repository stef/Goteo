<?php
//	TODO:
//	remove double entries from .po files
//	swap spanish and english

	$numfiles = 0;
	$numstrings = 0;
	$notfound = 0;
	$multiplefound = 0;
	
	$path = new RecursiveDirectoryIterator('../');
	$iterator = new RecursiveIteratorIterator($path);
	$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
	
	$database = file_get_contents('../db/goteo.sql');
	$database.= file_get_contents('../db/pages.sql');
	$database.= file_get_contents('../db/texts.sql');
	
	$po = array();
	
	function text2gettext($search) {
		global $numstrings;
		global $notfound;
		global $multiplefound;
		global $database;
		global $po;

		$numstrings++;
		
		// match search key with full text replacement
		$pattern = '/\(\''.$search[1].'\', \'(.*?)\', NULL, \'.*?\'\)/';
		if(!preg_match_all($pattern, $database, $matches)) {
			$notfound++;
			return '';
		}
		$replacement = '';
		foreach($matches[1] as $match) {
			if (!$replacement) $replacement = $match;
			elseif($replacement!=$match) $multiplefound++;
		}
		
		// find translations and write po array
		$pattern = '/\(\''.$search[1].'\', \'(..)\', \'(.*?)\'\)/';
		if(preg_match_all($pattern, $database, $matches))
			for ($i=0;$i<count($matches[0]);$i++)
				$po[$matches[1][$i]].= 'msgid "'.$replacement.'"'."\n".'msgstr "'.$matches[2][$i].'"'."\n\n";

		return 'Text::_("'.$replacement.'")';
	}
	
	foreach($files as $filename => $object){
		$numfiles++;
		$text = file_get_contents($filename);
		$text = preg_replace_callback("/Text::get\('(.*?)'\)/", 'text2gettext', $text);
		file_put_contents($filename, $text);
	}
	echo 'total: '.$numstrings.' matched strings in '.$numfiles.' files<br/>';
	echo ($numstrings-$notfound).' were converted to gettext format<br/>';
	if ($notfound) echo $notfound.' were not found<br/>';
	if ($multiplefound) echo $multiplefound.' could not be replaced because conflicting replacement strings were found<br/>';
	
	foreach($po as $key => $value) 
		file_put_contents('../locale/'.$key.'.po', $value);
?>
