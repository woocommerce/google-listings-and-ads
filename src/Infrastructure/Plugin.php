<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Infrastructure;

/**
 * Interface Plugin
 *
 * @package Automattic\WooCommerce\GoogleForWC\Infrastructure
 */
interface Plugin extends Activateable, Deactivateable, Registerable {}
