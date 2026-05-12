/**
 * External dependencies
 */
import { BaseControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import CountriesTimeInput from '~/components/countries-time-input';

/**
 * Renders the shipping times input control within the market edit form.
 * This control allows users to specify estimated shipping times for the market,
 * which apply per country regardless of language or currency.
 */
const ShippingTimesInput = () => {
	const {
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	return (
		<BaseControl
			id="gla-shipping-times-input"
			label={ __(
				'Estimated shipping times',
				'google-listings-and-ads'
			) }
			help={ __(
				'Delivery times apply per country, regardless of language or currency.',
				'google-listings-and-ads'
			) }
		>
			<CountriesTimeInput />
			{ renderRequestedValidation( 'flat_shipping_times' ) }
		</BaseControl>
	);
};

export default ShippingTimesInput;
