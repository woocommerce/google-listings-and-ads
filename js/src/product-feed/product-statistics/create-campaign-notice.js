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
	const { hasFinishedResolution: productsResolution, data: products } =
		useMCProductStatistics();
	const { loaded: campaignsLoaded, data: campaigns } = useAdsCampaigns();

	const isLoading =
		! productsResolution || products?.loading || ! campaignsLoaded;

	const inValidData =
		( productsResolution && ! products ) ||
		( campaignsLoaded && ! campaigns );

	if (
		isLoading ||
		inValidData ||
		products?.statistics?.active === 0 ||
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
					children={ __(
						'Create Campaign',
						'google-listings-and-ads'
					) }
				/>
			</section>
		</Notice>
	);
};

export default CreateCampaignNotice;
