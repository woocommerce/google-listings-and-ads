<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\API\PermissionsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationService
 *
 * Evaluates notification conditions in a uniform, pluggable way. Evaluators are
 * resolved as a tagged collection via the container (mirrors NoteInitializer),
 * sorted by priority, and the triggered/dismissed state is persisted per user.
 *
 * ContainerAware used to access:
 * - NotificationEvaluatorInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationService implements ContainerAwareInterface {

	use ContainerAwareTrait;
	use PermissionsTrait;

	/**
	 * The user_meta key under which the notifications state is stored.
	 */
	protected const STATE_META_KEY = 'gla_notifications_state';

	/**
	 * The current schema version of the persisted state.
	 */
	protected const SCHEMA_VERSION = 1;

	/**
	 * The key used to store the schema version inside the state array.
	 */
	protected const VERSION_KEY = '_version';

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * NotificationService constructor.
	 *
	 * @param WP $wp
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Get the notifications that should currently be shown, ordered by priority (ascending).
	 *
	 * @return array[] List of [ 'id' => string, 'triggered_at' => int ] entries.
	 */
	public function get_notifications(): array {
		if ( ! $this->can_manage() ) {
			return [];
		}

		$evaluators    = $this->get_evaluators();
		$state         = $this->get_state();
		$state_changed = false;
		$notifications = [];

		foreach ( $evaluators as $evaluator ) {
			$id = $evaluator->get_id();

			// A permanently-dismissed notification is excluded from all future results.
			if ( ! empty( $state[ $id ]['dismissed'] ) ) {
				continue;
			}

			if ( ! $evaluator->should_show() ) {
				continue;
			}

			// Record the trigger time the first time the condition is met, and never overwrite it.
			if ( ! isset( $state[ $id ]['triggered_at'] ) ) {
				$state[ $id ]['triggered_at'] = time();
				$state_changed                = true;
			}

			$notifications[] = [
				'id'           => $id,
				'triggered_at' => $state[ $id ]['triggered_at'],
			];
		}

		if ( $state_changed ) {
			$this->save_state( $state );
		}

		return $notifications;
	}

	/**
	 * Whether the given ID belongs to a registered notification evaluator.
	 *
	 * @param string $id The notification ID.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		foreach ( $this->get_evaluators() as $evaluator ) {
			if ( $evaluator->get_id() === $id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Permanently dismiss a notification so it is excluded from all future results.
	 *
	 * @param string $id The notification ID to dismiss.
	 *
	 * @return void
	 */
	public function dismiss( string $id ): void {
		if ( ! $this->can_manage() || ! $this->has( $id ) ) {
			return;
		}

		$state                     = $this->get_state();
		$state[ $id ]['dismissed'] = true;

		$this->save_state( $state );
	}

	/**
	 * Get all registered notification evaluators, ordered by priority (ascending).
	 *
	 * @return NotificationEvaluatorInterface[]
	 */
	protected function get_evaluators(): array {
		if ( ! $this->container->has( NotificationEvaluatorInterface::class ) ) {
			return [];
		}

		$evaluators = $this->container->get( NotificationEvaluatorInterface::class );

		usort(
			$evaluators,
			static function ( NotificationEvaluatorInterface $a, NotificationEvaluatorInterface $b ): int {
				return $a->get_priority() <=> $b->get_priority();
			}
		);

		return $evaluators;
	}

	/**
	 * Read the persisted notifications state for the current user.
	 *
	 * @return array
	 */
	protected function get_state(): array {
		$state = $this->wp->get_user_meta( $this->wp->get_current_user_id(), self::STATE_META_KEY );

		if ( ! is_array( $state ) || empty( $state ) ) {
			return [ self::VERSION_KEY => self::SCHEMA_VERSION ];
		}

		return $state;
	}

	/**
	 * Persist the notifications state for the current user.
	 *
	 * @param array $state The state to persist.
	 *
	 * @return void
	 */
	protected function save_state( array $state ): void {
		$state[ self::VERSION_KEY ] = self::SCHEMA_VERSION;

		$this->wp->update_user_meta( $this->wp->get_current_user_id(), self::STATE_META_KEY, $state );
	}
}
