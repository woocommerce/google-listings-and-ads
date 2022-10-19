/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

const PromotionContent = ( { adsAccount } ) => {
	const showFreeCredit =
		adsAccount.sub_account ||
		adsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED;

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

function PaidCampaignPromotionCard() {
	const { googleAdsAccount } = useGoogleAdsAccount();

	return (
		<div className="gla-summary-card__body">
			{ googleAdsAccount ? (
				<PromotionContent adsAccount={ googleAdsAccount } />
			) : (
				<Spinner />
			) }
		</div>
	);
}

export default PaidCampaignPromotionCard;
