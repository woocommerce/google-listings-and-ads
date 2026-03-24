<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Exports\Writer;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\ExportException;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class CsvExportWriterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Exports\Writer
 */
class CsvExportWriterTest extends UnitTest {
	/** @var CsvExportWriter $writer */
	protected $writer;

	/** @var string $test_upload_dir */
	protected $test_upload_dir;

	/** @var array $original_upload_dir */
	protected $original_upload_dir;

	public function setUp(): void {
		parent::setUp();

		// Store original upload directory.
		$this->original_upload_dir = wp_upload_dir();

		// Create a test upload directory.
		$this->test_upload_dir = sys_get_temp_dir() . '/gla-test-uploads-' . uniqid();
		wp_mkdir_p( $this->test_upload_dir );

		// Override wp_upload_dir for testing.
		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['basedir'] = $this->test_upload_dir;
				$dirs['baseurl'] = 'http://example.com/wp-content/uploads';
				$dirs['path']    = $this->test_upload_dir;
				$dirs['url']     = 'http://example.com/wp-content/uploads';
				return $dirs;
			}
		);

		// Initialize WP_Filesystem.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->writer = new CsvExportWriter();
	}

	public function tearDown(): void {
		// Clean up test files and directories.
		if ( is_dir( $this->test_upload_dir ) ) {
			global $wp_filesystem;
			if ( $wp_filesystem ) {
				$wp_filesystem->rmdir( $this->test_upload_dir, true );
			}
		}

		remove_all_filters( 'upload_dir' );

		parent::tearDown();
	}

	public function test_create_file_creates_export_directory() {
		$filename  = 'test-export';
		$file_path = $this->writer->create_file( $filename );

		$this->assertStringContainsString( 'gla-exports', $file_path );
		$this->assertStringContainsString( $filename . '.csv', $file_path );
		$this->assertFileExists( $file_path );
	}

	public function test_create_file_returns_existing_file_path() {
		$filename    = 'existing-export';
		$file_path_1 = $this->writer->create_file( $filename );
		$file_path_2 = $this->writer->create_file( $filename );

		$this->assertEquals( $file_path_1, $file_path_2 );
		$this->assertFileExists( $file_path_1 );
	}

	public function test_create_file_throws_exception_when_wp_upload_dir_has_error() {
		// Override upload_dir to return an error.
		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['error'] = 'Unable to create directory wp-content/uploads. Is its parent directory writable by the server?';
				return $dirs;
			},
			20
		);

		$this->expectException( ExportException::class );
		$this->expectExceptionMessage( 'Unable to create directory wp-content/uploads. Is its parent directory writable by the server?' );

		$this->writer->create_file( 'test' );
	}

	public function test_create_file_throws_exception_when_upload_directory_invalid() {
		// Override upload_dir to return invalid directory.
		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['basedir'] = '';
				return $dirs;
			},
			20
		);

		$this->expectException( ExportException::class );
		$this->expectExceptionMessage( 'Unable to determine upload directory.' );

		$this->writer->create_file( 'test' );
	}

	public function test_create_file_throws_exception_when_directory_creation_fails() {
		// Create a mock filesystem that fails directory creation.
		global $wp_filesystem;
		$original_fs = $wp_filesystem;

		$mock_fs = $this->createMock( \WP_Filesystem_Direct::class );
		$mock_fs->method( 'is_dir' )->willReturn( false );
		$wp_filesystem = $mock_fs; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Create a new writer instance to use the mocked filesystem.
		$writer = new CsvExportWriter();

		$this->expectException( ExportException::class );
		$this->expectExceptionMessage( 'Failed to create export directory' );

		try {
			$writer->create_file( 'test' );
		} finally {
			$wp_filesystem = $original_fs; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	public function test_create_file_throws_exception_when_file_creation_fails() {
		// Create a mock filesystem that fails file creation.
		global $wp_filesystem;
		$original_fs = $wp_filesystem;

		$mock_fs = $this->createMock( \WP_Filesystem_Direct::class );
		$mock_fs->method( 'is_dir' )->willReturn( true );
		$mock_fs->method( 'exists' )->willReturn( false );
		$mock_fs->method( 'put_contents' )->willReturn( false );
		$wp_filesystem = $mock_fs; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Create a new writer instance to use the mocked filesystem.
		$writer = new CsvExportWriter();

		$this->expectException( ExportException::class );
		$this->expectExceptionMessage( 'Failed to create CSV file' );

		try {
			$writer->create_file( 'test' );
		} finally {
			$wp_filesystem = $original_fs; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	public function test_append_row_creates_header_when_file_empty() {
		$filename  = 'test-append-header';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => 'value1',
			'column2' => 'value2',
		];

		$this->writer->append_row( $file_path, $row );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		$this->assertStringContainsString( 'column1', $content );
		$this->assertStringContainsString( 'column2', $content );
		$this->assertStringContainsString( 'value1', $content );
		$this->assertStringContainsString( 'value2', $content );
	}

	public function test_append_row_appends_data_rows() {
		$filename  = 'test-append-rows';
		$file_path = $this->writer->create_file( $filename );

		$row1 = [
			'column1' => 'value1',
			'column2' => 'value2',
		];

		$row2 = [
			'column1' => 'value3',
			'column2' => 'value4',
		];

		$this->writer->append_row( $file_path, $row1 );
		$this->writer->append_row( $file_path, $row2 );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		$lines   = explode( "\n", trim( $content ) );

		// Should have header + 2 data rows.
		$this->assertCount( 3, $lines );
		$this->assertStringContainsString( 'value1', $lines[1] );
		$this->assertStringContainsString( 'value3', $lines[2] );
	}

	public function test_append_row_handles_html_entity_decoding() {
		$filename  = 'test-html-entities';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => '&amp;test',
			'column2' => '&quot;quoted&quot;',
			'column3' => '&lt;tag&gt;',
		];

		$this->writer->append_row( $file_path, $row );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		$this->assertStringContainsString( '&test', $content );
		$this->assertStringContainsString( '"quoted"', $content );
		$this->assertStringContainsString( '<tag>', $content );
	}

	public function test_append_row_handles_csv_escaping() {
		$filename  = 'test-csv-escaping';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => 'value with "quotes"',
			'column2' => 'value,with,commas',
			'column3' => "value\nwith\nnewlines",
		];

		$this->writer->append_row( $file_path, $row );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		// Verify the CSV is properly formatted (fputcsv handles escaping).
		$this->assertNotEmpty( $content );
		// The content should be valid CSV format.
		$lines = str_getcsv( $content, "\n", '"', '\\' );
		$this->assertGreaterThanOrEqual( 1, count( $lines ) );
	}

	public function test_append_row_handles_empty_values() {
		$filename  = 'test-empty-values';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => 'value1',
			'column2' => '',
			'column3' => null,
		];

		$this->writer->append_row( $file_path, $row );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		$this->assertStringContainsString( 'value1', $content );
		// Empty values should still create CSV columns.
		$lines = explode( "\n", trim( $content ) );
		$this->assertCount( 2, $lines ); // Header + 1 data row.
	}

	public function test_append_row_handles_non_string_values() {
		$filename  = 'test-non-string-values';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => 123,
			'column2' => 45.67,
			'column3' => true,
		];

		$this->writer->append_row( $file_path, $row );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		$this->assertStringContainsString( '123', $content );
		$this->assertStringContainsString( '45.67', $content );
	}

	public function test_generate_url_creates_correct_url() {
		$filename  = 'test-url';
		$file_path = $this->writer->create_file( $filename );

		$url = $this->writer->generate_url( $file_path );

		$this->assertStringContainsString( 'http://example.com/wp-content/uploads', $url );
		$this->assertStringContainsString( 'gla-exports', $url );
		$this->assertStringContainsString( $filename . '.csv', $url );
	}

	public function test_generate_url_handles_path_conversion() {
		$filename  = 'test-path-conversion';
		$file_path = $this->writer->create_file( $filename );

		$url = $this->writer->generate_url( $file_path );

		// URL should not contain the basedir path.
		$this->assertStringNotContainsString( $this->test_upload_dir, $url );
		// URL should be a valid URL format.
		$this->assertStringStartsWith( 'http://', $url );
	}

	public function test_generate_url_throws_exception_when_wp_upload_dir_has_error() {
		$filename  = 'test-url-error';
		$file_path = $this->test_upload_dir . '/gla-exports/' . $filename . '.csv';

		// Override upload_dir to return an error.
		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['error'] = 'Unable to create directory wp-content/uploads. Is its parent directory writable by the server?';
				return $dirs;
			},
			20
		);

		$this->expectException( ExportException::class );
		$this->expectExceptionMessage( 'Unable to create directory wp-content/uploads. Is its parent directory writable by the server?' );

		$this->writer->generate_url( $file_path );
	}

	public function test_append_row_preserves_existing_content() {
		$filename  = 'test-preserve-content';
		$file_path = $this->writer->create_file( $filename );

		$row1 = [
			'col1' => 'val1',
			'col2' => 'val2',
		];

		$row2 = [
			'col1' => 'val3',
			'col2' => 'val4',
		];

		$this->writer->append_row( $file_path, $row1 );
		global $wp_filesystem;
		$content_after_first = $wp_filesystem->get_contents( $file_path );

		$this->writer->append_row( $file_path, $row2 );
		$content_after_second = $wp_filesystem->get_contents( $file_path );

		// Second append should include first row.
		$this->assertStringContainsString( 'val1', $content_after_second );
		$this->assertStringContainsString( 'val3', $content_after_second );
		$this->assertGreaterThan( strlen( $content_after_first ), strlen( $content_after_second ) );
	}

	public function test_append_row_handles_special_characters() {
		$filename  = 'test-special-chars';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => 'Test with émojis 🎉',
			'column2' => 'Unicode: 测试',
			'column3' => 'Symbols: ©®™',
		];

		$this->writer->append_row( $file_path, $row );

		global $wp_filesystem;
		$content = $wp_filesystem->get_contents( $file_path );
		$this->assertNotEmpty( $content );
		// Verify UTF-8 encoding is preserved.
		$this->assertEquals( 'UTF-8', mb_detect_encoding( $content, 'UTF-8', true ) );
	}

	public function test_get_file_size_returns_correct_size() {
		$filename  = 'test-file-size';
		$file_path = $this->writer->create_file( $filename );

		$row = [
			'column1' => 'value1',
			'column2' => 'value2',
		];

		$this->writer->append_row( $file_path, $row );

		$size = $this->writer->get_file_size( $file_path );

		$this->assertGreaterThan( 0, $size );
		$this->assertEquals( filesize( $file_path ), $size );
	}

	public function test_get_file_size_returns_zero_for_empty_file() {
		$filename  = 'test-empty-file-size';
		$file_path = $this->writer->create_file( $filename );

		$size = $this->writer->get_file_size( $file_path );

		$this->assertEquals( 0, $size );
	}

	public function test_get_file_size_returns_zero_for_nonexistent_file() {
		$fake_path = $this->test_upload_dir . '/gla-exports/nonexistent-file.csv';

		$size = $this->writer->get_file_size( $fake_path );

		$this->assertEquals( 0, $size );
	}

	public function test_delete_file_removes_file() {
		$filename  = 'test-delete-file';
		$file_path = $this->writer->create_file( $filename );

		$this->assertFileExists( $file_path );

		$result = $this->writer->delete_file( $file_path );

		$this->assertTrue( $result );
		$this->assertFileDoesNotExist( $file_path );
	}

	public function test_delete_file_returns_false_for_nonexistent_file() {
		$fake_path = $this->test_upload_dir . '/gla-exports/nonexistent-file.csv';

		$result = $this->writer->delete_file( $fake_path );

		$this->assertFalse( $result );
	}
}
