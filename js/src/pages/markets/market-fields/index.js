/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import MarketSelectControl from './market-select-control';
import LanguageSelectControl from './language-select-control';
import CurrencySelectControl from './currency-select-control';
import ShippingRateInputControl from '~/components/shipping-rate-section/estimated-shipping-rates-card/shipping-rate-input-control';
import FreeShippingThresholdControl from '~/components/order-value-condition-section/minimum-order-card/free-shipping-threshold-control';
import CountriesTimeInput from '~/components/free-listings/configure-product-listings/shipping-time-setup/countries-time-input';

const MarketFields = () => {
	const { settings } = useSettings();
	const { getInputProps, values } = useAdaptiveFormContext();
	const freeShippingInputProps = useAdaptiveFormInputProps(
		'shipping_country_rates',
		'free_shipping_threshold'
	);
	const shouldDisplayFreeShippingThreshold = values.flat_shipping_rate > 0;
	if ( settings?.shipping_rate !== SHIPPING_RATE_METHOD.FLAT ) {
		return null;
	}

	return (
		<Flex direction="column" gap={ 6 }>
			<MarketSelectControl />
			<LanguageSelectControl />
			<CurrencySelectControl />

			<Notice isDismissible={ false }>
				{ __(
					'Want to sell in multiple languages? Install a compatible multilingual plugin to add language and currency support to your markets.',
					'google-listings-and-ads'
				) }
			</Notice>

			<ShippingRateInputControl
				hideLabelFromVision={ false }
				label={ __(
					'Estimated shipping rates',
					'google-listings-and-ads'
				) }
				{ ...getInputProps( 'flat_shipping_rate' ) }
			/>

			{ shouldDisplayFreeShippingThreshold && (
				<FreeShippingThresholdControl { ...freeShippingInputProps } />
			) }

			<CountriesTimeInput />
		</Flex>
	);
};

export default MarketFields;
