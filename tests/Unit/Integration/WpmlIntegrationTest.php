<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WpmlIntegration;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class WpmlIntegrationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Integration
 */
class WpmlIntegrationTest extends UnitTest {

	/** @var WpmlIntegration */
	protected $integration;

	public function setUp(): void {
		parent::setUp();

		$this->integration = new WpmlIntegration();
	}

	public function tearDown(): void {
		remove_all_filters( 'wpml_active_languages' );

		parent::tearDown();
	}

	public function test_is_active_returns_false_when_wpml_not_loaded(): void {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->markTestSkipped( 'ICL_SITEPRESS_VERSION is already defined.' );
		}

		$this->assertFalse( $this->integration->is_active() );
	}

	public function test_is_active_returns_true_when_wpml_is_loaded(): void {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '4.9.2' );
		}

		$this->assertTrue( $this->integration->is_active() );
	}

	public function test_get_languages_returns_empty_when_wpml_not_active(): void {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$this->markTestSkipped( 'ICL_SITEPRESS_VERSION is already defined.' );
		}

		$this->assertSame( [], $this->integration->get_languages() );
	}

	public function test_get_languages_returns_formatted_languages(): void {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '4.9.2' );
		}

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
			$this->integration->get_languages()
		);
	}

	public function test_get_languages_uses_native_name_when_translated_name_missing(): void {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '4.9.2' );
		}

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
			$this->integration->get_languages()
		);
	}

	public function test_get_languages_uses_display_name_when_translated_and_native_missing(): void {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '4.9.2' );
		}

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
			$this->integration->get_languages()
		);
	}

	public function test_get_languages_returns_empty_when_filter_returns_non_array(): void {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '4.9.2' );
		}

		add_filter(
			'wpml_active_languages',
			function () {
				return null;
			}
		);

		$this->assertSame( [], $this->integration->get_languages() );
	}
}
