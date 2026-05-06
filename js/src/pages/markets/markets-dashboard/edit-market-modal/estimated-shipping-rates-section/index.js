/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EstimatedShippingRatesCard from '~/components/shipping-rate-section/estimated-shipping-rates-card/estimated-shipping-rates-card';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
import './index.scss';

const EstimatedShippingRatesSection = () => {
	const [ estimatedRate, setEstimatedRate ] = useState( 10 );
	const [ offerFreeShipping, setOfferFreeShipping ] = useState( false );

	return (
		<EstimatedShippingRatesCard
			className="gla-edit-market-modal__estimated-rates"
			audienceCountries={ [] }
			value={ estimatedRate }
			onChange={ setEstimatedRate }
			helper={
				<div className="gla-edit-market-modal__estimated-rates__helper">
					<OfferFreeShippingCheckbox
						value={ offerFreeShipping }
						onChange={ setOfferFreeShipping }
					/>
				</div>
			}
		/>
	);
};

export default EstimatedShippingRatesSection;
