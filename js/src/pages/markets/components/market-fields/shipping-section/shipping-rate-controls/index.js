/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import useSettings from '~/hooks/useSettings';
import ShippingRateNotice from './shipping-rate-notice';
import ShippingRateControl from './shipping-rate-control';
import FreeShippingThresholdField from './free-shipping-threshold-field';

const ShippingRateControls = () => {
	const { settings } = useSettings();

	if ( settings?.shipping_rate === SHIPPING_RATE_METHOD.AUTOMATIC ) {
		return <ShippingRateNotice />;
	}

	return (
		<>
			<ShippingRateControl />
			<FreeShippingThresholdField />
		</>
	);
};

export default ShippingRateControls;
