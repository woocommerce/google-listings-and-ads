/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import ShippingRateSetup from './shipping-rate-setup';

const EstimatedShippingRatesCard = ( props ) => {
	const { audienceCountries, value, onChange } = props;

	return (
		<Section.Card className="gla-estimated-shipping-rates-card">
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping rates',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<ShippingRateSetup
					audienceCountries={ audienceCountries }
					value={ value }
					onChange={ onChange }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EstimatedShippingRatesCard;
