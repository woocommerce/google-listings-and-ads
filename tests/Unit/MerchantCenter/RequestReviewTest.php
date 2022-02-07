<?php

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\RequestReview;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test suit for RequestReview Model
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 * @group RequestReview
 * @property MockObject|OptionsInterface $options
 * @property RequestReview $request_review
 */
class RequestReviewTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->options        = $this->createMock( OptionsInterface::class );
		$this->request_review = new RequestReview();
		$this->request_review->set_options_object( $this->options );
	}

	public function test_get_next_attempt() {
		$this->options->method( 'get' )->willReturn( time() );

		$this->assertEquals(
			time(),
			$this->request_review->get_next_attempt()
		);
	}

	public function test_is_allowed() {
		$this->options->method( 'get' )->willReturn( time() );

		$this->assertEquals(
			true,
			$this->request_review->is_allowed()
		);
	}


	public function test_is_not_allowed() {
		$this->options->method( 'get' )->willReturn( strtotime( '+ 7 days', time() ) );

		$this->assertEquals(
			false,
			$this->request_review->is_allowed()
		);
	}

}
