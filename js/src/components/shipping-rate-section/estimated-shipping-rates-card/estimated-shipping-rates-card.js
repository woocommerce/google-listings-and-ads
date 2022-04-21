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
import { AddRateFormModal, EditRateFormModal } from './rate-form-modals';
import { SHIPPING_RATE_METHOD } from '.~/constants';
import getHandlers from './getHandlers';

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 */

/**
 * The "Estimated shipping rates" card to provide shipping rates for individual countries,
 * with an UI, that allows to aggregate countries with the same rate.
 *
 * @param {Object} props
 * @param {Array<ShippingRate>} props.value Array of individual shipping rates to be used as the initial values of the form.
 * @param {Array<CountryCode>} props.audienceCountries Array of country codes of all audience countries.
 * @param {(newValue: Array<ShippingRate>) => void} props.onChange Callback called with new data once shipping rates are changed.
 */
export default function EstimatedShippingRatesCard( {
	audienceCountries,
	value,
	onChange,
} ) {
	const { code: currencyCode } = useStoreCurrency();
	const {
		handleAddSubmit,
		getChangeHandler,
		getDeleteHandler,
	} = getHandlers( { value, onChange } );

	/**
	 * An Edit button that displays EditRateFormModal to edit shipping rate group upon clicking on the Edit button.
	 *
	 * @param {Object} props Props.
	 * @param {Array<CountryCode>} props.countryOptions Country options to be passed to EditRateFormModal.
	 * @param {ShippingRateGroup} props.group Shipping rate group to be edited.
	 */
	const GroupEditModalButton = ( { countryOptions, group } ) => {
		return (
			<AppButtonModalTrigger
				button={
					<Button isTertiary>
						{ __( 'Edit', 'google-listings-and-ads' ) }
					</Button>
				}
				modal={
					<EditRateFormModal
						countryOptions={ countryOptions }
						initialValues={ group }
						onSubmit={ getChangeHandler( group ) }
						onDelete={ getDeleteHandler( group ) }
					/>
				}
			/>
		);
	};

	/**
	 * Function to render the shipping rate groups from `value`.
	 *
	 * If there is no group, we render a `ShippingRateInputControl`
	 * with a pre-filled group, so that users can straight away
	 * key in shipping rate for all countries immediately.
	 *
	 * If there are groups, we render `ShippingRateInputControl` for each group,
	 * and render an "Add rate button" if there are remaining countries.
	 */
	const renderGroups = () => {
		const groups = groupShippingRatesByMethodCurrencyRate( value );

		if ( groups.length === 0 ) {
			const prefilledGroup = {
				countries: audienceCountries,
				method: SHIPPING_RATE_METHOD.FLAT_RATE,
				currency: currencyCode,
				rate: undefined,
			};

			return (
				<ShippingRateInputControl
					labelButton={
						<GroupEditModalButton
							countryOptions={ audienceCountries }
							group={ prefilledGroup }
						/>
					}
					value={ prefilledGroup }
					onChange={ getChangeHandler( prefilledGroup ) }
				/>
			);
		}

		/**
		 * The remaining countries that do not have a shipping rate value yet.
		 */
		const remainingCountries = audienceCountries.filter( ( country ) => {
			const exist = value.some(
				( shippingRate ) =>
					shippingRate.country === country &&
					shippingRate.method === SHIPPING_RATE_METHOD.FLAT_RATE
			);

			return ! exist;
		} );

		return (
			<>
				{ groups.map( ( group ) => {
					return (
						<ShippingRateInputControl
							key={ group.countries.join( '-' ) }
							labelButton={
								<GroupEditModalButton
									countryOptions={ audienceCountries }
									group={ group }
								/>
							}
							value={ group }
							onChange={ getChangeHandler( group ) }
						/>
					);
				} ) }
				{ remainingCountries.length >= 1 && (
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
										method: SHIPPING_RATE_METHOD.FLAT_RATE,
										currency: currencyCode,
										rate: 0,
									} }
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
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping rates',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					{ renderGroups() }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
}
