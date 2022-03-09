<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Helpers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;

class ExtendedBaseController extends BaseController {

	public function __construct( $server ) {
		parent::__construct( $server );
	}

	protected function get_schema_properties(): array {
		return [];
	}

	protected function get_schema_title(): string {
		return '';
	}

	public function get_matched_data_with_schema( $schema, $data ) {
		return $this->match_data_with_schema( $schema, $data );
	}


}

