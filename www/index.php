<?php
// Import GeSHi
include_once 'geshi.php';

// Settings
$url_ahk = "http://ahk.us.to";
$url_script = "http://ahk.uk.to";
$pastes = "/tmp/pastes/";
$bot_port = 26656;
$cookie_time = 60*60*24*30; // 30 days

// Default values
$body = '';
header("Content-type: text/html; charset=utf-8");

// Ensure pastes directory exists
if (!file_exists($pastes)) { mkdir($pastes); }

// Variable "A_" redirect workaround
if (isset($_GET["docs"]))
{
	$page = str_replace("A_", "", $_GET["docs"]);
	header("Location: http://ahkscript.org/docs/Variables.htm#" . $page);
	die();
}

// Submitting code
if (isset($_POST["code"]))
{
	$code = trim($_POST["code"]);
	if ($code)
	{
		// Save the code with the filename as a partial sha1 hash
		$hash = substr(sha1($code), 0, 6);
		file_put_contents($pastes . $hash, $code);
		header("Location: /?p=" . $hash);
		
		// Save the submitted name as a cookie
		$name = substr(trim($_POST["name"]), 0, 16);
		setcookie("name", $name, time()+$cookie_time);
		
		// Announce to IRC
		if ($_POST["announce"])
		{
			// Set name
			if (!$name) { $name = "Anonymous"; }
			if ($name == "tidbit") { $name = "tidbuttz"; }
			
			// Choose which channel to announce in
			if ($_POST["channel"] == "#ahk")
			{
				$channel = "#ahk";
				$url = $url_ahk;
			}
			else
			{
				$channel = "#ahkscript";
				$url = $url_script;
			}
			
			// Create the API call
			$in = $channel.",$name just pasted $url/?p=$hash";
			
			// Call the API
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($socket === false) { die(); }
			$result = socket_connect($socket, 'localhost', $bot_port);
			if ($result === false) { die(); }
			socket_write($socket, $in, strlen($in));
			socket_close($socket);
		}
	}
	else // empty file
	{
		header("Location: /");
	}
}
else if (isset($_GET["p"])) // Viewing a paste
{
	// Remove all non-alphanumeric characters
	$name = substr(preg_replace("/[^A-Fa-f0-9]/", "", $_GET["p"]), 0, 6);
	
	if (file_exists($pastes . $name))
	{
		// Add relevant buttons to the top of the page
		$body = "<a class='button' href='/?r=$name'>Raw</a><a class='button' href='/?e=$name'>Edit</a>";
		
		// Get code
		$code = file_get_contents($pastes . $name);
		
		// Put code into GeSHi box
		$geshi = new GeSHi($code, 'AutoHotkey');
		$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS); 
		$geshi->set_code_style("font-weight: bold;");
		$geshi->set_link_target("_blank", "");
		$body .= $geshi->parse_code();
		
		/* Non-highlighted code display
			$body .= "<ol class='code'>";
			foreach (explode("\n", str_replace("\r", "", htmlentities($code))) as $line)
			{
				$body .= "<li><pre>$line</pre></li>";
			}
			$body .= "</ol>";
		*/
	}
	else // Paste does not exist
	{
		// header("HTTP/1.0 404 Not Found");
		header("Location: /");
	}
}
else if (isset($_GET["r"])) // Viewing a raw paste
{
	// Remove all non-alphanumeric characters
	$name = $pastes . substr(preg_replace("/[^A-Fa-f0-9]/", "", $_GET["r"]), 0, 6);
	
	if (file_exists($name))
	{
		header("Content-type: text/plain; charset=utf-8");
		$code = file_get_contents($name);
		die($code);
	}
	else
	{
		// header("HTTP/1.0 404 Not Found");
		header("Location: /");
	}
}
else // Not doing anything special, load the normal page
{
	$code = "";
	
	// Editing a page
	if (isset($_GET["e"]))
	{
		$name = substr(preg_replace("/[^A-Fa-f0-9]/", "", $_GET["e"]), 0, 6);
		if (file_exists($pastes . $name))
			$code = htmlentities(file_get_contents($pastes . $name));
	}
	
	// Get saved name
	if (isset($_COOKIE["name"]))
		$name = $_COOKIE["name"];
	else
		$name = "";
	
	// Pick default channel for auto-announce
	if (stristr($_SERVER["HTTP_HOST"], "us.to"))
		$options = '<option selected="selected">#ahk</option><option>#ahkscript</option>';
	else
		$options = '<option selected="selected">#ahkscript</option><option>#ahk</option>';
	
	// Fill in the body
	$body .= '<form action="/" method="POST">'
	. '<textarea name="code">'.$code.'</textarea>'
	. '<div class="options">'
	. '<input type="text" name="name" placeholder="Anonymous" maxlength="16" value="'.$name.'" />'
	. '<input type="submit" value="Paste it" />'
	. '<input type="Checkbox" checked name="announce">Announce to IRC:</input>'
	. '<select name="channel">'.$options.'</select>'
	. '</div>'
	. '</form>';
}
?>
<!DOCTYPE text/html>
<html>
	<head>
		<link rel="icon" href="/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="index.css" />
	</head>
	<body>
			<div class="header" ><a href="/" style="text-decoration:underline;">Ahkbin!</a>&nbsp;<a href="/test.php" style="text-decoration:underline;">IDE Test!</a></div>
			<div class="body">
				<?=$body."\n"?>
			</div>
	</body>
</html>