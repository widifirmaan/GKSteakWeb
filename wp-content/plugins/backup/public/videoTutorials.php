<?php
require_once(SG_SCHEDULE_PATH.'SGSchedule.php');
$contentClassName = getBackupPageContentClassName('videoTutorials');
?>
<div id="sg-backup-page-content-videoTutorials" class="sg-backup-page-content <?php echo $contentClassName; ?>">
	<div><h1 class="sg-backup-page-title"><?php _backupGuardT('Video Tutorials')?></h1></div>
	
	<h2><?php _backupGuardT('BackupGuard WordPress Plugin Walkthrough'); ?></h2>
	<iframe width="859" height="483" src="https://www.youtube.com/embed/xn_-FtZNHEw" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
</div>