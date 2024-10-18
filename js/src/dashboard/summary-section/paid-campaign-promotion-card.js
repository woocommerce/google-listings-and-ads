/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import PaidFeatures from './paid-features';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

const PromotionContent = ( { adsAccount } ) => {
	const showFreeCredit =
		adsAccount.sub_account ||
		adsAccount.status === GOOGLE_ADS_ACCOUNT_STATUS.DISCONNECTED;

	return <PaidFeatures showFreeCredit={ showFreeCredit } />;
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
