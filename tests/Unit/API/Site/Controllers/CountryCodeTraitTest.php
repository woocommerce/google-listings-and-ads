<?php
// This namespace must be identical to CountryCodeTrait for replacing and
// mocking the global function rest_validate_request_arg.
namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers {
	use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\CountryCodeTraitTest;

	/**
	 * Replace and mock the global function rest_validate_request_arg.
	 */
	function rest_validate_request_arg() {
		$mocked_callback = CountryCodeTraitTest::$rest_validate_request_arg_callback;

		if ( is_null( $mocked_callback ) ) {
			return \rest_validate_request_arg( ...func_get_args() );
		}

		return $mocked_callback( ...func_get_args() );
	}
};

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers {
	use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
	use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
	use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
	use PHPUnit\Framework\MockObject\MockObject;
	use PHPUnit\Framework\TestCase;
	use Exception;
	use WP_Error;
	use WP_REST_Request as Request;

	/**
	 * Class CountryCodeTraitTest
	 *
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
	 * @phpcs:disable Squiz.Classes.ClassDeclaration.SpaceBeforeKeyword
	 * @phpcs:disable Squiz.Classes.ClassDeclaration.SpaceBeforeCloseBrace
	 */
	class CountryCodeTraitTest extends TestCase {

		/** @var callable $rest_validate_request_arg_callback */
		public static $rest_validate_request_arg_callback = null;

		/** @var MockObject|ISO3166DataProvider $iso_provider */
		protected $iso_provider;

		/** @var MockObject|GoogleHelper $google_helper */
		protected $google_helper;

		/** @var bool $country_supported */
		protected $country_supported;

		/**
		 * Runs before each test is executed.
		 */
		public function setUp(): void {
			parent::setUp();

			$this->iso_provider  = $this->createMock( ISO3166DataProvider::class );
			$this->google_helper = $this->createMock( GoogleHelper::class );

			// phpcs:ignore WordPress.Classes.ClassInstantiation.MissingParenthesis
			$this->trait = new class {
				use CountryCodeTrait {
					get_country_code_sanitize_callback as public;
					get_country_code_validate_callback as public;
					get_supported_country_code_validate_callback as public;
				}
			};

			$this->trait->set_iso3166_provider( $this->iso_provider );
			$this->trait->set_google_helper_object( $this->google_helper );

			$this->country_supported = true;

			$this->google_helper->method( 'is_country_supported' )
				->willReturnCallback(
					function () {
						return $this->country_supported;
					}
				);
		}

		/**
		 * Runs after each test is executed.
		 */
		public function tearDown(): void {
			self::$rest_validate_request_arg_callback = null;
		}

		/**
		 * Test all get_*_callback methods return a callable.
		 */
		public function test_all_get_callbacks_return_callable() {
			$this->assertIsCallable( $this->trait->get_country_code_sanitize_callback() );
			$this->assertIsCallable( $this->trait->get_country_code_validate_callback() );
			$this->assertIsCallable( $this->trait->get_supported_country_code_validate_callback() );
		}

		/**
		 * Test the get_country_code_validate_callback and get_supported_country_code_validate_callback
		 * methods will apply arguments to rest_validate_request_arg_callback and return early if invalid.
		 */
		public function test_all_get_validate_callbacks_with_rest_validate_request_arg() {
			$called_count = 0;

			self::$rest_validate_request_arg_callback = function () use ( &$called_count ) {
				// When it returns a value other than `true`, it means the validation did not pass,
				// and here we also make it return a called count.
				$called_count++;
				return [ $called_count, func_get_args() ];
			};

			$args = [ 'US', new Request(), 'country_code' ];

			$callback = $this->trait->get_country_code_validate_callback();
			$results  = $callback( ...$args );

			$this->assertEquals( [ 1, $args ], $results );

			$callback = $this->trait->get_supported_country_code_validate_callback();
			$results  = $callback( ...$args );

			$this->assertEquals( [ 2, $args ], $results );
		}

		/**
		 * Test a sanitized country code with a string.
		 */
		public function test_get_country_code_sanitize_callback_with_string() {
			$this->assertEquals( 'US', $this->trait->get_country_code_sanitize_callback()( 'us' ) );
		}

		/**
		 * Test sanitized country codes with an array.
		 */
		public function test_get_country_code_sanitize_callback_with_array() {
			$callback = $this->trait->get_country_code_sanitize_callback();
			$results  = $callback( [ 'us', 'gb', 'jp' ] );

			$this->assertEquals( [ 'US', 'GB', 'JP' ], $results );
		}

		/**
		 * Test a valid country code with a string.
		 */
		public function test_get_country_code_validate_callback_with_string() {
			$callback = $this->trait->get_country_code_validate_callback();
			$result   = $callback( 'US', new Request(), 'country_code' );

			$this->assertTrue( $result );
		}

		/**
		 * Test valid country codes with an array.
		 */
		public function test_get_country_code_validate_callback_with_array() {
			$callback = $this->trait->get_country_code_validate_callback();
			$result   = $callback( [ 'US', 'GB' ], new Request(), 'country_codes' );

			$this->assertTrue( $result );
		}

		/**
		 * Test valid country codes with unsupported countries.
		 *
		 * It determines as valid since this method only checks if the countries match ISO 3166
		 * but won't check if Google Merchant Center supports the countries.
		 */
		public function test_get_country_code_validate_callback_with_unsupported_country() {
			$this->country_supported = false;

			$callback = $this->trait->get_country_code_validate_callback();
			$result   = $callback( [ 'CN', 'KP' ], new Request(), 'country_codes' );

			$this->assertTrue( $result );
		}

		/**
		 * Test an invalid country code.
		 */
		public function test_get_country_code_validate_callback_with_invalid_country_code() {
			$this->iso_provider
				->method( 'alpha2' )
				->willThrowException( new Exception( 'Country is invalid' ) );

			$callback = $this->trait->get_country_code_validate_callback();
			$result   = $callback( [ 'United States' ], new Request(), 'country_codes' );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertEquals( 'gla_invalid_country', $result->get_error_code() );
			$this->assertEquals( 'Country is invalid', $result->get_error_message() );
		}

		/**
		 * Test a valid country code with a string.
		 */
		public function test_get_supported_country_code_validate_callback_with_string() {
			$callback = $this->trait->get_supported_country_code_validate_callback();
			$result   = $callback( 'US', new Request(), 'country_code' );

			$this->assertTrue( $result );
		}

		/**
		 * Test valid country codes with an array.
		 */
		public function test_get_supported_country_code_validate_callback_with_array() {
			$callback = $this->trait->get_supported_country_code_validate_callback();
			$result   = $callback( [ 'US', 'GB' ], new Request(), 'country_codes' );

			$this->assertTrue( $result );
		}

		/**
		 * Test unsupported country codes.
		 */
		public function test_get_supported_country_code_validate_callback_with_unsupported_country() {
			$this->country_supported = false;

			$callback = $this->trait->get_supported_country_code_validate_callback();
			$result   = $callback( [ 'CN', 'KP' ], new Request(), 'country_codes' );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertEquals( 'gla_invalid_country', $result->get_error_code() );
			$this->assertEquals( 'Country is not supported', $result->get_error_message() );
		}

		/**
		 * Test an invalid country code.
		 */
		public function test_get_supported_country_code_validate_callback_with_invalid_country_code() {
			$this->iso_provider
				->method( 'alpha2' )
				->willThrowException( new Exception( 'Country is invalid' ) );

			$callback = $this->trait->get_supported_country_code_validate_callback();
			$result   = $callback( [ 'United States' ], new Request(), 'country_codes' );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertEquals( 'gla_invalid_country', $result->get_error_code() );
			$this->assertEquals( 'Country is invalid', $result->get_error_message() );
		}
	}

};
