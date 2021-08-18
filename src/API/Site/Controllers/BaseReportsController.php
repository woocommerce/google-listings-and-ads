<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use DateTime;
use DateTimeZone;
use Psr\Container\ContainerInterface;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class BaseReportsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
abstract class BaseReportsController extends BaseController {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * BaseReportsController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
		parent::__construct( $container->get( RESTServer::class ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		$params = [
			'context'   => $this->get_context_param( [ 'default' => 'view' ] ),
			'after'     => [
				'description'       => __( 'Limit response to data after a given ISO8601 compliant date.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'date',
				'default'           => '-7 days',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'before'    => [
				'description'       => __( 'Limit response to data before a given ISO8601 compliant date.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'date',
				'default'           => 'now',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'ids'       => [
				'description'       => __( 'Limit result to items with specified ids.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
			],
			'fields'    => [
				'description'       => __( 'Limit totals to a set of fields.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
			],
			'order'     => [
				'description'       => __( 'Order sort attribute ascending or descending.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => [ 'asc', 'desc' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'orderby'   => [
				'description'       => __( 'Sort collection by attribute.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page'  => [
				'description'       => __( 'Maximum number of rows to be returned in result data.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 200,
				'minimum'           => 1,
				'maximum'           => 1000,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'next_page' => [
				'description'       => __( 'Token to retrieve the next page.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];

		return $this->add_collection_parameters( $params );
	}

	/**
	 * Add collection parameters.
	 *
	 * @param array $params Initial set of collection parameters.
	 *
	 * @return array
	 */
	abstract protected function add_collection_parameters( array $params ): array;

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param Request $request REST Request.
	 * @return array
	 */
	protected function prepare_query_arguments( Request $request ): array {
		$params   = $this->get_collection_params();
		$defaults = $this->get_defaults( $params );
		$args     = wp_parse_args( array_intersect_key( $request->get_query_params(), $params ), $defaults );

		$this->normalize_timezones( $args );
		return $args;
	}

	/**
	 * Get parameter defaults.
	 *
	 * @param array $params List of parameters.
	 *
	 * @return array
	 */
	protected function get_defaults( array $params ): array {
		$defaults = [];
		foreach ( $params as $key => $param ) {
			if ( isset( $param['default'] ) ) {
				$defaults[ $key ] = $param['default'];
			}
		}

		return $defaults;
	}

	/**
	 * Converts input datetime parameters to local timezone.
	 *
	 * @param array $query_args Array of query arguments.
	 */
	protected function normalize_timezones( &$query_args ) {
		/** @var WP $wp */
		$wp       = $this->container->get( WP::class );
		$local_tz = new DateTimeZone( $wp->wp_timezone_string() );

		foreach ( [ 'before', 'after' ] as $query_arg_key ) {
			if ( isset( $query_args[ $query_arg_key ] ) && is_string( $query_args[ $query_arg_key ] ) ) {

				// Assume that unspecified timezone is a local timezone.
				$datetime = new DateTime( $query_args[ $query_arg_key ], $local_tz );

				// In case timezone was forced by using +HH:MM, convert to local timezone.
				$datetime->setTimezone( $local_tz );
				$query_args[ $query_arg_key ] = $datetime;

			} elseif ( isset( $query_args[ $query_arg_key ] ) && $query_args[ $query_arg_key ] instanceof DateTime ) {

				// In case timezone is in other timezone, convert to local timezone.
				$query_args[ $query_arg_key ]->setTimezone( $local_tz );
			}
		}
	}
}
