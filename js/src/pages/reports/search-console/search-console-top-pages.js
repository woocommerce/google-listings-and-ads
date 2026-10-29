/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import TopProductPages from './top-product-pages';
import TopStorePages from './top-store-pages';

/**
 * The page tables of the Organic search report. The top product pages are only shown once a
 * Merchant Center account is connected, so nothing is shown until that is known, which keeps the
 * tables from jumping around while it loads.
 */
const SearchConsoleTopPages = () => {
	const { hasGoogleMCConnection, hasFinishedResolution } =
		useGoogleMCAccount();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<>
			{ hasGoogleMCConnection && <TopProductPages /> }
			<TopStorePages />
		</>
	);
};

export default SearchConsoleTopPages;
