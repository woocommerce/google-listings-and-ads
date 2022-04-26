/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import isNonFreeFlatShippingRate from '.~/utils/isNonFreeFlatShippingRate';
import MinimumOrderInputControl from './minimum-order-input-control';
import { AddMinimumOrderFormModal } from './minimum-order-form-modals';
import groupShippingRatesByFreeShippingThreshold from './groupShippingRatesByFreeShippingThreshold';
import { calculateValueFromGroupChange } from './calculateValueFromGroupChange';
import './minimum-order-card.scss';

const MinimumOrderCard = ( props ) => {
	const { value = [], onChange } = props;

	const renderGroups = () => {
		const nonZeroShippingRates = value.filter( isNonFreeFlatShippingRate );
		const groups = groupShippingRatesByFreeShippingThreshold(
			nonZeroShippingRates
		);
		const countryOptions = nonZeroShippingRates.map(
			( shippingRate ) => shippingRate.country
		);

		// Event handlers for add, update, delete operations.
		const addHandler = ( newGroup ) => {
			onChange( calculateValueFromGroupChange( value, null, newGroup ) );
		};
		const getChangeHandler = ( oldGroup ) => ( newGroup ) => {
			onChange(
				calculateValueFromGroupChange( value, oldGroup, newGroup )
			);
		};
		const getDeleteHandler = ( oldGroup ) => () => {
			onChange( calculateValueFromGroupChange( value, oldGroup ) );
		};

		// If group length is 1, we render the group,
		// regardless of threshold is defined or not.
		if ( groups.length === 1 ) {
			return (
				<MinimumOrderInputControl
					countryOptions={ countryOptions }
					value={ groups[ 0 ] }
					onChange={ getChangeHandler( groups[ 0 ] ) }
					onDelete={ getDeleteHandler( groups[ 0 ] ) }
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
							onChange={ getChangeHandler( group ) }
							onDelete={ getDeleteHandler( group ) }
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
								<AddMinimumOrderFormModal
									countryOptions={
										emptyThresholdGroup.countries
									}
									initialValues={ emptyThresholdGroup }
									onSubmit={ addHandler }
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
