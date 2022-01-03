<?php

class SGReviewManager
{
	public function renderContent()
	{
		$dontShowAgain = SGConfig::get('closeReviewBanner');
		if ($dontShowAgain) {
			return '';
		}
		// review with backup Count
		$allowReviewCount = $this->isAllowToShowReviewByCount();
		if ($allowReviewCount) {
			// show review
			$key = 'backupCount';
			$backupCountReview = $this->getBackupCounts();
			$customContent = 'Yay! We see that you have made '.$backupCountReview.' backups.';
			echo $this->reviewContentMessage($customContent, $key);
			return '';
		}
		// review after successfully restore
		$isSuccessFullRestore = $this->isSuccessFullRestore();
		if ($isSuccessFullRestore) {
			// after successfully restore
			$key = 'restoreCount';
			$restoreReviewCount = $this->getBackupRestoreCounts();
			$customContent = 'Yay! Congrats, you have restored your website for the '.$restoreReviewCount.' st time!';
			echo $this->reviewContentMessage($customContent, $key);
			return '';
		}

		// review after X days
		$isAllowDaysReview = $this->isAllowDaysReview();
		if ($isAllowDaysReview) {
			$key = 'dayCount';
			$usageDays = $this->getBackupUsageDays();
			$customContent = 'Yay! You are a part of the BG team for over '.$usageDays.' days now! Hope you enjoy our service!';
			echo $this->reviewContentMessage($customContent, $key);
			return '';
		}

		return '';
	}

	public static function getBackupUsageDays()
	{
		$installDate = SGConfig::get('installDate');

		$timeDate = new \DateTime('now');
		$timeNow = strtotime($timeDate->format('Y-m-d H:i:s'));
		$diff = $timeNow-$installDate;
		$days  = floor($diff/(60*60*24));

		return $days;
	}

	public function reviewContentMessage($customContent, $type)
	{
		ob_start();
		?>
		<style>
			.sg-backup-buttons-wrapper .press{
				box-sizing:border-box;
				cursor:pointer;
				display:inline-block;
				margin:0;
				padding:0.5em 0.75em;
				text-decoration:none;
				transition:background 0.15s linear
				width: 148px;
				height: 50px;
			}
			.sg-backup-buttons-wrapper .press-grey {
				border:2px solid #FFFFFF;
				color: #FFF;
			}
			.sg-backup-buttons-wrapper .press-lightblue {
				background-color:#ffffff;
				border:2px solid #FFFFFF;
				color: rgba(0,29,182,1);
				margin: 0 20px;
			}
			.sg-backup-buttons-wrapper .press-lightblue:hover {
				background-color: rgba(0, 0, 0, 0);
				color: #FFFFFF;
			}
			.sg-backup-buttons-wrapper {
				text-align: center;
			}
			.sg-backup-review-wrapper {
				position: relative;
				text-align: center;
				background-color: #001DB6;
				height: 185px;
				box-sizing: border-box;
				background-image: url(<?php echo SG_IMAGE_URL.'reviewBg.png' ?>);
				margin-top: 45px;
				margin-right: 20px;
			}
			.sgpb-popup-dialog-main-div-wrapper .sg-backup-review-wrapper {
				margin-top: 0px;
				margin-right: 0px ;
			}
			.sgpb-popup-dialog-main-div-wrapper .banner-x {
				display: none !important;
			}
			.sg-backup-review-wrapper p {
				color: #FFFFFF;
			}
			.sg-backup-review-h1 {
				font-size: 22px;
				font-weight: normal;
				line-height: 1.384;
			}
			.sg-backup-review-h2 {
				font-size: 23px;
				font-weight: bold;
				color: #FFFFFF;
				margin: 10px 0;
				margin-top: 27px;
			}
			:root {
				--main-bg-color: #1ac6ff;
			}
			.sg-backup-review-strong {
				color: var(--main-bg-color);
			}
			.sg-backup-review-mt20 {
				margin-top: 10px;
				color: #FFFFFF !important;
				margin-bottom: 20px;
			}
			.sg-backup-wow {
				font-size: 35px;
				color: #FFFFFF;
				margin: 15px 0;
				padding-top: 16px;
			}
			.sg-backup-review-button {
				font-size: 15px !important;
				font-weight: bold;
				border-radius: 8px !important;
				width: 120px !important;
				height: 40px !important;
			}
			.sg-backup-button-1, .sg-backup-backup-button-2 {
				background-color: rgba(0, 0, 0, 0) !important;
				color: #ffffff !important;
			}
			.sg-backup-button-1:hover, .sg-backup-backup-button-2:hover {
				background-color: #FFFFFF !important;
				color: #001DB6 !important;
				border: 2px solid #FFFFFF;
			}
			.sg-backup-custom-content {
				color: #FFFFFF;
				font-size: 20px;
				margin: 14px 0;
			}
			.sg-backup-img-wrapper,
			.sg-backup-review-description-wrapper {
			    padding-top: 1px;
	    	}
			#sgpb-popup-dialog-main-div .sg-backup-img-wrapper,
			#sgpb-popup-dialog-main-div .sg-backup-review-description-wrapper {
				display: inline-block;
			}
			.sg-backup-img-wrapper {
				width: 256px;
				float: left;
				background-color: #FFFFFF;
				height: 100%;
			}
			.sg-backup-review-description-wrapper {
				max-width: 100%;
				vertical-align: top;
			}
			.sgpb-popup-dialog-main-div-wrapper .sg-backup-review-description {
				padding: 0 30px;
			}
			.banner-x {
				position: absolute;
				right: 14px;
				top: 5px;
				display: inline !important;
				cursor: pointer;
				width: auto !important;
				height: auto !important;
			}
			.banner-x:hover {
				background-color: #071cb6 !important;
				color: #ffffff !important;
				border: none !important;
			}
			@media (max-width: 1350px) {
				.sg-backup-wow {
					font-size: 27px;
				}
				.sgpb-popup-dialog-main-div-wrapper .sg-backup-review-description {
					padding: 0 5px;
				}
			}
            @media (max-width: 1173px) {
            	.sg-backup-img-wrapper {width: 202px;}
                .sg-backup-review-h2 {
                    margin-top: 24px;
                }
                #sgpb-popup-dialog-main-div .sg-backup-review-mt20 {margin-bottom: 20px;}
			}
			@media (max-width: 1027px) {
				#sgpb-popup-dialog-main-div .sg-backup-img-wrapper,
				#sgpb-popup-dialog-main-div .sg-backup-review-description-wrapper {
					display: inherit;
				}
				#sgpb-popup-dialog-main-div .sg-backup-review-h2 {margin-top: 18px;}
			}
            @media (max-width: 815px) {
            	.sg-backup-review-mt20 {margin-bottom: 10px;}
                .sg-backup-review-h2 {
                    margin-top: 23px;
                }
			}
			@media (max-width: 735px) {#sgpb-popup-dialog-main-div .sg-backup-review-h2 {margin-top: 10px;}}
			@media (max-width: 715px) {
            	.sg-backup-review-mt20 {margin-bottom: 10px;}
                .sg-backup-review-h2 {
                    margin-top: 15px;
                }
			}
			@media (max-width: 640px) {
				.sg-backup-img-wrapper {display: none;}
				.sg-backup-review-h2 {margin-top: 22px}
			}
		</style>
		<div id="sg-backup-review-wrapper" class="sg-backup-review-wrapper">
			<span class="banner-x sg-backup-review-button sg-backup-backup-button-2 sg-backup-show-popup-period" data-message-type="<?php echo $type; ?>">x</span>
			<div class="sg-backup-img-wrapper">
				<img src="<?php echo SG_IMAGE_URL; ?>sgBackupVerticalLogo.png" width="200px" height="181px">
			</div>
			<div class="sg-backup-review-description-wrapper">
				<div class="sg-backup-review-description">
					<!--				<h2 class="sg-backup-custom-content"></h2>-->
					<h2 class="sg-backup-review-h2"><?php echo $customContent; ?></h2>
					<p class="sg-backup-review-mt20"><?php _e('Have your input in the development of our plugin, and we’ll get better and happier. Leave your 5-star positive review and help <br> us go further to the perfection!'); ?></p>
				</div>
				<div class="sg-backup-buttons-wrapper">
					<button class="press press-grey sg-backup-review-button sg-backup-button-1 sg-already-did-review"><?php _e('I already did'); ?></button>
					<button class="press press-lightblue sg-backup-review-button sg-backup-button-3 sg-backup-you-worth-it"><?php _e('You worth it!'); ?></button>
					<button class="press press-grey sg-backup-review-button sg-backup-backup-button-2 sg-backup-show-popup-period" data-message-type="<?php echo $type; ?>"><?php _e('Maybe later'); ?></button>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			var closeBackupGuardReviewPopup = function ()
			{
				if (window.backupGuardReviewPopup) {
					window.backupGuardReviewPopup.close();
				}
			};
			var sgBackupDontShowAgain = function() {
				closeBackupGuardReviewPopup();
				jQuery('.sg-backup-review-wrapper').remove();
				var data = {
					action: 'backup_guard_reviewDontShow',
					token: BG_BACKUP_STRINGS.nonce
				};
				jQuery.post(ajaxurl, data, function () {
				});
			};
			var backupGuardReviewBannerButtons = function() {
				jQuery('.sg-backup-button-3').bind('click', function () {
					sgBackupDontShowAgain();
					window.open("<?php echo BACKUP_GUARD_WORDPRESS_REVIEW_URL; ?>")
				});
				jQuery('.sg-backup-button-1').bind('click', function () {
					sgBackupDontShowAgain();
				});
				jQuery('.sg-backup-backup-button-2').bind('click', function () {
					closeBackupGuardReviewPopup();
					jQuery('.sg-backup-review-wrapper').remove();
					var type = jQuery(this).data('message-type');

					var data = {
						action: 'backup_guard_review_later',
						type: type,
						token: BG_BACKUP_STRINGS.nonce
					};
					jQuery.post(ajaxurl, data, function () {
					});
				});
			}
			backupGuardReviewBannerButtons();

			jQuery(window).bind('sgpbDidOpen', function() {
				backupGuardReviewBannerButtons();
			});
		</script>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	private function isAllowDaysReview()
	{
		$shouldOpen = false;
		$periodNextTime =  SGConfig::get('openNextTime');

		$dontShowAgain = SGConfig::get('closeReviewBanner');
		// When period next time does not exits it means the user is old
		if (!$periodNextTime) {
			$usageDays = $this->getBackupTableCreationDate();
			SGConfig::set('usageDays', $usageDays);
			// For old users
			if (defined('SG_BACKUP_REVIEW_PERIOD') && $usageDays > SG_BACKUP_REVIEW_PERIOD && !$dontShowAgain) {
				return true;
			}

			$remainingDays = SG_BACKUP_REVIEW_PERIOD - $usageDays;

			$popupTimeZone = 'America/New_York';
			$timeDate = new \DateTime('now', new DateTimeZone($popupTimeZone));
			$timeDate->modify('+'.$remainingDays.' day');

			$timeNow = strtotime($timeDate->format('Y-m-d H:i:s'));
			SGConfig::set('openNextTime', $timeNow);

			return false;
		}

		$currentData = new \DateTime('now');
		$timeNow = $currentData->format('Y-m-d H:i:s');
		$timeNow = strtotime($timeNow);

		if ($periodNextTime < $timeNow) {
			$shouldOpen = true;
		}

		return $shouldOpen;
	}

	private function getBackupTableCreationDate()
	{
		$sgdb = SGDatabase::getInstance();

		$results = $sgdb->query('SELECT table_name, create_time FROM information_schema.tables WHERE table_schema="%s" AND table_name="%s"', array(DB_NAME, SG_ACTION_TABLE_NAME));

		if (empty($results) || empty($results[0]['create_time'])) {
			return 0;
		}

		$createTime = $results[0]['create_time'];
		$createTime = strtotime($createTime);
		SGConfig::set('installDate', $createTime);
		$diff = time() - $createTime;
		$days = floor($diff/(60*60*24));

		return $days;
	}

	public static function getBackupCounts()
	{
		$sgdb = SGDatabase::getInstance();
		$result = $sgdb->query('SELECT count(id) as countBackups FROM '.SG_ACTION_TABLE_NAME.' WHERE type='.SG_ACTION_TYPE_BACKUP.' AND status='.SG_ACTION_STATUS_FINISHED);

		if (empty($result[0]['countBackups'])) {
			return 0;
		}

		return (int)$result[0]['countBackups'];;
	}

	private function isAllowToShowReviewByCount()
	{
		$status = false;

		$backupsCount = SGReviewManager::getBackupCounts();

		if (empty($backupsCount)) {
			return $status;
		}

		$backupCountReview = SGConfig::get('backupReviewCount');
		if (empty($backupCountReview)) {
			$backupCountReview = SG_BACKUP_REVIEW_BACKUP_COUNT;
		}

		return ($backupsCount >= $backupCountReview);
	}

	public static function getBackupRestoreCounts()
	{
		$sgdb = SGDatabase::getInstance();
		$result = $sgdb->query('SELECT count(id) as countRestores FROM '.SG_ACTION_TABLE_NAME.' WHERE type='.SG_ACTION_TYPE_RESTORE.' AND status='.SG_ACTION_STATUS_FINISHED);

		if (empty($result[0]['countRestores'])) {
			return 0;
		}

		return (int)$result[0]['countRestores'];
	}

	private function isSuccessFullRestore()
	{
		$status = false;

		$countRestores = SGReviewManager::getBackupRestoreCounts();

		if (empty($countRestores)) {
			return $status;
		}

		$restoreReviewCount = SGConfig::get('restoreReviewCount');
		if (empty($restoreReviewCount)) {
			$restoreReviewCount = SG_BACKUP_REVIEW_RESTORE_COUNT;
		}

		return ($countRestores >= $restoreReviewCount);
	}
}