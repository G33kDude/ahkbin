<?php

// Settings
$url = getenv("BASE_URL");
$pastes = "/pastes/";
$cookie_time = 60 * 60 * 24 * 30; // 30 days

function ahkstrip($input, $max = 10)
{
	return substr(preg_replace("/[^A-Fa-f0-9]/", "", $input), 0, $max);
}

function reply(int $status_code, $message): void
{
	http_response_code($status_code);
	header('Content-Type: application/json');
	die(json_encode($message));
}


// Submitting code
if (isset($_POST["code"])) {
	$code = $_POST["code"];
	if (trim($code) === '') {
		header("Location: ./");
		die();
	}

	// Save the code with the filename as a partial sha1 hash
	$hash = substr(sha1($code), 0, 8);
	file_put_contents($pastes . $hash, $code);

	// Save the submitted nickname as a cookie
	$name = substr(trim($_POST["name"]), 0, 16);
	setcookie("name", $name, time() + $cookie_time);

	// Redirect to the paste
	header("Location: ./?p=$hash");

	// Announce to IRC
	if ($_POST["channel"] && getenv("IRC_ANNOUNCE_ENABLE") == "true")
	{
		// Set name and description
		if (!$name) { $name = "Anonymous"; }
		$desc = substr(trim($_POST["desc"]), 0, 128);
		if ($desc) { $desc = " - $desc"; }

		// Choose which channel to announce in
		$channel = $_POST["channel"];

		// Create the API call
		$in = json_encode([
			"MethodName" => "Chat",
			"Params" => [
				$channel,
				"[ahkbin] $name just pasted $url/?p=$hash$desc"
			]
		]);

		// Call the API
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) { die(); }
		$result = socket_connect($socket, getenv("IRC_ANNOUNCE_HOST"), (int)getenv("IRC_ANNOUNCE_PORT"));
		if ($result === false) { die(); }
		socket_write($socket, $in, strlen($in));
		socket_close($socket);
	}

	die();
} else if (isset($_GET["r"])) {
	// Viewing a raw paste
	$filepath = $pastes . ahkstrip($_GET["r"]);

	if (file_exists($filepath)) {
		header("Content-type: text/plain; charset=utf-8");
		die(file_get_contents($filepath));
	} else {
		// header("HTTP/1.0 404 Not Found");
		header("Location: ./");
	}
	die();
}

// Deleting a paste (ace integration)
// vibe coded so hard dude
if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$secret_key = getenv("SECRET_KEY");
	if (!$secret_key) {
		reply(400, ["error" => "im not configured correctly"]);
	}

	$api_key = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
	if ($api_key !== $secret_key) {
		reply(403, ["error" => "bad key"]);
	}

	try {
		$body = file_get_contents("php://input");
		$data = json_decode($body, true);
		$ids = $data['delete'];
	} catch (Exception $e) {
		reply(400, ["error" => "failed decoding json body"]);
	}

	if (!is_array($ids)) {
		reply(400, ["error" => "not a valid json array"]);
	}

	$stripped = [];
	try {
		foreach ($ids as $id) {
			$stripped[] = ahkstrip($id);
		}
	} catch (Exception $e) {
		reply(500, ["error" => "failure on stripping input"]);
	}

	$succeeded = 0;
	$failed = 0;

	foreach ($stripped as $id) {
		$file = $pastes . $id;

		if (file_exists($file)) {
			if (unlink($file)) {
				$succeeded++;
			} else {
				$failed++;
			}
		}
	}

	$resp = [
		"succeeded" => $succeeded,
		"failed" => $failed,
	];

	reply(200, $resp);
}


header("Content-type: text/html; charset=utf-8");

$code = "";
$readonly = false;

// Editing a paste
if (isset($_GET["e"])) {
	$hash = ahkstrip($_GET["e"]);
	$filepath = $pastes . $hash;
	if (file_exists($filepath))
		$code = htmlspecialchars(file_get_contents($filepath));
} else if (isset($_GET["p"])) // Viewing a paste
{
	$hash = ahkstrip($_GET["p"]);
	header("ahk-location: $url/?p=$hash");
	$filepath = $pastes . $hash;
	if (file_exists($filepath))
		$code = htmlspecialchars(file_get_contents($filepath));
	$readonly = true;
}

// Get saved name
if (isset($_COOKIE["name"]))
	$name = htmlspecialchars($_COOKIE["name"]);
else
	$name = "";

?>
<!DOCTYPE html>
<html>

<head>
	<title>ahkbin!</title>
	<link rel="icon" href="/favicon.ico" />
	<link rel="stylesheet" type="text/css" href="index.css" />
</head>

<body>
	<header>
		<a href="./">ahkbin!</a>
	</header>
	<div class="wrapper">
		<div class="content">
			<form action="." method="POST" id="ahkform">
				<?php if ($readonly) { ?>
					<div class="options">
						<a class='button reindent' href=''>Reindent</a>
						<a class='button' id='rawbutton' href='./?r=<?= $hash ?>'>Raw</a>
						<a class='button' id='editbutton' href='./?e=<?= $hash ?>'>Edit</a>
						<a class='button' href='ahk:<?= $url ?>/?r=<?= $hash ?>'>Open</a>
					</div>
				<?php } ?>
				<textarea name="code" id="ahkarea" <?= $readonly ? " readonly" : "" ?>><?= $code ?></textarea>
				<div id="ahkedit"></div>
				<?php if (!$readonly) { ?>
					<div class="options">
						<a class='button reindent' href=''>Reindent</a>
						<input type="text" name="name" placeholder="Anonymous" maxlength="16" value="<?= $name ?>" />
						<input type="submit" value="Paste it" />
						<input type="text" name="desc" placeholder="Description" maxlength="128" value="" />
					</div>
				<?php } ?>
			</form>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.7/ace.js" type="text/javascript" charset="utf-8"></script>
			<script src="mode-autohotkey.js" type="text/javascript"></script>
			<script src="index.js" type="text/javascript"></script>
			<script src="indent.js" type="text/javascript"></script>
		</div>
	</div>
	<footer>Pastebin &copy;GeekDude 2015-2025 &#8226; Storage not guaranteed</footer>
</body>

</html>
