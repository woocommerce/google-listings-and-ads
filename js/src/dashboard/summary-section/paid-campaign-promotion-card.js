/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import SummaryCard from './summary-card';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';

const PromotionContent = ( { adsAccount } ) => {
	const showFreeCredit =
		adsAccount.sub_account || adsAccount.status === 'disconnected';

	return (
		<>
			<p>
				{ showFreeCredit
					? __(
							'Create your first campaign and get $500 in ad credit*',
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
		<SummaryCard title={ title }>
			<div className="gla-summary-card__body">
				{ googleAdsAccount ? (
					<PromotionContent adsAccount={ googleAdsAccount } />
				) : (
					<Spinner />
				) }
			</div>
		</SummaryCard>
	);
}

export default PaidCampaignPromotionCard;
