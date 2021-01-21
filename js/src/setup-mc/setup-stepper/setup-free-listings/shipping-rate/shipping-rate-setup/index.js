/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AppInputControl from '../../../../../components/app-input-control';
import VerticalGapLayout from '../../components/vertical-gap-layout';
import AddRateButton from './add-rate-button';
import { STORE_KEY } from '../../../../../data';
import './index.scss';
import CountriesPriceInputForm from './countries-price-input-form';
import useStoreCurrency from '../../../../../hooks/useStoreCurrency';

const formKeys = {
	rows: 'shippingRateOption-rows',
	freeShipping: 'shippingRateOption-freeShipping',
	priceOver: 'shippingRateOption-freeShipping-priceOver',
};

const getCountriesPriceArray = ( shippingRates ) => {
	const map = new Map();

	shippingRates.forEach( ( el ) => {
		const { countryCode, rate } = el;
		const arr = map.get( rate ) || [];
		arr.push( countryCode );
		map.set( rate, arr );
	} );

	const arr = [];
	for ( const [ rate, countryCodes ] of map ) {
		arr.push( {
			countries: countryCodes,
			price: rate,
		} );
	}

	return arr;
};

const ShippingRateSetup = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;
	const selectedCountryCodes = useSelect( ( select ) =>
		select( STORE_KEY ).getAudienceSelectedCountryCodes()
	);
	const shippingRates = useSelect( ( select ) =>
		select( STORE_KEY ).getShippingRates()
	);
	const { code } = useStoreCurrency();

	const expectedCountryCount = selectedCountryCodes.length;
	const actualCountryCount = shippingRates.length;
	const remainingCount = expectedCountryCount - actualCountryCount;

	const countriesPriceArray = getCountriesPriceArray( shippingRates );

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<div className="countries-price">
					<VerticalGapLayout>
						{ shippingRates.length === 0 && (
							<CountriesPriceInputForm
								initialValue={ {
									countries: selectedCountryCodes,
									price: '',
									currency: code,
								} }
							/>
						) }
						{ countriesPriceArray.map( ( el, idx ) => {
							return (
								<div
									key={ idx }
									className="countries-price-input"
								>
									<CountriesPriceInputForm
										initialValue={ el }
									/>
								</div>
							);
						} ) }
						{ actualCountryCount >= 1 && remainingCount >= 1 && (
							<div className="add-rate-button">
								<AddRateButton />
							</div>
						) }
					</VerticalGapLayout>
				</div>
				<CheckboxControl
					label={ __(
						'I also offer free shipping for all countries for products over a certain price.',
						'google-listings-and-ads'
					) }
					{ ...getInputProps( formKeys.freeShipping ) }
				/>
				{ values[ formKeys.freeShipping ] && (
					<div className="price-over-input">
						<AppInputControl
							label={ __(
								'I offer free shipping for products priced over',
								'google-listings-and-ads'
							) }
							suffix={ code }
							{ ...getInputProps( formKeys.priceOver ) }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;
