/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import groupShippingRatesByMethodCurrencyRate from './groupShippingRatesByMethodCurrencyRate';
import ShippingRateInputControl from './shipping-rate-input-control';
import AddRateFormModal from './add-rate-form-modal';
import { SHIPPING_RATE_METHOD } from '.~/constants';
import isNonFreeFlatShippingRate from '.~/utils/isNonFreeFlatShippingRate';

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

const defaultShippingRate = {
	method: SHIPPING_RATE_METHOD.FLAT_RATE,
	options: {},
};

/**
 * Partial form to provide shipping rates for individual countries,
 * with an UI, that allows to aggregate countries with the same rate.
 *
 * @param {Object} props
 * @param {Array<ShippingRate>} props.value Array of individual shipping rates to be used as the initial values of the form.
 * @param {Array<CountryCode>} props.audienceCountries Array of country codes of all audience countries.
 * @param {(newValue: Object) => void} props.onChange Callback called with new data once shipping rates are changed.
 */
export default function EstimatedShippingRatesCard( {
	value: shippingRates,
	audienceCountries,
	onChange,
} ) {
	const { code: currencyCode } = useStoreCurrency();
	const actualCountryCount = shippingRates.length;
	const actualCountries = new Map(
		shippingRates.map( ( rate ) => [ rate.country, rate ] )
	);
	const remainingCountries = audienceCountries.filter(
		( el ) => ! actualCountries.has( el )
	);
	const remainingCount = remainingCountries.length;

	// Group countries with the same rate.
	const groups = groupShippingRatesByMethodCurrencyRate( shippingRates );

	// Prefill to-be-added group.
	if ( groups.length === 0 ) {
		groups.push( {
			countries: audienceCountries,
			method: SHIPPING_RATE_METHOD.FLAT_RATE,
			currency: currencyCode,
			rate: null,
		} );
	}

	// Given the limitations of `<Form>` component we can communicate up only onChange.
	// Therefore we loose the infromation whether it was add, change, delete.
	// In autosave/setup MC case, we would have to either re-calculate to deduct that information,
	// or fix that in `<Form>` component.
	function handleDelete( deletedCountries ) {
		onChange(
			shippingRates.filter(
				( rate ) => ! deletedCountries.includes( rate.country )
			)
		);
	}
	function handleAdd( { countries, currency, rate } ) {
		// Split aggregated rate, to individial rates per country.
		const addedIndividualRates = countries.map( ( country ) => ( {
			...defaultShippingRate,
			country,
			currency,
			rate, // TODO: unify that
		} ) );

		onChange( shippingRates.concat( addedIndividualRates ) );
	}
	function handleChange(
		{ countries, currency, rate },
		deletedCountries = []
	) {
		deletedCountries.forEach( ( country ) =>
			actualCountries.delete( country )
		);

		// Upsert rates.
		countries.forEach( ( country ) => {
			const oldShippingRate = actualCountries.get( country );
			const newShippingRate = {
				...defaultShippingRate,
				...oldShippingRate,
				country,
				currency,
				rate,
			};

			/*
			 * If the shipping rate is free,
			 * we remove the free_shipping_threshold.
			 */
			if ( ! isNonFreeFlatShippingRate( newShippingRate ) ) {
				newShippingRate.options.free_shipping_threshold = undefined;
			}

			actualCountries.set( country, newShippingRate );
		} );
		onChange( Array.from( actualCountries.values() ) );
	}

	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping rates',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					{ groups.map( ( el ) => {
						return (
							<div key={ el.countries.join( '-' ) }>
								<ShippingRateInputControl
									countryOptions={ audienceCountries }
									value={ el }
									onChange={ handleChange }
									onDelete={ handleDelete }
								/>
							</div>
						);
					} ) }
					{ actualCountryCount >= 1 && remainingCount >= 1 && (
						<div>
							<AppButtonModalTrigger
								button={
									<Button
										isSecondary
										icon={ <GridiconPlusSmall /> }
									>
										{ __(
											'Add another rate',
											'google-listings-and-ads'
										) }
									</Button>
								}
								modal={
									<AddRateFormModal
										countryOptions={ remainingCountries }
										initialValues={ {
											countries: remainingCountries,
											currency: currencyCode,
											rate: 0,
										} }
										onSubmit={ handleAdd }
									/>
								}
							/>
						</div>
					) }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
}
