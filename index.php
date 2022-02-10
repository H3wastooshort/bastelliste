<?
//using etags instead
/*if (!isset($_GET["json_hash"])) {
	header("Content-Type: text/plain");
	die(sha1(file_get_contents("bastelliste.json")));
}*/

//very bad auth code
session_set_cookie_params([
    'lifetime' => 604800,
    'samesite' => 'strict'
]);

session_start();

if (!isset($_SESSION["logged_in"])) {
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
	    header('WWW-Authenticate: Basic realm="Bastelliste"');
	    http_response_code(401);
	    die('No login, no access :(');
	} else {
		//USER LIST
	    $users["user here"] = 'password hash here';
		
		if (in_array($_SERVER['PHP_AUTH_USER'], array_keys($users))) {
			if (!password_verify($_SERVER['PHP_AUTH_PW'], $users[$_SERVER['PHP_AUTH_USER']])) {
				header('WWW-Authenticate: Basic realm="Bastelliste"');
				http_response_code(401);
				die("<h1>Wrong login, you dingus.</h1>");
			}
			else {
				$_SESSION["logged_in"] = true;
				session_write_close();
			}
		}
		else {
			header('WWW-Authenticate: Basic realm="Bastelliste"');
			http_response_code(401);
			die("<h1>Wrong login, you dingus.</h1>");
		}
	}
}

$json = file_get_contents("bastelliste.json");
if (empty($json)) {
	$json = "[]";
}
$entries = json_decode($json, true);

if (isset($_POST["add_entry"])) {
	if (isset($_POST["title"]) and isset($_POST["category"]) and isset($_POST["details"])) {
		
		if (isset($_POST["edit_id"])) {
			$edited_index = array_search($_POST["edit_id"], array_column($entries, "id"));
			if ($edited_index !== false) {
				$entries[$edited_index]["title"] = $_POST["title"];
				$entries[$edited_index]["category"] = $_POST["category"];
				$entries[$edited_index]["details"] = $_POST["details"];
				//$entries[$edited_index]["time"] = time();
			}
		}
		
		else {
			$addition["title"] = $_POST["title"];
			$addition["category"] = $_POST["category"];
			$addition["details"] = $_POST["details"];
			$addition["time"] = time();
			$addition["id"] = sha1(random_bytes(16));
			array_push($entries, $addition);
		}
		
		file_put_contents("bastelliste.json", json_encode($entries));
		header("Location: .");
		die("Added or edited {$_POST["title"]}.");
	}
}

if (isset($_POST["delete_entry"])) {
	if (isset($_POST["id"]) and !empty($_POST["id"])) {
		for ($i = 0; $i < count($entries); $i++) {
            if ($entries[$i]["id"] == $_POST["id"]) {
				$delfile = fopen("deleted.csv", "a");
				fwrite($delfile, json_encode($entries[$i]));
				fwrite($delfile, ",\n");
				fclose($delfile);
                array_splice($entries, $i, 1);
			}
		}
		unset($i);
		file_put_contents("bastelliste.json", json_encode($entries));
		header("Location: .");
		die("Removed {$_POST["id"]}.");
	}
}

if (isset($_POST["init_edit_entry"])) {
	if (isset($_POST["id"]) and !empty($_POST["id"])) {
		$edit_id = $_POST["id"];
		$edit_pos = array_search($_POST["id"], array_column($entries, "id"));
		if ($edit_pos !== false) {
			$edit_entry = $entries[$edit_pos];
		}
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Bastelliste</title>
<link rel="stylesheet" href="bastelliste.css?v=<?php echo sha1(file_get_contents("bastelliste.css")); ?>">
<!--<link rel="stylesheet" href="/js_lib/highlight/styles/base16/outrun-dark.min.css">
<script src="/js_lib/highlight/highlight.min.js"></script>-->
<meta charset="utf-8">

<meta name="msapplication-TileColor" content="#111111">
<meta name="theme-color" content="#111111">

<link rel="icon" href="soldering-5117508.svg">
</head>
<body>
<h1>Bastel<wbr>liste</h1>
<hr>

<h2>Eintrag hinzufügen</h2>

<form method="POST">
<label for="title">Titel</label>
<input name="title" type="text" autocomplete="off" <?php if(isset($edit_entry)) {print("value=\"{$edit_entry["title"]}\"");}?> required>
<br>

<label for="category">Kategorie</label>
<select name="category" autocomplete="off">
<?php if(isset($edit_entry)) {print("<option value=\"{$edit_entry["category"]}\">Nicht ändern</option>");}?>
<option value="">Keine Kategorie</option>
<option value="c_important">Wichtig</option>
<option value="c_idea">Idee</option>
<option value="c_basteln">Basteln</option>
<option value="c_school">Schule</option>
</select>
<br><br>

<noscript>
<style>textarea.ta_unfocused {
    height: 75vh;
    width: 100%;
    font-size: 12pt;
}

div.md_buttons {
	display: none;
	visibility: collapse;
}</style>

<p style="color: red;">MD Hotkeys ohne JavaScript nicht verfügbar.</p>
</noscript>

<div class="md_buttons">
<button onclick="mdMaker('B');" type="button"><b>B</b></button>
<button onclick="mdMaker('I');" type="button"><i>I</i></button>
<button onclick="mdMaker('U');" type="button"><u>U</u></button>
<button onclick="mdMaker('URL');" type="button">🔗</button>
<button onclick="mdMaker('IMG');" type="button">🖼️</button>
<button onclick="mdMaker('CODE');" type="button">📄</button>
</div>

<textarea id="details" name="details" class="ta_unfocused" autocomplete="off"><?php if(isset($edit_entry)) {print("{$edit_entry["details"]}");}?></textarea>
<script>
var textarea = document.getElementById('details');
//textarea.onblur = function () {textarea.className = "ta_unfocused";};
textarea.onfocus = function () {textarea.className = "ta_focused";};

window.addEventListener('beforeunload', function (e) {
    if (textarea.value.length > 0) {
        e.preventDefault();
        e.returnValue = 'Ungesendeter Text im Textfeld! Wirklich schließen und alle Änderungen verlieren?';
    }
    else {
        delete e['returnValue'];
    }
});

//Thanks https://www.w3resource.com/javascript-exercises/javascript-string-exercise-14.php. I was to lazy to do this myself.
function insert(main_string, ins_string, pos) {
   if(typeof(pos) == "undefined") {
    pos = 0;
  }
   if(typeof(ins_string) == "undefined") {
    ins_string = '';
  }
   return main_string.slice(0, pos) + ins_string + main_string.slice(pos);
}

function mdMaker(type) {
    textarea.focus();
    let workvar = textarea.value;
    // TODO: work in let and only put into textarea at the end to fix bug
    switch (type) {
    case 'B':
        workvar = insert(workvar, "**", textarea.selectionStart);
        workvar = insert(workvar, "**", textarea.selectionEnd + 2);
        break;
    case 'I':
        workvar = insert(workvar, "*", textarea.selectionStart);
        workvar = insert(workvar, "*", textarea.selectionEnd + 1);
        break;
    case 'U':
        workvar = insert(workvar, "_", textarea.selectionStart);
        workvar = insert(workvar, "_", textarea.selectionEnd + 1);
        break;
    case 'URL':
        workvar = insert(workvar, "[URL Name](", textarea.selectionStart);
        workvar = insert(workvar, ")", textarea.selectionEnd + 11);
        break;
    case 'IMG':
        workvar = insert(workvar, "![](", textarea.selectionStart);
        workvar = insert(workvar, ")", textarea.selectionEnd + 4);
        break;
    case 'CODE':
        workvar = insert(workvar, "\n```\n", textarea.selectionStart);
        workvar = insert(workvar, "\n```\n", textarea.selectionEnd + 5);
        break;

    default:
        console.error("Unknown MD Operation");
    }
    textarea.value = workvar;
}
</script>
<br>
<a class="fineprint" href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet">Markdown Unterstützt</a>
<br>
<?php
if (isset($edit_id)) {
	print("<input type=\"hidden\" name=\"edit_id\" value=\"$edit_id\">");
}
?>
<input name="add_entry" type="submit" value="<?php if(isset($edit_id)) {print("Bearbeiten");} else {print("Hinzufügen");} ?>">
</form>
<?php if(isset($edit_id)) {print('<a href="."><button>Abbrechen</button></a>');} ?>

<hr>

<h2>Einträge</h2>
<div id="entries">
<?php
require_once "../../php_lib/Michelf/MarkdownExtra.inc.php";
$md_parser = new \Michelf\MarkdownExtra;

array_reverse($entries);

date_default_timezone_set('Europe/Berlin');

function time_cmp($a, $b)
{
    return $b["time"] - $a["time"];
}

usort($entries, "time_cmp");

foreach ($entries as $entry) {
	$title = htmlspecialchars($entry["title"]);
	$time = date(DATE_RFC822, $entry["time"]);
	$category = htmlspecialchars($entry["category"]);
	$markdown = $md_parser->transform($entry["details"]);
	$id = $entry["id"];
	print("<div id=\"entry_$id\" class=\"entry $category\">\n<p class=\"e_title\">$title</p>\n<p class=\"e_time\">$time</p>\n<div class=\"e_details\">\n$markdown</div>\n<form method=\"POST\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"submit\" name=\"delete_entry\" value=\"Löschen\"></form>\n<form method=\"POST\"><input type=\"hidden\" name=\"id\" value=\"$id\"><input type=\"submit\" name=\"init_edit_entry\" value=\"Bearbeiten\"></form></div>\n\n");
}
unset($entry);
?>
</div>

<script>
var lastIdentifier = "";
var lastIdentifierValid = false;

function lookForChange() {
    fetch('bastelliste.json', {method: 'HEAD', headers: {'Cache-Control' : 'no-cache'}}).then(function(r){
        if (r.ok) {
            console.log("request ok");
			let identifier = r.headers.get("ETag");
			
			if (lastIdentifierValid) {
				console.log("identifier valid.");
				if (identifier != lastIdentifier) {
					console.log("identifier changed.");
					if (textarea.value.length == 0) {
						console.log("no text present");
						window.location.reload();
					}
					else {
						alert("Information: \nEntries not up to date. Please refresh page when done editing.");
					}
				}
			}
			else {
				console.log("identifier not yet valid.");
			}
			
			lastIdentifier = identifier;
			lastIdentifierValid = true;
        }
    });
}

lookForChange();
setInterval(lookForChange, 60000);



//hljs.highlightAll();
</script>
</body>
</html>