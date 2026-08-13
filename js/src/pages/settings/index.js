/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { API_RESPONSE_CODES } from '~/constants';
import useMenuEffect from '~/hooks/useMenuEffect';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useUpdateRestAPIAuthorizeStatusByUrlQuery from '~/hooks/useUpdateRestAPIAuthorizeStatusByUrlQuery';
import { subpaths, getReconnectAccountUrl } from '~/utils/urls';
import { ContactInformationPreview } from '~/components/contact-information';
import TargetAudienceSection from '~/components/target-audience-section';
import SetupTaxRate from './setup-tax-rate';
import ShippingRateSettings from './shipping-rate-settings';
import LinkedAccounts from './linked-accounts';
import ReconnectWPComAccount from './reconnect-wpcom-account';
import ReconnectGoogleAccount from './reconnect-google-account';
import EditStoreAddress from './edit-store-address';
import MainTabNav from '~/components/main-tab-nav';
import RebrandingTour from '~/components/tours/rebranding-tour';
import SetupEnhancedConversions from './enhanced-conversions/setup-enhanced-conversions';
import { GoogleCustomerReviewsSettings } from './reviews';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import { useAppDispatch } from '~/data';
import './index.scss';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 */

const pageClassName = 'gla-settings';

/**
 * Settings page component.
 *
 * @return {JSX.Element} The settings page component.
 */
const Settings = () => {
	const { subpath } = getQuery();
	// Make the component highlight GLA entry in the WC legacy menu.
	useMenuEffect();

	useUpdateRestAPIAuthorizeStatusByUrlQuery();

	const { google } = useGoogleAccount();
	const isReconnectGooglePage = subpath === subpaths.reconnectGoogleAccount;
	const { hasFinishedResolution, hasGoogleMCConnection } =
		useGoogleMCAccount();
	const { targetAudience, getFinalCountries } =
		useTargetAudienceFinalCountryCodes();
	const { saveTargetAudience } = useAppDispatch();
	const initTargetAudience = targetAudience?.location ? targetAudience : null;

	/**
	 * Callback called with new data once target audience data is changed.
	 *
	 * @param {TargetAudienceData} targetAudienceData Target audience data to be saved.
	 */
	const onTargetAudienceChange = ( targetAudienceData ) => {
		const hasNoCountriesSelected =
			targetAudienceData.location === 'selected' &&
			( ! targetAudienceData.countries ||
				targetAudienceData.countries.length === 0 );

		if ( ! hasNoCountriesSelected ) {
			saveTargetAudience( targetAudienceData );
		}
	};

	// This page wouldn't get any 401 response when losing Google account access,
	// so we still need to detect it here.
	useEffect( () => {
		if ( ! isReconnectGooglePage && google?.active === 'no' ) {
			getHistory().replace(
				getReconnectAccountUrl( API_RESPONSE_CODES.GOOGLE_DISCONNECTED )
			);
		}
	}, [ isReconnectGooglePage, google ] );

	// Navigate to subpath if any.
	switch ( subpath ) {
		case subpaths.reconnectWPComAccount:
			return (
				<div className={ pageClassName }>
					<ReconnectWPComAccount />
				</div>
			);
		case subpaths.reconnectGoogleAccount:
			return <ReconnectGoogleAccount />;
		case subpaths.editStoreAddress:
			return <EditStoreAddress />;
		default:
	}

	const shouldShowTargetAudienceSection =
		! hasGoogleMCConnection && hasFinishedResolution;

	return (
		<div className={ pageClassName }>
			<ExperienceRatingBanner />
			<MainTabNav />
			<RebrandingTour />
			<SetupEnhancedConversions />
			{ shouldShowTargetAudienceSection && (
				<TargetAudienceSection
					targetAudience={ initTargetAudience }
					resolveFinalCountries={ getFinalCountries }
					onTargetAudienceChange={ onTargetAudienceChange }
				/>
			) }
			{ hasGoogleMCConnection && (
				<>
					<GoogleCustomerReviewsSettings />
					<ContactInformationPreview />
					<ShippingRateSettings />
					<SetupTaxRate />
				</>
			) }
			<LinkedAccounts />
		</div>
	);
};

export default Settings;
