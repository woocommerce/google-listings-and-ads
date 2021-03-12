/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import AccountId from '.~/components/account-id';
import TitleButtonLayout from '.~/components/title-button-layout';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import './index.scss';
import AppButton from '.~/components/app-button';

const SetupCard = ( props ) => {
	const { billingUrl } = props;
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! googleAdsAccount ) {
		return <AppSpinner />;
	}

	const handleSetupBillingClick = () => {
		window.open( billingUrl, '_blank' );
	};

	return (
		<div className="gla-google-ads-billing-setup-card">
			<Section.Card>
				<Section.Card.Body>
					<div className="gla-google-ads-billing-setup-card__account-number">
						<TitleButtonLayout
							title={ <AccountId id={ googleAdsAccount.id } /> }
						/>
					</div>
					<div className="gla-google-ads-billing-setup-card__description">
						<div>
							{ __(
								'You do not have billing information set up in your Google Ads account. Once you have completed your billing setup, your campaign will launch automatically.',
								'google-listings-and-ads'
							) }
						</div>
						<AppButton
							isSecondary
							onClick={ handleSetupBillingClick }
						>
							{ __(
								'Set up billing',
								'google-listings-and-ads'
							) }
						</AppButton>
					</div>
				</Section.Card.Body>
			</Section.Card>
		</div>
	);
};

export default SetupCard;
