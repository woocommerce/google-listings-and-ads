/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '.~/constants';

const isNonFreeFlatShippingRate = ( shippingRate ) =>
	shippingRate.rate > 0 &&
	shippingRate.method === SHIPPING_RATE_METHOD.FLAT_RATE;

export default isNonFreeFlatShippingRate;
