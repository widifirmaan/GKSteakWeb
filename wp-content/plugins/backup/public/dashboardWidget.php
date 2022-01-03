<?php
require_once(SG_BACKUP_PATH.'SGBackup.php');
// Function that outputs the contents of the dashboard widget
function backup_guard_dashboard_widget_function( $post, $callback_args ) {

	$banner = backupGuardGetBanner(SG_ENV_WORDPRESS,"dashboard-widget");

	$backups = SGBackup::getAllBackups();
	$allBackupsCount = count($backups);
	$successBackups = 0;
	$faildBackups = 0;
	$inprogress = 0;
	$warningBackups = 0;
	$canceledBackups = 0;

	for($i = 0; $i < $allBackupsCount; $i++){
		if (empty($backups[$i])) {
			continue;
		}
		switch ($backups[$i]['status']){
			case SG_ACTION_STATUS_IN_PROGRESS_DB:
			case SG_ACTION_STATUS_IN_PROGRESS_FILES:
				$inprogress++;
				break;
			case SG_ACTION_STATUS_FINISHED_WARNINGS:
				$warningBackups++;
				break;
			case SG_ACTION_STATUS_CANCELLED:
				$canceledBackups++;
				break;
			case SG_ACTION_STATUS_ERROR:
				$faildBackups++;
				break;
			default:
				$successBackups++;
		}
	}
	if(strpos(SG_PRODUCT_IDENTIFIER,"silver") !== false || strpos(SG_PRODUCT_IDENTIFIER,"gold") !== false) {
		$sgb = new SGBackup();
		$scheduleParams = $sgb->getScheduleParamsById(SG_SCHEDULER_DEFAULT_ID);
		$scheduleParams = backupGuardParseBackupOptions($scheduleParams);
		$schedulesCount = strlen($scheduleParams['label'])? 1 : 0;

	}else if(strpos(SG_PRODUCT_IDENTIFIER,"free") === false) { // platinum
		require_once(SG_BACKUP_PATH.'SGBackupSchedule.php');
		$allSchedules = SGBackupSchedule::getAllSchedules();
		$schedulesCount = count($allSchedules);
	}


	?>
	<div style="width: 100%; font-size: 12px; "><?php echo $banner ?></div>
	<div id="canvas-holder" style="width:100%">
		<canvas id="chart-area"></canvas>
	</div>
	<script>
		function backupGuardLoadChart() {
			var config = {
				type: 'pie',
				data: {
					datasets: [{
						data: [
							<?php echo $successBackups ?>,
							<?php echo $faildBackups ?>,
							<?php echo $warningBackups ?>,
							<?php echo $canceledBackups ?>,
							<?php echo $inprogress ?>


						],
						backgroundColor: [
							"#2b8b3a",
							"#f96868",
							"#ffb848",
							"#7C858E",
							"#64aed9"

						],
						label: 'Dataset 1'
					}],
					labels: [
						"Success (<?php echo $successBackups ?>)",
						"Failed (<?php echo $faildBackups ?>)",
						"Warning (<?php echo $warningBackups ?>)",
						"Canceled (<?php echo $canceledBackups ?>)",
						"In progress (<?php echo $inprogress ?>)",
					]
				},
				options: {
					responsive: true,
					legend: {
						labels: {
							// This more specific font property overrides the global property
							fontFamily: "'Source Sans Pro', 'Calibri', 'Candara', 'Arial', 'sans-serif'"
						}
					}
				}
			};
			<?php
				if(isset($schedulesCount)) {
					?>
			config.data.datasets[0].data.push(<?php echo $schedulesCount ?>);
			config.data.datasets[0].backgroundColor.push("#cecece");
			config.data.labels.push("Schedules (<?php echo $schedulesCount ?>)");
					<?php
				}

			?>
			var ctx = document.getElementById("chart-area").getContext("2d");
			window.backupGuardPieChart = new Chart(ctx, config);
		}
		backupGuardLoadChart();
	</script>
	<?php
}

