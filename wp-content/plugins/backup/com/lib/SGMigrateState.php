<?php

require_once(dirname(__FILE__).'/SGState.php');

class SGMigrateState extends SGState
{
	private $tableCursor = 0;
	private $columnCursor = 0;

	function __construct()
	{
		$this->type = SG_STATE_TYPE_MIGRATE;
	}

	public function setTableCursor($tableCursor)
	{
		$this->tableCursor = $tableCursor;
	}

	public function getTableCursor()
	{
		return $this->tableCursor;
	}

	public function setColumnCursor($columnCursor)
	{
		$this->columnCursor = $columnCursor;
	}

	public function getColumnCursor()
	{
		return $this->columnCursor;
	}

	public function init($stateJson)
	{
		$this->tableCursor = $stateJson['tableCursor'];
		$this->columnCursor = $stateJson['columnCursor'];
		$this->inprogress = $stateJson['inprogress'];
		$this->action = $stateJson['action'];
		$this->actionId = $stateJson['actionId'];

		return $this;
	}

	public function save()
	{
		file_put_contents(SG_BACKUP_DIRECTORY.SG_STATE_FILE_NAME, json_encode(array(
			'inprogress' => $this->inprogress,
			'type' => $this->type,
			'token' => $this->token,
			'action' => $this->action,
			'actionId' => $this->actionId,
			'tableCursor' => $this->tableCursor,
			'columnCursor' => $this->columnCursor,
		)));
	}
}
