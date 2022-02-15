/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Hero from '.~/components/free-listings/configure-product-listings/hero';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import checkErrors from '.~/components/free-listings/configure-product-listings/checkErrors';
import FormContent from './form-content';
import AppButton from '.~/components/app-button';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useShippingRatesWithSavedSuggestions from './useShippingRatesWithSavedSuggestions';

/**
 * Setup step to configure free listings.
 * Auto-saves changes.
 *
 * @param {Object} props React props.
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked. Could be async. While it's being resolved the form would turn into a saving state.
 * @see /js/src/edit-free-campaign/setup-free-listings/index.js
 */
const SetupFreeListings = ( props ) => {
	const { onContinue = () => {} } = props;
	const { settings } = useSettings();
	const {
		loading: loadingShippingRates,
		data: dataShippingRates,
	} = useShippingRatesWithSavedSuggestions();
	const { data: shippingTimesData } = useShippingTimes();
	const {
		data: finalCountryCodesData,
	} = useTargetAudienceFinalCountryCodes();

	if (
		! settings ||
		loadingShippingRates ||
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

	const handleSubmitCallback = () => {
		onContinue();
	};

	return (
		<div className="gla-setup-free-listings">
			<Hero />
			<Form
				initialValues={ {
					shipping_rate: settings.shipping_rate || 'automatic',
					shipping_time: settings.shipping_time,
					tax_rate: settings.tax_rate,
					shipping_country_rates: dataShippingRates,
				} }
				validate={ handleValidate }
				onSubmit={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					const { values, handleSubmit } = formProps;

					const errors = checkErrors(
						values,
						dataShippingRates,
						shippingTimesData,
						finalCountryCodesData
					);

					const isContinueDisabled =
						Object.keys( errors ).length >= 1;

					return (
						<FormContent
							formProps={ formProps }
							submitButton={
								<AppButton
									isPrimary
									disabled={ isContinueDisabled }
									onClick={ handleSubmit }
								>
									{ __(
										'Continue',
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
