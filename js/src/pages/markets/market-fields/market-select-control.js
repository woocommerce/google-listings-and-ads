/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import AppSelectControl from '~/components/app-select-control';
import useMarkets from '~/hooks/useMarkets';
import usePrimaryMarketDetails from '~/hooks/usePrimaryMarketDetails';

/**
 * Select control for choosing a market (country) when adding a new market. The options are populated from the list of countries in the primary market that are
 * not already claimed by existing secondary markets.
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
	const { data: markets, hasFinishedResolution: hasResolvedMarkets } =
		useMarkets();
	const { getInputProps } = useAdaptiveFormContext();

	if (
		! hasResolvedCountries ||
		! hasResolvedPrimaryMarket ||
		! hasResolvedMarkets
	) {
		return null;
	}

	// Collect all claimed countries from non-primary markets to exclude them from the options list.
	const claimedCountries = new Set(
		markets
			.filter( ( market ) => market.id !== PRIMARY_MARKET_ID )
			.flatMap( ( market ) => market.countries )
	);

	const options = primaryMarket.countries
		.filter( ( countryCode ) => ! claimedCountries.has( countryCode ) )
		.map( ( countryCode ) => ( {
			value: countryCode,
			label: countries[ countryCode ]?.name || countryCode,
		} ) );

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
		<AppSelectControl
			label={ __( 'Market', 'google-listings-and-ads' ) }
			options={ options }
			{ ...appSelectControlProps }
		/>
	);
};

export default MarketSelectControl;
