/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Faqs from './faqs';
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import StepContent from '~/components/stepper/step-content';
import TargetAudienceSection from '~/components/target-audience-section';
import WPComAccountCard from '~/components/wpcom-account-card';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import GoogleComboAccountCard from '~/components/google-combo-account-card';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import { useAppDispatch } from '~/data';
import './index.scss';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { jetpack } = useJetpackAccount();
	const { google } = useGoogleAccount();
	const { saveTargetAudience } = useAppDispatch();
	const { hasFinishedResolution } = useGoogleAdsAccount();
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const { targetAudience, getFinalCountries } =
		useTargetAudienceFinalCountryCodes();
	const [ selectedTargetAudience, setSelectedTargetAudience ] =
		useState( null );
	const initTargetAudience = targetAudience?.location ? targetAudience : null;

	const handleSubmitCallback = async () => {
		setIsSubmitting( true );
		await saveTargetAudience( selectedTargetAudience );
		onContinue();
	};

	useEffect( () => {
		setSelectedTargetAudience( targetAudience );
	}, [ targetAudience ] );

	/**
	 * When jetpack is loading, or when google account is loading,
	 * we display the AppSpinner.
	 */
	const isLoadingJetpack = ! jetpack;
	const isJetpackActive = jetpack?.active === 'yes';

	const isLoadingGoogle = isJetpackActive && ! google;

	if ( isLoadingJetpack || isLoadingGoogle ) {
		return <AppSpinner />;
	}

	const isContinueButtonDisabled = ! (
		hasFinishedResolution &&
		isGoogleAdsReady &&
		( ( selectedTargetAudience?.location === 'selected' &&
			selectedTargetAudience?.countries.length > 0 ) ||
			selectedTargetAudience?.location === 'all' )
	);

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect the accounts required to use Google for WooCommerce.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				className="gla-setup-ads-only-section"
				title={ __( 'Connect accounts', 'google-listings-and-ads' ) }
				description={ __(
					'The following accounts are required to use the Google for WooCommerce plugin.',
					'google-listings-and-ads'
				) }
			>
				{ ! isJetpackActive && (
					<WPComAccountCard jetpack={ jetpack } />
				) }

				<GoogleComboAccountCard disabled={ ! isJetpackActive } />
			</Section>

			{ isGoogleAdsReady && (
				<TargetAudienceSection
					targetAudience={ initTargetAudience }
					resolveFinalCountries={ getFinalCountries }
					onTargetAudienceChange={ setSelectedTargetAudience }
				/>
			) }

			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						loading={ isSubmitting }
						text={ __( 'Continue', 'google-listings-and-ads' ) }
						onClick={ handleSubmitCallback }
					/>
				</StepContentActions>
			</StepContentFooter>
			<Section className="gla-setup-ads-only-section__faqs">
				<Faqs />
			</Section>
		</StepContent>
	);
};

export default SetupAccounts;
