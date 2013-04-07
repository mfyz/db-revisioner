<?php

require_once 'config.php';
require_once 'revisioner.php';

$db = new PDO("mysql:host=" . CONFIG_HOST . ";dbname=" . CONFIG_DATABASE, CONFIG_USERNAME, CONFIG_PASSWORD);
$revisioner = new Revisioner($db);


if (!$revisioner->isRevisionerInstalled()) {
	if (isset($_GET['install_revisioner'])) {
		$revisioner->installRevisioner();
		header('Location: ./'); exit;
	}
	die("<div style='font: 16px Arial; padding: 20px;'><h1>Revisioner is not installed.</h1> <a href='?install_revisioner'>Install</a> (Only adds schema_version table to your database).</div>");
}


if (isset($_GET['install_all_revisions'])) {
	$revisioner->updateAll();
	header('Location: ./'); exit;
}

$_all_versions      = $revisioner->getAllVersions();
$current_version_id = $revisioner->getCurrentVersion();
$latest_version_id  = $revisioner->getLatestVersion();

$_versions = array();
foreach ($_all_versions as $vid => $version_folder) {
	$_versions[$vid] = array(
		'id'       => $vid,
		'folder'   => $version_folder,
		'name'     => ucwords(trim(substr($version_folder, strpos($version_folder, '-')), ' -')),
		'files'    => $revisioner->getVersionFiles($version_folder, TRUE),
		'imported' => ($vid > $current_version_id - 5 AND $vid <= $current_version_id),
		'pending'  => ($vid > $current_version_id),
	);
}


?><!DOCTYPE html>
<html>
<head>
	<title>Database Revisioner</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css" />
	<link rel="stylesheet" href="http://twitter.github.com/bootstrap/assets/js/google-code-prettify/prettify.css" />
	<style type="text/css">
		body {
			padding: 50px;
		}

		.version.imported .version_details {
			display: none;
		}
	</style>
</head>
<body>
<div class="container-fluid">
	<div class="hero-unit">
		<h2>Current Database Schema Version: <?= $current_version_id; ?></h2>

		<p>
			<?php if ($current_version_id == $latest_version_id) : ?>
				Your database is up to date. <br />
				Here are latest imported versions.
			<?php else : ?>
				There are <?= ($latest_version_id - $current_version_id); ?> new revisions. <br />
				Here are pending and latest imported versions.
			<?php endif; ?>
		</p>
	</div>

	<table class="table versions_table">
		<tr>
			<th width="40">&nbsp;</th>
			<th width="30" align="center">#</th>
			<th>Version</th>
			<th width="100">Actions</th>
		</tr>
		<?php foreach ($_versions as $vid => $_version): ?>
			<tr class="version <?=($_version['imported'] ? 'imported success' : 'pending warning')?>">
				<?php if ($_version['imported']): ?>
					<td><span class="badge badge-success" title="Imported"><i class="icon-ok icon-white"></i></span></td>
				<?php else: ?>
					<td>&nbsp;</td>
				<?php endif; ?>

				<td align="center"><?=$vid;?></td>
				<td>
					<?=$_version['name'];?>

					<div class="version_details">
						<ul>
							<?php foreach ($_version['files'] as $file): ?>
							<li>
								<h5><?=pathinfo($file, PATHINFO_FILENAME) . '.' . pathinfo($file, PATHINFO_EXTENSION);?></h5>
								<pre class="pre-scrollable prettyprint linenums languague-sql"><?php readfile($file); ?></pre>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</td>
				<td>
					<a href="javascript:;" class="details_button btn btn-mini">Details</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<br />

	<?php if ($current_version_id != $latest_version_id) : ?>
		<div>
			<a href="?install_all_revisions" class="btn btn-success btn-large">Install Pending Revisions</a>
		</div>
	<?php endif; ?>
</div>
<script src="http://code.jquery.com/jquery.js"></script>
<script src="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/assets/js/google-code-prettify/prettify.js"></script>
<script type="text/javascript">
	$(function(){
		prettyPrint();

		$('.versions_table tr.version .details_button').click(function(){
			$(this).parents('td').parents('tr').find('.version_details').slideToggle();
		});
	});
</script>
</body>
</html>