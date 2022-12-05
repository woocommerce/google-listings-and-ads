/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from 'extracted/@wordpress/components';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import useAutoCheckBillingStatusEffect from './useAutoCheckBillingStatusEffect';
import './billing-setup-card.scss';

/**
 * Returns a string of window.open()'s features that aligns with the center of the current window.
 *
 * @param {Window} defaultView The window object.
 * @param {number} windowWidth Expected window width.
 * @param {number} windowHeight Expected window height.
 * @return {string} Centered alignment window features for calling with window.open().
 */
function getWindowFeatures( defaultView, windowWidth, windowHeight ) {
	const { innerWidth, innerHeight, screenX, screenY, screen } = defaultView;
	const width = Math.min( windowWidth, screen.availWidth );
	const height = Math.min( windowHeight, screen.availHeight );
	const left = ( innerWidth - width ) / 2 + screenX;
	const top = ( innerHeight - height ) / 2 + screenY;

	return `popup=1,left=${ left },top=${ top },width=${ width },height=${ height }`;
}

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
 * @param {Function} [props.onSetupComplete] Callback function when setup is completed
 * @return {JSX.Element} Card filled with content or `AppSpinner`.
 */
const BillingSetupCard = ( { billingUrl, onSetupComplete } ) => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	useAutoCheckBillingStatusEffect( onSetupComplete );

	if ( ! googleAdsAccount ) {
		return <AppSpinner />;
	}

	const handleClick = ( e ) => {
		const { defaultView } = e.target.ownerDocument;
		const features = getWindowFeatures( defaultView, 600, 800 );

		defaultView.open( billingUrl, '_blank', features );
	};

	return (
		<Section.Card className="gla-google-ads-billing-setup-card">
			<Section.Card.Body>
				<div className="gla-google-ads-billing-setup-card__description">
					{ __(
						'You do not have billing information set up in your Google Ads account. Once you have set up billing, you can start running ads.',
						'google-listings-and-ads'
					) }
					<div className="gla-google-ads-billing-setup-card__description__helper">
						{ createInterpolateElement(
							__(
								'You will be directed to Google Ads for this step. In case your browser is unable to open the pop-up, <link>click here instead</link>.',
								'google-listings-and-ads'
							),
							{ link: <ExternalLink href={ billingUrl } /> }
						) }
					</div>
				</div>
				<AppButton
					isSecondary
					onClick={ handleClick }
					eventName="gla_ads_set_up_billing_click"
					eventProps={ {
						context: 'setup-ads',
						link_id: 'set-up-billing',
						href: billingUrl,
					} }
				>
					{ __( 'Set up billing', 'google-listings-and-ads' ) }
				</AppButton>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default BillingSetupCard;
