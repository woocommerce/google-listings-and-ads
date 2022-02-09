/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import MinimumOrderInputControl from './minimum-order-input-control';
import './minimum-order-card.scss';

const groupShippingRatesByFreeShippingThreshold = ( shippingRates ) => {
	const map = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const threshold = Number(
			shippingRate.options.free_shipping_threshold
		);
		const thresholdCurrency = `${ threshold } ${ shippingRate.currency }`;

		const group = map.get( thresholdCurrency ) || {
			countries: [],
			threshold,
			currency: shippingRate.currency,
		};
		group.countries.push( shippingRate.country );

		map.set( thresholdCurrency, group );
	} );

	return Array.from( map.values() );
};

const getMinimumOrderCountryOptions = ( shippingRates ) => {
	return shippingRates
		.filter( ( shippingRate ) => shippingRate.rate > 0 )
		.map( ( shippingRate ) => shippingRate.country );
};

const MinimumOrderCard = ( props ) => {
	const { value, onChange } = props;
	const groups = groupShippingRatesByFreeShippingThreshold( value );
	const countryOptions = getMinimumOrderCountryOptions( value );

	const handleChange = ( oldGroup ) => ( newGroup ) => {
		const newValue = value.map( ( shippingRate ) => {
			const newShippingRate = {
				...shippingRate,
				options: {
					...shippingRate.options,
				},
			};

			if ( oldGroup.countries.includes( newShippingRate.country ) ) {
				newShippingRate.options.free_shipping_threshold = newGroup.countries.includes(
					newShippingRate.country
				)
					? newGroup.threshold
					: undefined;
			}

			return newShippingRate;
		} );

		onChange( newValue );
	};

	return (
		<Section.Card className="gla-minimum-order-card">
			<Section.Card.Body>
				<VerticalGapLayout size="large">
					<Subsection.Title>
						{ __(
							'Minimum order to qualify for free shipping',
							'google-listings-and-ads'
						) }
					</Subsection.Title>
					{ groups.map( ( group ) => {
						const key = group.countries.join( '-' );

						return (
							<MinimumOrderInputControl
								key={ key }
								countryOptions={ countryOptions }
								value={ group }
								onChange={ handleChange( group ) }
							/>
						);
					} ) }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
