<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class RequestReviewStatusesTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class RequestReviewStatusesTest extends UnitTest {

	/** @var RequestReviewStatuses */
	protected $statuses;

	public function setUp(): void {
		parent::setUp();
		$this->statuses = new RequestReviewStatuses();
	}

	/**
	 * Wrap a list of issues in a renderaccountissues response.
	 *
	 * @param array $issues Rendered issues.
	 *
	 * @return array
	 */
	private function response( array $issues ): array {
		return [ 'renderedIssues' => $issues ];
	}

	/**
	 * Build a single rendered issue.
	 *
	 * @param string $severity Issue impact severity.
	 * @param string $title    Issue title.
	 * @param array  $actions  Actions attached to the issue.
	 *
	 * @return array
	 */
	private function issue( string $severity, string $title, array $actions = [] ): array {
		return [
			'title'   => $title,
			'impact'  => [ 'severity' => $severity ],
			'actions' => $actions,
		];
	}

	public function test_error_issue_is_disapproved() {
		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended' ) ] )
		);

		$this->assertSame( RequestReviewStatuses::DISAPPROVED, $result['status'] );
		$this->assertSame( [ 'Suspended' ], $result['issues'] );
		$this->assertNull( $result['reviewAction'] );
	}

	public function test_warning_issue_is_warning() {
		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_WARNING, 'Limited' ) ] )
		);

		$this->assertSame( RequestReviewStatuses::WARNING, $result['status'] );
	}

	public function test_no_issues_is_approved() {
		$result = $this->statuses->get_statuses_from_response( $this->response( [] ) );

		$this->assertSame( RequestReviewStatuses::APPROVED, $result['status'] );
		$this->assertSame( [], $result['issues'] );
		$this->assertNull( $result['reviewAction'] );
	}

	public function test_missing_rendered_issues_key_is_approved() {
		$result = $this->statuses->get_statuses_from_response( [] );

		$this->assertSame( RequestReviewStatuses::APPROVED, $result['status'] );
		$this->assertSame( [], $result['issues'] );
	}

	public function test_error_takes_precedence_over_warning() {
		$result = $this->statuses->get_statuses_from_response(
			$this->response(
				[
					$this->issue( RequestReviewStatuses::SEVERITY_WARNING, 'Limited' ),
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended' ),
				]
			)
		);

		$this->assertSame( RequestReviewStatuses::DISAPPROVED, $result['status'] );
	}

	public function test_dedupes_titles_and_skips_empty() {
		$result = $this->statuses->get_statuses_from_response(
			$this->response(
				[
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended' ),
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended' ),
					$this->issue( RequestReviewStatuses::SEVERITY_INFO, '' ),
				]
			)
		);

		$this->assertSame( [ 'Suspended' ], $result['issues'] );
	}

	public function test_excludes_info_severity_titles() {
		$result = $this->statuses->get_statuses_from_response(
			$this->response(
				[
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended' ),
					$this->issue( RequestReviewStatuses::SEVERITY_INFO, 'Just so you know' ),
				]
			)
		);

		$this->assertSame( [ 'Suspended' ], $result['issues'] );
	}

	public function test_finds_in_app_action() {
		$action = [
			'isAvailable'            => true,
			'buttonLabel'            => 'Request review',
			'builtinUserInputAction' => [
				'actionContext' => 'ctx-token',
				'flows'         => [ [ 'id' => 'flow-1' ] ],
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended', [ $action ] ) ] )
		);

		$this->assertSame(
			[
				'type'          => 'in_app',
				'isAvailable'   => true,
				'buttonLabel'   => 'Request review',
				'actionContext' => 'ctx-token',
				'flowId'        => 'flow-1',
				'inputValues'   => [],
			],
			$result['reviewAction']
		);
	}

	public function test_in_app_action_builds_checkbox_input_values() {
		$action = [
			'isAvailable'            => true,
			'buttonLabel'            => 'Request review',
			'builtinUserInputAction' => [
				'actionContext' => 'ctx-token',
				'flows'         => [
					[
						'id'     => 'flow-1',
						'inputs' => [
							[
								'id'        => 'notes',
								'textInput' => [],
							],
							[
								'id'            => 'confirm',
								'required'      => true,
								'checkboxInput' => [],
							],
							[
								'id'            => 'optin',
								'required'      => false,
								'checkboxInput' => [],
							],
						],
					],
				],
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended', [ $action ] ) ] )
		);

		// Every checkbox is confirmed regardless of its required flag; the text field carries no
		// server-side value.
		$this->assertSame(
			[
				[
					'inputFieldId'       => 'confirm',
					'checkboxInputValue' => [ 'value' => true ],
				],
				[
					'inputFieldId'       => 'optin',
					'checkboxInputValue' => [ 'value' => true ],
				],
			],
			$result['reviewAction']['inputValues']
		);
	}

	public function test_finds_redirect_action() {
		$action = [
			'isAvailable'    => true,
			'buttonLabel'    => 'Request review',
			'externalAction' => [
				'type' => RequestReviewStatuses::EXTERNAL_REVIEW_ACTION,
				'uri'  => 'https://merchants.google.com/review',
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended', [ $action ] ) ] )
		);

		$this->assertSame(
			[
				'type'        => 'redirect',
				'isAvailable' => true,
				'buttonLabel' => 'Request review',
				'uri'         => 'https://merchants.google.com/review',
			],
			$result['reviewAction']
		);
	}

	public function test_skips_in_app_action_without_flow() {
		$action = [
			'isAvailable'            => true,
			'builtinUserInputAction' => [
				'actionContext' => 'ctx-token',
				'flows'         => [],
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended', [ $action ] ) ] )
		);

		$this->assertNull( $result['reviewAction'] );
	}

	public function test_is_available_defaults_to_false() {
		$action = [
			'builtinUserInputAction' => [
				'actionContext' => 'ctx-token',
				'flows'         => [ [ 'id' => 'flow-1' ] ],
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response( [ $this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Suspended', [ $action ] ) ] )
		);

		$this->assertFalse( $result['reviewAction']['isAvailable'] );
	}

	public function test_finds_review_action_across_issues() {
		$in_app = [
			'isAvailable'            => true,
			'builtinUserInputAction' => [
				'actionContext' => 'ctx-token',
				'flows'         => [ [ 'id' => 'flow-1' ] ],
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response(
				[
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'First', [] ),
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Second', [ $in_app ] ),
				]
			)
		);

		$this->assertSame( 'in_app', $result['reviewAction']['type'] );
	}

	public function test_finds_redirect_action_across_issues() {
		$redirect = [
			'isAvailable'    => true,
			'externalAction' => [
				'type' => RequestReviewStatuses::EXTERNAL_REVIEW_ACTION,
				'uri'  => 'https://merchants.google.com/review',
			],
		];

		$result = $this->statuses->get_statuses_from_response(
			$this->response(
				[
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'First', [] ),
					$this->issue( RequestReviewStatuses::SEVERITY_ERROR, 'Second', [ $redirect ] ),
				]
			)
		);

		$this->assertSame( 'redirect', $result['reviewAction']['type'] );
	}

	public function test_account_review_lifetime_is_filterable() {
		$this->assertSame(
			RequestReviewStatuses::MC_ACCOUNT_REVIEW_LIFETIME,
			$this->statuses->get_account_review_lifetime()
		);

		add_filter( 'woocommerce_gla_mc_account_review_lifetime', fn() => 60 );
		$this->assertSame( 60, $this->statuses->get_account_review_lifetime() );
		remove_all_filters( 'woocommerce_gla_mc_account_review_lifetime' );
	}
}
