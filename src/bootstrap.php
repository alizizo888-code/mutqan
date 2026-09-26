<?php
defined('ABSPATH') || exit;

/*
 * Bootstrap order is intentional:
 * Core dependencies must be loaded before Unit02+ files because several
 * unit init() methods register listeners immediately at file load time.
 */
require_once MUTQAN_DIR.'src/Core/class-mutqan-registry.php';
require_once MUTQAN_DIR.'src/Core/class-mutqan-events.php';
require_once MUTQAN_DIR.'src/Core/class-mutqan-readiness.php';

require_once MUTQAN_DIR.'src/Unit02/class-mutqan-operations.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-orders.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-dispatch.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-gps.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-radar.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-radar-ui.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-communications.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-chat-ui.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-field-execution.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-inventory.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-fleet.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-technician-portal.php';
require_once MUTQAN_DIR.'src/Unit02/class-mutqan-chat-upload.php';

require_once MUTQAN_DIR.'src/Unit03/class-mutqan-services.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-warranty.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-ratings.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-pricing.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-customer-portal.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-crm.php';
require_once MUTQAN_DIR.'src/Unit03/class-mutqan-quality.php';

require_once MUTQAN_DIR.'src/Unit04/class-mutqan-finance.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-billing.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-wallets.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-wallet-topup-requests.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-commissions.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-advances.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-financial-reports.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-accounting.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-expenses.php';
require_once MUTQAN_DIR.'src/Unit04/class-mutqan-settlements.php';

require_once MUTQAN_DIR.'src/Unit05/class-mutqan-marketing.php';
require_once MUTQAN_DIR.'src/Unit05/class-mutqan-promotions.php';

require_once MUTQAN_DIR.'src/Unit06/class-mutqan-system.php';
require_once MUTQAN_DIR.'src/Unit06/class-mutqan-notifications.php';
require_once MUTQAN_DIR.'src/Unit06/class-mutqan-ai.php';
require_once MUTQAN_DIR.'src/Unit06/class-mutqan-analytics.php';
require_once MUTQAN_DIR.'src/Unit06/class-mutqan-content-seo.php';

require_once MUTQAN_DIR.'src/UI/class-mutqan-app.php';
require_once MUTQAN_DIR.'src/Core/class-mutqan-registry-admin.php';
require_once MUTQAN_DIR.'src/Core/class-mutqan-permissions-admin.php';

/* Activation is registered once from mutqan.php after all classes are loaded. */
