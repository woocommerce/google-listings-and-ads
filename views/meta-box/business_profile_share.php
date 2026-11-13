<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/**
 * @var string
 */
$share_field_id = $this->share_field_id;

/**
 * @var string
 */
$summary_field_id = $this->summary_field_id;

wp_nonce_field( $this->nonce_action, $this->nonce_name );
?>

<div
	id="gla-business-profile-share-box"
	data-share-field-name="<?php echo esc_attr( $share_field_id ); ?>"
	data-summary-field-name="<?php echo esc_attr( $summary_field_id ); ?>"
></div>
