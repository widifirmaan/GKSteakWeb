<?php

require_once(dirname(__FILE__).'/SGEntry.php');

/**
*
*/
class SGFileEntry implements SGEntry
{
	private $name;
	private $type;
	private $path;
	private $dateModified;

	public function __construct()
	{
		$this->type = SG_ENTRY_TYPE_FILE;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setPath($path)
	{
		$this->path = $path;
	}

	public function setDateModified($date)
	{
		$this->dateModified = $date;
	}

	public function getDateModified()
	{
		return $this->dateModified;
	}

	public function toArray()
	{
		$fileEntry = array(
			'name' => $this->getName(),
			'path' => $this->getPath(),
			'type' => $this->getType(),
			'date_modified' => $this->getDateModified()
		);

		return $fileEntry;
	}
}
