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
import AppSpinner from '.~/components/app-spinner';
import Hero from '.~/components/free-listings/configure-product-listings/hero';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import checkErrors from '.~/components/free-listings/configure-product-listings/checkErrors';
import FormContent from './form-content';
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * Setup step to configure free listings.
 * Auto-saves changes.
 *
 * @see /js/src/edit-free-campaign/setup-free-listings/index.js
 */
const SetupFreeListings = () => {
	const [ completing, setCompleting ] = useState( false );
	const { settings } = useSettings();
	const { data: shippingRatesData } = useShippingRates();
	const { data: shippingTimesData } = useShippingTimes();
	const {
		data: finalCountryCodesData,
	} = useTargetAudienceFinalCountryCodes();
	const { createNotice } = useDispatchCoreNotices();
	const adminUrl = useAdminUrl();

	if (
		! settings ||
		! shippingRatesData ||
		! shippingTimesData ||
		! finalCountryCodesData
	) {
		return <AppSpinner />;
	}

	/**
	 * Validation handler.
	 *
	 * We just return empty object here,
	 * because we call `checkErrors` in the rendering below,
	 * to accommodate for shippingRatesData and shippingTimesData from wp-data store.
	 * Apparently when shippingRates and shippingTimes are changed,
	 * the handleValidate function here does not get called.
	 *
	 * When we have shipping rates and shipping times as part of form values,
	 * then we can move `checkErrors` from inside rendering into this handleValidate function.
	 */
	const handleValidate = () => {
		return {};
	};

	const handleSubmitCallback = async () => {
		try {
			setCompleting( true );

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
					const { values, handleSubmit } = formProps;

					const errors = checkErrors(
						values,
						shippingRatesData,
						shippingTimesData,
						finalCountryCodesData
					);

					const isCompleteSetupDisabled =
						Object.keys( errors ).length >= 1;

					return (
						<FormContent
							formProps={ formProps }
							submitButton={
								<AppButton
									isPrimary
									loading={ completing }
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
