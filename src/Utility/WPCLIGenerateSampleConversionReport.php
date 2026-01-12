<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPCLIGenerateSampleConversionReport
 *
 * WP-CLI command to generate sample CSV conversion data for testing.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Utility
 */
class WPCLIGenerateSampleConversionReport implements Service, Registerable, Conditional {

	/**
	 * @var CsvExportWriter
	 */
	protected $writer;

	/**
	 * Product name pool for varied, realistic product names.
	 *
	 * @var array
	 */
	protected $product_names = [
		'Organic Cotton Hoodie',
		'Wireless Charging Pad',
		'Stainless Steel Water Bottle',
		'Bluetooth Noise-Cancelling Headphones',
		'Leather Crossbody Bag',
		'Bamboo Cutting Board Set',
		'Smart Fitness Tracker',
		'Ceramic Pour-Over Coffee Maker',
		'Merino Wool Beanie',
		'Portable Power Bank 20000mAh',
		'Cast Iron Skillet 12-inch',
		'Yoga Mat with Carrying Strap',
		'Insulated Travel Mug',
		'Wireless Mechanical Keyboard',
		'Linen Throw Blanket',
		'Minimalist Wall Clock',
		'Ergonomic Office Chair',
		'LED Desk Lamp',
		'Bamboo Phone Stand',
		'Cord Organizer Cable Management',
		'Memory Foam Pillow',
		'Adjustable Laptop Stand',
		'USB-C Hub Multiport Adapter',
		'Glass Food Storage Containers',
		'Reusable Silicone Food Bags',
		'Stainless Steel Mixing Bowls',
		'Non-Stick Baking Sheet',
		'Digital Kitchen Scale',
		'Immersion Blender',
		'French Press Coffee Maker',
		'Herb Garden Starter Kit',
		'Indoor Plant Pot Set',
		'Wall Mounted Plant Hanger',
		'Macrame Wall Hanging',
		'Canvas Art Print',
		'Faux Leather Journal',
		'Gel Ink Pen Set',
		'Desk Organizer Tray',
		'Acrylic Monitor Stand',
		'Laptop Sleeve Case',
		'Phone Grip Stand',
		'Car Phone Mount',
		'Wireless Car Charger',
		'Dash Cam Front and Rear',
		'Car Seat Cushion',
		'Steering Wheel Cover',
		'Car Trunk Organizer',
		'Portable Jump Starter',
		'Tire Pressure Monitor',
		'Car Air Freshener Diffuser',
	];

	/**
	 * Currency codes with weights for realistic distribution.
	 *
	 * @var array
	 */
	protected $currencies = [
		'USD' => 70,
		'GBP' => 15,
		'EUR' => 10,
		'CAD' => 5,
	];

	/**
	 * Country codes mapped to currencies.
	 *
	 * @var array
	 */
	protected $country_currency_map = [
		'USD' => 'US',
		'GBP' => 'GB',
		'EUR' => 'DE',
		'CAD' => 'CA',
	];

	/**
	 * Reversal reasons for refunds.
	 *
	 * @var array
	 */
	protected $reversal_reasons = [
		'Changed mind',
		'Defective product',
		'Wrong size',
		'Not as described',
		'Damaged in shipping',
		'Late delivery',
		'Customer request',
		'Duplicate order',
		'Price adjustment',
		'Returned unused',
	];

	/**
	 * Coupon codes.
	 *
	 * @var array
	 */
	protected $coupon_codes = [
		'SAVE10',
		'WELCOME20',
		'BLACKFRIDAY',
		'CYBERMONDAY',
		'SPRING15',
		'SUMMER25',
		'FREESHIP',
		'NEWCUSTOMER',
		'LOYALTY30',
		'BULK5',
	];

	/**
	 * Constructor.
	 *
	 * @param CsvExportWriter $writer
	 */
	public function __construct( CsvExportWriter $writer ) {
		$this->writer = $writer;
	}

	/**
	 * Register service and initialize hooks.
	 */
	public function register(): void {
		WP_CLI::add_hook( 'after_wp_load', [ $this, 'register_commands' ] );
	}

	/**
	 * Register the commands.
	 */
	public function register_commands(): void {
		WP_CLI::add_command(
			'gla generate-sample-conversion-csv',
			[ $this, 'generate_sample_csv' ],
			[
				'shortdesc' => 'Generate sample conversion CSV data for S2S testing.',
				'synopsis'  => [
					[
						'type'        => 'assoc',
						'name'        => 'rows',
						'description' => 'Number of CSV rows to generate.',
						'optional'    => false,
					],
					[
						'type'        => 'assoc',
						'name'        => 'output',
						'description' => 'Output file path (defaults to uploads/gla-exports/).',
						'optional'    => true,
					],
					[
						'type'        => 'assoc',
						'name'        => 'refund-rate',
						'description' => 'Percentage of refunds (default: 10).',
						'optional'    => true,
					],
					[
						'type'        => 'assoc',
						'name'        => 'date-range',
						'description' => 'Days back to randomise dates (default: 30).',
						'optional'    => true,
					],
				],
			]
		);
	}

	/**
	 * Generate sample conversion CSV.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function generate_sample_csv( array $args, array $assoc_args ): void {
		$rows        = isset( $assoc_args['rows'] ) ? absint( $assoc_args['rows'] ) : 0;
		$output      = $assoc_args['output'] ?? null;
		$refund_rate = isset( $assoc_args['refund-rate'] ) ? absint( $assoc_args['refund-rate'] ) : 10;
		$date_range  = isset( $assoc_args['date-range'] ) ? absint( $assoc_args['date-range'] ) : 30;

		if ( $rows <= 0 ) {
			WP_CLI::error( '--rows must be a positive integer.' );
		}

		if ( $refund_rate < 0 || $refund_rate > 100 ) {
			WP_CLI::error( '--refund-rate must be between 0 and 100.' );
		}

		$filename  = 'sample-conversion-report-' . gmdate( 'Y-m-d-His' );
		$file_path = $this->writer->create_file( $filename );

		if ( $output ) {
			// Move to specified output path.
			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			$wp_filesystem->move( $file_path, $output, true );
			$file_path = $output;
		}

		WP_CLI::log( sprintf( 'Generating %d rows of sample conversion data...', $rows ) );

		$progress   = WP_CLI\Utils\make_progress_bar( 'Generating CSV', $rows );
		$start_time = microtime( true );

		$gmc_merchant_id = get_option( 'gla_merchant_id', '5690838257' );

		for ( $i = 1; $i <= $rows; $i++ ) {
			$is_refund = ( wp_rand( 1, 100 ) <= $refund_rate );
			$row       = $this->generate_row( $i, $is_refund, $gmc_merchant_id, $date_range );
			$this->writer->append_row( $file_path, $row );
			$progress->tick();
		}

		$progress->finish();
		$total_time = microtime( true ) - $start_time;
		$file_size  = $this->writer->get_file_size( $file_path );

		WP_CLI::success(
			sprintf(
				'Generated %d rows in %.2f seconds. File: %s (%s)',
				$rows,
				$total_time,
				$file_path,
				size_format( $file_size, 2 )
			)
		);
	}

	/**
	 * Generate a single CSV row.
	 *
	 * @param int    $row_number Row number.
	 * @param bool   $is_refund Whether this is a refund row.
	 * @param string $gmc_merchant_id GMC merchant ID.
	 * @param int    $date_range Days back to randomise dates.
	 * @return array
	 */
	protected function generate_row( int $row_number, bool $is_refund, string $gmc_merchant_id, int $date_range ): array {
		$currency = $this->get_weighted_random_currency();
		$country  = $this->country_currency_map[ $currency ];

		// Random transaction date within date range.
		$days_ago         = wp_rand( 0, $date_range );
		$transaction_date = new \DateTime( "-{$days_ago} days" );
		$transaction_date->setTime( wp_rand( 0, 23 ), wp_rand( 0, 59 ), wp_rand( 0, 59 ) );

		$refund_date = '';
		if ( $is_refund ) {
			$refund_days_after = wp_rand( 1, 14 );
			$refund_date_obj   = clone $transaction_date;
			$refund_date_obj->modify( "+{$refund_days_after} days" );
			$refund_date = $refund_date_obj->format( 'c' );
		}

		$quantity                   = wp_rand( 1, 5 );
		$item_unit_price            = wp_rand( 500, 50000 ) / 100; // $5.00 to $500.00
		$discount_percent           = wp_rand( 0, 30 );
		$item_unit_discounted_price = $item_unit_price * ( 1 - ( $discount_percent / 100 ) );

		$item_price            = $item_unit_price * $quantity;
		$item_discounted_price = $item_unit_discounted_price * $quantity;

		$has_coupon = wp_rand( 1, 100 ) <= 20; // 20% chance
		$coupons    = $has_coupon ? $this->coupon_codes[ array_rand( $this->coupon_codes ) ] : '';

		$transaction_tax      = $item_discounted_price * ( wp_rand( 5, 10 ) / 100 );
		$transaction_shipping = wp_rand( 0, 1500 ) / 100; // $0 to $15.00
		$transaction_total    = $item_discounted_price + $transaction_tax + $transaction_shipping;

		$attribution_id   = 'YT3-' . bin2hex( random_bytes( 32 ) );
		$landing_page_url = sprintf(
			'https://example.store/product?utm_source=youtube&utm_content=%s',
			$attribution_id
		);

		$transaction_id = 'ORD-' . strtoupper( substr( md5( (string) $row_number ), 0, 8 ) );
		$item_id        = wp_rand( 1000, 9999 );
		$item_name      = $this->product_names[ array_rand( $this->product_names ) ];

		return [
			'transaction_type'           => $is_refund ? 'refund' : 'purchase',
			'gmc_merchant_id'            => $gmc_merchant_id,
			'transaction_id'             => $transaction_id,
			'item_id'                    => $item_id,
			'item_name'                  => $item_name,
			'transaction_date'           => $transaction_date->format( 'c' ),
			'refund_date'                => $refund_date,
			'quantity'                   => $quantity,
			'item_unit_price'            => number_format( $item_unit_price, 2, '.', '' ),
			'item_unit_discounted_price' => number_format( $item_unit_discounted_price, 2, '.', '' ),
			'item_price'                 => number_format( $item_price, 2, '.', '' ),
			'item_discounted_price'      => number_format( $item_discounted_price, 2, '.', '' ),
			'coupons'                    => $coupons,
			'transaction_tax'            => number_format( $transaction_tax, 2, '.', '' ),
			'transaction_shipping'       => number_format( $transaction_shipping, 2, '.', '' ),
			'transaction_total'          => number_format( $transaction_total, 2, '.', '' ),
			'currency_code'              => $currency,
			'landing_page_url'           => $landing_page_url,
			'attribution_id'             => $attribution_id,
			'country_code'               => $country,
			'subaccount_id'              => '',
			'reversal_reason'            => $is_refund ? $this->reversal_reasons[ array_rand( $this->reversal_reasons ) ] : '',
		];
	}

	/**
	 * Get a weighted random currency.
	 *
	 * @return string Currency code.
	 */
	protected function get_weighted_random_currency(): string {
		$total_weight = array_sum( $this->currencies );
		$random       = wp_rand( 1, $total_weight );
		$cumulative   = 0;

		foreach ( $this->currencies as $currency => $weight ) {
			$cumulative += $weight;
			if ( $random <= $cumulative ) {
				return $currency;
			}
		}

		return 'USD'; // Fallback.
	}

	/**
	 * Check if this Service is needed.
	 *
	 * @return bool
	 */
	public static function is_needed(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}
}
