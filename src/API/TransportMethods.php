<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API;

/**
 * Interface TransportMethods
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API
 */
interface TransportMethods {

	/**
	 * Alias for GET transport method.
	 *
	 * @var string
	 */
	public const READABLE = 'GET';

	/**
	 * Alias for POST transport method.
	 *
	 * @var string
	 */
	public const CREATABLE = 'POST';

	/**
	 * Alias for POST, PUT, PATCH transport methods together.
	 *
	 * @var string
	 */
	public const EDITABLE = 'POST, PUT, PATCH';

	/**
	 * Alias for DELETE transport method.
	 *
	 * @var string
	 */
	public const DELETABLE = 'DELETE';

	/**
	 * Alias for GET, POST, PUT, PATCH & DELETE transport methods together.
	 *
	 * @var string
	 */
	public const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
}
