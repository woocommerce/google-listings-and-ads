/**
 * Internal dependencies
 */
import useSettings from '~/hooks/useSettings';
import ShippingTimesInput from '../market-fields/shipping-times-input';

const EditShippingTimes = () => {
	const { settings } = useSettings();

	if ( settings?.shipping_time !== 'flat' ) {
		return null;
	}

	return <ShippingTimesInput className="gla-edit-shipping-times" />;
};

export default EditShippingTimes;
