/**
 * External dependencies
 */
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import FreeShippingThresholdControl from '~/components/free-shipping-threshold-control';

/**
 * Renders the free shipping threshold field in the market form.
 * This field is conditionally rendered based on the flat shipping rate.
 */
const FreeShippingThresholdField = () => {
	const {
		getInputProps,
		values,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { onChange, value: threshold } = getInputProps(
		'free_shipping_threshold'
	);
	const { code: storeCurrencyCode } = useStoreCurrency();
	// Flat rate markets don't carry a per-market currency, so fall back to
	// the store currency on non-multilingual stores.
	const currency = glaData.isMultiLingualStore
		? values.currency
		: storeCurrencyCode;
	const shouldDisplayFreeShippingThreshold = values.flat_shipping_rate > 0;

	if ( ! shouldDisplayFreeShippingThreshold ) {
		return null;
	}

	return (
		<Flex
			direction="column"
			gap={ 2 }
			className="gla-market-fields__free-shipping-threshold"
		>
			<FreeShippingThresholdControl
				onChange={ onChange }
				threshold={ threshold }
				currency={ currency }
			/>
			{ renderRequestedValidation( 'free_shipping_threshold' ) }
		</Flex>
	);
};

export default FreeShippingThresholdField;
