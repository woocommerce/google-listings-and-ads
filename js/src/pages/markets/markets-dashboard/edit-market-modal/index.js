/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState, useCallback } from '@wordpress/element';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../../constants';
import { useAppDispatch } from '~/data';
import { checkErrors } from '../../utils';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import EstimatedShippingRatesSection from './estimated-shipping-rates-section';
import EstimatedShippingTimesSection from './estimated-shipping-times-section';
import AppButton from '~/components/app-button';
import ValidationErrors from '~/components/validation-errors';
import EditPrimaryAudience from './edit-primary-audience.js';
import ShippingNotice from './shipping-notice';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';

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
	const { saveShippingRates } = useSaveShippingRates();
	const { getFinalCountries } = useTargetAudienceFinalCountryCodes();
	const { id } = market;
	const [ saving, setSaving ] = useState( false );
	const [ shippingRatesDirty, setShippingRatesDirty ] = useState( false );
	const isPrimaryMarket = id === PRIMARY_MARKET_ID;
	const formRef = useRef();
	const latestRatesRef = useRef( null );
	const baselineRatesRef = useRef( null );

	const handleRatesPayload = useCallback( ( rates, { isBaseline } ) => {
		latestRatesRef.current = rates;
		if ( isBaseline ) {
			baselineRatesRef.current = rates;
		}
		setShippingRatesDirty(
			Boolean(
				baselineRatesRef.current &&
					latestRatesRef.current &&
					! isEqual(
						baselineRatesRef.current,
						latestRatesRef.current
					)
			)
		);
	}, [] );

	const handleSubmit = async ( values ) => {
		setSaving( true );

		try {
			if ( isPrimaryMarket ) {
				await updateMarket( values.id, {
					countries: values.countries,
				} );
				if ( latestRatesRef.current?.length ) {
					await saveShippingRates( latestRatesRef.current );
				}
				invalidateResolution( 'getTargetAudience', [] );
				invalidateResolution( 'getShippingRates', [] );
				onRequestClose();
			}
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
	if ( isPrimaryMarket ) {
		initialValues = {
			countries: targetAudience.countries || [],
		};
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
								disabled={
									! isValidForm ||
									( ! isDirty && ! shippingRatesDirty )
								}
								loading={ saving }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						{ isPrimaryMarket && <EditPrimaryAudience /> }

						<ShippingNotice />
						<EstimatedShippingRatesSection
							key={ `estimated-rates-${ id }` }
							audienceCountryCodes={ audienceCountryCodes }
							onRatesPayloadChange={
								isPrimaryMarket
									? handleRatesPayload
									: undefined
							}
						/>
						<EstimatedShippingTimesSection />
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default EditMarketModal;
