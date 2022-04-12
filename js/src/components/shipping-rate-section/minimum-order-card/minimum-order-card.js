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
import AppButton from '.~/components/app-button';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import isNonFreeFlatShippingRate from '.~/utils/isNonFreeFlatShippingRate';
import MinimumOrderInputControl from './minimum-order-input-control';
import AddMinimumOrderModal from './add-minimum-order-modal';
import groupShippingRatesByMethodFreeShippingThreshold from './groupShippingRatesByMethodFreeShippingThreshold';
import './minimum-order-card.scss';

const MinimumOrderCard = ( props ) => {
	const { value = [], onChange = noop } = props;
	const nonZeroShippingRates = value.filter( isNonFreeFlatShippingRate );
	const groups = groupShippingRatesByMethodFreeShippingThreshold(
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
		 * Groups with defined threshold. This is used
		 * to render MinimumOrderInputControl.
		 */
		const thresholdGroups = groups.filter(
			( group ) => group.threshold !== undefined
		);

		/**
		 * The first group with undefined threshold. This is used
		 * to render the "Add another minimum order" button
		 * after all the groups with defined threshold.
		 */
		const emptyThresholdGroup = groups.find(
			( group ) => group.threshold === undefined
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
									countryOptions={
										emptyThresholdGroup.countries
									}
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
				<Section.Card.Title>
					{ __(
						'Minimum order to qualify for free shipping',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					{ renderGroups() }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
