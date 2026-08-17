/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import AppSelectControl from '~/components/app-select-control';
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
		data: { countries },
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

	// Exclude countries already assigned to other markets,
	// as well as the primary market's own countries.
	// Technically the primary market's countries should be up to date
	// in the form state, but this ensures the list is correct even if not.
	const usedCountries = new Set(
		markets
			?.filter( ( market ) => market.country )
			.map( ( market ) => market.country )
	);

	const options = [
		{ value: '', label: __( 'Select…', 'google-listings-and-ads' ) },
		...primaryMarket.countries
			.filter( ( countryCode ) => ! usedCountries.has( countryCode ) )
			.map( ( countryCode ) => ( {
				value: countryCode,
				label: countries[ countryCode ]?.name || countryCode,
			} ) ),
	];

	const inputProps = getInputProps( 'country' );

	const appSelectControlProps = {
		...inputProps,
		...( ! inputProps.selected
			? {
					autoSelectFirstOption: true,
					value: undefined,
			  }
			: {} ),
	};

	return (
		<div>
			<AppSelectControl
				label={ __( 'Market', 'google-listings-and-ads' ) }
				options={ options }
				{ ...appSelectControlProps }
			/>
			{ renderRequestedValidation( 'country' ) }
		</div>
	);
};

export default MarketSelectControl;
