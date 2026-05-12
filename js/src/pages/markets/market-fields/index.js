/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import MarketSelectControl from './market-select-control';
import LanguageSelectControl from './language-select-control';
import CurrencySelectControl from './currency-select-control';
import ShippingTimesInput from './shipping-times-input';
import ShippingRateInputControl from '~/components/shipping-rate-input-control';
import FreeShippingThresholdControl from '~/components/free-shipping-threshold-control';

/**
 * Renders the market details fields within the market edit form.
 * The fields rendered here vary based on the scenario (manual vs multilingual),
 * but this component is scenario-agnostic; it only owns the shipping-rate-specific fields
 * and the notice about multilingual support, which are common to both scenarios.
 *
 * The scenario-specific field sets and data shapes are handled within
 * `useMarketDataViewsConfig`.
 */
const MarketFields = () => {
	const { settings } = useSettings();
	const {
		getInputProps,
		values,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { currency } = values;
	const { onChange, value } = getInputProps( 'free_shipping_threshold' );

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
				<Flex direction="column" gap={ 2 }>
					<FreeShippingThresholdControl
						onChange={ onChange }
						threshold={ value }
						currency={ currency }
					/>
					{ renderRequestedValidation( 'free_shipping_threshold' ) }
				</Flex>
			) }

			<ShippingTimesInput />
		</Flex>
	);
};

export default MarketFields;
