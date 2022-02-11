/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AppButton from '../app-button';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import MinimumOrderInputControl from './minimum-order-input-control';
import AddMinimumOrderModal from './add-minimum-order-modal';
import './minimum-order-card.scss';

const groupShippingRatesByFreeShippingThreshold = ( shippingRates ) => {
	const map = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const threshold =
			shippingRate.options?.free_shipping_threshold === undefined
				? undefined
				: Number( shippingRate.options.free_shipping_threshold );
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

const MinimumOrderCard = ( props ) => {
	const { value = [], onChange = noop } = props;
	const nonZeroShippingRates = value.filter(
		( shippingRate ) => shippingRate.rate > 0
	);
	const groups = groupShippingRatesByFreeShippingThreshold(
		nonZeroShippingRates
	);
	const countryOptions = nonZeroShippingRates.map(
		( shippingRate ) => shippingRate.country
	);

	const handleEditChange = ( oldGroup ) => ( newGroup ) => {
		const newValue = value.map( ( shippingRate ) => {
			const newShippingRate = {
				...shippingRate,
				options: {
					...shippingRate.options,
				},
			};

			if ( newGroup.countries.includes( newShippingRate.country ) ) {
				/**
				 * Shipping rate's country exists in the new value countries,
				 * so we just assign the new value threshold.
				 */
				newShippingRate.options.free_shipping_threshold =
					newGroup.threshold;
			} else if (
				oldGroup.countries.includes( newShippingRate.country )
			) {
				/**
				 * Shipping rate's country does not exist in the new value countries,
				 * but it exists in the old value countries.
				 * This means users removed the country in the edit modal,
				 * so we set the threshold value to undefined.
				 */
				newShippingRate.options.free_shipping_threshold = undefined;
			}

			return newShippingRate;
		} );

		onChange( newValue );
	};

	const handleAddChange = ( newGroup ) => {
		const newValue = value.map( ( shippingRate ) => {
			const newShippingRate = {
				...shippingRate,
				options: {
					...shippingRate.options,
				},
			};

			if ( newGroup.countries.includes( newShippingRate.country ) ) {
				newShippingRate.options.free_shipping_threshold =
					newGroup.threshold;
			}

			return newShippingRate;
		} );

		onChange( newValue );
	};

	const renderGroups = () => {
		/**
		 * If group length is 1, we render the group,
		 * regardless of threshold is defined or not.
		 */
		if ( groups.length === 1 ) {
			return (
				<MinimumOrderInputControl
					countryOptions={ countryOptions }
					value={ groups[ 0 ] }
					onChange={ handleEditChange( groups[ 0 ] ) }
				/>
			);
		}

		/**
		 * When there is more than one group,
		 * we render the groups with defined threshold first.
		 * Then, if there is a group with undefined threshold,
		 * we render an "Add another minimum order" button last.
		 */
		const emptyThresholdGroup = groups.find(
			( group ) => group.threshold === undefined
		);
		const thresholdGroups = groups.filter(
			( group ) => group !== emptyThresholdGroup
		);

		return (
			<>
				{ thresholdGroups.map( ( group ) => {
					return (
						<MinimumOrderInputControl
							key={ group.countries.join( '-' ) }
							countryOptions={ countryOptions }
							value={ group }
							onChange={ handleEditChange( group ) }
						/>
					);
				} ) }
				{ emptyThresholdGroup && (
					<div>
						<AppButtonModalTrigger
							button={
								<AppButton
									isSecondary
									icon={ <GridiconPlusSmall /> }
								>
									{ __(
										'Add another minimum order',
										'google-listings-and-ads'
									) }
								</AppButton>
							}
							modal={
								<AddMinimumOrderModal
									countryOptions={ countryOptions }
									value={ emptyThresholdGroup }
									onChange={ handleAddChange }
								/>
							}
						/>
					</div>
				) }
			</>
		);
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
					{ renderGroups() }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
