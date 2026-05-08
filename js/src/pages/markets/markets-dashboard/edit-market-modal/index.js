/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../../constants';
import { useAppDispatch } from '~/data';
import { checkErrors } from '../../utils';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import AppSpinner from '~/components/app-spinner';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import getOfferFreeShippingInitialValue from '~/utils/getOfferFreeShippingInitialValue';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import EditShippingRates from './edit-shipping-rates.js';
import EditShippingTimes from './edit-shipping-times.js';
import AppButton from '~/components/app-button';
import ValidationErrors from '~/components/validation-errors';
import EditPrimaryAudience from './edit-primary-audience';
import ShippingNotice from './shipping-notice';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import {
	buildShippingRatesPayload,
	buildShippingTimesPayload,
} from './utils.js';
import './edit-market-modal.scss';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Placeholder for the Edit Market modal.
 *
 * The follow-up task will replace this with a real form. For now, the modal
 * renders the selected market's name and a Close button so the open/close
 * wiring from `MarketDataViews` can be reviewed end-to-end.
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form with.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, targetAudience, onRequestClose } ) => {
	const { updateMarket, invalidateResolution } = useAppDispatch();
	const { data: shippingRates, hasFinishedResolution: hasResolvedRates } =
		useShippingRates();
	const { data: shippingTimes, hasFinishedResolution: hasResolvedTimes } =
		useShippingTimes();
	const {
		getFinalCountries,
		loading: audienceLoading,
	} = useTargetAudienceFinalCountryCodes();
	const { id } = market;
	const [ saving, setSaving ] = useState( false );
	const isPrimaryMarket = id === PRIMARY_MARKET_ID;
	const formRef = useRef();

	const handleSubmit = async ( values ) => {
		const { id: marketId, ...data } = values;
		setSaving( true );

		try {
			await updateMarket( marketId, data);
			invalidateResolution( 'getTargetAudience', [] );
			onRequestClose();
		} catch ( error ) {}

		setSaving( false );
	};

	const extendAdapter = ( formContext ) => {
		return {
			renderRequestedValidation( key ) {
				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	const appModalTitle = isPrimaryMarket
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: __( 'Edit market', 'google-listings-and-ads' );

	let initialValues = {};
	if ( ! hasResolvedRates || ! hasResolvedTimes || audienceLoading ) {
		return <AppSpinner />;
	}

	const rates = shippingRates ?? [];
	const times = shippingTimes ?? [];
	const nonFreeRates = rates.filter( isNonFreeShippingRate );
	const thresholdFromStore =
		nonFreeRates[ 0 ]?.options?.free_shipping_threshold;

	initialValues = {
		flat_shipping_rate: rates?.[ 0 ]?.rate ?? 0,
		offer_free_shipping:
			getOfferFreeShippingInitialValue( rates ) ?? false,
		free_shipping_threshold: thresholdFromStore,
		shipping_currency: rates?.[ 0 ]?.currency,
		flat_shipping_min_time: times?.[ 0 ]?.time ?? null,
		flat_shipping_max_time: times?.[ 0 ]?.maxTime ?? null,
	};

	if ( isPrimaryMarket ) {
		initialValues.countries = targetAudience.countries || [];
	}

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ {
				id,
				...initialValues,
			} }
			extendAdapter={ extendAdapter }
			validate={ checkErrors }
			onSubmit={ handleSubmit }
		>
			{ ( formContext ) => {
				const {
					isValidForm,
					handleSubmit: handleSave,
					isDirty,
				} = formContext;

				const mergedAudience = isPrimaryMarket
					? {
							...targetAudience,
							countries:
								formContext.values?.countries ??
								targetAudience.countries ??
								[],
					  }
					: targetAudience;
				const audienceCountryCodes =
					getFinalCountries( mergedAudience ) ?? [];

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
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="save"
								variant="primary"
								onClick={ handleSave }
								disabled={ ! isValidForm || ! isDirty }
								loading={ saving }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						{ isPrimaryMarket && <EditPrimaryAudience /> }

						<ShippingNotice />
						<EditShippingRates
							audienceCountryCodes={ audienceCountryCodes }
						/>
						<EditShippingTimes />
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default EditMarketModal;
