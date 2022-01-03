<?php

interface SGIScheduleAdapter
{
    public static function create($cron, $id);
    public static function remove($cron);
    public static function isCronAvailable($force = false);
}
