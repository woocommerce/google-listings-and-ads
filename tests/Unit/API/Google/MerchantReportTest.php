<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Date as GoogleDate;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductView;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ReportRow;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\SearchRequest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\SearchResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Reports;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ShoppingContentDateTrait;
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantReportTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class MerchantReportTest extends UnitTest {

	use ShoppingContentDateTrait;

	/** @var MockObject|ShoppingContent $shopping_client */
	protected $shopping_client;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MerchantReport $merchant_report */
	protected $merchant_report;


	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->shopping_client          = $this->createMock( ShoppingContent::class );
		$this->product_helper           = $this->createMock( ProductHelper::class );
		$this->shopping_client->reports = $this->createMock( Reports::class );

		$this->options         = $this->createMock( OptionsInterface::class );
		$this->merchant_report = new MerchantReport( $this->shopping_client, $this->product_helper );
		$this->merchant_report->set_options_object( $this->options );
	}

	/**
	 * Creates a product view with the given id, status and expiration date.
	 *
	 * @param string        $mc_id The MC center id.
	 * @param string        $status The status of the product.
	 * @param DateTime|null $expiration_date The expiration date of the product.
	 *
	 * @return ProductView
	 */
	protected function create_product_view_product_status( string $mc_id, string $status = 'ELIGIBLE', $expiration_date = null ): ProductView {
		$expiration_date = $expiration_date ?? new DateTime( 'tomorrow', wp_timezone() );
		$product_view    = new ProductView();
		$google_date     = new GoogleDate();
		$google_date->setYear( $expiration_date->format( 'Y' ) );
		$google_date->setMonth( $expiration_date->format( 'm' ) );
		$google_date->setDay( $expiration_date->format( 'd' ) );
		$product_view->setExpirationDate( $google_date );
		$product_view->setAggregatedDestinationStatus( $status );
		$product_view->setId( $mc_id );
		return $product_view;
	}

	public function test_get_product_view_report() {
		$test_merchant_id = 432;
		$wc_product_id_1  = 882;
		$wc_product_id_2  = 883;
		$wc_product_id_3  = 884;

		add_filter(
			'woocommerce_gla_product_view_report_page_size',
			function () {
				return 500;
			}
		);

		$this->options->method( 'get_merchant_id' )->willReturn( $test_merchant_id );

		$this->product_helper->method( 'get_wc_product_id' )->will(
			$this->returnCallback(
				function ( $mc_id ) use ( $wc_product_id_1, $wc_product_id_2, $wc_product_id_3 ) {
					if ( $mc_id === 'online:en:ES:gla_' . $wc_product_id_1 ) {
							return $wc_product_id_1;
					}

					if ( $mc_id === 'online:en:ES:gla_' . $wc_product_id_2 ) {
						return $wc_product_id_2;
					}

					if ( $mc_id === 'online:en:ES:gla_' . $wc_product_id_3 ) {
						return $wc_product_id_3;
					}

					return 0;
				}
			)
		);

		$product_view_1 = $this->create_product_view_product_status( 'online:en:ES:gla_' . $wc_product_id_1 );
		$product_view_2 = $this->create_product_view_product_status( 'online:en:ES:gla_' . $wc_product_id_2, 'NOT_ELIGIBLE_OR_DISAPPROVED' );
		$product_view_3 = $this->create_product_view_product_status( 'online:en:ES:external' . $wc_product_id_3 );

		$report_row_1 = new ReportRow();
		$report_row_1->setProductView( $product_view_1 );

		$report_row_2 = new ReportRow();
		$report_row_2->setProductView( $product_view_2 );

		$report_row_3 = new ReportRow();
		$report_row_3->setProductView( $product_view_3 );

		$response = $this->createMock( SearchResponse::class );
		$response->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [ $report_row_1, $report_row_2, $report_row_3 ] );

		$response->expects( $this->once() )
			->method( 'getNextPageToken' )
			->willReturn( null );

		$search_request = new SearchRequest();
		$search_request->setQuery(
			'SELECT product_view.id,product_view.expiration_date,product_view.aggregated_destination_status FROM ProductView'
		);

		$search_request->setPageSize( 500 );

		$this->shopping_client->reports->expects( $this->once() )
			->method( 'search' )
			->with( $test_merchant_id, $search_request )
			->willReturn( $response );

		$this->assertEquals(
			[
				'statuses'        => [
					$wc_product_id_1 => [
						'product_id'      => $wc_product_id_1,
						'status'          => MCStatus::APPROVED,
						'expiration_date' => $this->convert_shopping_content_date( $product_view_1->getExpirationDate() ),
					],
					$wc_product_id_2 => [
						'product_id'      => $wc_product_id_2,
						'status'          => MCStatus::DISAPPROVED,
						'expiration_date' => $this->convert_shopping_content_date( $product_view_2->getExpirationDate() ),
					],
				],
				'next_page_token' => null,
			],
			$this->merchant_report->get_product_view_report()
		);
	}

	public function test_get_product_view_report_with_exception() {
		$this->options->method( 'get_merchant_id' )->willReturn( 432 );

		$this->shopping_client->reports->expects( $this->once() )
			->method( 'search' )
			->will(
				$this->throwException( new GoogleException( 'Test exception' ) )
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to retrieve Product View Report.' );
		$this->merchant_report->get_product_view_report();
	}
}
