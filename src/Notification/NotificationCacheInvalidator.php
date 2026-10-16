<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationCacheInvalidator
 *
 * Binds the action hooks declared by each InvalidatableNotificationEvaluatorInterface and,
 * when one fires, clears that evaluator's cached result so the next page load recalculates
 * it. This keeps a notification's appearance/disappearance in step with the action that
 * changed it (e.g. pausing a campaign) instead of waiting for the one-hour cache to expire.
 *
 * Relies on the cached condition being site-scoped (see CachedNotificationEvaluatorTrait):
 * one transient per notification, cleared reliably with a single delete_transient() even
 * under an external object cache.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationCacheInvalidator implements ContainerAwareInterface, Registerable, Service {

	use ContainerAwareTrait;

	/**
	 * Bind each evaluator's invalidation hooks to a cache clear.
	 */
	public function register(): void {
		foreach ( $this->get_invalidatable_evaluators() as $evaluator ) {
			$cache_key = NotificationCacheKeys::for_site( $evaluator->get_id() );

			foreach ( $evaluator->get_invalidation_hooks() as $hook ) {
				add_action(
					$hook,
					static function () use ( $cache_key ): void {
						delete_transient( $cache_key );
					}
				);
			}
		}
	}

	/**
	 * The registered evaluators that opt into event-driven cache invalidation.
	 *
	 * @return InvalidatableNotificationEvaluatorInterface[]
	 */
	protected function get_invalidatable_evaluators(): array {
		if ( ! $this->container->has( NotificationEvaluatorInterface::class ) ) {
			return [];
		}

		return array_filter(
			$this->container->get( NotificationEvaluatorInterface::class ),
			static function ( $evaluator ): bool {
				return $evaluator instanceof InvalidatableNotificationEvaluatorInterface;
			}
		);
	}
}
