<?php

require_once 'config.php';
require_once 'revisioner.php';

$db = new PDO("mysql:host=" . CONFIG_HOST . ";dbname=" . CONFIG_DATABASE, CONFIG_USERNAME, CONFIG_PASSWORD);
$reversioner = new Reversioner($db);


if (!$reversioner->isReversionerInstalled()) {
	if (isset($_GET['install_reversioner'])) {
		$reversioner->installReversioner();
		header('Location: ./'); exit;
	}
	die("<h1>Reversioner is not installed.</h1> <a href='?install_reversioner'>Install</a> (Only adds schema_version table in your database).");
}


if (isset($_GET['install_all_revisions'])) {
	$reversioner->updateAll();
	header('Location: ./'); exit;
}


$current_version = $reversioner->getCurrentVersion();
$latest_version  = $reversioner->getLatestVersion();


?><!DOCTYPE html>
<html>
<head>
	<title>Database Reversioner</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.0.4/css/bootstrap-combined.min.css" />
	<style type="text/css">
		body {
			padding: 50px;
		}
	</style>
</head>
<body>
<div class="container-fluid">
	<div class="hero-unit">
		<h1>Current Version: <?= $current_version; ?></h1>
		<br />

		<?php if ($current_version == $latest_version) : ?>
			<p>Your database is up to date.</p>
		<?php else : ?>
			<p>There are <?= ($latest_version - $current_version); ?> new revisions.</p>
			<br />
			<a href="?install_all_revisions" class="btn btn-success btn-large">Install Revisions</a>
		<?php endif; ?>
	</div>
</div>
<script src="http://code.jquery.com/jquery.js"></script>
<script src="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.0.4/js/bootstrap.min.js"></script>
</body>
</html>