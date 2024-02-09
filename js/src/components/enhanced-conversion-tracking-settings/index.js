/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ENHANCED_ADS_CONVERSION_STATUS,
	GOOGLE_ADS_ACCOUNT_STATUS,
} from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppSpinner from '.~/components/app-spinner';
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppButton from '../app-button';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import PendingStatus from './PendingStatus';

const DESCRIPTION = (
	<p>
		{ __(
			'Improve your conversion tracking accuracy and unlock more powerful bidding.This feature works alongside your existing conversion tags, sending secure, privacy-friendly conversion data from your website to Google',
			'google-listings-and-ads'
		) }
	</p>
);

const TITLE = __( 'Enhanced Conversion Tracking', 'google-listings-and-ads' );

/**
 * Renders the settings panel for enhanced conversion tracking
 */
const EnhancedConversionTrackingSettings = () => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();
	const { acceptedCustomerDataTerms } = useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

	const TOS_URL = 'https://ads.google.com/aw/conversions/customersettings';

	const handleTOS = useCallback( () => {
		window.open( TOS_URL, '_blank' );

		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);
	}, [ updateEnhancedAdsConversionStatus ] );

	const handleDisable = useCallback( () => {
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.DISABLED
		);
	}, [ updateEnhancedAdsConversionStatus ] );

	const handleEnable = useCallback( () => {
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);
	}, [ updateEnhancedAdsConversionStatus ] );

	if (
		( ! googleAdsAccount ||
			googleAdsAccount?.status !==
				GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ) &&
		hasFinishedResolution
	) {
		return null;
	}

	const getCTA = () => {
		if ( ! acceptedCustomerDataTerms ) {
			return (
				<AppButton isPrimary onClick={ handleTOS }>
					{ __(
						'Accept Terms & Conditions',
						'google-listings-and-ads'
					) }
				</AppButton>
			);
		}

		if (
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		) {
			return (
				<AppButton isPrimary isDestructive onClick={ handleDisable }>
					{ __( 'Disable', 'google-listings-and-ads' ) }
				</AppButton>
			);
		}

		if (
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.DISABLED
		) {
			return (
				<AppButton isPrimary onClick={ handleEnable }>
					{ __( 'Enable', 'google-listings-and-ads' ) }
				</AppButton>
			);
		}

		if (
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING
		) {
			return (
				<AppButton isSecondary disabled>
					<AppSpinner />
					<PendingStatus />
				</AppButton>
			);
		}
	};

	return (
		<Section title={ TITLE } description={ <div>{ DESCRIPTION }</div> }>
			{ ! hasFinishedResolution && <SpinnerCard /> }

			{ hasFinishedResolution && (
				<VerticalGapLayout size="large">
					<Section.Card>
						<Section.Card.Body>{ getCTA() }</Section.Card.Body>
					</Section.Card>
				</VerticalGapLayout>
			) }
		</Section>
	);
};

export default EnhancedConversionTrackingSettings;
