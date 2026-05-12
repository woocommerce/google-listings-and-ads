/**
 * External dependencies
 */
import { BaseControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CountriesTimeInput from '~/components/free-listings/configure-product-listings/shipping-time-setup/countries-time-input';

const ShippingTimesInput = ( { className } ) => {
	return (
		<BaseControl
			className={ className }
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
		</BaseControl>
	);
};

export default ShippingTimesInput;
