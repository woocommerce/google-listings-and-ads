<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Throwable;
use WP_CLI;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPCLIBusinessProfileDiagnostics
 *
 * WP-CLI commands for exercising the live Business Profile endpoints directly, without going
 * through the editor UI -- useful while the merchant-facing flows aren't fully built yet.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class WPCLIBusinessProfileDiagnostics implements Service, Registerable, Conditional {

	/** @var LocationService */
	private $location_service;

	/** @var LocalPostService */
	private $local_post_service;

	/**
	 * WPCLIBusinessProfileDiagnostics constructor.
	 *
	 * @param LocationService  $location_service
	 * @param LocalPostService $local_post_service
	 */
	public function __construct( LocationService $location_service, LocalPostService $local_post_service ) {
		$this->location_service   = $location_service;
		$this->local_post_service = $local_post_service;
	}

	/**
	 * Register the commands.
	 */
	public function register(): void {
		WP_CLI::add_hook( 'after_wp_load', [ $this, 'register_commands' ] );
	}

	/**
	 * Register the commands.
	 */
	public function register_commands(): void {
		WP_CLI::add_command( 'wc g4wc business-profile list-locations', [ $this, 'list_locations' ] );
		WP_CLI::add_command( 'wc g4wc business-profile test-post', [ $this, 'test_post' ] );
	}

	/**
	 * List every Business Profile location the connected Google user can reach.
	 *
	 * Exercises `accounts.list` and `accounts.locations.list` directly against the real
	 * Connect Server, bypassing the Settings UI entirely.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc g4wc business-profile list-locations
	 */
	public function list_locations(): void {
		try {
			$locations = $this->location_service->list_locations();
		} catch ( Throwable $exception ) {
			// A CLI command's own entry point: nothing downstream handles this, so it's reported
			// and the command exits, rather than left to crash with a raw stack trace.
			WP_CLI::error( $exception->getMessage() );
			return;
		}

		if ( empty( $locations ) ) {
			WP_CLI::log( 'No locations returned for the connected Google user.' );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $locations, array_keys( $locations[0] ) );
	}

	/**
	 * Send an existing WordPress post to Google Business Profile, then delete it immediately.
	 *
	 * Exercises `localPosts.create` and `localPosts.delete` directly against the real Connect
	 * Server, the same create-then-delete round trip used to validate the live endpoint, without
	 * needing the publish/delete triggers' own editor-driven flow.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of an existing, supported-type WordPress post to send.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc g4wc business-profile test-post 123
	 *
	 * @param string[] $args Positional arguments; `$args[0]` is the post ID.
	 */
	public function test_post( array $args ): void {
		$post = get_post( (int) ( $args[0] ?? 0 ) );

		if ( ! $post instanceof WP_Post ) {
			WP_CLI::error( 'No post found with that ID.' );
			return;
		}

		if ( ! in_array( $post->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			WP_CLI::error( sprintf( 'Post type "%s" is not supported.', $post->post_type ) );
			return;
		}

		try {
			$created = $this->local_post_service->create( $post );
			WP_CLI::success(
				sprintf(
					'Created: %s (search URL: %s)',
					(string) $created->get_name(),
					(string) ( $created->get_search_url() ?? '(none yet)' )
				)
			);

			$this->local_post_service->delete( $post->ID );
			WP_CLI::success( 'Deleted the test post from Google Business Profile.' );
		} catch ( Throwable $exception ) {
			// Same CLI entry-point boundary as list_locations() above.
			WP_CLI::error( $exception->getMessage() );
		}
	}

	/**
	 * Check if this service is needed.
	 *
	 * @see https://make.wordpress.org/cli/handbook/guides/commands-cookbook/#include-in-a-plugin-or-theme
	 *
	 * @return bool
	 */
	public static function is_needed(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}
}
