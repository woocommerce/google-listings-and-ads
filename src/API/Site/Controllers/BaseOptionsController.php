<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

defined( 'ABSPATH' ) || exit;

/**
 * Class BaseOptionsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
abstract class BaseOptionsController extends BaseController {

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer       $server
	 * @param OptionsInterface $options
	 */
	public function __construct( RESTServer $server, OptionsInterface $options ) {
		parent::__construct( $server );
		$this->options = $options;
	}
}
