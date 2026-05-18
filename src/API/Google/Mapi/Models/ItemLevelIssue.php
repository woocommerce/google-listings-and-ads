<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class ItemLevelIssue
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models
 */
class ItemLevelIssue {

	/** @var string|null */
	protected $code;

	/** @var string|null */
	protected $description;

	/** @var string|null */
	protected $detail;

	/** @var string|null */
	protected $documentation;

	/** @var string|null */
	protected $resolution;

	/** @var string|null */
	protected $severity;

	/** @var string[] */
	protected $applicable_countries = [];

	/**
	 * Hydrate from a MAPI response array.
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$instance                       = new self();
		$instance->code                 = $data['code'] ?? null;
		$instance->description          = $data['description'] ?? null;
		$instance->detail               = $data['detail'] ?? null;
		$instance->documentation        = $data['documentation'] ?? null;
		$instance->resolution           = $data['resolution'] ?? null;
		$instance->severity             = $data['severity'] ?? null;
		$instance->applicable_countries = $data['applicableCountries'] ?? [];

		return $instance;
	}

	/**
	 * @return string|null
	 */
	public function get_code(): ?string {
		return $this->code;
	}

	/**
	 * @return string|null
	 */
	public function get_description(): ?string {
		return $this->description;
	}

	/**
	 * @return string|null
	 */
	public function get_detail(): ?string {
		return $this->detail;
	}

	/**
	 * @return string|null
	 */
	public function get_documentation(): ?string {
		return $this->documentation;
	}

	/**
	 * @return string|null
	 */
	public function get_resolution(): ?string {
		return $this->resolution;
	}

	/**
	 * @return string|null
	 */
	public function get_severity(): ?string {
		return $this->severity;
	}

	/**
	 * @return string[]
	 */
	public function get_applicable_countries(): array {
		return $this->applicable_countries;
	}
}
