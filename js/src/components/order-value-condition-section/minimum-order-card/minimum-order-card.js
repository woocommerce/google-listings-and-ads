/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * @typedef { import("~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import useToggle from '~/hooks/useToggle';
import { useAdaptiveFormInputProps } from '~/components/adaptive-form';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import MinimumOrderInputControl from './minimum-order-input-control';
import { AddMinimumOrderFormModal } from './minimum-order-form-modals';
import groupShippingRatesByCurrencyFreeShippingThreshold from './groupShippingRatesByCurrencyFreeShippingThreshold';
import { calculateValueFromGroupChange } from './calculateValueFromGroupChange';
import './minimum-order-card.scss';

/**
 * Renders a Card UI to provide the free shipping threshold for individual countries.
 *
 * @param {Object} props React props.
 * @param {Array<ShippingRate>} [props.value=[]] Array of individual shipping rates to be used as the initial values of the form.
 * @param {JSX.Element} [props.helper] Helper content to be rendered at the bottom of the card body.
 * @param {(nextValue: Array<ShippingRate>) => void} props.onChange Callback called with the next data once shipping rates are changed.
 */
const MinimumOrderCard = ( { value = [], helper, onChange } ) => {
	const offerFreeShippingCardInputProps = useAdaptiveFormInputProps(
		'offer_free_shipping'
	);
	const [ isAddModalOpen, toggleAddModal ] = useToggle();

	const renderGroups = () => {
		const nonZeroShippingRates = value.filter( isNonFreeShippingRate );
		const groups =
			groupShippingRatesByCurrencyFreeShippingThreshold(
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
						<AppButton
							isSecondary
							icon={ <GridiconPlusSmall /> }
							onClick={ toggleAddModal }
						>
							{ __(
								'Add another condition',
								'google-listings-and-ads'
							) }
						</AppButton>
						{ isAddModalOpen && (
							<AddMinimumOrderFormModal
								countryOptions={ emptyThresholdGroup.countries }
								initialValues={ emptyThresholdGroup }
								onSubmit={ addHandler }
								onRequestClose={ toggleAddModal }
							/>
						) }
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
						'Only select if applicable',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<OfferFreeShippingCheckbox
						{ ...offerFreeShippingCardInputProps }
					/>
					{ renderGroups() }
				</VerticalGapLayout>
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
