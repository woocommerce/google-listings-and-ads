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
import StepContentFooter from '.~/components/stepper/step-content-footer';
import ContactInformation from '.~/components/contact-information';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import PreLaunchChecklist from './pre-launch-checklist';
import usePolicyCheck from '.~/hooks/usePolicyCheck';

function _checkErrors() {
	const errors = {};
	return errors;
}

export default function StoreRequirements() {
	const adminUrl = useAdminUrl();
	const { updateGoogleMCContactInformation, saveSettings } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: address } = useStoreAddress();
	const { settings } = useSettings();
	const { data: policy_check_data } = usePolicyCheck();

	/**
	 * Since it still lacking the phone verification state,
	 * all onboarding accounts are considered unverified phone numbers.
	 */
	const [ isPhoneNumberReady, setPhoneNumberReady ] = useState( false );
	const [ settingsSaved, setSettingsSaved ] = useState( true );
	const [ policyChecked, setPolicyChecked ] = useState( false );
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
	if ( ! policy_check_data ) {
		return <AppSpinner />;
	}

	if ( ! policyChecked ) {
		const websiteLive =
			policy_check_data.allowed_countries &&
			! policy_check_data.robots_restriction &&
			! policy_check_data.page_not_found_error &&
			! policy_check_data.page_redirects;
		if ( websiteLive !== settings.website_live ) {
			settings.website_live = policy_check_data.website_live;
		}

		if (
			policy_check_data.store_ssl !== settings.checkout_process_secure
		) {
			settings.checkout_process_secure = policy_check_data.store_ssl;
		}

		if (
			policy_check_data.refund_returns !== settings.refund_tos_visible
		) {
			settings.refund_tos_visible = policy_check_data.refund_returns;
		}

		if (
			policy_check_data.payment_gateways !==
			settings.payment_methods_visible
		) {
			settings.payment_methods_visible =
				policy_check_data.payment_gateways;
		}
		setPolicyChecked( true );
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
