/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';
import { noop } from 'lodash';

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
import {
	AddMinimumOrderFormModal,
	EditMinimumOrderFormModal,
} from './minimum-order-form-modals';
import groupShippingRatesByMethodFreeShippingThreshold from './groupShippingRatesByMethodFreeShippingThreshold';
import getMinimumOrderHandlers from './getMinimumOrderHandlers';
import './minimum-order-card.scss';

const MinimumOrderCard = ( props ) => {
	const { value = [], onChange = noop } = props;
	const {
		handleAddSubmit,
		getChangeHandler,
		getDeleteHandler,
	} = getMinimumOrderHandlers( { value, onChange } );

	/**
	 * Render an Edit button that displays EditMinimumOrderFormModal upon click.
	 *
	 * @param {Object} props Props.
	 * @param {Array<CountryCode>} props.countryOptions Country options to be passed to EditMinimumOrderFormModal.
	 * @param {MinimumOrderGroup} props.group Minimum order group to be edited.
	 */
	const renderGroupEditModalButton = ( { countryOptions, group } ) => {
		return (
			<AppButtonModalTrigger
				button={
					<AppButton isTertiary>
						{ __( 'Edit', 'google-listings-and-ads' ) }
					</AppButton>
				}
				modal={
					<EditMinimumOrderFormModal
						countryOptions={ countryOptions }
						initialValues={ group }
						onSubmit={ getChangeHandler( group ) }
						onDelete={ getDeleteHandler( group ) }
					/>
				}
			/>
		);
	};

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
					labelButton={ renderGroupEditModalButton( {
						countryOptions,
						group: groups[ 0 ],
					} ) }
					value={ groups[ 0 ] }
					onChange={ getChangeHandler( groups[ 0 ] ) }
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
							labelButton={ renderGroupEditModalButton( {
								countryOptions,
								group,
							} ) }
							value={ group }
							onChange={ getChangeHandler( group ) }
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
									onSubmit={ handleAddSubmit }
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
