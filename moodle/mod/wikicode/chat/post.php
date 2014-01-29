<?
	$text = $_POST['text'];
	$path = $_POST['path'];
	
	if (file_exists($path."/wikicode/chat/logs/log_".$_POST['itemid'].".html")) {
		$fp = fopen($path."/wikicode/chat/logs/log_".$_POST['itemid'].".html", 'a');
	}
	else {
		$fp = fopen($path."/wikicode/chat/logs/log_".$_POST['itemid'].".html", 'w');
	}
	fwrite($fp, "<div class='msgln'>(".date("g:i A").") <b>".$_POST['user']."</b>: ".stripslashes(htmlspecialchars($text))."<br></div>");
	fclose($fp);
?>