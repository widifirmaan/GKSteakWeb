<?php

class SGSchedule
{
	public static function create($cron, $id)
	{
		$className = self::getCurrentScheduleClassName();
		require_once(SG_SCHEDULE_PATH.$className.'.php');
		$className::create($cron, $id);
	}

	public static function getCronExecutionData($cron)
	{
		$className = self::getCurrentScheduleClassName();
		require_once(SG_SCHEDULE_PATH.$className.'.php');
		return $className::getCronExecutionData($cron);
	}

	public static function remove($id)
	{
		$className = self::getCurrentScheduleClassName();
		require_once(SG_SCHEDULE_PATH.$className.'.php');
		$className::remove($id);
	}

	public static function isCronAvailable($force = false)
	{
		$className = self::getCurrentScheduleClassName();
		require_once(SG_SCHEDULE_PATH.$className.'.php');
		return $className::isCronAvailable($force);
	}

	private static function getCurrentScheduleClassName()
	{
		return 'SGScheduleAdapter'.SG_ENV_ADAPTER;
	}
}
