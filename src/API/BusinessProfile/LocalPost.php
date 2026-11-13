<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPost
 *
 * Payload for the Business Profile `accounts.locations.localPosts.create` endpoint (v4).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class LocalPost {

	/** @var string|null Resource name, populated on the response. */
	protected $name;

	/** @var string */
	protected $summary;

	/** @var string */
	protected $language_code;

	/** @var string */
	protected $call_to_action_url;

	/** @var string */
	protected $media_url;

	/** @var string|null */
	protected $search_url;

	/**
	 * LocalPost constructor.
	 *
	 * @param string $summary            The merchant-edited text to send as the post body.
	 * @param string $language_code      BCP-47 language code.
	 * @param string $call_to_action_url The post's canonical public permalink.
	 * @param string $media_url          The post's featured image URL, or an empty string when it has none.
	 */
	public function __construct(
		string $summary,
		string $language_code,
		string $call_to_action_url,
		string $media_url = ''
	) {
		$this->summary            = $summary;
		$this->language_code      = $language_code;
		$this->call_to_action_url = $call_to_action_url;
		$this->media_url          = $media_url;
	}

	/**
	 * Hydrate from a `LocalPost` response array.
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$instance = new self(
			$data['summary'] ?? '',
			$data['languageCode'] ?? '',
			$data['callToAction']['url'] ?? '',
			// A freshly created post is still PROCESSING and Google hasn't hosted the image
			// yet, so `create` returns `sourceUrl`; a later `get`/`patch` response carries
			// `googleUrl` once Google has it.
			$data['media'][0]['googleUrl'] ?? $data['media'][0]['sourceUrl'] ?? ''
		);

		$instance->name       = $data['name'] ?? null;
		$instance->search_url = $data['searchUrl'] ?? null;

		return $instance;
	}

	/**
	 * Serialize to an array for an outbound request body.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$data = [
			'languageCode' => $this->language_code,
			'summary'      => $this->summary,
			'topicType'    => 'STANDARD',
			'callToAction' => [
				'actionType' => 'LEARN_MORE',
				'url'        => $this->call_to_action_url,
			],
		];

		if ( '' !== $this->media_url ) {
			$data['media'] = [
				[
					'mediaFormat' => 'PHOTO',
					'sourceUrl'   => $this->media_url,
				],
			];
		}

		return $data;
	}

	/**
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_summary(): string {
		return $this->summary;
	}

	/**
	 * @return string
	 */
	public function get_language_code(): string {
		return $this->language_code;
	}

	/**
	 * @return string
	 */
	public function get_call_to_action_url(): string {
		return $this->call_to_action_url;
	}

	/**
	 * @return string
	 */
	public function get_media_url(): string {
		return $this->media_url;
	}

	/**
	 * @return string|null
	 */
	public function get_search_url(): ?string {
		return $this->search_url;
	}
}
