/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TreeSelect } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import useMarkets from '../../../hooks/useMarkets';
import usePrimaryMarketDetails from '../../../hooks/usePrimaryMarketDetails';

/**
 * Renders the market select control within the market edit form.
 * The options for this control are derived from the primary market's countries,
 * which are fetched from the store; thus, this control is only rendered once
 * the relevant data has been resolved.
 */
const MarketSelectControl = () => {
	const {
		data: { countries, continents },
		hasFinishedResolution: hasResolvedCountries,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const {
		data: primaryMarket,
		hasFinishedResolution: hasResolvedPrimaryMarket,
	} = usePrimaryMarketDetails();
	const { data: markets } = useMarkets();
	const {
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	if ( ! hasResolvedCountries || ! hasResolvedPrimaryMarket ) {
		return null;
	}

	// Exclude countries already assigned to a market: the primary market's own
	// countries, as well as every secondary market's country.
	const usedCountries = new Set( [
		...primaryMarket.countries,
		...( markets
			?.filter( ( market ) => market.country )
			.map( ( market ) => market.country ) ?? [] ),
	] );

	const continentGroups = Object.values( continents ).reduce(
		( acc, continent ) => {
			const countryOptions = continent.countries
				.filter( ( countryCode ) => ! usedCountries.has( countryCode ) )
				.map( ( countryCode ) => ( {
					value: countryCode,
					label: countries[ countryCode ]?.name || countryCode,
				} ) );

			if ( countryOptions.length ) {
				acc.push( {
					label: continent.name,
					options: countryOptions,
				} );
			}

			return acc;
		},
		[]
	);

	const { onChange, value } = getInputProps( 'country' );

	return (
		<div>
			<TreeSelect
				label={ __( 'Market', 'google-listings-and-ads' ) }
				onChange={ onChange }
				selectedId={ value ?? '' }
				__next40pxDefaultSize
			>
				<option value="">
					{ __( 'Select…', 'google-listings-and-ads' ) }
				</option>
				{ continentGroups.map(
					( { label, options: countryOptions } ) => (
						<optgroup key={ label } label={ label }>
							{ countryOptions.map(
								( {
									value: countryCode,
									label: countryName,
								} ) => (
									<option
										key={ countryCode }
										value={ countryCode }
									>
										{ countryName }
									</option>
								)
							) }
						</optgroup>
					)
				) }
			</TreeSelect>
			{ renderRequestedValidation( 'country' ) }
		</div>
	);
};

export default MarketSelectControl;
