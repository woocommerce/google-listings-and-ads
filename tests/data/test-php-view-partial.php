<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/** @var string $some_variable */
$some_variable = $this->some_variable;
?>
Partial variable value is: <?php echo $some_variable; ?>
