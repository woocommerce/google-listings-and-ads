/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Icon } from '@wordpress/components';
import { external as externalIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import useAutoCheckBillingStatusEffect from './useAutoCheckBillingStatusEffect';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import { FILTER_ONBOARDING, recordGlaEvent } from '.~/utils/tracks';
import './billing-setup-card.scss';

/**
 * "Set up billing" button for Google Ads account is clicked.
 *
 * @event gla_ads_set_up_billing_click
 * @property {string} link_id A unique ID for the button within the context, e.g. `set-up-billing`.
 * @property {string} href Indicates the destination where the users is directed to.
 * @property {string} [context] Indicates the place where the button is located, e.g. `setup-mc` or `setup-ads`.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * @fires gla_ads_set_up_billing_click When the user clicks on the button to set up billing in Google Ads.
 * @param {Object} props React props.
 * @param {string} props.billingUrl The URL for setting the billing up in Google Ads
 * @param {Function} [props.onSetupComplete] Callback function when setup is completed
 * @return {JSX.Element} Card filled with content or `AppSpinner`.
 */
const BillingSetupCard = ( { billingUrl, onSetupComplete } ) => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	useAutoCheckBillingStatusEffect( onSetupComplete );

	if ( ! googleAdsAccount ) {
		return <AppSpinner />;
	}

	const handleClick = ( e ) => {
		const eventProps = getEventProps( {
			link_id: 'set-up-billing',
			href: billingUrl,
		} );

		recordGlaEvent( 'gla_ads_set_up_billing_click', eventProps );

		if ( e.currentTarget.nodeName === 'BUTTON' ) {
			const { defaultView } = e.target.ownerDocument;
			const features = getWindowFeatures( defaultView, 600, 800 );

			defaultView.open( billingUrl, '_blank', features );
		}
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
								'You will be directed to Google Ads for this step. In case your browser is unable to open the pop-up, <link>click here instead <icon /></link>.',
								'google-listings-and-ads'
							),
							{
								// compatibility-code "WP < 6.2" -- The following two components can be replaced with `ExternalLink` as it started supporting onClick since WordPress 6.2
								link: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a
										target="_blank"
										rel="external noreferrer noopener"
										href={ billingUrl }
										onClick={ handleClick }
									/>
								),
								icon: (
									<Icon icon={ externalIcon } size={ 12 } />
								),
							}
						) }
					</div>
				</div>
				<AppButton isSecondary onClick={ handleClick }>
					{ __( 'Set up billing', 'google-listings-and-ads' ) }
				</AppButton>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default BillingSetupCard;
