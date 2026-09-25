<?php
defined('ABSPATH') || exit;
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-operations.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-services.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-finance.php';
require_once MUTQAN_DIR.'src/Unit05/class-mutqan-marketing.php';
require_once MUTQAN_DIR.'src/Unit06/class-mutqan-system.php';
register_activation_hook(MUTQAN_FILE,function(){
    MUTQAN_Operations::install();
    MUTQAN_Services::install();
    MUTQAN_Finance::install();
    MUTQAN_Marketing::install();
    MUTQAN_Audit::install();
});
