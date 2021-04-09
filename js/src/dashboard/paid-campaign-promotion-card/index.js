/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardHeader } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import './index.scss';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';

const PromotionContent = ( { adsAccount } ) => {
	const showFreeCredit =
		adsAccount.sub_account || adsAccount.status === 'disconnected';

	return (
		<>
			<p className="gla-paid-campaign-promotion-card__text">
				{ showFreeCredit
					? __(
							'Create your first campaign and get up to $150* free',
							'google-listings-and-ads'
					  )
					: __(
							'Create your first campaign',
							'google-listings-and-ads'
					  ) }
			</p>
			<AddPaidCampaignButton
				eventProps={ { context: 'add-paid-campaign-promotion' } }
			/>
		</>
	);
};

function PaidCampaignPromotionCard( { title } ) {
	const { googleAdsAccount } = useGoogleAdsAccount();

	return (
		<Card className="gla-paid-campaign-promotion-card">
			<CardHeader size="medium">{ title }</CardHeader>
			<div className="gla-paid-campaign-promotion-card__body">
				{ googleAdsAccount ? (
					<PromotionContent adsAccount={ googleAdsAccount } />
				) : (
					<Spinner />
				) }
			</div>
		</Card>
	);
}

export default PaidCampaignPromotionCard;
