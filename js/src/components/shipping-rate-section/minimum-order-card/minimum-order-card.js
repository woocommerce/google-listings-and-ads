/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';
import { noop } from 'lodash';

/**
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
import groupShippingRatesByMethodFreeShippingThreshold from './groupShippingRatesByMethodFreeShippingThreshold';
import {
	handleAdd,
	handleChange,
	handleDelete,
} from './getMinimumOrderHandlers';
import './minimum-order-card.scss';

const MinimumOrderCard = ( props ) => {
	const { value = [], onChange = noop } = props;

	// Bind handlers to a local value and onChange callback.
	const handleValueChange = handleChange.bind( null, value, onChange );
	const handleValueDelete = handleDelete.bind( null, value, onChange );

	const renderGroups = () => {
		const nonZeroShippingRates = value.filter( isNonFreeFlatShippingRate );
		const groups = groupShippingRatesByMethodFreeShippingThreshold(
			nonZeroShippingRates
		);
		const countryOptions = nonZeroShippingRates.map(
			( shippingRate ) => shippingRate.country
		);

		/**
		 * If group length is 1, we render the group,
		 * regardless of threshold is defined or not.
		 */
		if ( groups.length === 1 ) {
			return (
				<MinimumOrderInputControl
					countryOptions={ countryOptions }
					value={ groups[ 0 ] }
					onChange={ handleValueChange.bind( null, groups[ 0 ] ) }
					onDelete={ handleValueDelete.bind( null, groups[ 0 ] ) }
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
							onChange={ handleValueChange.bind( null, group ) }
							onDelete={ handleValueDelete.bind( null, group ) }
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
									onSubmit={ handleAdd.bind(
										null,
										value,
										onChange
									) }
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
