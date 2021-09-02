<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/** @var string $some_variable */
$some_variable = $this->some_variable;
/** @var string $raw_variable */
$raw_variable = $this->raw( 'raw_variable' );
/** @var bool $bool_variable */
$bool_variable = $this->bool_variable;
/** @var array $array_variable */
$array_variable = $this->array_variable;

/** @var string $partial_path */
$partial_path = $this->partial_path;
?>
Variable value is: <?php echo $some_variable; ?>

Raw variable value is: <?php echo $raw_variable; ?>

Boolean variable value is: <?php echo true === $bool_variable ? '1' : '0' ; ?>

Array variable value is: <?php echo implode( ', ', $array_variable ); ?>

<?php
echo $this->render_partial( $partial_path, [ 'some_variable' => 'Test partial' ] );
?>

Partial with no context:
<?php
echo $this->render_partial( $partial_path );
?>
