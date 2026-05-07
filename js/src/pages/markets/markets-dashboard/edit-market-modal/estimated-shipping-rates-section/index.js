/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '~/components/app-input-price-control';
import AppSpinner from '~/components/app-spinner';
import EstimatedShippingRatesCard from '~/components/shipping-rate-section/estimated-shipping-rates-card/estimated-shipping-rates-card';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import useShippingRates from '~/hooks/useShippingRates';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import getOfferFreeShippingInitialValue from '~/utils/getOfferFreeShippingInitialValue';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import './index.scss';

const EstimatedShippingRatesSection = ( {
	audienceCountryCodes = [],
	onRatesPayloadChange,
} ) => {
	const { data: shippingRates, hasFinishedResolution } = useShippingRates();
	const { loading: audienceLoading } = useTargetAudienceFinalCountryCodes();

	const [ estimatedRate, setEstimatedRate ] = useState( 0 );
	const [ offerFreeShipping, setOfferFreeShipping ] = useState( false );
	const [ costThreshold, setCostThreshold ] = useState();
	const [ ready, setReady ] = useState( false );
	const isFirstPayload = useRef( true );
	const audienceKeyRef = useRef( null );

	useEffect( () => {
		const nextKey = audienceCountryCodes.join( ',' );
		if ( audienceKeyRef.current !== nextKey ) {
			audienceKeyRef.current = nextKey;
			isFirstPayload.current = true;
		}
	}, [ audienceCountryCodes ] );

	useEffect( () => {
		if ( ! hasFinishedResolution || audienceLoading ) {
			return;
		}
		const rates = shippingRates ?? [];
		const nonFreeRates = rates.filter( isNonFreeShippingRate );
		const thresholdFromStore =
			nonFreeRates[ 0 ]?.options?.free_shipping_threshold;

		setEstimatedRate( rates?.[ 0 ]?.rate ?? 0 );
		setOfferFreeShipping(
			getOfferFreeShippingInitialValue( rates ) ?? false
		);
		setCostThreshold( thresholdFromStore );
		setReady( true );
	}, [ hasFinishedResolution, audienceLoading, shippingRates ] );

	useEffect( () => {
		if ( ! ready || ! onRatesPayloadChange ) {
			return;
		}
		const existingByCountry = new Map(
			( shippingRates ?? [] ).map( ( r ) => [ r.country, r ] )
		);
		const currency = shippingRates?.[ 0 ]?.currency ?? '';
		const ratesPayload = audienceCountryCodes.map( ( country ) => {
			const existing = existingByCountry.get( country );
			const threshold = offerFreeShipping
				? costThreshold > 0
					? costThreshold
					: existing?.options?.free_shipping_threshold
				: undefined;
			return {
				id: existing?.id,
				country,
				currency,
				rate: estimatedRate,
				options: {
					free_shipping_threshold: threshold,
				},
			};
		} );
		const isBaseline = isFirstPayload.current;
		if ( isFirstPayload.current ) {
			isFirstPayload.current = false;
		}
		onRatesPayloadChange( ratesPayload, { isBaseline } );
	}, [
		ready,
		estimatedRate,
		offerFreeShipping,
		costThreshold,
		audienceCountryCodes,
		shippingRates,
		onRatesPayloadChange,
	] );

	if ( ! hasFinishedResolution || audienceLoading || ! ready ) {
		return <AppSpinner />;
	}

	const currencyCode = shippingRates?.[ 0 ]?.currency;

	const handleCostBlur = ( _event, numberValue ) => {
		setCostThreshold(
			numberValue > 0 ? numberValue : undefined
		);
	};

	return (
		<EstimatedShippingRatesCard
			className="gla-edit-market-modal__estimated-rates"
			audienceCountries={ audienceCountryCodes }
			value={ estimatedRate }
			onChange={ setEstimatedRate }
			helper={
				<div className="gla-edit-market-modal__estimated-rates__helper">
					<VerticalGapLayout size="large">
						<OfferFreeShippingCheckbox
							value={ offerFreeShipping }
							onChange={ setOfferFreeShipping }
						/>
						{ offerFreeShipping && (
							<div className="gla-edit-market-modal__estimated-rates__cost">
								<AppInputPriceControl
									label={ __(
										'Cost',
										'google-listings-and-ads'
									) }
									suffix={ currencyCode }
									value={ costThreshold }
									onBlur={ handleCostBlur }
								/>
							</div>
						) }
					</VerticalGapLayout>
				</div>
			}
		/>
	);
};

export default EstimatedShippingRatesSection;
