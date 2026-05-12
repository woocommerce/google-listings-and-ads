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
import usePrimaryMarketDetails from '~/hooks/usePrimaryMarketDetails';

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
	const { getInputProps } = useAdaptiveFormContext();

	if ( ! hasResolvedCountries || ! hasResolvedPrimaryMarket ) {
		return null;
	}

	const options = primaryMarket.countries.map( ( countryCode ) => ( {
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
