/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AddPaidCampaignButton from '~/components/paid-ads/add-paid-campaign-button';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import './index.scss';

/**
 * Suggests creating a Google Ads campaign when Google approved some products
 * and the store has no campaign yet.
 *
 * @param {Object} props
 * @param {number} [props.approvedCount] Products approved by Google, once known.
 */
const CreateCampaignNotice = ( { approvedCount } ) => {
	const { loaded: campaignsLoaded, data: campaigns } = useAdsCampaigns();

	if ( ! approvedCount || ! campaignsLoaded || campaigns?.length > 0 ) {
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
					{ __( 'Create Campaign', 'google-listings-and-ads' ) }
				</AddPaidCampaignButton>
			</FlexItem>
		</Flex>
	);
};

export default CreateCampaignNotice;
