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
