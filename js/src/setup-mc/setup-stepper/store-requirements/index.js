/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useStoreAddress from '.~/hooks/useStoreAddress';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentActions from '.~/components/stepper/step-content-actions';
import AdaptiveForm from '.~/components/adaptive-form';
import ValidationErrors from '.~/components/validation-errors';
import ContactInformation from '.~/components/contact-information';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import PreLaunchChecklist from './pre-launch-checklist';
import usePolicyCheck from '.~/hooks/usePolicyCheck';
import checkErrors from './pre-launch-checklist/checkErrors';

/**
 * Step for the store requirements in the onboarding flow.
 *
 * @param {Object} props React props.
 * @param {() => void} props.onContinue Callback called once continue button is clicked.
 */
export default function StoreRequirements( { onContinue } ) {
	const { updateGoogleMCContactInformation, saveSettings } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: address } = useStoreAddress();
	const { settings } = useSettings();
	const { data: policyCheckData } = usePolicyCheck();

	/**
	 * Since it still lacking the phone verification state,
	 * all onboarding accounts are considered unverified phone numbers.
	 */
	const [ isPhoneNumberReady, setPhoneNumberReady ] = useState( false );
	const [ settingsSaved, setSettingsSaved ] = useState( true );
	const [ preprocessed, setPreprocessed ] = useState( false );

	const handleChangeCallback = async ( _, values ) => {
		try {
			await saveSettings( values );
			setSettingsSaved( true );
		} catch ( error ) {
			//Create the notice only once
			if ( settingsSaved ) {
				createNotice(
					'error',
					__(
						'There was an error trying to save settings. Please try again later.',
						'google-listings-and-ads'
					)
				);
			}
			setSettingsSaved( false );
		}
	};

	const handleSubmitCallback = async () => {
		try {
			await updateGoogleMCContactInformation();
			onContinue();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to update your contact information. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	// Preprocess the auto-checked state and data saving.
	useEffect( () => {
		if ( preprocessed || ! settings || ! policyCheckData ) {
			return;
		}

		const newSettings = { ...settings };

		const websiteLive =
			policyCheckData.allowed_countries &&
			! policyCheckData.robots_restriction &&
			! policyCheckData.page_not_found_error &&
			! policyCheckData.page_redirects;

		if ( websiteLive !== settings.website_live ) {
			newSettings.website_live = websiteLive;
		}

		if ( policyCheckData.store_ssl !== settings.checkout_process_secure ) {
			newSettings.checkout_process_secure = policyCheckData.store_ssl;
		}

		if ( policyCheckData.refund_returns !== settings.refund_tos_visible ) {
			newSettings.refund_tos_visible = policyCheckData.refund_returns;
		}

		if (
			policyCheckData.payment_gateways !==
			newSettings.payment_methods_visible
		) {
			newSettings.payment_methods_visible =
				policyCheckData.payment_gateways;
		}

		const promise = isEqual( newSettings, settings )
			? Promise.resolve()
			: saveSettings( newSettings );

		promise.finally( () => setPreprocessed( true ) );
	}, [ preprocessed, policyCheckData, settings, saveSettings ] );

	if ( ! preprocessed ) {
		return <AppSpinner />;
	}

	const extendAdapter = ( formContext ) => {
		return {
			renderRequestedValidation( key ) {
				if ( formContext.adapter.requestedShowValidation ) {
					return (
						<ValidationErrors
							messages={ formContext.errors[ key ] }
						/>
					);
				}
				return null;
			},
		};
	};

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Confirm store requirements',
					'google-listings-and-ads'
				) }
				description={ __(
					'Review and confirm that your store meets Google Merchant Center requirements.',
					'google-listings-and-ads'
				) }
			/>
			<AdaptiveForm
				initialValues={ {
					website_live: settings.website_live,
					checkout_process_secure: settings.checkout_process_secure,
					payment_methods_visible: settings.payment_methods_visible,
					refund_tos_visible: settings.refund_tos_visible,
					contact_info_visible: settings.contact_info_visible,
				} }
				extendAdapter={ extendAdapter }
				validate={ checkErrors }
				onChange={ handleChangeCallback }
				onSubmit={ handleSubmitCallback }
			>
				{ ( formContext ) => {
					const { handleSubmit, isValidForm, adapter } = formContext;

					const handleSubmitClick = ( event ) => {
						const isReadyToComplete =
							isValidForm &&
							isPhoneNumberReady &&
							address.isAddressFilled;

						if ( isReadyToComplete ) {
							return handleSubmit( event );
						}

						adapter.showValidation();
					};

					return (
						<>
							<ContactInformation
								onPhoneNumberVerified={ () =>
									setPhoneNumberReady( true )
								}
							/>
							<PreLaunchChecklist />
							<StepContentActions>
								<AppButton
									isPrimary
									loading={ adapter.isSubmitting }
									disabled={ ! settingsSaved }
									onClick={ handleSubmitClick }
								>
									{ __(
										'Continue',
										'google-listings-and-ads'
									) }
								</AppButton>
							</StepContentActions>
						</>
					);
				} }
			</AdaptiveForm>
		</StepContent>
	);
}
