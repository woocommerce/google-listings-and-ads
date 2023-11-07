<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler;

use ActionScheduler_AsyncRequest_QueueRunner as QueueRunnerAsyncRequest;
use ActionScheduler_Lock;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class AsyncActionRunner
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler
 */
class AsyncActionRunner implements Service {

	/**
	 * Whether the shutdown hook has been attached.
	 *
	 * @var bool
	 */
	protected $has_attached_shutdown_hook = false;

	/**
	 * @var QueueRunnerAsyncRequest
	 */
	protected $async_request;

	/**
	 * @var ActionScheduler_Lock
	 */
	protected $locker;

	/**
	 * AsyncActionRunner constructor.
	 *
	 * @param QueueRunnerAsyncRequest $async_request
	 * @param ActionScheduler_Lock    $locker
	 */
	public function __construct( QueueRunnerAsyncRequest $async_request, ActionScheduler_Lock $locker ) {
		$this->async_request = $async_request;
		$this->locker        = $locker;
	}

	/**
	 * Attach async runner shutdown hook before ActionScheduler shutdown hook.
	 *
	 * The shutdown hook should only be attached if an async event has been created in the current request.
	 * The hook is only attached if it hasn't already been attached.
	 *
	 * @see ActionScheduler_QueueRunner::hook_dispatch_async_request
	 */
	public function attach_shutdown_hook() {
		if ( $this->has_attached_shutdown_hook ) {
			return;
		}

		$this->has_attached_shutdown_hook = true;
		add_action( 'shutdown', [ $this, 'maybe_dispatch_async_request' ], 9 );
	}

	/**
	 * Dispatches an async queue runner request if various conditions are met.
	 *
	 * Note: This is a temporary solution. In the future (probably ActionScheduler 3.2) we should use the filter
	 * added in https://github.com/woocommerce/action-scheduler/pull/628.
	 */
	public function maybe_dispatch_async_request() {
		if ( is_admin() ) {
			// ActionScheduler will dispatch an async runner request on it's own.
			return;
		}

		if ( $this->locker->is_locked( 'async-request-runner' ) ) {
			// An async runner request has already occurred in the last 60 seconds.
			return;
		}

		$this->locker->set( 'async-request-runner' );
		$this->async_request->maybe_dispatch();
	}
}
