/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import EditPrimaryAudience from './edit-primary-audience';
import EditShippingRates from './edit-shipping-rates';
import EditShippingTimes from './edit-shipping-times';
import MarketNotice from '../market-notice';
import MarketForm from '../market-form';
import './index.scss';

const CONTEXT = 'edit_market_modal';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 * @typedef {import('~/data/actions').ShippingRate} ShippingRate
 * @typedef {import('~/data/actions').ShippingTime} ShippingTime
 */

/**
 * Event fired when the "Cancel" button in the EditMarketModal is clicked.
 * @event gla_cancel_button_clicked
 * @property {string} context The context in which the cancel button click happened, e.g. "edit_market_modal".
 */

/**
 * Event fired when the "Save" button in the EditMarketModal is clicked.
 * @event gla_save_button_clicked
 * @property {string} context The context in which the save button click happened, e.g. "edit_market_modal".
 */

/**
 * Modal component for editing an existing market.
 *
 * @fires gla_cancel_button_clicked when the cancel button is clicked with context of "edit_market_modal"
 * @fires gla_save_button_clicked when the save button is clicked with context of "edit_market_modal"
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form with.
 * @param {Array<ShippingRate>} props.shippingRates Shipping rates to pre-populate the form with.
 * @param {Array<ShippingTime>} props.shippingTimes Shipping times to pre-populate the form with.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( {
	market,
	targetAudience,
	shippingRates,
	shippingTimes,
	onRequestClose,
} ) => {
	const { id } = market;
	const isPrimaryMarket = id === PRIMARY_MARKET_ID;

	const appModalTitle = isPrimaryMarket
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: __( 'Edit market', 'google-listings-and-ads' );

	let initialValues = {};
	if ( isPrimaryMarket ) {
		const freeShippingThreshold =
			shippingRates?.[ 0 ]?.options?.free_shipping_threshold ?? null;
		initialValues = {
			countries: targetAudience.countries || [],
			flat_shipping_rate: shippingRates?.[ 0 ]?.rate,
			offer_free_shipping: ( freeShippingThreshold ?? 0 ) > 0,
			free_shipping: freeShippingThreshold,
			flat_shipping_min_time: shippingTimes?.[ 0 ]?.time ?? null,
			flat_shipping_max_time: shippingTimes?.[ 0 ]?.maxTime ?? null,
		};
	}

	return (
		<MarketForm
			initialMarket={ {
				id,
				...initialValues,
			} }
			onSubmit={ onRequestClose }
		>
			{ ( formContext ) => {
				const {
					isValidForm,
					handleSubmit: handleSave,
					isDirty,
					adapter,
				} = formContext;
				const { isSaving } = adapter;

				return (
					<AppModal
						className="gla-edit-market-modal"
						title={ appModalTitle }
						onRequestClose={ onRequestClose }
						overflow="visible"
						buttons={ [
							<AppButton
								key="close"
								variant="tertiary"
								onClick={ onRequestClose }
								disabled={ isSaving }
								eventName="gla_cancel_button_clicked"
								eventProps={ {
									context: CONTEXT,
								} }
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="save"
								variant="primary"
								onClick={ handleSave }
								disabled={ ! isValidForm || ! isDirty }
								loading={ isSaving }
								eventName="gla_save_button_clicked"
								eventProps={ {
									context: CONTEXT,
								} }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						{ isPrimaryMarket && <EditPrimaryAudience /> }
						<EditShippingRates />
						<EditShippingTimes />

						<MarketNotice context="edit-market-modal" />
					</AppModal>
				);
			} }
		</MarketForm>
	);
};

export default EditMarketModal;
