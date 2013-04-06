<?php

class Reversioner {
	public $db;

	function __construct($db) {
		$this->db = $db;
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function run($sqls, $return = FALSE, $is_files_array = FALSE) {
		try {
			$this->db->beginTransaction();

			if ($is_files_array) {
				foreach ($sqls as $file) {
					$this->db->query(file_get_contents($file));
				}
			}
			else {
				$query = $this->db->query($sqls);
			}

			$this->db->commit();

			if ($return) {
				return $query;
			}
			else {
				return TRUE;
			}
		} catch (Exception $e) {
			$this->db->rollBack();
			die("Failed: " . $e->getMessage());
		}
	}

	function isReversionerInstalled(){
		$response = $this->run("SHOW TABLES LIKE 'schema_version'", TRUE);
		return ($response->rowCount() > 0);
	}

	function installReversioner(){
		$this->run("CREATE TABLE `schema_version` (`version` int(10) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		INSERT INTO `schema_version` (`version`) VALUES ('0');");
	}

	function getCurrentVersion () {
		$response = $this->run("SELECT version FROM `schema_version`", TRUE);
		return (int) $response->fetchColumn();
	}

	function getAllVersions () {
		$versions_folder_path = __DIR__ . '/versions';

		$folder = dir($versions_folder_path);
		$_versions = array();
		while ($version_folder = $folder->read()) {
			if ($version_folder !== '.' AND $version_folder !== '..' AND is_dir($versions_folder_path . '/' . $version_folder)) {
				$_versions[] = $version_folder;
			}
		}

		return $_versions;
	}

	function getLatestVersion () {
		return max($this->getAllVersions());
	}

	function updateDbSchemaVersionTo($version){
		return $this->run('UPDATE `schema_version` SET version = ' . $version);
	}

	function runVersion($version_id){
		$version_folder_path = __DIR__ . '/versions/' . $version_id;

		if (!file_exists($version_folder_path) OR !is_dir($version_folder_path)) throw new Exception("Version folder not found (or is not folder)!");

		$folder = dir($version_folder_path);
		$_files = array();
		while ($file = $folder->read()) {
			if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'sql') {
				$_files[] = $version_folder_path . '/' . $file;
			}
		}

		$result = $this->run($_files, FALSE, TRUE);

		$this->updateDbSchemaVersionTo($version_id);
	}

	function updateAll() {
		$current = $this->getCurrentVersion();
		$latest  = $this->getLatestVersion();

		for ($i = $current; $i <= $latest; $i++) {
			if (file_exists(__DIR__ . '/versions/' . $i)) {
				$this->runVersion($i);
			}
		}

		return TRUE;
	}

}