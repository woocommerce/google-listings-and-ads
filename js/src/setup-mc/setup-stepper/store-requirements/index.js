/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useStoreAddress from '.~/hooks/useStoreAddress';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentActions from '.~/components/stepper/step-content-actions';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AdaptiveForm from '.~/components/adaptive-form';
import ValidationErrors from '.~/components/validation-errors';
import ContactInformation from '.~/components/contact-information';
import AppButton from '.~/components/app-button';

/**
 * Step for the store requirements in the onboarding flow.
 *
 * @param {Object} props React props.
 * @param {() => void} props.onContinue Callback called once continue button is clicked.
 */
export default function StoreRequirements( { onContinue } ) {
	const { updateGoogleMCContactInformation } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: address } = useStoreAddress();

	/**
	 * Since it still lacking the phone verification state,
	 * all onboarding accounts are considered unverified phone numbers.
	 */
	const [ isPhoneNumberReady, setPhoneNumberReady ] = useState( false );

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
				extendAdapter={ extendAdapter }
				onSubmit={ handleSubmitCallback }
			>
				{ ( formContext ) => {
					const { handleSubmit, adapter } = formContext;

					const handleSubmitClick = ( event ) => {
						const isReadyToComplete =
							isPhoneNumberReady && address.isAddressFilled;

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

							<StepContentFooter>
								<StepContentActions>
									<AppButton
										isPrimary
										loading={ adapter.isSubmitting }
										onClick={ handleSubmitClick }
									>
										{ __(
											'Continue',
											'google-listings-and-ads'
										) }
									</AppButton>
								</StepContentActions>
							</StepContentFooter>
						</>
					);
				} }
			</AdaptiveForm>
		</StepContent>
	);
}
