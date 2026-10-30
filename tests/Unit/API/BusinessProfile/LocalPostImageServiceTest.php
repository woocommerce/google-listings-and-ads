<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostImageService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostImageServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class LocalPostImageServiceTest extends UnitTest {

	/** @var LocalPostImageService $service */
	protected $service;

	/** @var WP_Post $post */
	protected $post;

	/** @var string[] Source images written outside the uploads directory. */
	protected $source_files = [];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service = new LocalPostImageService();
		$this->post    = $this->factory()->post->create_and_get();
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		$this->remove_added_uploads();
		array_map( 'wp_delete_file', $this->source_files );

		parent::tearDown();
	}

	public function test_register_hooks_delete_attachment_to_delete_the_converted_image() {
		$this->service->register();

		$this->assertSame( 10, has_action( 'delete_attachment', [ $this->service, 'delete_converted_image' ] ) );
	}

	public function test_is_empty_for_a_post_without_a_featured_image() {
		$this->assertSame( '', $this->service->get_image_url( $this->post ) );
	}

	public function test_uses_an_original_jpg_that_google_accepts() {
		$attachment_id = $this->set_featured_image( 'jpg', 400, 300 );

		$this->assertSame( wp_get_attachment_url( $attachment_id ), $this->service->get_image_url( $this->post ) );
	}

	public function test_uses_the_largest_accepted_generated_size_when_the_original_is_too_large_a_file() {
		$attachment_id        = $this->set_featured_image( 'jpg', 1200, 900 );
		$metadata             = wp_get_attachment_metadata( $attachment_id );
		$metadata['filesize'] = 6 * MB_IN_BYTES;
		wp_update_attachment_metadata( $attachment_id, $metadata );

		$this->assertSame(
			wp_get_attachment_image_url( $attachment_id, 'large' ),
			$this->service->get_image_url( $this->post )
		);
	}

	public function test_is_empty_when_no_version_of_a_jpg_is_large_enough() {
		$this->set_featured_image( 'jpg', 240, 180 );

		$this->assertSame( '', $this->service->get_image_url( $this->post ) );
	}

	public function test_uses_an_existing_accepted_jpg_size_of_a_webp_original_without_converting() {
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );
		$jpg_size      = $this->add_jpg_size( $attachment_id, 'jpg-copy', 400, 300 );

		$url = $this->service->get_image_url( $this->post );

		$this->assertStringEndsWith( "/{$jpg_size}", $url );
		$this->assertArrayNotHasKey( LocalPostImageService::CONVERTED_IMAGE_KEY, wp_get_attachment_metadata( $attachment_id ) );
	}

	public function test_converts_a_webp_original_with_no_accepted_version_to_a_jpg_next_to_it() {
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );

		$url      = $this->service->get_image_url( $this->post );
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$image    = $metadata[ LocalPostImageService::CONVERTED_IMAGE_KEY ] ?? [];

		$this->assertSame( 'image/jpeg', $image['mime-type'] ?? null );
		$this->assertStringEndsWith( '-gla-business-profile.jpg', $image['file'] ?? '' );
		$this->assertStringEndsWith( "/{$image['file']}", $url );
		$this->assertFileExists( path_join( dirname( get_attached_file( $attachment_id ) ), $image['file'] ) );
	}

	public function test_links_a_converted_jpg_to_the_local_uploads_directory_when_media_is_offloaded() {
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );
		$this->offload_media_urls();

		$first  = $this->service->get_image_url( $this->post );
		$second = $this->service->get_image_url( $this->post );

		$file     = wp_get_attachment_metadata( $attachment_id )[ LocalPostImageService::CONVERTED_IMAGE_KEY ]['file'];
		$expected = trailingslashit( dirname( str_replace( wp_get_upload_dir()['basedir'], wp_get_upload_dir()['baseurl'], get_attached_file( $attachment_id ) ) ) ) . $file;

		$this->assertSame( $expected, $first );
		$this->assertSame( $expected, $second );
	}

	public function test_links_an_existing_size_to_the_attachments_own_url_when_media_is_offloaded() {
		$attachment_id = $this->set_featured_image( 'jpg', 400, 300 );
		$this->offload_media_urls();

		$url = $this->service->get_image_url( $this->post );

		$this->assertSame( wp_get_attachment_url( $attachment_id ), $url );
		$this->assertStringStartsWith( 'https://cdn.example.com/', $url );
	}

	public function test_keeps_the_converted_jpg_out_of_the_generated_sizes() {
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );

		$this->service->get_image_url( $this->post );

		$sizes = wp_get_attachment_metadata( $attachment_id )['sizes'] ?? [];
		$this->assertEmpty( array_filter( array_column( $sizes, 'mime-type' ), fn( $mime ) => 'image/jpeg' === $mime ) );
	}

	public function test_reuses_the_converted_jpg_on_a_later_send_without_converting_again() {
		$this->set_featured_image( 'webp', 400, 300 );
		$first = $this->service->get_image_url( $this->post );

		// With no image editor available, only the stored conversion can return a URL.
		add_filter( 'wp_image_editors', '__return_empty_array' );
		$second = $this->service->get_image_url( $this->post );
		remove_filter( 'wp_image_editors', '__return_empty_array' );

		$this->assertNotSame( '', $first );
		$this->assertSame( $first, $second );
	}

	public function test_converts_again_once_the_image_is_edited_and_deletes_the_earlier_jpg() {
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );
		$this->service->get_image_url( $this->post );
		$directory = dirname( get_attached_file( $attachment_id ) );
		$earlier   = path_join( $directory, wp_get_attachment_metadata( $attachment_id )[ LocalPostImageService::CONVERTED_IMAGE_KEY ]['file'] );

		$edited = $this->edit_image( $attachment_id, 300, 400 );
		$url    = $this->service->get_image_url( $this->post );
		$image  = wp_get_attachment_metadata( $attachment_id )[ LocalPostImageService::CONVERTED_IMAGE_KEY ];

		$this->assertSame( $edited, $image['source'] );
		$this->assertSame( [ 300, 400 ], [ $image['width'], $image['height'] ] );
		$this->assertStringEndsWith( "/{$image['file']}", $url );
		$this->assertFileDoesNotExist( $earlier );
	}

	public function test_scales_a_converted_jpg_down_to_the_longest_edge_limit() {
		$attachment_id = $this->set_featured_image( 'webp', 2400, 600 );

		$this->service->get_image_url( $this->post );

		$image = wp_get_attachment_metadata( $attachment_id )[ LocalPostImageService::CONVERTED_IMAGE_KEY ];
		$this->assertSame( 2048, $image['width'] );
		$this->assertSame( 512, $image['height'] );
	}

	public function test_is_empty_and_leaves_no_file_when_the_conversion_is_saved_in_another_format() {
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );
		$directory     = dirname( get_attached_file( $attachment_id ) );

		$to_webp = static function () {
			return [ 'image/jpeg' => 'image/webp' ];
		};
		add_filter( 'image_editor_output_format', $to_webp );
		$url = $this->service->get_image_url( $this->post );
		remove_filter( 'image_editor_output_format', $to_webp );

		$this->assertSame( '', $url );
		$this->assertArrayNotHasKey( LocalPostImageService::CONVERTED_IMAGE_KEY, wp_get_attachment_metadata( $attachment_id ) );
		$this->assertEmpty( glob( "{$directory}/*-gla-business-profile.*" ) );
	}

	public function test_is_empty_when_no_image_editor_can_convert_a_webp_original() {
		$this->set_featured_image( 'webp', 400, 300 );

		add_filter( 'wp_image_editors', '__return_empty_array' );
		$url = $this->service->get_image_url( $this->post );
		remove_filter( 'wp_image_editors', '__return_empty_array' );

		$this->assertSame( '', $url );
	}

	public function test_deleting_the_attachment_deletes_the_converted_jpg() {
		$this->service->register();
		$attachment_id = $this->set_featured_image( 'webp', 400, 300 );
		$this->service->get_image_url( $this->post );
		$file = path_join(
			dirname( get_attached_file( $attachment_id ) ),
			wp_get_attachment_metadata( $attachment_id )[ LocalPostImageService::CONVERTED_IMAGE_KEY ]['file']
		);

		wp_delete_attachment( $attachment_id, true );

		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * Rewrite attachment URLs to another host, the way a media offload plugin does.
	 */
	private function offload_media_urls(): void {
		add_filter(
			'wp_get_attachment_url',
			static function ( string $url ) {
				return str_replace( wp_get_upload_dir()['baseurl'], 'https://cdn.example.com/uploads', $url );
			}
		);
	}

	/**
	 * Upload an image and set it as the post's featured image.
	 *
	 * @param string $type   `jpg` or `webp`.
	 * @param int    $width
	 * @param int    $height
	 *
	 * @return int The attachment ID.
	 */
	private function set_featured_image( string $type, int $width, int $height ): int {
		$attachment_id = $this->factory()->attachment->create_upload_object( $this->create_image_file( $type, $width, $height ), $this->post->ID );
		set_post_thumbnail( $this->post, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Replace an attachment's file with a new WebP, the way the media library's image editor does:
	 * a new file name, with the rest of the metadata kept.
	 *
	 * @param int $attachment_id
	 * @param int $width
	 * @param int $height
	 *
	 * @return string The new file's name.
	 */
	private function edit_image( int $attachment_id, int $width, int $height ): string {
		$file = get_attached_file( $attachment_id );
		$name = wp_basename( $file, '.webp' ) . '-e1700000000000.webp';
		$path = path_join( dirname( $file ), $name );
		copy( $this->create_image_file( 'webp', $width, $height ), $path );
		update_attached_file( $attachment_id, $path );

		$metadata             = wp_get_attachment_metadata( $attachment_id );
		$metadata['file']     = _wp_relative_upload_path( $path );
		$metadata['width']    = $width;
		$metadata['height']   = $height;
		$metadata['filesize'] = wp_filesize( $path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $name;
	}

	/**
	 * Write a JPG next to an attachment's file and record it as one of its generated sizes.
	 *
	 * @param int    $attachment_id
	 * @param string $size_name
	 * @param int    $width
	 * @param int    $height
	 *
	 * @return string The size's file name.
	 */
	private function add_jpg_size( int $attachment_id, string $size_name, int $width, int $height ): string {
		$name = "{$size_name}-{$width}x{$height}.jpg";
		$path = path_join( dirname( get_attached_file( $attachment_id ) ), $name );
		copy( $this->create_image_file( 'jpg', $width, $height ), $path );

		$metadata                        = wp_get_attachment_metadata( $attachment_id );
		$metadata['sizes'][ $size_name ] = [
			'file'      => $name,
			'width'     => $width,
			'height'    => $height,
			'mime-type' => 'image/jpeg',
			'filesize'  => wp_filesize( $path ),
		];
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $name;
	}

	/**
	 * Write an image of random colored blocks, which compresses too poorly to fall under Google's
	 * smallest file size.
	 *
	 * @param string $type   `jpg` or `webp`.
	 * @param int    $width
	 * @param int    $height
	 *
	 * @return string The file's path.
	 */
	private function create_image_file( string $type, int $width, int $height ): string {
		$image = imagecreatetruecolor( $width, $height );

		for ( $x = 0; $x < $width; $x += 4 ) {
			for ( $y = 0; $y < $height; $y += 4 ) {
				imagefilledrectangle( $image, $x, $y, $x + 3, $y + 3, wp_rand( 0, 0xFFFFFF ) );
			}
		}

		$path = get_temp_dir() . wp_unique_filename( get_temp_dir(), "gla-test-image.{$type}" );

		if ( 'webp' === $type ) {
			imagewebp( $image, $path, 90 );
		} else {
			imagejpeg( $image, $path, 90 );
		}

		imagedestroy( $image );
		$this->source_files[] = $path;

		return $path;
	}
}
