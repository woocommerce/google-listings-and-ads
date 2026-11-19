<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;

defined( 'ABSPATH' ) || exit;

/**
 * Class SmallProductImagesEvaluator
 *
 * Fires when at least one product synced to Google has a main image smaller than
 * ProductImageSizeAudit::MIN_IMAGE_DIMENSION in width or height.
 *
 * The audit caches its count and recalculates it in a background job, so this evaluator
 * does not add its own cache.
 *
 * @since 3.9.6
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SmallProductImagesEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, Service {

	use MerchantCenterAwareTrait;

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/** @var ProductImageSizeAudit */
	private $image_size_audit;

	/**
	 * SmallProductImagesEvaluator constructor.
	 *
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 * @param ProductImageSizeAudit     $image_size_audit
	 */
	public function __construct( ServiceBasedMerchantState $service_based_merchant_state, ProductImageSizeAudit $image_size_audit ) {
		$this->service_based_merchant_state = $service_based_merchant_state;
		$this->image_size_audit             = $image_size_audit;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'small-product-images';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
			return false;
		}

		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		return $this->image_size_audit->get_small_image_product_count() > 0;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SMALL_PRODUCT_IMAGES;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::SMALL_PRODUCT_IMAGES;
	}
}
