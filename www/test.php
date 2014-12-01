<?php
include_once 'geshi.php';
$body = '';
$pastes = "/tmp/pastes/";
$url_ahk = "http://ahk.us.to";
$url_script = "http://a.hk.am";
$bot_port = 26656;
$cookie_time = 60*60*24*30;
if (!file_exists($pastes))
{
	mkdir($pastes);
}
if (isset($_GET["docs"]))
{
	$page = str_replace("A_", "", $_GET["docs"]);
	header("Location: http://ahkscript.org/docs/Variables.htm#" . $page);
	die();
}
if (isset($_POST["code"]))
{
	$code = trim($_POST["code"]);
	if ($code)
	{
		$hash = substr(sha1($code), 0, 6);
		file_put_contents($pastes . $hash, $code);
		header("Location: /?p=" . $hash);
		
		$name = substr(trim($_POST["name"]), 0, 16);
		setcookie("name", $name, time()+$cookie_time);
		
		if ($_POST["announce"])
		{
			if (!$name) { $name = "Anonymous"; }
			if ($_POST["channel"] == "#ahk")
			{
				$channel = "#ahk"; $url = $url_ahk;
			}
			else
			{
				$channel = "#ahkscript"; $url = $url_script;
			}
			
			$in = $channel.",$name just pasted $url/?p=$hash";
			
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($socket === false) { die(); }
			$result = socket_connect($socket, 'localhost', $bot_port);
			if ($result === false) { die(); }
			socket_write($socket, $in, strlen($in));
			socket_close($socket);
		}
	}
	else
	{
		header("Location: /");
	}
}
else if (isset($_GET["p"]))
{
	$name = substr(preg_replace("/[^A-Fa-f0-9]/", "", $_GET["p"]), 0, 6);
	if (file_exists($pastes . $name))
	{
		$code = file_get_contents($pastes . $name);
		$body = "<a class='button' href='/?r=$name'>Raw</a><a class='button' href='/?e=$name'>Edit</a>";
		$geshi = new GeSHi($code, 'AutoHotkey');
		$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS); 
		$geshi->set_code_style("font-weight: bold;");
		$geshi->set_link_target("_blank", "");
		$body .= $geshi->parse_code();
		// $body .= "<ol class='code'>";
		// foreach (explode("\n", str_replace("\r", "", htmlentities($code))) as $line)
		// {
			// $body .= "<li><pre>$line</pre></li>";
		// }
		// $body .= "</ol>";
	}
	else
	{
		// header("HTTP/1.0 404 Not Found");
		header("Location: /");
	}
}
else if (isset($_GET["r"]))
{
	$name = $pastes . substr(preg_replace("/[^A-Fa-f0-9]/", "", $_GET["r"]), 0, 6);
	if (file_exists($name))
	{
		header("Content-type: text/plain");
		$code = file_get_contents($name);
		die($code);
	}
	else
	{
		// header("HTTP/1.0 404 Not Found");
		header("Location: /");
	}
}
else
{
	$code = "";
	if (isset($_GET["e"]))
	{
		$name = substr(preg_replace("/[^A-Fa-f0-9]/", "", $_GET["e"]), 0, 6);
		if (file_exists($pastes . $name))
			$code = htmlentities(file_get_contents($pastes . $name));
	}
	
	if (isset($_COOKIE["name"]))
		$name = $_COOKIE["name"];
	else
		$name = "";
	
	if (stristr($_SERVER["HTTP_HOST"], "ahk"))
		$options = '<option selected="selected">#ahk</option><option>#ahkscript</option>';
	else
		$options = '<option selected="selected">#ahkscript</option><option>#ahk</option>';
	
	$body .= '<div id="editor"></div>'
	. '<form action="/" method="POST">'
	. '<textarea name="code">'.$code.'</textarea>'
	. '<div class="options">'
	. '<input type="text" name="name" placeholder="Anonymous" value="'.$name.'" />'
	. '<input type="submit" value="Paste it" />'
	. '<input type="Checkbox" checked name="announce">Announce to IRC:</input>'
	. '<select name="channel">'.$options.'</select>'
	. '</div>'
	. '</form>'
	. '<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>'
	. '<script src="/ace-builds/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>'
	. '<script>
var editor = ace.edit("editor");
editor.setTheme("ace/theme/zenburn");
editor.getSession().setMode("ace/mode/autohotkey");
var textarea = $("textarea[name=\'code\']").hide();
editor.getSession().setValue(textarea.val());
editor.getSession().on("change", function(){
  textarea.val(editor.getSession().getValue());
});
</script>'
//	. '<style type="text/css" media="screen">'
//	. '#editor {'
//	. 'position: absolute;'
//	. '}</style>'
;
}
?>
<!DOCTYPE text/html>
<html>
	<head>
		<link rel="icon" href="/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="index.css" />
	</head>
	<body>
			<div class="header"><a href="/">Ahkbin!</a></div>
			<div class="body">
				<?=$body."\n"?>
			</div>
	</body>
</html>
