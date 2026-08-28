/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useAdminUrl from '~/hooks/useAdminUrl';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useTargetAudienceWithSuggestions from './useTargetAudienceWithSuggestions';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useSettings from '~/hooks/useSettings';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import SetupAccounts from './setup-accounts';
import SetupListings from './setup-listings';
import SetupPaidAds from './setup-paid-ads';
import EuPoliticalDeclarationProvider from '~/components/eu-political-declaration/eu-political-declaration-provider';
import { STEP_NAME_KEY_MAP } from './constants';
import {
	GUIDE_NAMES,
	SHIPPING_RATE_METHOD,
	SHIPPING_TIME_METHOD,
	DEFAULT_SHIPPING_MIN_TIME,
	DEFAULT_SHIPPING_MAX_TIME,
	glaData,
} from '~/constants';
import { getProductFeedUrl } from '~/utils/urls';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_EXTENSION_ONBOARDING,
} from '~/utils/tracks';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 * @fires gla_setup_mc with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button', action: 'go-to-step2' | 'go-to-step3' }`.
 * @fires gla_setup_mc with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button', action: 'go-to-step1' | 'go-to-step2' }`.
 */
const SavedSetupStepper = ( { savedStep } ) => {
	const [ step, setStep ] = useState( savedStep );
	const adminUrl = useAdminUrl();
	const { settings, saveSettings } = useSettings();
	const { data: suggestedAudience } = useTargetAudienceWithSuggestions();
	const {
		targetAudience,
		getFinalCountries,
		loaded: hasResolvedTargetAudience,
	} = useTargetAudienceFinalCountryCodes();
	const {
		hasFinishedResolution: hasResolvedShippingRates,
		data: shippingRates,
	} = useShippingRates();
	const {
		hasFinishedResolution: hasResolvedShippingTimes,
		data: shippingTimes,
	} = useShippingTimes();

	const { saveTargetAudience } = useAppDispatch();
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();
	const { createNotice } = useDispatchCoreNotices();

	useEventPropertiesFilter( FILTER_ONBOARDING, {
		context: CONTEXT_EXTENSION_ONBOARDING,
		step,
	} );

	// Auto-save the suggested audience data as the initial values to fall back with the original implementation.
	// Ref: https://github.com/woocommerce/google-listings-and-ads/blob/2.0.2/js/src/setup-mc/setup-stepper/choose-audience/form-content.js#L37
	useEffect( () => {
		if (
			targetAudience?.location === null &&
			suggestedAudience?.location
		) {
			saveTargetAudience( suggestedAudience );
		}
	}, [ targetAudience, suggestedAudience, saveTargetAudience ] );

	// Auto-save the default values for shipping options to fall back with the original implementation.
	// Ref: https://github.com/woocommerce/google-listings-and-ads/blob/2.0.2/js/src/setup-mc/setup-stepper/setup-free-listings/form-content.js#L33
	useEffect( () => {
		if ( settings?.shipping_rate === null ) {
			saveSettings( {
				...settings,
				shipping_rate: glaData.isMultiLingualStore
					? SHIPPING_RATE_METHOD.MANUAL
					: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: glaData.isMultiLingualStore
					? SHIPPING_TIME_METHOD.MANUAL
					: SHIPPING_TIME_METHOD.FLAT,
			} );
		}
	}, [ settings, saveSettings ] );

	// getFinalCountries is redefined inside mapSelect on every store update, giving it an
	// unstable reference. A ref keeps the latest version without putting it in effect deps.
	const getFinalCountriesRef = useRef( getFinalCountries );
	getFinalCountriesRef.current = getFinalCountries;

	// Auto-save default shipping times when no times have been saved yet.
	useEffect( () => {
		if (
			hasResolvedTargetAudience &&
			hasResolvedShippingTimes &&
			! shippingTimes.length &&
			targetAudience?.location
		) {
			const countries = getFinalCountriesRef.current( targetAudience );

			if ( countries?.length ) {
				const defaultTimes = countries.map( ( countryCode ) => ( {
					countryCode,
					time: DEFAULT_SHIPPING_MIN_TIME,
					maxTime: DEFAULT_SHIPPING_MAX_TIME,
				} ) );

				saveShippingTimes( defaultTimes ).catch( () =>
					createNotice(
						'error',
						__(
							'There was an error saving shipping times.',
							'google-listings-and-ads'
						)
					)
				);
			}
		}
	}, [
		hasResolvedTargetAudience,
		hasResolvedShippingTimes,
		shippingTimes,
		targetAudience,
		saveShippingTimes,
		createNotice,
	] );

	/**
	 * Handles "onContinue" callback to set the current step and record event tracking.
	 *
	 * @param {string} to The next step to go to.
	 */
	const continueStep = ( to ) => {
		const from = step;

		recordStepContinueEvent( 'gla_setup_mc', from, to );
		setStep( to );
	};

	const handleSetupAccountsContinue = () => {
		continueStep( STEP_NAME_KEY_MAP.product_listings );
	};

	const handleSetupListingsContinue = () => {
		continueStep( STEP_NAME_KEY_MAP.paid_ads );
	};

	const handleStepClick = ( stepKey ) => {
		// Only allow going back to the previous steps.
		if ( Number( stepKey ) < Number( step ) ) {
			recordStepperChangeEvent( 'gla_setup_mc', stepKey );
			setStep( stepKey );
		}
	};

	const redirectToProductFeed = () => {
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getProductFeedUrl( query );
	};

	/**
	 * Handles form change callback and callback's errors via binding an actual callback function and an error message.
	 *
	 * `this` should be an async callback function that handles the form change.
	 * For example:
	 * `handleFormChange.bind( saveSettings, __( 'Oops!', 'google-listings-and-ads' ) )`
	 *
	 * @this {(newValue: *) => Promise}
	 * @param {string} errorMessage Message when the error occurs.
	 * @param {*} newValue The new values will be called with the bound callback function.
	 */
	function handleFormChange( errorMessage, newValue ) {
		this( newValue ).catch( () => createNotice( 'error', errorMessage ) );
	}

	const initShippingRates = hasResolvedShippingRates ? shippingRates : null;
	const initShippingTimes = hasResolvedShippingTimes ? shippingTimes : null;
	const initTargetAudience = targetAudience?.location ? targetAudience : null;
	const baseSettings = settings?.shipping_rate ? { ...settings } : null;

	// If the store is multilingual and the shipping rate method is set to flat,
	// we need to override it to manual to allow for per-country shipping rates.
	const needsManualOverride =
		baseSettings?.shipping_rate === SHIPPING_RATE_METHOD.FLAT &&
		glaData.isMultiLingualStore;

	const initSettings = needsManualOverride
		? {
				...baseSettings,
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				shipping_time: SHIPPING_TIME_METHOD.MANUAL,
		  }
		: baseSettings;

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: STEP_NAME_KEY_MAP.accounts,
					label: __(
						'Set up your accounts',
						'google-listings-and-ads'
					),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: STEP_NAME_KEY_MAP.product_listings,
					label: __(
						'Configure product listings',
						'google-listings-and-ads'
					),
					content: (
						<SetupListings
							onContinue={ handleSetupListingsContinue }
							onSettingsChange={ handleFormChange.bind(
								saveSettings,
								__(
									'There was an error saving settings.',
									'google-listings-and-ads'
								)
							) }
							onShippingRatesChange={ handleFormChange.bind(
								saveShippingRates,
								__(
									'There was an error saving shipping rates.',
									'google-listings-and-ads'
								)
							) }
							onShippingTimesChange={ handleFormChange.bind(
								saveShippingTimes,
								__(
									'There was an error saving shipping times.',
									'google-listings-and-ads'
								)
							) }
							onTargetAudienceChange={ handleFormChange.bind(
								saveTargetAudience,
								__(
									'There was an error saving audience.',
									'google-listings-and-ads'
								)
							) }
							resolveFinalCountries={ getFinalCountries }
							settings={ initSettings }
							shippingRates={ initShippingRates }
							shippingTimes={ initShippingTimes }
							submitLabel={ __(
								'Continue',
								'google-listings-and-ads'
							) }
							targetAudience={ initTargetAudience }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: STEP_NAME_KEY_MAP.paid_ads,
					label: __( 'Create a campaign', 'google-listings-and-ads' ),
					content: (
						<EuPoliticalDeclarationProvider
							context={ CONTEXT_EXTENSION_ONBOARDING }
						>
							<SetupPaidAds
								onSetupComplete={ redirectToProductFeed }
								onSetupSkipped={ redirectToProductFeed }
							/>
						</EuPoliticalDeclarationProvider>
					),
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SavedSetupStepper;
