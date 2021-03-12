/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconCreditCard from 'gridicons/dist/credit-card';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import AccountId from '.~/components/account-id';
import TitleButtonLayout from '.~/components/title-button-layout';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import './index.scss';

const BillingSavedCard = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! googleAdsAccount ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-google-ads-billing-saved-card">
			<Section.Card>
				<Section.Card.Body>
					<div className="gla-google-ads-billing-saved-card__account-number">
						<TitleButtonLayout
							title={ <AccountId id={ googleAdsAccount.id } /> }
						/>
					</div>
					<div className="gla-google-ads-billing-saved-card__description">
						<GridiconCreditCard />
						<div>
							{ __(
								'Great! You already have billing information saved for this Google Ads account.',
								'google-listings-and-ads'
							) }
						</div>
					</div>
				</Section.Card.Body>
			</Section.Card>
		</div>
	);
};

export default BillingSavedCard;
