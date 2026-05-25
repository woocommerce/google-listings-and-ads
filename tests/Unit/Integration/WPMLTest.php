<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPMLTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration
 */
class WPMLTest extends UnitTest {

	public function tearDown(): void {
		remove_all_filters( 'wpml_active_languages' );

		parent::tearDown();
	}

	public function test_get_languages_returns_empty_when_not_active(): void {
		$integration = $this->create_integration( false );

		$this->assertSame( [], $integration->get_languages() );
	}

	public function test_get_languages_returns_formatted_languages(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'en' => [
						'code'            => 'en',
						'translated_name' => 'English',
						'native_name'     => 'English',
						'display_name'    => 'English',
					],
					'de' => [
						'code'            => 'de',
						'translated_name' => 'German',
						'native_name'     => 'Deutsch',
						'display_name'    => 'German',
					],
				];
			}
		);

		$this->assertSame(
			[
				[
					'code'  => 'en',
					'label' => 'English',
				],
				[
					'code'  => 'de',
					'label' => 'German',
				],
			],
			$integration->get_languages()
		);
	}

	public function test_get_languages_uses_native_name_when_translated_name_missing(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'de' => [
						'code'         => 'de',
						'native_name'  => 'Deutsch',
						'display_name' => 'German',
					],
				];
			}
		);

		$this->assertSame(
			[
				[
					'code'  => 'de',
					'label' => 'Deutsch',
				],
			],
			$integration->get_languages()
		);
	}

	public function test_get_languages_uses_display_name_when_translated_and_native_missing(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return [
					'fr' => [
						'code'         => 'fr',
						'display_name' => 'French',
					],
				];
			}
		);

		$this->assertSame(
			[
				[
					'code'  => 'fr',
					'label' => 'French',
				],
			],
			$integration->get_languages()
		);
	}

	public function test_get_languages_returns_empty_when_filter_returns_non_array(): void {
		$integration = $this->create_integration( true );

		add_filter(
			'wpml_active_languages',
			function () {
				return null;
			}
		);

		$this->assertSame( [], $integration->get_languages() );
	}

	/**
	 * @param bool $is_active
	 *
	 * @return WPML&MockObject
	 */
	private function create_integration( bool $is_active ): WPML {
		$integration = $this->createPartialMock( WPML::class, [ 'is_active' ] );
		$integration->method( 'is_active' )->willReturn( $is_active );

		return $integration;
	}
}
