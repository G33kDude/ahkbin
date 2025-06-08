<?php

declare(strict_types=1);

namespace AhkBin;

class Templates
{
    static function index($readonly, $url, $hash, $name, $code)
    {
        ob_start();
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
                <textarea name="code" id="ahkarea" <?= $readonly ? " readonly" : "" ?>><?= htmlspecialchars($code) ?></textarea>
                <div id="ahkedit"></div>
                <?php if (!$readonly) { ?>
                    <div class="options">
                        <a class='button reindent' href=''>Reindent</a>
                        <input type="text" name="name" placeholder="Anonymous" maxlength="16" value="<?= htmlspecialchars($name) ?>" />
                        <input type="submit" value="Paste it" />
                        <?php if (getenv("IRC_ANNOUNCE_ENABLE") == "true") { ?>
                            <input type="checkbox" name="channel" value="#ahk">Announce to IRC</input>
                        <?php } ?>
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
<?php
        return ob_get_clean();
    }
}
