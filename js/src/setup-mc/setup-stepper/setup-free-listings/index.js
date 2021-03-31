/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Hero from '.~/components/free-listings/configure-product-listings/hero';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import FormContent from './form-content';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import isPreLaunchChecklistComplete from './isPreLaunchChecklistComplete';

/**
 * Setup step to configure free listings.
 * Auto-saves changes.
 *
 * @see /js/src/edit-free-campaign/setup-free-listings/index.js
 */
const SetupFreeListings = () => {
	const { settings } = useSettings();
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchSettingsSync, { loading } ] = useApiFetchCallback( {
		path: `/wc/gla/mc/settings/sync`,
		method: 'POST',
	} );

	if ( ! settings ) {
		return <AppSpinner />;
	}

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = async () => {
		try {
			await fetchSettingsSync();

			// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
			const path = `/wp-admin/${ getNewPath(
				{ guide: 'submission-success' },
				'/google/product-feed'
			) }`;
			window.location.href = path;
		} catch ( error ) {
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
		<div className="gla-setup-free-listings">
			<Hero
				stepHeader={ __( 'STEP THREE', 'google-listings-and-ads' ) }
			/>
			<Form
				initialValues={ {
					shipping_rate: settings.shipping_rate,
					offers_free_shipping: settings.offers_free_shipping,
					free_shipping_threshold: settings.free_shipping_threshold,
					shipping_time: settings.shipping_time,
					share_shipping_time: settings.share_shipping_time,
					tax_rate: settings.tax_rate,
					website_live: settings.website_live,
					checkout_process_secure: settings.checkout_process_secure,
					payment_methods_visible: settings.payment_methods_visible,
					refund_tos_visible: settings.refund_tos_visible,
					contact_info_visible: settings.contact_info_visible,
				} }
				validate={ handleValidate }
				onSubmitCallback={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { values, errors, handleSubmit } = formProps;

					const isCompleteSetupDisabled =
						Object.keys( errors ).length >= 1 ||
						! isPreLaunchChecklistComplete( values );

					return (
						<FormContent
							formProps={ formProps }
							submitButton={
								<AppButton
									isPrimary
									loading={ loading }
									disabled={ isCompleteSetupDisabled }
									onClick={ handleSubmit }
								>
									{ __(
										'Complete setup',
										'google-listings-and-ads'
									) }
								</AppButton>
							}
						/>
					);
				} }
			</Form>
		</div>
	);
};

export default SetupFreeListings;
