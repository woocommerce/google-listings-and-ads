<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework;

use WP_UnitTestCase;

/**
 * Class UnitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework
 */
abstract class UnitTest extends WP_UnitTestCase {
	/**
	 * Simulate a request for the given URL.
	 *
	 * WP_UnitTestCase_Base::go_to() unsets a batch of request globals, `$pagenow` among them,
	 * and never restores it. This suite runs with `backupGlobals="false"`, so once any test
	 * calls go_to() the global stays unset for every test that runs afterwards in the same
	 * process, and code reading `global $pagenow` then sees null instead of a string.
	 *
	 * Restore it after the parent has rebuilt the request state so the leak is contained to
	 * the test that opted into it.
	 *
	 * @param string $url The URL for the request.
	 */
	public function go_to( $url ) {
		$pagenow = $GLOBALS['pagenow'] ?? null;

		parent::go_to( $url );

		if ( null !== $pagenow ) {
			$GLOBALS['pagenow'] = $pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Create a new user in a given role and set it as the current user.
	 *
	 * @param string $role The role for the user to be created.
	 *
	 * @return int The id of the user created.
	 */
	public function login_as_role( string $role ): int {
		$user_id = $this->factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Create a new administrator user and set it as the current user.
	 *
	 * @return int The id of the user created.
	 */
	public function login_as_administrator(): int {
		return $this->login_as_role( 'administrator' );
	}
}
