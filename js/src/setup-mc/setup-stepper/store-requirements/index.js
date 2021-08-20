/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Form } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAdminUrl from '.~/hooks/useAdminUrl';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
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

export default function StoreRequirements() {
	const adminUrl = useAdminUrl();
	const { updateGoogleMCContactInformation, saveSettings } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: initPhoneNumber } = useGoogleMCPhoneNumber();
	const { data: address } = useStoreAddress();
	const { settings } = useSettings();

	const [ completing, setCompleting ] = useState( false );
	const [ phoneNumber, setPhoneNumber ] = useState( {
		isValid: false,
		isDirty: false,
	} );

	const handleChangeCallback = ( _, values ) => {
		saveSettings( values );
	};

	const handleSubmitCallback = async () => {
		try {
			const { isDirty, countryCallingCode, nationalNumber } = phoneNumber;
			const args = isDirty ? [ countryCallingCode, nationalNumber ] : [];

			setCompleting( true );

			await updateGoogleMCContactInformation( ...args );

			await apiFetch( {
				path: '/wc/gla/mc/settings/sync',
				method: 'POST',
			} );

			// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
			const path = getNewPath(
				{ guide: 'submission-success' },
				'/google/product-feed'
			);
			window.location.href = adminUrl + path;
		} catch ( error ) {
			setCompleting( false );

			createNotice(
				'error',
				__(
					'Unable to complete your setup. Please try again later.',
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
				onChangeCallback={ handleChangeCallback }
				onChange={ handleChangeCallback }
				onSubmitCallback={ handleSubmitCallback }
				onSubmit={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { handleSubmit, isValidForm } = formProps;

					const isPhoneNumberReady = phoneNumber.isDirty
						? phoneNumber.isValid
						: initPhoneNumber.isValid;

					const isReadyToComplete =
						isValidForm &&
						isPhoneNumberReady &&
						address.isAddressFilled;

					return (
						<>
							<ContactInformation
								view="setup-mc"
								onPhoneNumberChange={ setPhoneNumber }
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
										'Complete setup',
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
