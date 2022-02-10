<?
if (isset($_POST["make_hash"])) {
	global $user;
	$user[$_POST["user"]] = password_hash($_POST["pass"], PASSWORD_BCRYPT);
}

?>
<!DOCTYPE html>
<html>
<head>
<title>secrets.json maker</title>
</head>
<body>
<h1>secrets.json maker</h1>

<?php
if (isset($user)) {
	print("<p>");
	print(json_encode($user));
	print("</p>");
}
?>

<form method="POST">
<label for="user">User</label>
<input name="user" type="text">
<label for="pass">Password</label>
<input name="pass" type="password">

<input name="make_hash" type="submit">
</form>
</body>
</html>