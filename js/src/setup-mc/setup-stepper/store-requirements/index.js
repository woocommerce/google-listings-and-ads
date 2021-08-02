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
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppButton from '.~/components/app-button';

export default function StoreRequirements() {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
	const [ completing, setCompleting ] = useState( false );

	const handleValidate = () => {
		// TODO: [lite-contact-info] add validation
		return {};
	};

	const handleSubmitCallback = async () => {
		try {
			setCompleting( true );

			// TODO: [lite-contact-info] POST phone number to API if it has changed

			// TODO: [lite-contact-info] POST address to API

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

	return (
		<StepContent>
			<StepContentHeader
				step={ __( 'STEP FOUR', 'google-listings-and-ads' ) }
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
				initialValues={ {} }
				validate={ handleValidate }
				onSubmitCallback={ handleSubmitCallback }
				onSubmit={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { handleSubmit, isValidForm } = formProps;

					return (
						<>
							<div>TODO: implement contact information setup</div>
							<div>TODO: move pre-lauch checklist to here</div>
							<StepContentFooter>
								<AppButton
									isPrimary
									loading={ completing }
									disabled={ ! isValidForm }
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
