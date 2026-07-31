<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\RequestException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Handler\MockHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\YouTube
 */
class ConnectionTest extends UnitTest {

	/** @var Container $container */
	protected $container;

	/** @var Connection $connection */
	protected $connection;

	/** @var string $test_upload_dir */
	protected $test_upload_dir;

	protected const CONNECT_SERVER_ROOT = 'https://wcs.example.com/';

	public function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->add( 'connect_server_root', self::CONNECT_SERVER_ROOT );

		// Create a test upload directory.
		$this->test_upload_dir = sys_get_temp_dir() . '/gla-test-uploads-' . uniqid();
		wp_mkdir_p( $this->test_upload_dir );

		// Initialize WP_Filesystem.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->connection = new Connection();
		$this->connection->set_container( $this->container );
	}

	public function tearDown(): void {
		// Clean up test files and directories.
		if ( is_dir( $this->test_upload_dir ) ) {
			global $wp_filesystem;
			if ( $wp_filesystem ) {
				$wp_filesystem->rmdir( $this->test_upload_dir, true );
			}
		}

		parent::tearDown();
	}

	public function test_get_shopping_url_returns_correct_url() {
		$method = new ReflectionMethod( Connection::class, 'get_shopping_url' );
		$method->setAccessible( true );

		$url = $method->invoke( $this->connection );

		$this->assertEquals(
			self::CONNECT_SERVER_ROOT . 'google/youtube/shopping/report/conversion/',
			$url
		);
	}

	public function test_upload_reports_returns_success_on_200() {
		$file_path = $this->create_test_file( 'test-upload.csv', 'col1,col2' . "\n" . 'val1,val2' );

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], 'OK' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertTrue( $results['success'] );
		$this->assertEquals( 1, $results['uploaded'] );
		$this->assertEquals( 0, $results['failed'] );
		$this->assertEmpty( $results['errors'] );
	}

	public function test_upload_reports_returns_success_on_201() {
		$file_path = $this->create_test_file( 'test-upload.csv', 'col1,col2' . "\n" . 'val1,val2' );

		$mock_handler = new MockHandler(
			[
				new Response( 201, [], 'Created' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertTrue( $results['success'] );
		$this->assertEquals( 1, $results['uploaded'] );
		$this->assertEquals( 0, $results['failed'] );
	}

	public function test_upload_reports_handles_multipart_files() {
		$file_path_1 = $this->create_test_file( 'youtube-merchant-conversion-report-2026-01-08.csv', 'data1' );
		$file_path_2 = $this->create_test_file( 'youtube-merchant-conversion-report-2026-01-08-1.csv', 'data2' );
		$file_path_3 = $this->create_test_file( 'youtube-merchant-conversion-report-2026-01-08-2.csv', 'data3' );

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], 'OK' ),
				new Response( 200, [], 'OK' ),
				new Response( 200, [], 'OK' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports(
			[ $file_path_1, $file_path_2, $file_path_3 ],
			'2026-01-08'
		);

		$this->assertTrue( $results['success'] );
		$this->assertEquals( 3, $results['uploaded'] );
		$this->assertEquals( 0, $results['failed'] );
	}

	public function test_upload_reports_returns_failure_on_http_error() {
		$file_path = $this->create_test_file( 'test-upload.csv', 'data' );

		$mock_handler = new MockHandler(
			[
				new Response( 500, [], 'Internal Server Error' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertFalse( $results['success'] );
		$this->assertEquals( 0, $results['uploaded'] );
		$this->assertEquals( 1, $results['failed'] );
		$this->assertNotEmpty( $results['errors'] );
		$this->assertStringContainsString( '500', $results['errors'][0] );
	}

	public function test_upload_reports_handles_file_read_error() {
		$fake_path = $this->test_upload_dir . '/nonexistent.csv';

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], 'OK' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports( [ $fake_path ], '2026-01-08' );

		$this->assertFalse( $results['success'] );
		$this->assertEquals( 0, $results['uploaded'] );
		$this->assertEquals( 1, $results['failed'] );
		$this->assertNotEmpty( $results['errors'] );
		$this->assertStringContainsString( 'Unable to read file', $results['errors'][0] );
	}

	public function test_upload_reports_handles_client_exception() {
		$file_path = $this->create_test_file( 'test-upload.csv', 'data' );

		$mock_handler = new MockHandler(
			[
				new RequestException(
					'Connection timeout',
					new Request( 'PUT', 'https://example.com' )
				),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertFalse( $results['success'] );
		$this->assertEquals( 0, $results['uploaded'] );
		$this->assertEquals( 1, $results['failed'] );
		$this->assertNotEmpty( $results['errors'] );
		$this->assertStringContainsString( 'Connection timeout', $results['errors'][0] );
	}

	public function test_upload_reports_extracts_part_number_from_filename() {
		$file_path = $this->create_test_file( 'youtube-merchant-conversion-report-2026-01-08-5.csv', 'data' );

		$captured_url = null;
		$mock_handler = new MockHandler(
			[
				function ( $request ) use ( &$captured_url ) {
					$captured_url = (string) $request->getUri();
					return new Response( 200, [], 'OK' );
				},
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertStringEndsWith( '/2026-01-08/5', $captured_url );
	}

	public function test_upload_reports_single_file_has_no_part_in_url() {
		// Use the exact filename format without a part suffix.
		$file_path = $this->create_test_file( 'youtube-merchant-conversion-report-2026-01-08.csv', 'data' );

		$captured_url = null;
		$mock_handler = new MockHandler(
			[
				function ( $request ) use ( &$captured_url ) {
					$captured_url = (string) $request->getUri();
					return new Response( 200, [], 'OK' );
				},
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		// URL should end with date only, no trailing slash or part number.
		$expected_suffix = 'google/youtube/shopping/report/conversion/2026-01-08';
		$this->assertStringEndsWith( $expected_suffix, $captured_url );
	}

	public function test_upload_reports_sends_csv_content_type() {
		$file_path = $this->create_test_file( 'test-upload.csv', 'col1,col2' . "\n" . 'val1,val2' );

		$captured_content_type = null;
		$mock_handler          = new MockHandler(
			[
				function ( $request ) use ( &$captured_content_type ) {
					$captured_content_type = $request->getHeader( 'Content-Type' )[0] ?? null;
					return new Response( 200, [], 'OK' );
				},
			]
		);
		$handlers              = HandlerStack::create( $mock_handler );
		$client                = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertEquals( 'text/csv', $captured_content_type );
	}

	public function test_upload_reports_sends_file_contents() {
		$csv_content = 'col1,col2' . "\n" . 'val1,val2';
		$file_path   = $this->create_test_file( 'test-upload.csv', $csv_content );

		$captured_body = null;
		$mock_handler  = new MockHandler(
			[
				function ( $request ) use ( &$captured_body ) {
					$captured_body = (string) $request->getBody();
					return new Response( 200, [], 'OK' );
				},
			]
		);
		$handlers      = HandlerStack::create( $mock_handler );
		$client        = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$this->connection->upload_reports( [ $file_path ], '2026-01-08' );

		$this->assertEquals( $csv_content, $captured_body );
	}

	public function test_upload_reports_continues_on_partial_failure() {
		$file_path_1 = $this->create_test_file( 'report-1.csv', 'data1' );
		$file_path_2 = $this->create_test_file( 'report-2.csv', 'data2' );
		$file_path_3 = $this->create_test_file( 'report-3.csv', 'data3' );

		$mock_handler = new MockHandler(
			[
				new Response( 200, [], 'OK' ),
				new Response( 500, [], 'Error' ),
				new Response( 200, [], 'OK' ),
			]
		);
		$handlers     = HandlerStack::create( $mock_handler );
		$client       = new Client( [ 'handler' => $handlers ] );
		$this->container->add( Client::class, $client );

		$results = $this->connection->upload_reports(
			[ $file_path_1, $file_path_2, $file_path_3 ],
			'2026-01-08'
		);

		$this->assertFalse( $results['success'] );
		$this->assertEquals( 2, $results['uploaded'] );
		$this->assertEquals( 1, $results['failed'] );
		$this->assertCount( 1, $results['errors'] );
	}

	/**
	 * Helper method to create a test file.
	 *
	 * @param string $filename File name.
	 * @param string $content  File content.
	 * @return string Full file path.
	 */
	protected function create_test_file( string $filename, string $content ): string {
		global $wp_filesystem;
		$file_path = $this->test_upload_dir . '/' . $filename;
		$wp_filesystem->put_contents( $file_path, $content );
		return $file_path;
	}
}
