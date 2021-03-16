<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
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
	 * Maps query arguments from the REST request.
	 *
	 * @param Request $request REST Request.
	 * @return array
	 */
	protected function prepare_query_arguments( Request $request ) {
		$args = [
			'before'   => $request['before'],
			'after'    => $request['after'],
			'interval' => $request['interval'],
		];

		$defaults = [
			'before'   => TimeInterval::default_before(),
			'after'    => TimeInterval::default_after(),
			'interval' => '',
		];

		$args = wp_parse_args( $args, $defaults );
		$this->normalize_timezones( $args, $defaults );
		return $args;
	}

	/**
	 * Converts input datetime parameters to local timezone. If there are no inputs from the user in query_args,
	 * uses default from $defaults.
	 *
	 * @param array $query_args Array of query arguments.
	 * @param array $defaults Array of default values.
	 */
	protected function normalize_timezones( &$query_args, $defaults ) {
		$local_tz = new \DateTimeZone( wc_timezone_string() );
		foreach ( [ 'before', 'after' ] as $query_arg_key ) {
			if ( isset( $query_args[ $query_arg_key ] ) && is_string( $query_args[ $query_arg_key ] ) ) {
				// Assume that unspecified timezone is a local timezone.
				$datetime = new \DateTime( $query_args[ $query_arg_key ], $local_tz );
				// In case timezone was forced by using +HH:MM, convert to local timezone.
				$datetime->setTimezone( $local_tz );
				$query_args[ $query_arg_key ] = $datetime;
			} elseif ( isset( $query_args[ $query_arg_key ] ) && is_a( $query_args[ $query_arg_key ], 'DateTime' ) ) {
				// In case timezone is in other timezone, convert to local timezone.
				$query_args[ $query_arg_key ]->setTimezone( $local_tz );
			} else {
				$query_args[ $query_arg_key ] = isset( $defaults[ $query_arg_key ] ) ? $defaults[ $query_arg_key ] : null;
			}
		}
	}
}
