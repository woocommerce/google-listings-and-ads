<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignLabelQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\V16\ResourceNames;
use Google\Ads\GoogleAds\V16\Resources\Label;
use Google\Ads\GoogleAds\V16\Resources\CampaignLabel;
use Google\Ads\GoogleAds\V16\Services\LabelOperation;
use Google\Ads\GoogleAds\V16\Services\CampaignLabelOperation;
use Google\Ads\GoogleAds\V16\Services\MutateOperation;
use Google\Ads\GoogleAds\V16\Services\MutateGoogleAdsRequest;

/**
 * Class AdsCampaignLabel
 * https://developers.google.com/google-ads/api/docs/reporting/labels
 *
 * @since 2.8.1
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignLabel implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -1;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;


	/**
	 * AdsCampaignLabel constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get the label ID by name.
	 *
	 * @param string $name The label name.
	 *
	 * @return null|int The label ID.
	 *
	 * @throws ApiException If the search call fails.
	 */
	protected function get_label_id_by_name( string $name ) {
		$query = new AdsCampaignLabelQuery();
		$query->set_client( $this->client, $this->options->get_ads_id() );
		$query->where( 'label.name', $name, '=' );
		$label_results = $query->get_results();

		foreach ( $label_results->iterateAllElements() as $row ) {
			return $row->getLabel()->getId();
		}

		return null;
	}

	/**
	 * Assign a label to a campaign by label name.
	 *
	 * @param int    $campaign_id The campaign ID.
	 * @param string $label_name  The label name.
	 *
	 * @throws ApiException If searching for the label fails.
	 */
	public function assign_label_to_campaign_by_label_name( int $campaign_id, string $label_name ) {
		$label_id   = $this->get_label_id_by_name( $label_name );
		$operations = [];

		if ( ! $label_id ) {
			$operations[] = $this->create_operation( $label_name );
			$label_id     = self::TEMPORARY_ID;
		}

		$operations[] = $this->assign_label_to_campaign_operation( $campaign_id, $label_id );
		$this->mutate( $operations );
	}

	/**
	 * Create a label operation.
	 *
	 * @param string $name The label name.
	 *
	 * @return MutateOperation
	 */
	protected function create_operation( string $name ): MutateOperation {
		$label = new Label(
			[
				'name'          => $name,
				'resource_name' => $this->temporary_resource_name(),
			]
		);

		$operation = ( new LabelOperation() )->setCreate( $label );
		return ( new MutateOperation() )->setLabelOperation( $operation );
	}

	/**
	 * Return a temporary resource name for the label.
	 *
	 * @return string
	 */
	protected function temporary_resource_name() {
		return ResourceNames::forLabel( $this->options->get_ads_id(), self::TEMPORARY_ID );
	}

	/**
	 * Creates a campaign label operation.
	 *
	 * @param int $campaign_id The campaign ID.
	 * @param int $label_id    The label ID.
	 *
	 * @return MutateOperation
	 */
	protected function assign_label_to_campaign_operation( int $campaign_id, int $label_id ): MutateOperation {
		$label_resource_name = ResourceNames::forLabel( $this->options->get_ads_id(), $label_id );

		$campaign_label = new CampaignLabel(
			[
				'campaign' => ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id ),
				'label'    => $label_resource_name,
			]
		);

		$operation = ( new CampaignLabelOperation() )->setCreate( $campaign_label );
		return ( new MutateOperation() )->setCampaignLabelOperation( $operation );
	}

	/**
	 * Mutate the operations.
	 *
	 * @param array $operations The operations to mutate.
	 *
	 * @throws ApiException — Thrown if the API call fails.
	 */
	protected function mutate( array $operations ) {
		$request = new MutateGoogleAdsRequest();
		$request->setCustomerId( $this->options->get_ads_id() );
		$request->setMutateOperations( $operations );
		$this->client->getGoogleAdsServiceClient()->mutate( $request );
	}
}
