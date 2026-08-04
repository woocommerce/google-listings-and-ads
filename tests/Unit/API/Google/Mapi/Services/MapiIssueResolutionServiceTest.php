<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiIssueResolutionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiIssueResolutionServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiIssueResolutionServiceTest extends UnitTest {

	protected const MERCHANT_ID  = 12345;
	protected const RENDER_PATH  = 'issueresolution/v1/accounts/12345:renderaccountissues';
	protected const TRIGGER_PATH = 'issueresolution/v1/accounts/12345:triggeraction';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiIssueResolutionService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiIssueResolutionService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_render_account_issues_defaults_to_in_app() {
		$response = [ 'renderedIssues' => [ [ 'title' => 'Issue one' ] ] ];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::RENDER_PATH,
				[
					'userInputActionOption' => MapiIssueResolutionService::USER_INPUT_BUILT_IN,
					'contentOption'         => MapiIssueResolutionService::CONTENT_PRE_RENDERED_HTML,
				]
			)
			->willReturn( $response );

		$this->assertSame( $response, $this->service->render_account_issues() );
	}

	public function test_render_account_issues_with_redirect_option() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::RENDER_PATH,
				[
					'userInputActionOption' => MapiIssueResolutionService::USER_INPUT_REDIRECT,
					'contentOption'         => MapiIssueResolutionService::CONTENT_PRE_RENDERED_HTML,
				]
			)
			->willReturn( [ 'renderedIssues' => [] ] );

		$this->service->render_account_issues( MapiIssueResolutionService::USER_INPUT_REDIRECT );
	}

	public function test_render_account_issues_appends_language_code() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( self::RENDER_PATH . '?languageCode=en-US', $this->anything() )
			->willReturn( [ 'renderedIssues' => [] ] );

		$this->service->render_account_issues( MapiIssueResolutionService::USER_INPUT_BUILT_IN, 'en-US' );
	}

	public function test_trigger_action_wraps_action_input() {
		$response = [ 'name' => 'accounts/12345/action' ];

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				self::TRIGGER_PATH,
				[
					'actionContext' => 'ctx-token',
					'actionInput'   => [
						'actionFlowId' => 'flow-1',
						'inputValues'  => [],
					],
				]
			)
			->willReturn( $response );

		$this->assertSame(
			$response,
			$this->service->trigger_action( 'ctx-token', 'flow-1' )
		);
	}
}
