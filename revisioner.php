<?php

class Revisioner {
	public $db;
	public $all_versions = NULL;

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

	function isRevisionerInstalled(){
		$response = $this->run("SHOW TABLES LIKE 'schema_version'", TRUE);
		return ($response->rowCount() > 0);
	}

	function installRevisioner(){
		$this->run("CREATE TABLE `schema_version` (`version` int(10) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		INSERT INTO `schema_version` (`version`) VALUES ('0');");
	}

	function getCurrentVersion () {
		$response = $this->run("SELECT version FROM `schema_version`", TRUE);
		return (int) $response->fetchColumn();
	}

	function getAllVersions () {
		if (!$this->all_versions) {
			$folder = dir(CONFIG_VERSIONS_DIR);
			$_versions = array();
			while ($version_folder = $folder->read()) {
				if ($version_folder !== '.' AND $version_folder !== '..' AND is_dir(CONFIG_VERSIONS_DIR . '/' . $version_folder)) {
					$version_folder_name = explode('-', $version_folder);
					$version_id = trim($version_folder_name[0]);
					$_versions[$version_id] = $version_folder;
				}
			}

			ksort($_versions);

			return $_versions;
		}

		return $this->all_versions;
	}

	function getLatestVersion () {
		return max(array_keys($this->getAllVersions()));
	}

	function updateDbSchemaVersionTo($version){
		return $this->run('UPDATE `schema_version` SET version = ' . $version);
	}

	function getVersionFiles($version_id, $isFolderName = FALSE){
		if ($isFolderName) {
			$version_folder_path = CONFIG_VERSIONS_DIR . '/' . $version_id;
		}
		else {
			$_all_versions = $this->getAllVersions();
			if (!$_all_versions[$version_id]) throw new Exception("Version id is not found!");
			$version_folder_path = CONFIG_VERSIONS_DIR . '/' . $_all_versions[$version_id];
		}

		if (!file_exists($version_folder_path) OR !is_dir($version_folder_path)) {
			throw new Exception("Version folder not found (or is not folder)!");
		}

		$folder = dir($version_folder_path);
		$_files = array();
		while ($file = $folder->read()) {
			if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'sql') {
				$_files[] = $version_folder_path . '/' . $file;
			}
		}

		return $_files;
	}

	function runVersion($version_id){
		$_files = $this->getVersionFiles($version_id);
		$result = $this->run($_files, FALSE, TRUE);
		$this->updateDbSchemaVersionTo($version_id);
	}

	function updateAll() {
		$_versions = $this->getAllVersions();
		$current   = $this->getCurrentVersion();
		$latest    = $this->getLatestVersion();


		for ($i = $current; $i <= $latest; $i++) {
			if (isset($_versions[$i])) {
				if (file_exists(CONFIG_VERSIONS_DIR . '/' . $_versions[$i])) {
					$this->runVersion($i);
				}
			}
		}

		return TRUE;
	}

}