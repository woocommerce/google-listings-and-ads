/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Hero from './hero';
import useSettings from './useSettings';
import FormContent from './form-content';

const SetupFreeListings = () => {
	const { settings } = useSettings();

	if ( ! settings ) {
		return <AppSpinner />;
	}

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	// TODO: call backend API when submit form.
	const handleSubmitCallback = () => {};

	return (
		<div className="gla-setup-free-listings">
			<Hero />
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
					return <FormContent formProps={ formProps } />;
				} }
			</Form>
		</div>
	);
};

export default SetupFreeListings;
