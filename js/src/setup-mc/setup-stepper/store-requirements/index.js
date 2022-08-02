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
import useStoreAddress from '.~/hooks/useStoreAddress';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import AppSpinner from '.~/components/app-spinner';
import PreLaunchChecklist from './pre-launch-checklist';

function _checkErrors( values ) {
	const errors = {};
	return errors;
}

export default function StoreRequirements() {
	const adminUrl = useAdminUrl();
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
					'[internal] Confirm store requirements',
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
				validate={ _checkErrors }
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
							<PreLaunchChecklist formProps={ formProps } />
						</>
					);
				} }
			</Form>
		</StepContent>
	);
}
