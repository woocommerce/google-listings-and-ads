<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignLabel;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Google\ApiCore\ApiException;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignLabelTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsCampaignLabelTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var AdsCampaignLabel $campaign_label */
	protected $campaign_label;

	protected const TEST_CAMPAIGN_ID = 1234567890;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );

		$this->campaign_label = new AdsCampaignLabel( $this->client );
		$this->campaign_label->set_options_object( $this->options );

		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );
	}

	public function test_assign_new_label() {
		$label_id = -1;
		$this->generate_mock_label_query_with_no_existing_labels();
		$this->generate_campaign_label_mutate_mock( self::TEST_CAMPAIGN_ID, $label_id );
		$this->campaign_label->assign_label_to_campaign_by_label_name( self::TEST_CAMPAIGN_ID, 'new-label' );
	}

	public function test_assign_existing_label() {
		$label_id = 1234567890;
		$name     = 'wc-gla';
		$this->generate_label_query_mock(
			[
				[
					'id'   => $label_id,
					'name' => $name,
				],
			]
		);
		$this->generate_campaign_label_mutate_mock( self::TEST_CAMPAIGN_ID, $label_id );
		$this->campaign_label->assign_label_to_campaign_by_label_name( self::TEST_CAMPAIGN_ID, $name );
	}

	public function test_query_labels_exception() {
		$name = 'wc-gla';
		$this->generate_ads_query_mock_exception( new ApiException( 'No labels', 14, 'UNAVAILABLE' ) );

		try {
			$this->campaign_label->assign_label_to_campaign_by_label_name( self::TEST_CAMPAIGN_ID, $name );
		} catch ( ApiException $e ) {

			$this->assertEquals( 14, $e->getCode() );
			$this->assertEquals( 'No labels', $e->getMessage() );
		}
	}
}
