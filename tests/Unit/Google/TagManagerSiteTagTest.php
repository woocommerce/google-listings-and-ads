<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerSiteTagTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class TagManagerSiteTagTest extends UnitTest {

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var TagManagerSiteTag $tag */
	protected $tag;

	protected const TEST_CONTAINER_PUBLIC_ID = 'GTM-ABCDEF';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->tag        = new TagManagerSiteTag( $this->connection );
	}

	public function test_register_injects_script_when_container_connected() {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'container_id'        => '98765432',
				'container_public_id' => self::TEST_CONTAINER_PUBLIC_ID,
			]
		);

		$this->tag->register();

		$this->assertStringContainsString( self::TEST_CONTAINER_PUBLIC_ID, $this->get_wp_head() );
	}

	public function test_register_injects_noscript_fallback_when_container_connected() {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'container_id'        => '98765432',
				'container_public_id' => self::TEST_CONTAINER_PUBLIC_ID,
			]
		);

		$this->tag->register();

		$this->assertStringContainsString(
			'https://www.googletagmanager.com/ns.html?id=' . self::TEST_CONTAINER_PUBLIC_ID,
			$this->get_wp_body_open()
		);
	}

	public function test_register_does_not_inject_when_no_container_connected() {
		$this->connection->method( 'get_connection_data' )->willReturn( [] );

		$this->tag->register();

		$this->assertStringNotContainsString( 'googletagmanager.com', $this->get_wp_head() );
		$this->assertStringNotContainsString( 'googletagmanager.com', $this->get_wp_body_open() );
	}

	public function test_has_injection_failed_false_when_no_account_connected_at_all() {
		$this->connection->method( 'get_connection_data' )->willReturn( [] );

		$this->assertFalse( $this->tag->has_injection_failed() );
	}

	public function test_has_injection_failed_false_when_container_public_id_present() {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'container_id'        => '98765432',
				'container_public_id' => self::TEST_CONTAINER_PUBLIC_ID,
			]
		);

		$this->assertFalse( $this->tag->has_injection_failed() );
	}

	public function test_has_injection_failed_true_when_container_id_present_but_public_id_missing() {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'container_id'        => '98765432',
				'container_public_id' => '',
			]
		);

		$this->assertTrue( $this->tag->has_injection_failed() );
	}

	protected function get_wp_head(): string {
		ob_start();
		do_action( 'wp_head' );
		return ob_get_clean();
	}

	protected function get_wp_body_open(): string {
		ob_start();
		do_action( 'wp_body_open' );
		return ob_get_clean();
	}
}
