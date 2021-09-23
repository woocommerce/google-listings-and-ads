<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Something that will be run daily.
 *
 * Classes that implement this will be automatically "run" each day via the Action Scheduler cron task.
 *
 * @since x.x.x
 */
interface RunDaily extends Runnable {}
