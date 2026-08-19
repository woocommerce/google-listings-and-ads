<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class SitesService
 *
 * Matches, creates, and resolves Search Console Sites API properties against the
 * store's own URL. Domain-alignment filtering, URL-covering matching, and the
 * property-preference rules are pure functions over already-fetched Sites API
 * data (no side effects) so they can be tested independently of the API client.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class SitesService {

	use PluginHelper;

	/** @var string A URL-prefix property, e.g. `https://example.com/`. */
	public const PROPERTY_TYPE_URL_PREFIX = 'url_prefix';

	/** @var string A domain property, e.g. `sc-domain:example.com`. */
	public const PROPERTY_TYPE_DOMAIN = 'domain';

	/** @var string Sites API permissionLevel value meaning the account has no verified ownership of the property. */
	public const PERMISSION_UNVERIFIED = 'siteUnverifiedUser';

	/**
	 * @var string Sites API permissionLevel value meaning another account already owns the
	 * property and this account only has limited, non-owner access. Unlike PERMISSION_UNVERIFIED
	 * (no relationship yet, resolvable via this plugin's own META-tag flow), this plugin has no
	 * path to complete or request verification for a restricted property — that requires Google's
	 * own "request access from an existing owner" flow, which this plugin doesn't build. Such
	 * properties are excluded entirely from matching rather than surfaced as an unusable option.
	 */
	public const PERMISSION_RESTRICTED = 'siteRestrictedUser';

	/** @var string Sites API siteUrl prefix identifying a domain property. */
	private const DOMAIN_PROPERTY_PREFIX = 'sc-domain:';

	/** @var SearchConsoleApiClient */
	protected $client;

	/**
	 * SitesService constructor.
	 *
	 * @param SearchConsoleApiClient $client
	 */
	public function __construct( SearchConsoleApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * List every Search Console property the connecting account can access.
	 *
	 * @return array[] Sites API `siteEntry` resources (each with `siteUrl`, `permissionLevel`).
	 * @throws SearchConsoleApiException On a non-2xx Sites API response.
	 */
	public function list_sites(): array {
		return $this->client->get( 'sites' )['siteEntry'] ?? [];
	}

	/**
	 * Create a URL-prefix property for the store URL.
	 *
	 * The plugin never creates, or offers to create, a domain property — domain
	 * properties require DNS-level verification, which this plugin's META-tag-only
	 * verification flow can never complete.
	 *
	 * @param string|null $site_url The URL-prefix property to create (a full, trailing-slashed
	 *                               URL). Defaults to the plugin's own canonical site URL, same
	 *                               default as {@see self::resolve_property()}.
	 *
	 * @return array A `siteEntry`-shaped resource for the newly created property. The Sites API's
	 *               own `sites.add` response body is empty on success, so this is constructed
	 *               locally rather than read from the response. Deliberately constructed as
	 *               unverified: creating a property does not by itself grant verified ownership —
	 *               a newly created property still needs to resolve through the same
	 *               same-account-inheritance-or-META-tag verification path as any other property.
	 * @throws SearchConsoleApiException On a non-2xx Sites API response.
	 */
	public function create_site( ?string $site_url = null ): array {
		$site_url = $site_url ?? $this->get_site_url();

		$this->client->put( 'sites/' . rawurlencode( $site_url ) );

		return [
			'siteUrl'         => $site_url,
			'permissionLevel' => self::PERMISSION_UNVERIFIED,
		];
	}

	/**
	 * Resolve which property the store should connect to.
	 *
	 * Fetches every accessible property, narrows to the ones aligned to the store's
	 * domain, and applies the matching/preference rules. Never assumes verified
	 * ownership — `resolved` may still need to go through the normal verification
	 * flow; this method only resolves *which property*, not whether it's verified.
	 *
	 * @param string|null $store_url Defaults to the plugin's own canonical site URL.
	 *
	 * @return array {
	 *     @type array|null $resolved Single `siteEntry`-shaped resource this ticket auto-selected
	 *                                or created, or null if the merchant must choose (multi-match).
	 *     @type array[]    $matches  Every domain-aligned property, each with `covers` and `usable`
	 *                                booleans added — used by the frontend property selector to
	 *                                render selectable vs. greyed-out options.
	 *     @type bool       $created  Whether `resolved` came from silently auto-creating a property.
	 * }
	 * @throws SearchConsoleApiException On a non-2xx Sites API response.
	 */
	public function resolve_property( ?string $store_url = null ): array {
		$store_url = $store_url ?? $this->get_site_url();

		$matches = array_values(
			array_filter(
				$this->list_sites(),
				function ( array $site_entry ) use ( $store_url ) {
					// A restricted-access property is excluded entirely, not just marked unusable —
					// this plugin has no path to request or complete verification for it at all.
					if ( self::PERMISSION_RESTRICTED === ( $site_entry['permissionLevel'] ?? '' ) ) {
						return false;
					}

					return $this->is_domain_aligned( $site_entry['siteUrl'], $store_url );
				}
			)
		);

		$matches = array_map(
			function ( array $site_entry ) use ( $store_url ) {
				$covers               = $this->covers_store_url( $site_entry['siteUrl'], $store_url );
				$site_entry['covers'] = $covers;
				$site_entry['usable'] = $covers && $this->is_usable( $site_entry );
				return $site_entry;
			},
			$matches
		);

		$usable = array_values(
			array_filter(
				$matches,
				function ( array $site_entry ) {
					return $site_entry['usable'];
				}
			)
		);

		if ( count( $usable ) > 1 ) {
			$preferred = $this->resolve_usable_preference( $usable );

			if ( null !== $preferred ) {
				return [
					'resolved' => $preferred,
					'matches'  => $matches,
					'created'  => false,
				];
			}

			// More than one usable match and no url-prefix-vs-domain preference applies
			// (e.g. two usable url-prefix properties) — the merchant must choose.
			return [
				'resolved' => null,
				'matches'  => $matches,
				'created'  => false,
			];
		}

		if ( 1 === count( $usable ) ) {
			return [
				'resolved' => $usable[0],
				'matches'  => $matches,
				'created'  => false,
			];
		}

		$created = $this->create_site( $store_url );

		return [
			'resolved' => $created,
			'matches'  => array_merge(
				$matches,
				[
					array_merge(
						$created,
						[
							'covers' => true,
							'usable' => $this->is_usable( $created ),
						]
					),
				]
			),
			'created'  => true,
		];
	}

	/**
	 * Determine a site entry's property type from its `siteUrl` shape.
	 *
	 * @param string $site_url
	 *
	 * @return string self::PROPERTY_TYPE_URL_PREFIX or self::PROPERTY_TYPE_DOMAIN
	 */
	public function get_property_type( string $site_url ): string {
		return 0 === strpos( $site_url, self::DOMAIN_PROPERTY_PREFIX )
			? self::PROPERTY_TYPE_DOMAIN
			: self::PROPERTY_TYPE_URL_PREFIX;
	}

	/**
	 * Whether a property belongs to the store's own domain at all (regardless of
	 * whether it covers the specific store URL) — decides whether a property is
	 * shown at all, before the narrower covering check below decides selectability.
	 *
	 * @param string $site_url  The property's `siteUrl`.
	 * @param string $store_url The store's canonical URL.
	 *
	 * @return bool
	 */
	private function is_domain_aligned( string $site_url, string $store_url ): bool {
		$store_host = wp_parse_url( $store_url, PHP_URL_HOST ) ?? '';

		if ( self::PROPERTY_TYPE_DOMAIN === $this->get_property_type( $site_url ) ) {
			$property_domain = substr( $site_url, strlen( self::DOMAIN_PROPERTY_PREFIX ) );

			// A domain property covers its exact domain and every subdomain of it.
			return $store_host === $property_domain || substr( $store_host, -strlen( '.' . $property_domain ) ) === '.' . $property_domain;
		}

		$property_host = wp_parse_url( $site_url, PHP_URL_HOST ) ?? '';

		return '' !== $property_host && $store_host === $property_host;
	}

	/**
	 * Whether a property actually covers the store's specific URL — a narrower
	 * check than domain alignment, since a URL-prefix property can share the
	 * store's domain but scope only a different path (e.g. `/blog`).
	 *
	 * Deliberately does not use a naive string-prefix check: `example.com/store`
	 * would otherwise incorrectly "cover" `example.com/storefront`, since the
	 * latter starts with the same characters without actually being a subpath.
	 *
	 * @param string $site_url  The property's `siteUrl`.
	 * @param string $store_url The store's canonical URL.
	 *
	 * @return bool
	 */
	private function covers_store_url( string $site_url, string $store_url ): bool {
		if ( self::PROPERTY_TYPE_DOMAIN === $this->get_property_type( $site_url ) ) {
			// A domain property that's already domain-aligned covers every path on that domain.
			return $this->is_domain_aligned( $site_url, $store_url );
		}

		$property_path = untrailingslashit( $this->strip_url_protocol( $site_url ) );
		$store_path    = untrailingslashit( $this->strip_url_protocol( $store_url ) );

		return $store_path === $property_path || 0 === strpos( $store_path, $property_path . '/' );
	}

	/**
	 * Whether a covering property can be auto-used without merchant input.
	 *
	 * Deliberately asymmetric: a URL-prefix property is always usable if it
	 * covers the store URL — if it later turns out unverified, the normal META-tag
	 * verification flow (owned by VerificationService) still completes it. A domain
	 * property is usable only when the Sites API already reports verified ownership,
	 * because domain properties require DNS-level verification, which this plugin's
	 * META-tag-only flow can never complete — an unverified domain property would be
	 * a dead end if auto-selected, so it's treated as not usable at all instead.
	 *
	 * @param array $site_entry A `siteEntry` resource (`siteUrl`, `permissionLevel`).
	 *
	 * @return bool
	 */
	private function is_usable( array $site_entry ): bool {
		if ( self::PROPERTY_TYPE_DOMAIN === $this->get_property_type( $site_entry['siteUrl'] ) ) {
			return self::PERMISSION_UNVERIFIED !== ( $site_entry['permissionLevel'] ?? self::PERMISSION_UNVERIFIED );
		}

		return true;
	}

	/**
	 * Resolve a single preferred property out of more than one usable match.
	 *
	 * An already-verified property is favored over an unverified one of the
	 * other type, regardless of type — auto-selecting a verified domain
	 * property is strictly better for the merchant than routing them to a
	 * still-unverified URL-prefix property. Only when the candidates are
	 * equally verified (or equally unverified) does URL-prefix win as a
	 * tiebreaker, since it can be completed via this plugin's own META-tag
	 * flow, unlike a domain property.
	 *
	 * (Revised 2026-08-18 — the original rule favored URL-prefix unconditionally;
	 * see `search-console-connection.prd.md`'s BR-005 changelog for why.)
	 *
	 * @param array[] $usable Usable site entries (already filtered to `usable === true`).
	 *
	 * @return array|null The preferred entry, or null if no preference resolves the
	 *                     set to one (e.g. two usable, equally-verified URL-prefix properties).
	 */
	private function resolve_usable_preference( array $usable ): ?array {
		$verified = array_values(
			array_filter(
				$usable,
				function ( array $site_entry ) {
					return $this->is_verified_entry( $site_entry );
				}
			)
		);

		if ( 1 === count( $verified ) ) {
			return $verified[0];
		}

		$tiebreak_pool = $verified ? $verified : $usable;

		$url_prefix_matches = array_values(
			array_filter(
				$tiebreak_pool,
				function ( array $site_entry ) {
					return self::PROPERTY_TYPE_URL_PREFIX === $this->get_property_type( $site_entry['siteUrl'] );
				}
			)
		);

		return 1 === count( $url_prefix_matches ) ? $url_prefix_matches[0] : null;
	}

	/**
	 * Whether a usable property is already verified for the connecting account.
	 *
	 * A domain property only ever reaches the `usable` set when it's already
	 * verified (see `is_usable()`), so this only meaningfully discriminates
	 * between verified and unverified URL-prefix properties.
	 *
	 * @param array $site_entry A `siteEntry` resource (`siteUrl`, `permissionLevel`).
	 *
	 * @return bool
	 */
	private function is_verified_entry( array $site_entry ): bool {
		return self::PERMISSION_UNVERIFIED !== ( $site_entry['permissionLevel'] ?? self::PERMISSION_UNVERIFIED );
	}
}
