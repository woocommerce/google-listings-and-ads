<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\ScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\StyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use PHPUnit\Framework\TestCase;

class AssetsHandlerTest extends TestCase {

	/**
	 * Build an Asset mock with no WP side effects.
	 *
	 * @param string      $handle      Handle to expose via get_handle().
	 * @param string|null $asset_class Concrete class to mock (defaults to ScriptAsset).
	 * @return Asset
	 */
	private function asset( string $handle, ?string $asset_class = null ): Asset {
		$asset_class = $asset_class ?? ScriptAsset::class;

		$mock = $this->getMockBuilder( $asset_class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_handle', 'register', 'enqueue', 'dequeue' ] )
			->getMock();
		$mock->method( 'get_handle' )->willReturn( $handle );

		return $mock;
	}

	public function test_registers_a_single_asset() {
		$handler = new AssetsHandler();
		$asset   = $this->asset( 'gla-foo' );
		$asset->expects( $this->once() )->method( 'register' );
		$asset->expects( $this->once() )->method( 'enqueue' );

		$handler->register( $asset );
		$handler->enqueue_handle( 'gla-foo' );
	}

	public function test_allows_script_and_style_with_same_handle() {
		$handler = new AssetsHandler();
		$script  = $this->asset( 'gla-foo', ScriptAsset::class );
		$style   = $this->asset( 'gla-foo', StyleAsset::class );

		$script->expects( $this->once() )->method( 'register' );
		$style->expects( $this->once() )->method( 'register' );

		$handler->register( $script );
		$handler->register( $style );

		// Reaching this point without an InvalidAsset exception is the assertion.
		$this->addToAssertionCount( 1 );
	}

	public function test_enqueue_handle_enqueues_all_assets_under_handle() {
		$handler = new AssetsHandler();
		$script  = $this->asset( 'gla-foo', ScriptAsset::class );
		$style   = $this->asset( 'gla-foo', StyleAsset::class );
		$handler->register( $script );
		$handler->register( $style );

		$script->expects( $this->once() )->method( 'enqueue' );
		$style->expects( $this->once() )->method( 'enqueue' );

		$handler->enqueue_handle( 'gla-foo' );
	}

	public function test_enqueue_asset_object_enqueues_all_assets_under_same_handle() {
		$handler = new AssetsHandler();
		$script  = $this->asset( 'gla-foo', ScriptAsset::class );
		$style   = $this->asset( 'gla-foo', StyleAsset::class );
		$handler->register( $script );
		$handler->register( $style );

		$script->expects( $this->once() )->method( 'enqueue' );
		$style->expects( $this->once() )->method( 'enqueue' );

		// Enqueuing just the script object also enqueues the style sharing its handle.
		$handler->enqueue( $script );
	}

	public function test_dequeue_handle_dequeues_all_assets_under_handle() {
		$handler = new AssetsHandler();
		$script  = $this->asset( 'gla-foo', ScriptAsset::class );
		$style   = $this->asset( 'gla-foo', StyleAsset::class );
		$handler->register( $script );
		$handler->register( $style );

		$script->expects( $this->once() )->method( 'dequeue' );
		$style->expects( $this->once() )->method( 'dequeue' );

		$handler->dequeue_handle( 'gla-foo' );
	}

	public function test_enqueue_all_enqueues_every_asset() {
		$handler = new AssetsHandler();
		$a       = $this->asset( 'gla-foo', ScriptAsset::class );
		$b       = $this->asset( 'gla-foo', StyleAsset::class );
		$c       = $this->asset( 'gla-bar', ScriptAsset::class );

		$handler->register( $a );
		$handler->register( $b );
		$handler->register( $c );

		$a->expects( $this->once() )->method( 'enqueue' );
		$b->expects( $this->once() )->method( 'enqueue' );
		$c->expects( $this->once() )->method( 'enqueue' );

		$handler->enqueue_all();
	}

	public function test_register_is_idempotent_for_same_handle_and_class() {
		$handler = new AssetsHandler();
		$first   = $this->asset( 'gla-foo', ScriptAsset::class );
		$second  = $this->asset( 'gla-foo', ScriptAsset::class );

		$first->expects( $this->once() )->method( 'register' );
		$second->expects( $this->never() )->method( 'register' );

		$handler->register( $first );
		// Re-registering the same handle + same class should silently no-op, matching
		// WordPress's own wp_register_script behavior.
		$handler->register( $second );

		$first->expects( $this->once() )->method( 'enqueue' );
		$second->expects( $this->never() )->method( 'enqueue' );

		$handler->enqueue_handle( 'gla-foo' );
	}

	public function test_enqueue_handle_with_unknown_handle_throws() {
		$handler = new AssetsHandler();

		$this->expectException( InvalidAsset::class );
		$handler->enqueue_handle( 'gla-unknown' );
	}

	public function test_dequeue_handle_with_unknown_handle_throws() {
		$handler = new AssetsHandler();

		$this->expectException( InvalidAsset::class );
		$handler->dequeue_handle( 'gla-unknown' );
	}

	public function test_register_many_handles_mixed_classes() {
		$handler = new AssetsHandler();
		$assets  = [
			$this->asset( 'gla-foo', ScriptAsset::class ),
			$this->asset( 'gla-foo', StyleAsset::class ),
			$this->asset( 'gla-bar', ScriptAsset::class ),
		];

		foreach ( $assets as $asset ) {
			$asset->expects( $this->once() )->method( 'register' );
		}

		$handler->register_many( $assets );
		$this->addToAssertionCount( 1 );
	}
}
