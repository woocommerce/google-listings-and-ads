/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import VerticalGapLayout from '../../components/vertical-gap-layout';
import AddTimeButton from './add-time-button';
import CountriesTimeInput from './countries-time-input';

const formKeys = {
	rows: 'shippingTimeOption-rows',
	allowGoogleDataCollection: 'shippingTimeOption-allowGoogleDataCollection',
};

const ShippingTimeSetup = ( props ) => {
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
			key: 'CHN',
			label: 'China',
		},
		{
			key: 'USA',
			label: 'United States of America',
		},
	];

	const expectedCountryCount = audienceCountries.length;
	const actualCountryCount = values[ formKeys.rows ].reduce( ( acc, cur ) => {
		return acc + cur.countries.length;
	}, 0 );

	const handleCountriesTimeChange = ( idx ) => ( countriesPrice ) => {
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
									<CountriesTimeInput
										value={ el }
										onChange={ handleCountriesTimeChange(
											idx
										) }
									/>
								</div>
							);
						} ) }
						{ actualCountryCount < expectedCountryCount && (
							<div className="add-rate-button">
								<AddTimeButton />
							</div>
						) }
					</VerticalGapLayout>
				</div>
				<CheckboxControl
					label={ createInterpolateElement(
						__(
							'I allow Google to collect and calculate my shipping times for more accurate estimates. <link>Learn more</link>',
							'google-listings-and-ads'
						),
						{
							link: (
								<Link
									type="external"
									href="https://www.google.com/retail/solutions/merchant-center/"
									target="_blank"
								/>
							),
						}
					) }
					{ ...getInputProps( formKeys.allowGoogleDataCollection ) }
				/>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingTimeSetup;
