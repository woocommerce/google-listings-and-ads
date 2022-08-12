/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useTargetAudienceWithSuggestions from './useTargetAudienceWithSuggestions';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '.~/hooks/useSaveShippingTimes';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import SetupAccounts from './setup-accounts';
import SetupFreeListings from '.~/components/free-listings/setup-free-listings';
import StoreRequirements from './store-requirements';
import './index.scss';
import stepNameKeyMap from './stepNameKeyMap';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 * @param {Function} [props.onRefetchSavedStep] Callback when Saved Step is updated
 * @fires gla_setup_mc with `{ target: 'step1_continue' | 'step2_continue' | 'step3_continue', trigger: 'click' }`.
 */
const SavedSetupStepper = ( { savedStep, onRefetchSavedStep = () => {} } ) => {
	const [ step, setStep ] = useState( savedStep );

	const { settings } = useSettings();
	const { data: suggestedAudience } = useTargetAudienceWithSuggestions();
	const {
		targetAudience,
		getFinalCountries,
	} = useTargetAudienceFinalCountryCodes();
	const {
		hasFinishedResolution: hasResolvedShippingRates,
		data: shippingRates,
	} = useShippingRates();
	const {
		hasFinishedResolution: hasResolvedShippingTimes,
		data: shippingTimes,
	} = useShippingTimes();

	const { saveTargetAudience, saveSettings } = useAppDispatch();
	const { saveShippingRates } = useSaveShippingRates();
	const { saveShippingTimes } = useSaveShippingTimes();
	const { createNotice } = useDispatchCoreNotices();

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
				shipping_rate: 'automatic',
				shipping_time: 'flat',
			} );
		}
	}, [ settings, saveSettings ] );

	const handleSetupAccountsContinue = () => {
		recordEvent( 'gla_setup_mc', {
			target: 'step1_continue',
			trigger: 'click',
		} );
		setStep( stepNameKeyMap.target_audience );
		onRefetchSavedStep();
	};

	const handleSetupListingsContinue = () => {
		recordEvent( 'gla_setup_mc', {
			target: 'step2_continue',
			trigger: 'click',
		} );
		setStep( stepNameKeyMap.store_requirements );
		onRefetchSavedStep();
	};

	const handleStepClick = ( stepKey ) => {
		if ( Number( stepKey ) <= Number( savedStep ) ) {
			setStep( stepKey );
		}
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
	const initSettings = settings?.shipping_rate ? settings : null;

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: stepNameKeyMap.accounts,
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
					key: stepNameKeyMap.target_audience,
					label: __(
						'Configure product listings',
						'google-listings-and-ads'
					),
					content: (
						<SetupFreeListings
							targetAudience={ initTargetAudience }
							settings={ initSettings }
							shippingRates={ initShippingRates }
							shippingTimes={ initShippingTimes }
							resolveFinalCountries={ getFinalCountries }
							onTargetAudienceChange={ handleFormChange.bind(
								saveTargetAudience,
								__(
									'There was an error saving audience.',
									'google-listings-and-ads'
								)
							) }
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
							onContinue={ handleSetupListingsContinue }
							submitLabel={ __(
								'Continue',
								'google-listings-and-ads'
							) }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: stepNameKeyMap.store_requirements,
					label: __(
						'Confirm store requirements',
						'google-listings-and-ads'
					),
					content: <StoreRequirements />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SavedSetupStepper;
