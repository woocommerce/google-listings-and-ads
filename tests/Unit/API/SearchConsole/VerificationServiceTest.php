<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SitesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\VerificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class VerificationServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class VerificationServiceTest extends UnitTest {

	/** @var MockObject|SiteVerification $site_verification */
	protected $site_verification;

	/** @var VerificationService $service */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->site_verification = $this->createMock( SiteVerification::class );
		$this->service            = new VerificationService( $this->site_verification );
	}

	public function test_resolve_verification_returns_verified_when_property_permission_is_already_verified() {
		$this->site_verification->expects( $this->never() )->method( 'is_verified' );

		$result = $this->service->resolve_verification( [ 'siteUrl' => 'https://example.com/', 'permissionLevel' => 'siteOwner' ] );

		$this->assertEquals( SiteVerification::VERIFICATION_STATUS_VERIFIED, $result );
	}

	public function test_resolve_verification_falls_back_to_same_account_inheritance_when_property_is_unverified() {
		$this->site_verification->expects( $this->once() )
			->method( 'is_verified' )
			->willReturn( true );

		$result = $this->service->resolve_verification(
			[ 'siteUrl' => 'https://example.com/', 'permissionLevel' => SitesService::PERMISSION_UNVERIFIED ]
		);

		$this->assertEquals( SiteVerification::VERIFICATION_STATUS_VERIFIED, $result );
	}

	public function test_resolve_verification_returns_unverified_when_neither_signal_confirms_ownership() {
		$this->site_verification->method( 'is_verified' )->willReturn( false );

		$result = $this->service->resolve_verification(
			[ 'siteUrl' => 'https://example.com/', 'permissionLevel' => SitesService::PERMISSION_UNVERIFIED ]
		);

		$this->assertEquals( SiteVerification::VERIFICATION_STATUS_UNVERIFIED, $result );
	}

	public function test_resolve_verification_treats_missing_permission_level_as_unverified() {
		$this->site_verification->method( 'is_verified' )->willReturn( false );

		$result = $this->service->resolve_verification( [ 'siteUrl' => 'https://example.com/' ] );

		$this->assertEquals( SiteVerification::VERIFICATION_STATUS_UNVERIFIED, $result );
	}

	public function test_is_owner_verified_true_for_non_unverified_permission_level() {
		$this->assertTrue( $this->service->is_owner_verified( [ 'permissionLevel' => 'siteFullUser' ] ) );
	}

	public function test_is_owner_verified_false_for_unverified_permission_level() {
		$this->assertFalse( $this->service->is_owner_verified( [ 'permissionLevel' => SitesService::PERMISSION_UNVERIFIED ] ) );
	}

	public function test_verify_delegates_to_site_verification() {
		$this->site_verification->expects( $this->once() )
			->method( 'verify_site' )
			->with( 'https://example.com/' );

		$this->service->verify( 'https://example.com/' );
	}
}
