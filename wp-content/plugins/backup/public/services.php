<?php
require_once(dirname(__FILE__).'/boot.php');
require_once(SG_PUBLIC_INCLUDE_PATH.'header.php');
$contentClassName = getBackupPageContentClassName('services');
?>
<div id="sg-backup-page-content-services" class="sg-backup-page-content <?php echo $contentClassName; ?>">
    <div><h1 class="sg-backup-page-title"><?php _backupGuardT('Special services')?></h1></div>
    <div class="sg-service-container">
        <div class="plugin-card-top">
            <div class="row">
                <div class="col-md-3">
                    <div class="sg-migration-icon"></div>
                   <!--  <img src="<?php echo SG_PUBLIC_URL."img/wordPress-migration-service-product.png"?>" class="" alt=""> -->
                </div>
                <div class="col-md-7 sg-migration-info">
                    <div class="column-name">
                        <h1>
                            <a href="<?php echo SG_MIGRATION_SERVICE_URL?>" class="thickbox" target="_blank"><?php _backupGuardT('WordPress'); ?> <b><?php _backupGuardT('Migration Service'); ?></b></a>
                        </h1>
                    </div>
                    <div class="column-description">
                        <p class="column-description-p"><?php _backupGuardT('Our professionals will migrate all of your files and database and ensure <br> everything is working properly on your new server. With our migration service, you can expect:')?></p>
                        <div class="row">
                            <div class="col-md-5">
                                <p class="sg-migration-features">
                                    <span class="sg-right-arrow sg-services-arrow"></span>
                                    <?php _backupGuardT('Migration of your files')?>
                                </p>
                                <p class="sg-migration-features">
                                    <span class="sg-right-arrow sg-services-arrow"></span>
                                    <?php _backupGuardT('Migration of your database')?>
                                </p>
                                <p class="sg-migration-features">
                                    <span class="sg-right-arrow sg-services-arrow"></span>
                                    <?php _backupGuardT('Refactoring of all urls')?>
                                </p>
                            </div>
                            <div class="col-md-7">
                                <p class="sg-migration-features">
                                    <span class="sg-right-arrow sg-services-arrow"></span>
                                    <?php _backupGuardT('Refactoring of all file names and image paths')?>
                                </p>
                                <p class="sg-migration-features">
                                    <span class="sg-right-arrow sg-services-arrow"></span>
                                    <?php _backupGuardT('Serialized data refactoring')?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 sg-migration-right-column">
                    <div class="migration-price-wrapper">
                        <ul class="sg-migration-price-ul">
                            <li><p id="sg-migration-service-price">$<b>84.95</b></p></li>
                            <li>
                                <a class="btn btn-success" target="_blank" data-slug="" href="<?php echo SG_MIGRATION_SERVICE_URL?>" aria-label="" data-name=""><?php _backupGuardT('Order now')?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>