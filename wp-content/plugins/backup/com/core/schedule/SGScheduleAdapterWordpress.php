<?php

require_once(SG_SCHEDULE_PATH . 'SGIScheduleAdapter.php');

class SGScheduleAdapterWordpress implements SGIScheduleAdapter
{
    public static function create($cron, $id = SG_SCHEDULER_DEFAULT_ID)
    {
        if (!self::isCronAvailable()) {
            return false;
        }

        $cronExecutionData = self::getCronExecutionData($cron);
        $time              = $cronExecutionData['time'];
        $recurrence        = $cronExecutionData['recurrence'];

        $args = array((int) $id);
        wp_schedule_event($time, $recurrence, SG_SCHEDULE_ACTION, $args);
    }

    public static function getCronExecutionData($cron)
    {
        $recurrence = '';
        $tmpTime    = self::getTmpTime($cron['intervalHour']);
        $tmpTimeUTC = strtotime(backupGuardConvertDateTimezoneToUTC(@date('Y-m-d H:i:s', $tmpTime)));

        if ($cron['interval'] == BG_SCHEDULE_INTERVAL_HOURLY) {
            $recurrence = 'hourly';
            $time       = strtotime(backupGuardCeliDateTimezone(time() + 3600));
            $time       = strtotime(backupGuardConvertDateTimezone(@date('Y-m-d H:i:s', $time), true));
        } else if ($cron['interval'] == BG_SCHEDULE_INTERVAL_DAILY) {
            $recurrence = 'daily';
            if ($tmpTimeUTC <= time()) {
                $time = strtotime('Next day ' . sprintf("%02d:00", $cron['intervalHour']));
            } else {
                $time = $tmpTime;
            }
        } else if ($cron['interval'] == BG_SCHEDULE_INTERVAL_WEEKLY) {
            $recurrence    = 'weekly';
            $dayOfInterval = $cron['dayOfInterval'];

            switch ($dayOfInterval) {
                case 2:
                    $dayOfInterval = 'Tuesday';
                    break;
                case 3:
                    $dayOfInterval = 'Wednesday';
                    break;
                case 4:
                    $dayOfInterval = 'Thursday';
                    break;
                case 5:
                    $dayOfInterval = 'Friday';
                    break;
                case 6:
                    $dayOfInterval = 'Saturday';
                    break;
                case 7:
                    $dayOfInterval = 'Sunday';
                    break;
                default:
                    $dayOfInterval = 'Monday';
                    break;
            }

            if ($tmpTimeUTC <= time()) {
                $time = strtotime('Next ' . $dayOfInterval . ' ' . sprintf("%02d:00", $cron['intervalHour']));
            } else {
                $time = strtotime('this ' . $dayOfInterval . ' ' . sprintf("%02d:00", $cron['intervalHour']));
            }
        } else if ($cron['interval'] == BG_SCHEDULE_INTERVAL_MONTHLY) {
            $recurrence    = 'monthly';
            $dayOfInterval = $cron['dayOfInterval'];
            $today         = (int) date('d');

            if ($today > $dayOfInterval) {
                $time = strtotime('first day of next month ' . sprintf("%02d:00", $cron['intervalHour']));
                $time += ($dayOfInterval - 1) * SG_ONE_DAY_IN_SECONDS;
            } else if ($today == $dayOfInterval) {
                if ($tmpTimeUTC > time()) {
                    $time = $tmpTime;
                } else {
                    $time = strtotime('first day of next month ' . sprintf("%02d:00", $cron['intervalHour']));
                    $time += ($dayOfInterval - 1) * SG_ONE_DAY_IN_SECONDS;
                }
            } else {
                $time = $tmpTime + ($dayOfInterval - $today) * SG_ONE_DAY_IN_SECONDS;
            }
        } else {
            $recurrence          = 'yearly';
            $dayOfInterval       = $cron['dayOfInterval'];
            $monthOfInterval     = $cron['monthOfInterval'];
            $monthOfIntervalName = date('F', mktime(0, 0, 0, $monthOfInterval, 10));
            $today               = (int) date('d');
            $thisMonth           = (int) date('n');

            if ($thisMonth > $monthOfInterval) {
                $time = strtotime($dayOfInterval . ' ' . $monthOfIntervalName . ' next year ' . sprintf('%02d:00', $cron['intervalHour']));
            } else if ($thisMonth == $monthOfInterval) {
                if ($today > $dayOfInterval) {
                    $time = strtotime($dayOfInterval . ' ' . $monthOfIntervalName . ' next year ' . sprintf('%02d:00', $cron['intervalHour']));
                } else if ($today == $dayOfInterval) {
                    if ($tmpTimeUTC > time()) {
                        $time = strtotime($dayOfInterval . ' ' . $monthOfIntervalName . ' this year ' . sprintf('%02d:00', $cron['intervalHour']));
                    } else {
                        $time = strtotime($dayOfInterval . ' ' . $monthOfIntervalName . ' next year ' . sprintf('%02d:00', $cron['intervalHour']));
                    }
                } else {
                    $time = strtotime($dayOfInterval . ' ' . $monthOfIntervalName . ' this year ' . sprintf('%02d:00', $cron['intervalHour']));
                }
            } else {
                $time = strtotime($dayOfInterval . ' ' . $monthOfIntervalName . ' this year ' . sprintf('%02d:00', $cron['intervalHour']));
            }
        }

        return array(
            'time' => strtotime(backupGuardConvertDateTimezoneToUTC(@date("Y-m-d H:i:s", $time))),
            'recurrence' => $recurrence
        );
    }

    public static function remove($id = SG_SCHEDULER_DEFAULT_ID)
    {
        $args = array((int) $id);
        wp_clear_scheduled_hook(SG_SCHEDULE_ACTION, $args);
    }

    public static function getTmpTime($hours)
    {
        return (int) strtotime('Today ' . sprintf("%02d:00", $hours));
    }

    public static function isCronAvailable($force = false)
    {
        if ($force) {
            return !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON;
        }

        return true;
    }
}
