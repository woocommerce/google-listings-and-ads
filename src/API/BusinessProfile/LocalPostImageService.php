<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostImageService
 *
 * Picks a version of a post's featured image that Google Business Profile accepts. Google fetches
 * the image itself from a public URL, and only accepts a JPG or PNG of 10 KB to 5 MB that is at
 * least 250px on its shortest edge.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class LocalPostImageService implements Service, Registerable {

	/** @var string[] Image types Google accepts. */
	private const ACCEPTED_MIME_TYPES = [ 'image/jpeg', 'image/png' ];

	/** @var string[] Image types converted to JPG when no accepted version exists. */
	private const CONVERTIBLE_MIME_TYPES = [ 'image/webp', 'image/avif' ];

	/** @var int Smallest file Google accepts, in bytes. */
	private const MIN_FILE_SIZE = 10 * KB_IN_BYTES;

	/** @var int Largest file Google accepts, in bytes. */
	private const MAX_FILE_SIZE = 5 * MB_IN_BYTES;

	/** @var int Shortest edge Google accepts, in pixels. */
	private const MIN_EDGE = 250;

	/** @var int Longest edge of a converted image, in pixels, so it stays well under the file size limit. */
	private const MAX_CONVERTED_EDGE = 2048;

	/**
	 * Attachment metadata key recording the converted image. It's kept out of `sizes` so the
	 * converted JPG never joins the image's `srcset` on the site.
	 *
	 * @var string
	 */
	public const CONVERTED_IMAGE_KEY = 'gla_business_profile_image';

	/** @var string Suffix added to a converted image's file name. */
	private const CONVERTED_FILE_SUFFIX = 'gla-business-profile';

	/**
	 * Remove a converted image along with its attachment.
	 */
	public function register(): void {
		add_action( 'delete_attachment', [ $this, 'delete_converted_image' ] );
	}

	/**
	 * Get the URL of a version of the post's featured image that Google accepts.
	 *
	 * Uses the original or an existing generated size when one qualifies. Only when none does, and
	 * the original is WebP or AVIF, it converts the image to JPG once and keeps it for later sends.
	 *
	 * @param WP_Post $post
	 *
	 * @return string The image URL, or an empty string when the post has no usable featured image.
	 */
	public function get_image_url( WP_Post $post ): string {
		$attachment_id = (int) get_post_thumbnail_id( $post );
		$file          = $attachment_id ? (string) get_attached_file( $attachment_id ) : '';
		$url           = $attachment_id ? (string) wp_get_attachment_url( $attachment_id ) : '';

		if ( '' === $file || '' === $url ) {
			return '';
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );
		$metadata = is_array( $metadata ) ? $metadata : [];
		$mime     = (string) get_post_mime_type( $attachment_id );

		foreach ( self::get_candidates( $metadata, $file, $mime ) as $candidate ) {
			if ( $this->is_accepted( $candidate, dirname( $file ) ) ) {
				return isset( $candidate['source'] )
					? self::get_local_url( path_join( dirname( $file ), $candidate['file'] ) )
					: self::get_url( $url, $candidate['file'] );
			}
		}

		if ( ! in_array( $mime, self::CONVERTIBLE_MIME_TYPES, true ) ) {
			return '';
		}

		$converted = $this->convert( $attachment_id, $metadata, $file );

		return null === $converted ? '' : self::get_local_url( path_join( dirname( $file ), $converted['file'] ) );
	}

	/**
	 * Delete the converted image when its attachment is deleted.
	 *
	 * @param int $attachment_id
	 */
	public function delete_converted_image( $attachment_id ): void {
		$this->handle_delete_converted_image( (int) $attachment_id );
	}

	/**
	 * Delete the attachment's converted JPG, if it has one.
	 *
	 * @param int $attachment_id
	 */
	protected function handle_delete_converted_image( int $attachment_id ): void {
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$name     = is_array( $metadata ) ? ( $metadata[ self::CONVERTED_IMAGE_KEY ]['file'] ?? '' ) : '';

		if ( ! is_string( $name ) || '' === $name ) {
			return;
		}

		$directory = dirname( (string) get_attached_file( $attachment_id ) );
		wp_delete_file_from_directory( path_join( $directory, wp_basename( $name ) ), $directory );
	}

	/**
	 * Convert the image to a JPG next to the original, and record it in the attachment's metadata,
	 * replacing a JPG converted from an earlier version of the image.
	 *
	 * @param int    $attachment_id
	 * @param array  $metadata The attachment's metadata.
	 * @param string $file     Path of the attached image.
	 *
	 * @return array|null The converted image, shaped like an entry of `sizes`, or null when it can't be made acceptable.
	 */
	private function convert( int $attachment_id, array $metadata, string $file ): ?array {
		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) ) {
			return null;
		}

		$size = $editor->get_size();

		if ( max( $size['width'], $size['height'] ) > self::MAX_CONVERTED_EDGE ) {
			$resized = $editor->resize( self::MAX_CONVERTED_EDGE, self::MAX_CONVERTED_EDGE );

			if ( is_wp_error( $resized ) ) {
				return null;
			}
		}

		$saved = $editor->save( $editor->generate_filename( self::CONVERTED_FILE_SUFFIX, null, 'jpg' ), 'image/jpeg' );

		if ( is_wp_error( $saved ) || ! is_array( $saved ) ) {
			return null;
		}

		$image = [
			'source'    => wp_basename( $file ),
			'file'      => wp_basename( $saved['path'] ),
			'width'     => (int) $saved['width'],
			'height'    => (int) $saved['height'],
			'mime-type' => (string) $saved['mime-type'],
			'filesize'  => (int) ( $saved['filesize'] ?? wp_filesize( $saved['path'] ) ),
		];

		// A site can filter the editor's output to another format, so check what it actually saved.
		if ( ! $this->is_accepted( $image, dirname( $file ) ) ) {
			wp_delete_file( $saved['path'] );
			return null;
		}

		$previous = $metadata[ self::CONVERTED_IMAGE_KEY ]['file'] ?? '';

		if ( is_string( $previous ) && '' !== $previous && $image['file'] !== wp_basename( $previous ) ) {
			wp_delete_file_from_directory( path_join( dirname( $file ), wp_basename( $previous ) ), dirname( $file ) );
		}

		$metadata[ self::CONVERTED_IMAGE_KEY ] = $image;
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $image;
	}

	/**
	 * List the image's versions in the order to try them: the original, a JPG converted from the
	 * current image by an earlier send, then the generated sizes from largest to smallest.
	 *
	 * @param array  $metadata The attachment's metadata.
	 * @param string $file     Path of the attached image.
	 * @param string $mime     The attachment's MIME type.
	 *
	 * @return array[] Each shaped like an entry of `sizes`.
	 */
	private static function get_candidates( array $metadata, string $file, string $mime ): array {
		$sizes = is_array( $metadata['sizes'] ?? null ) ? array_filter( $metadata['sizes'], 'is_array' ) : [];
		usort(
			$sizes,
			static function ( array $a, array $b ) {
				return (int) ( $b['width'] ?? 0 ) <=> (int) ( $a['width'] ?? 0 );
			}
		);

		$candidates = [
			[
				'file'      => wp_basename( $file ),
				'width'     => $metadata['width'] ?? 0,
				'height'    => $metadata['height'] ?? 0,
				'mime-type' => $mime,
				'filesize'  => $metadata['filesize'] ?? null,
			],
		];

		$converted = $metadata[ self::CONVERTED_IMAGE_KEY ] ?? null;

		// Editing the image in the media library keeps this record but changes the attached file,
		// so a JPG made from the earlier version no longer matches what the post shows.
		if ( is_array( $converted ) && wp_basename( $file ) === ( $converted['source'] ?? null ) ) {
			$candidates[] = $converted;
		}

		return array_merge( $candidates, $sizes );
	}

	/**
	 * Whether Google accepts an image version's type, dimensions, and file size.
	 *
	 * @param array  $image     Shaped like an entry of `sizes`.
	 * @param string $directory Directory holding the image's files, to read a size the metadata lacks.
	 *
	 * @return bool
	 */
	private function is_accepted( array $image, string $directory ): bool {
		$name = (string) ( $image['file'] ?? '' );

		if ( '' === $name || ! in_array( $image['mime-type'] ?? '', self::ACCEPTED_MIME_TYPES, true ) ) {
			return false;
		}

		if ( min( (int) ( $image['width'] ?? 0 ), (int) ( $image['height'] ?? 0 ) ) < self::MIN_EDGE ) {
			return false;
		}

		$path      = path_join( $directory, wp_basename( $name ) );
		$file_size = isset( $image['filesize'] ) ? (int) $image['filesize'] : ( file_exists( $path ) ? wp_filesize( $path ) : 0 );

		return $file_size >= self::MIN_FILE_SIZE && $file_size <= self::MAX_FILE_SIZE;
	}

	/**
	 * Build a version's URL from the attachment's URL, the same way WordPress does for its sizes.
	 *
	 * @param string $attachment_url
	 * @param string $file The version's file name.
	 *
	 * @return string
	 */
	private static function get_url( string $attachment_url, string $file ): string {
		return str_replace( wp_basename( $attachment_url ), wp_basename( $file ), $attachment_url );
	}

	/**
	 * Build a converted JPG's URL from the local uploads directory, where it's written.
	 *
	 * Media offload plugins rewrite the attachment's URL to their own storage, and copy its `sizes`
	 * there, but not this JPG, so a URL built from the attachment's would point at a missing file.
	 *
	 * @param string $path The JPG's path.
	 *
	 * @return string The URL, or an empty string when the file is outside the uploads directory.
	 */
	private static function get_local_url( string $path ): string {
		$uploads  = wp_get_upload_dir();
		$base_dir = trailingslashit( $uploads['basedir'] );

		if ( 0 !== strpos( $path, $base_dir ) ) {
			return '';
		}

		return trailingslashit( $uploads['baseurl'] ) . substr( $path, strlen( $base_dir ) );
	}
}
