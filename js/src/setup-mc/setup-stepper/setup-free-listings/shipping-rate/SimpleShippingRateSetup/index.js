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
import './index.scss';

const formKeys = {
	rows: 'shippingRateOption-rows',
	freeShipping: 'shippingRateOption-freeShipping',
	priceOver: 'shippingRateOption-freeShipping-priceOver',
};

const SimpleShippingRateSetup = ( props ) => {
	const {
		formProps: { getInputProps, values, setValue },
	} = props;

	// TODO: call backend API to get the selected countries
	// from Step 2 Choose Your Audience.
	const audienceCountries = [
		{
			key: 'AUS',
			label: 'Australia',
		},
		{
			key: 'USA',
			label: 'United States of America',
		},
	];

	const handleCountriesPriceChange = ( idx ) => ( countriesPrice ) => {
		const newValues = [ ...values[ formKeys.rows ] ];
		newValues[ idx ] = countriesPrice;
		setValue( formKeys.rows, newValues );
	};

	return (
		<div className="gla-simple-shipping-rate-setup">
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
						<AddRateButton />
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

export default SimpleShippingRateSetup;
