/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppInputControl from '../../../../../components/app-input-control';
import VerticalGapLayout from '../../components/vertical-gap-layout';
import CountriesPriceInput from './countries-price-input';
import AddRateButton from './add-rate-button';
import useGetAudienceCountries from '../../hooks/useGetAudienceCountries';
import './index.scss';

const formKeys = {
	rows: 'shippingRateOption-rows',
	freeShipping: 'shippingRateOption-freeShipping',
	priceOver: 'shippingRateOption-freeShipping-priceOver',
};

const ShippingRateSetup = ( props ) => {
	const {
		formProps: { getInputProps, values, setValue },
	} = props;

	const audienceCountries = useGetAudienceCountries();
	const expectedCountryCount = audienceCountries.length;
	const actualCountryCount = values[ formKeys.rows ].reduce( ( acc, cur ) => {
		return acc + cur.countries.length;
	}, 0 );

	const handleCountriesPriceChange = ( idx ) => ( countriesPrice ) => {
		const newValues = [ ...values[ formKeys.rows ] ];
		newValues[ idx ] = countriesPrice;
		setValue( formKeys.rows, newValues );
	};

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<div className="countries-price">
					<VerticalGapLayout>
						{ values[ formKeys.rows ].map( ( el, idx ) => {
							return (
								<div
									key={ idx }
									className="countries-price-input"
								>
									<CountriesPriceInput
										value={ el }
										onChange={ handleCountriesPriceChange(
											idx
										) }
									/>
								</div>
							);
						} ) }
						{ actualCountryCount < expectedCountryCount && (
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
							suffix={ __( 'USD', 'google-listings-and-ads' ) }
							{ ...getInputProps( formKeys.priceOver ) }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;
