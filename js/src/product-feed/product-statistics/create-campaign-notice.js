/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';

const CreateCampaignNotice = () => {
	const { hasFinishedResolution, data: products } = useMCProductStatistics();
	const { loaded: campaignsLoaded, data: campaigns } = useAdsCampaigns();

	const isLoading =
		! hasFinishedResolution || products?.loading || ! campaignsLoaded;

	if (
		isLoading ||
		! products ||
		products?.statistics?.active === 0 ||
		! campaigns ||
		campaigns?.length > 0
	) {
		return null;
	}

	return (
		<Notice className="gla-create-campaign-notice" isDismissible={ false }>
			<section>
				<p>
					{ __(
						'You have approved products. Create a Google Ads campaign to reach more customers and drive more sales.',
						'google-listings-and-ads'
					) }
				</p>
				<AddPaidCampaignButton
					eventProps={ {
						context: 'product-feed-overview-promotion',
					} }
				>
					{ __( 'Create Campaign', 'google-listings-and-ads' ) }{ ' ' }
				</AddPaidCampaignButton>
			</section>
		</Notice>
	);
};

export default CreateCampaignNotice;
