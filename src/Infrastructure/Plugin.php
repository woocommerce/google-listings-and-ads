<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Interface Plugin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
interface Plugin extends Activateable, Deactivateable, Registerable {}
