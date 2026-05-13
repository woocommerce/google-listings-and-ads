/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import MarketSelectControl from './market-select-control';
import LocaleControls from './locale-controls';
import ShippingTimesInput from './shipping-times-input';
import ShippingRateInputControl from '~/components/shipping-rate-input-control';
import FreeShippingThresholdControl from '~/components/free-shipping-threshold-control';
import EditPrimaryAudience from '../edit-market-modal/edit-primary-audience';
import './index.scss';

/**
 * Renders all market form fields for both the Add and Edit Market modals.
 * Returns null when `shipping_rate` is not `flat`.
 */
const MarketFields = ( { isPrimaryMarket = false } ) => {
	const { settings } = useSettings();
	const {
		getInputProps,
		values,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { currency } = values;

	if ( settings?.shipping_rate !== SHIPPING_RATE_METHOD.FLAT ) {
		return null;
	}

	const shouldDisplayFreeShippingThreshold = values.flat_shipping_rate > 0;
	const { onChange, value: threshold } = getInputProps(
		'free_shipping_threshold'
	);

	return (
		<Flex direction="column" gap={ 6 } className="gla-market-fields">
			{ ! isPrimaryMarket && <MarketSelectControl /> }
			{ ! isPrimaryMarket && <LocaleControls /> }

			{ isPrimaryMarket && <EditPrimaryAudience /> }

			{ isPrimaryMarket && (
				<ShippingRateInputControl
					hideLabelFromVision={ false }
					label={ __(
						'Estimated shipping rates',
						'google-listings-and-ads'
					) }
					{ ...getInputProps( 'flat_shipping_rate' ) }
				/>
			) }

			{ shouldDisplayFreeShippingThreshold && isPrimaryMarket && (
				<Flex
					direction="column"
					gap={ 2 }
					className="gla-market-fields__free-shipping-threshold"
				>
					<FreeShippingThresholdControl
						threshold={ threshold }
						currency={ currency }
						onChange={ onChange }
					/>
					{ renderRequestedValidation( 'free_shipping_threshold' ) }
				</Flex>
			) }

			{ isPrimaryMarket && <ShippingTimesInput /> }
		</Flex>
	);
};

export default MarketFields;
