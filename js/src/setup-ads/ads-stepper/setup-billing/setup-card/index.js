/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AppSpinner from '.~/components/app-spinner';
import TitleButtonLayout from '.~/components/title-button-layout';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import './index.scss';
import AppButton from '.~/components/app-button';
import useAutoCheckBillingStatusEffect from './useAutoCheckBillingStatusEffect';

/**
 * "Set up billing" button for Google Ads account is clicked.
 *
 * @event gla_ads_set_up_billing_click
 * @property {string} context Indicates the place where the button is located, e.g. `setup-ads`.
 * @property {string} link_id A unique ID for the button within the context, e.g. `set-up-billing`.
 * @property {string} href Indicates the destination where the users is directed to.
 */

/**
 * @fires gla_ads_set_up_billing_click with `{ context: 'setup-ads', link_id: 'set-up-billing',	href: billingUrl }`
 * @param {Object} props React props.
 * @param {string} props.billingUrl The URL for setting the billing up in Google Ads
 * @param {Function} props.onSetupComplete Callback function when setup is completed
 * @return {JSX.Element} Card filled with content or `AppSpinner`.
 */
const SetupCard = ( { billingUrl, onSetupComplete } ) => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	useAutoCheckBillingStatusEffect( onSetupComplete );

	if ( ! googleAdsAccount ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-google-ads-billing-setup-card">
			<Section.Card>
				<Section.Card.Body>
					<div className="gla-google-ads-billing-setup-card__account-number">
						<TitleButtonLayout
							title={ toAccountText( googleAdsAccount.id ) }
						/>
					</div>
					<div className="gla-google-ads-billing-setup-card__description">
						<div className="gla-google-ads-billing-setup-card__description__text">
							{ __(
								'You do not have billing information set up in your Google Ads account. Once you have completed your billing setup, your campaign will launch automatically.',
								'google-listings-and-ads'
							) }
						</div>
						<AppButton
							isSecondary
							href={ billingUrl }
							target="_blank"
							eventName="gla_ads_set_up_billing_click"
							eventProps={ {
								context: 'setup-ads',
								link_id: 'set-up-billing',
								href: billingUrl,
							} }
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
