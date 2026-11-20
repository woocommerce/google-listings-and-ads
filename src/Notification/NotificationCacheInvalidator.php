<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshNotificationEvaluators;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationCacheInvalidator
 *
 * Binds the action hooks declared by each InvalidatableNotificationEvaluatorInterface and,
 * when one fires, marks that evaluator's cached result stale and schedules a background
 * refresh. This keeps a notification's appearance/disappearance in step with the action that
 * changed it (e.g. pausing a campaign) instead of waiting for the result to go stale on its own.
 *
 * The stale result stays visible until the refresh replaces it, so a notification whose
 * condition did not change does not disappear and come back.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationCacheInvalidator implements ContainerAwareInterface, Registerable, Service {

	use ContainerAwareTrait;

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * Whether a refresh was already scheduled during this request.
	 *
	 * @var bool
	 */
	protected $refresh_scheduled = false;

	/**
	 * NotificationCacheInvalidator constructor.
	 *
	 * @param JobRepository $job_repository
	 */
	public function __construct( JobRepository $job_repository ) {
		$this->job_repository = $job_repository;
	}

	/**
	 * Bind each evaluator's invalidation hooks.
	 */
	public function register(): void {
		foreach ( $this->get_invalidatable_evaluators() as $evaluator ) {
			foreach ( $evaluator->get_invalidation_hooks() as $hook ) {
				add_action(
					$hook,
					function () use ( $evaluator ): void {
						$this->handle_invalidation( $evaluator );
					}
				);
			}
		}
	}

	/**
	 * Mark the evaluator's cached result stale and schedule a refresh.
	 *
	 * An evaluator without the stale-while-revalidate cache has its transient deleted instead,
	 * so its next should_show() call recalculates it.
	 *
	 * @param InvalidatableNotificationEvaluatorInterface $evaluator
	 *
	 * @return void
	 */
	protected function handle_invalidation( InvalidatableNotificationEvaluatorInterface $evaluator ): void {
		if ( ! $evaluator instanceof CachedNotificationEvaluatorInterface ) {
			delete_transient( NotificationCacheKeys::for_site( $evaluator->get_id() ) );
			return;
		}

		$evaluator->invalidate();
		$this->schedule_refresh();
	}

	/**
	 * Schedule the refresh job, once per request.
	 *
	 * Skipped before Action Scheduler is initialized; the stale result then schedules the
	 * refresh on the next admin page load.
	 *
	 * @return void
	 */
	protected function schedule_refresh(): void {
		if ( $this->refresh_scheduled || ! did_action( 'action_scheduler_init' ) ) {
			return;
		}

		$this->job_repository->get( RefreshNotificationEvaluators::class )->schedule();
		$this->refresh_scheduled = true;
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
