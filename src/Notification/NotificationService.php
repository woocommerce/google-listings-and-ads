<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\API\PermissionsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationService
 *
 * Evaluates notification conditions in a uniform, pluggable way. Evaluators are
 * resolved as a tagged collection via the container (mirrors NoteInitializer),
 * sorted by priority, and the triggered/dismissed state is persisted per user
 * or per site for site-scoped evaluators.
 *
 * ContainerAware used to access:
 * - NotificationEvaluatorInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationService implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use PermissionsTrait;

	/**
	 * The user_meta key under which per-user notifications state is stored.
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

		$evaluators         = $this->get_evaluators();
		$user_state         = $this->get_user_state();
		$site_state         = $this->get_site_state();
		$user_state_changed = false;
		$site_state_changed = false;
		$notifications      = [];

		foreach ( $evaluators as $evaluator ) {
			$id          = $evaluator->get_id();
			$site_scoped = $evaluator instanceof SiteScopedNotificationEvaluatorInterface;

			if ( $site_scoped ) {
				$state = &$site_state;
			} else {
				$state = &$user_state;
			}

			// A permanently-dismissed notification is excluded from all future results.
			if ( ! empty( $state[ $id ]['dismissed'] ) ) {
				unset( $state );
				continue;
			}

			if ( ! $evaluator->should_show() ) {
				unset( $state );
				continue;
			}

			// Record the trigger time the first time the condition is met, and never overwrite it.
			if ( ! isset( $state[ $id ]['triggered_at'] ) ) {
				$state[ $id ]['triggered_at'] = time();

				if ( $site_scoped ) {
					$site_state_changed = true;
				} else {
					$user_state_changed = true;
				}
			}

			$notifications[] = [
				'id'           => $id,
				'triggered_at' => $state[ $id ]['triggered_at'],
			];

			unset( $state );
		}

		if ( $user_state_changed ) {
			$this->save_user_state( $user_state );
		}

		if ( $site_state_changed ) {
			$this->save_site_state( $site_state );
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
		return null !== $this->find_evaluator( $id );
	}

	/**
	 * Permanently dismiss a notification so it is excluded from all future results.
	 *
	 * @param string $id The notification ID to dismiss.
	 *
	 * @return void
	 */
	public function dismiss( string $id ): void {
		if ( ! $this->can_manage() ) {
			return;
		}

		$evaluator = $this->find_evaluator( $id );
		if ( null === $evaluator ) {
			return;
		}

		if ( $evaluator instanceof SiteScopedNotificationEvaluatorInterface ) {
			$state                     = $this->get_site_state();
			$state[ $id ]['dismissed'] = true;
			$this->save_site_state( $state );

			return;
		}

		$state                     = $this->get_user_state();
		$state[ $id ]['dismissed'] = true;
		$this->save_user_state( $state );
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
	 * Find a registered evaluator by notification ID.
	 *
	 * @param string $id
	 *
	 * @return NotificationEvaluatorInterface|null
	 */
	protected function find_evaluator( string $id ): ?NotificationEvaluatorInterface {
		foreach ( $this->get_evaluators() as $evaluator ) {
			if ( $evaluator->get_id() === $id ) {
				return $evaluator;
			}
		}

		return null;
	}

	/**
	 * Read the persisted per-user notifications state.
	 *
	 * @return array
	 */
	protected function get_user_state(): array {
		$state = $this->wp->get_user_meta( $this->wp->get_current_user_id(), self::STATE_META_KEY );

		if ( ! is_array( $state ) || empty( $state ) ) {
			return [ self::VERSION_KEY => self::SCHEMA_VERSION ];
		}

		return $state;
	}

	/**
	 * Persist the per-user notifications state.
	 *
	 * @param array $state The state to persist.
	 *
	 * @return void
	 */
	protected function save_user_state( array $state ): void {
		$state[ self::VERSION_KEY ] = self::SCHEMA_VERSION;

		$this->wp->update_user_meta( $this->wp->get_current_user_id(), self::STATE_META_KEY, $state );
	}

	/**
	 * Read the persisted site-wide notifications state.
	 *
	 * @return array
	 */
	protected function get_site_state(): array {
		$state = $this->options->get( OptionsInterface::NOTIFICATIONS_SITE_STATE, [] );

		if ( ! is_array( $state ) || empty( $state ) ) {
			return [ self::VERSION_KEY => self::SCHEMA_VERSION ];
		}

		return $state;
	}

	/**
	 * Persist the site-wide notifications state.
	 *
	 * @param array $state The state to persist.
	 *
	 * @return void
	 */
	protected function save_site_state( array $state ): void {
		$state[ self::VERSION_KEY ] = self::SCHEMA_VERSION;

		$this->options->update( OptionsInterface::NOTIFICATIONS_SITE_STATE, $state );
	}
}
