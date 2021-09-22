<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Exception as GoogleException;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Promotion as GooglePromotion;
defined('ABSPATH') || exit();

/**
 * Class GooglePromotionService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class GooglePromotionService implements OptionsAwareInterface, Service {

    use OptionsAwareTrait;
    use ValidateInterface;

    public const INTERNAL_ERROR_REASON = 'internalError';

    public const NOT_FOUND_ERROR_REASON = 'notFound';

    /**
     *
     * @var ShoppingContent
     */
    protected $shopping_service;

    /**
     * GooglePromotionService constructor.
     *
     * @param ShoppingContent $shopping_service
     */
    public function __construct(ShoppingContent $shopping_service) {
        $this->shopping_service = $shopping_service;
    }

    /**
     *
     * @param GooglePromotion $promotion
     *
     * @return GooglePromotion
     *
     * @throws GoogleException If there are any Google API errors.
     */
    public function create(GooglePromotion $promotion): GooglePromotion {
        $merchant_id = $this->options->get_merchant_id();

        return $this->shopping_service->promotions->create($merchant_id,
            $promotion);
    }
}
