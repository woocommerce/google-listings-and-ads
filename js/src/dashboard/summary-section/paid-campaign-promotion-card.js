/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import PaidFeatures from './paid-features';

function PaidCampaignPromotionCard() {
	const { googleAdsAccount } = useGoogleAdsAccount();

	return (
		<div className="gla-summary-card__body">
			{ googleAdsAccount ? <PaidFeatures /> : <Spinner /> }
		</div>
	);
}

export default PaidCampaignPromotionCard;
