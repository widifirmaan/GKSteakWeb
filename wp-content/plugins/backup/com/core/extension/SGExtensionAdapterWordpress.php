<?php

require_once(dirname(__FILE__).'/SGIExtensionAdapter.php');

class SGExtensionAdapterWordpress implements SGIExtensionAdapter
{
	public function isExtensionActive($extension)
	{
		return is_plugin_active($extension."/".$extension.".php");
	}

	public function isExtensionAvailable($extension)
	{
		return file_exists(SG_EXTENSIONS_FOLDER_PATH.$extension);
	}

	public function isExtensionAlreadyInPluginsFolder($extension)
	{
		return file_exists(WP_PLUGIN_DIR."/".$extension);
	}

	public function installExtension($extension)
	{
		if (!$this->isExtensionAlreadyInPluginsFolder($extension)) {
			return $this->copyExtensionFilesToPLuginsFolder($extension);
		}

		return false;
	}

	public function activateExtension($extension)
	{
		SGConfig::set($extension, 1);
		if (!$this->isExtensionActive($extension)) {
			activate_plugin($extension."/".$extension.".php");
		}
	}

	public function copyExtensionFilesToPLuginsFolder($extension)
	{
		$extensionsDirectoryPath = SG_EXTENSIONS_FOLDER_PATH.$extension;
		if (!$this->isExtensionAvailable($extension)) {
			return false;
		}

		$pluginsDirectoryPath = WP_PLUGIN_DIR."/".$extension;
		@mkdir($pluginsDirectoryPath, 0755, true);

		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($extensionsDirectoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST,
			RecursiveIteratorIterator::CATCH_GET_CHILD
		);

		foreach ($it as $path => $fileInfo) {
			$filename = $fileInfo->getFilename();
			if ($filename == '.DS_Store') {
				continue;
			}

			$relativePath = substr($path, strlen($extensionsDirectoryPath));

			if (substr($relativePath, 0, 4) == '.git') {
				continue;
			}

			if ($fileInfo->isDir()) {
				@mkdir($pluginsDirectoryPath.$relativePath, 0755, true);
			}
			else {
				if (!@copy($path, $pluginsDirectoryPath.$relativePath)) {
					return false;
				}
			}
		}

		return true;
	}
}
