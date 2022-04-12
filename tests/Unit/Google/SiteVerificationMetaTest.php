<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\SiteVerificationMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerificationMetaTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property SiteVerificationMeta        $meta
 */
class SiteVerificationMetaTest extends UnitTest {

	protected const TEST_META_TAG = '<meta name="google-site-verification" content="abc" />';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );

		$this->meta = new SiteVerificationMeta();
		$this->meta->set_options_object( $this->options );
		$this->meta->register();
	}

	public function test_meta_tag_in_header() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SITE_VERIFICATION )
			->willReturn( [ 'meta_tag' => self::TEST_META_TAG ] );

		$this->assertStringContainsString(
			self::TEST_META_TAG,
			$this->get_wp_head()
		);
	}

	public function test_meta_tag_not_in_header() {
		$this->assertStringNotContainsString(
			'meta name="google-site-verification"',
			$this->get_wp_head()
		);
	}

	protected function get_wp_head() {
		ob_start();
		do_action( 'wp_head' );
		return ob_get_clean();
	}

}
