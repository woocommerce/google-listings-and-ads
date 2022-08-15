/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useStoreAddress from '.~/hooks/useStoreAddress';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import ContactInformation from '.~/components/contact-information';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import PreLaunchChecklist from './pre-launch-checklist';
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

	/**
	 * Since it still lacking the phone verification state,
	 * all onboarding accounts are considered unverified phone numbers.
	 */
	const [ isPhoneNumberReady, setPhoneNumberReady ] = useState( false );
	const [ settingsSaved, setSettingsSaved ] = useState( true );
	const [ completing, setCompleting ] = useState( false );

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
			setCompleting( true );

			await updateGoogleMCContactInformation();
			onContinue();
		} catch ( error ) {
			setCompleting( false );

			createNotice(
				'error',
				__(
					'Unable to update your contact information. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	if ( ! settings ) {
		return <AppSpinner />;
	}

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
			<Form
				initialValues={ {
					website_live: settings.website_live,
					checkout_process_secure: settings.checkout_process_secure,
					payment_methods_visible: settings.payment_methods_visible,
					refund_tos_visible: settings.refund_tos_visible,
					contact_info_visible: settings.contact_info_visible,
				} }
				validate={ checkErrors }
				onChange={ handleChangeCallback }
				onSubmit={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { handleSubmit, isValidForm } = formProps;

					const isReadyToComplete =
						isValidForm &&
						isPhoneNumberReady &&
						address.isAddressFilled &&
						settingsSaved;

					return (
						<>
							<ContactInformation
								onPhoneNumberVerified={ () =>
									setPhoneNumberReady( true )
								}
							/>
							<PreLaunchChecklist formProps={ formProps } />
							<StepContentFooter>
								<AppButton
									isPrimary
									loading={ completing }
									disabled={ ! isReadyToComplete }
									onClick={ handleSubmit }
								>
									{ __(
										'Continue',
										'google-listings-and-ads'
									) }
								</AppButton>
							</StepContentFooter>
						</>
					);
				} }
			</Form>
		</StepContent>
	);
}
