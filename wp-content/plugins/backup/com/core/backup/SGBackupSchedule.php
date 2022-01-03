<?php

require_once(SG_SCHEDULE_PATH . 'SGSchedule.php');

class SGBackupSchedule
{
    public static function create($cron, $options, $label)
    {
        $sgdb   = SGDatabase::getInstance();
        $params = array();
        $query  = '';

        if (!SGBoot::isFeatureAvailable('MULTI_SCHEDULE')) {
            self::remove();
            $query = 'INSERT INTO ' . SG_SCHEDULE_TABLE_NAME . ' (id, label, status, schedule_options, backup_options) VALUES (%d, %s, %d, %s, %s) ON DUPLICATE KEY UPDATE label=%s, schedule_options=%s, backup_options=%s';

            $params = array(
                SG_SCHEDULER_DEFAULT_ID,
                $label,
                SG_SHCEDULE_STATUS_PENDING,
                json_encode($cron),
                json_encode($options),
                $label,
                json_encode($cron),
                json_encode($options)
            );
        } else {
            $query = 'INSERT INTO ' . SG_SCHEDULE_TABLE_NAME . ' (label, status, schedule_options, backup_options) VALUES (%s, %d, %s, %s)';

            $params = array(
                $label,
                SG_SHCEDULE_STATUS_PENDING,
                json_encode($cron),
                json_encode($options)
            );
        }

        $res = $sgdb->query($query, $params);

        if ($res) {
            $id = $sgdb->lastInsertId();
            SGSchedule::create($cron, $id);
        }
    }

    public static function remove($id = SG_SCHEDULER_DEFAULT_ID)
    {
        $sgdb = SGDatabase::getInstance();
        $sgdb->query('DELETE FROM ' . SG_SCHEDULE_TABLE_NAME . ' WHERE id=%d', array($id));
        SGSchedule::remove($id);
    }

    public static function getCronExecutionData($cron)
    {
        $cron = json_decode($cron, true);

        return SGSchedule::getCronExecutionData($cron);
    }

    public static function getAllSchedules($modifyData = true)
    {
        $sgdb    = SGDatabase::getInstance();
        $results = $sgdb->query('SELECT id, label, status, schedule_options, backup_options FROM ' . SG_SCHEDULE_TABLE_NAME);
        if (!$modifyData) {
            return $results;
        }

        $schedules = array();
        foreach ($results as $key => $row) {
            $schedules[$key]['id']     = $row['id'];
            $schedules[$key]['label']  = $row['label'];
            $schedules[$key]['status'] = $row['status'];

            if (SGSchedule::isCronAvailable(true)) {
                $args                             = array((int) $row['id']);
                $cronExecutionData                = json_decode(json_encode(wp_get_scheduled_event(SG_SCHEDULE_ACTION, $args)), true);
                $schedules[$key]['recurrence']    = ucfirst($cronExecutionData['schedule']);
                $schedules[$key]['executionDate'] = $cronExecutionData['timestamp'];
            } else {
                $cronExecutionData                = self::getCronExecutionData($row['schedule_options']);
                $schedules[$key]['recurrence']    = ucfirst($cronExecutionData['recurrence']);
                $schedules[$key]['executionDate'] = $cronExecutionData['time'];
            }

            $schedules[$key]['backup_options'] = $row['backup_options'];
        }

        return $schedules;
    }
}
