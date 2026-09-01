<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

use Automattic\WooCommerce\GoogleListingsAndAds\API\PermissionsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use WP_User;

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
class NotificationService implements ContainerAwareInterface, OptionsAwareInterface, Registerable, Service {

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
	 * Register hooks for notification state management.
	 */
	public function register(): void {
		add_action( 'wp_login', [ $this, 'clear_login_scoped_dismissals' ], 10, 2 );
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

			if ( $this->is_dismissed( $evaluator, $state[ $id ] ?? [] ) ) {
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
	 * Dismiss a notification according to the evaluator's snooze configuration.
	 *
	 * Snoozable notifications are hidden until their snooze expires; all others are
	 * dismissed permanently. State is persisted per site for site-scoped evaluators,
	 * otherwise per user.
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

		$site_scoped = $evaluator instanceof SiteScopedNotificationEvaluatorInterface;
		$state       = $site_scoped ? $this->get_site_state() : $this->get_user_state();
		$snooze      = $evaluator->get_snooze_duration();

		if ( is_int( $snooze ) && $snooze > 0 ) {
			$state[ $id ]['snoozed_until'] = time() + $snooze;
			unset( $state[ $id ]['dismissed'] );
		} else {
			$state[ $id ]['dismissed'] = true;
			unset( $state[ $id ]['snoozed_until'] );
		}

		if ( $site_scoped ) {
			$this->save_site_state( $state );
		} else {
			$this->save_user_state( $state );
		}
	}

	/**
	 * Clear login-scoped dismissals when a user logs in.
	 *
	 * Untyped and defaulted because some plugins/login flows fire `wp_login` with
	 * only the username (see Integration/JetpackWPCOM.php for the same
	 * accommodation); strict, required params here would fatal with an
	 * ArgumentCountError on that call shape.
	 *
	 * @param string       $user_login The user's login name.
	 * @param WP_User|null $user       The authenticated user.
	 *
	 * @return void
	 */
	public function clear_login_scoped_dismissals( $user_login = '', $user = null ): void {
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$state = $this->get_state_for_user( $user->ID );

		if ( empty( $state ) ) {
			return;
		}

		$state_changed = false;

		foreach ( $this->get_evaluators() as $evaluator ) {
			if ( NotificationSnoozeDurations::UNTIL_NEXT_LOGIN !== $evaluator->get_snooze_duration() ) {
				continue;
			}

			$id = $evaluator->get_id();

			if ( empty( $state[ $id ]['dismissed'] ) ) {
				continue;
			}

			unset( $state[ $id ]['dismissed'] );
			$state_changed = true;
		}

		if ( $state_changed ) {
			$this->save_state_for_user( $user->ID, $state );
		}
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
	 * @param string $id The notification ID.
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
	 * Whether a notification is currently dismissed for the given persisted state entry.
	 *
	 * @param NotificationEvaluatorInterface $evaluator The notification evaluator.
	 * @param array                          $entry     The persisted state entry for the notification.
	 *
	 * @return bool
	 */
	protected function is_dismissed( NotificationEvaluatorInterface $evaluator, array $entry ): bool {
		$snooze = $evaluator->get_snooze_duration();

		if ( is_int( $snooze ) && $snooze > 0 ) {
			return ! empty( $entry['snoozed_until'] ) && time() < (int) $entry['snoozed_until'];
		}

		return ! empty( $entry['dismissed'] );
	}

	/**
	 * Read the persisted notifications state for the current user.
	 *
	 * @return array
	 */
	protected function get_user_state(): array {
		return $this->get_state_for_user( $this->wp->get_current_user_id() );
	}

	/**
	 * Persist the notifications state for the current user.
	 *
	 * @param array $state The state to persist.
	 *
	 * @return void
	 */
	protected function save_user_state( array $state ): void {
		$this->save_state_for_user( $this->wp->get_current_user_id(), $state );
	}

	/**
	 * Read the persisted notifications state for a user.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array
	 */
	protected function get_state_for_user( int $user_id ): array {
		$state = $this->wp->get_user_meta( $user_id, self::STATE_META_KEY );

		if ( ! is_array( $state ) || empty( $state ) ) {
			return [ self::VERSION_KEY => self::SCHEMA_VERSION ];
		}

		return $state;
	}

	/**
	 * Persist the notifications state for a user.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $state   The state to persist.
	 *
	 * @return void
	 */
	protected function save_state_for_user( int $user_id, array $state ): void {
		$state[ self::VERSION_KEY ] = self::SCHEMA_VERSION;

		$this->wp->update_user_meta( $user_id, self::STATE_META_KEY, $state );
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
