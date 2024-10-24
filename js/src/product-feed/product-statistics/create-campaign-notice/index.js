/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import './index.scss';

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
		<Flex className="gla-ads-inline-notice">
			<FlexItem>
				<p>
					{ __(
						'You have approved products. Create a Google Ads campaign to reach more customers and drive more sales.',
						'google-listings-and-ads'
					) }
				</p>
				<AddPaidCampaignButton
					isSmall={ false }
					eventProps={ {
						context: 'product-feed-overview-promotion',
					} }
				>
					{ __( 'Create Campaign', 'google-listings-and-ads' ) }{ ' ' }
				</AddPaidCampaignButton>
			</FlexItem>
		</Flex>
	);
};

export default CreateCampaignNotice;
