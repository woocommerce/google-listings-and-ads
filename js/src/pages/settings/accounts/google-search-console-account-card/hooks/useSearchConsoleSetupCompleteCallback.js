/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

/**
 * A hook that confirms a Search Console OAuth setup actually completed, and refreshes the
 * account data once it has. Search Console shares its OAuth connection with Merchant
 * Center/Ads, so a cancelled attempt leaves that shared connection untouched — the caller
 * should only invoke this once it detects Woo's OAuth redirect confirming genuine success,
 * never on a cancellation.
 *
 * @return {[Function, Object]} Callback to trigger the confirmation, and the underlying fetch result.
 */
const useSearchConsoleSetupCompleteCallback = () => {
	const { invalidateResolution } = useAppDispatch();
	const [ fetchCompleteSetup, result ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/setup/complete`,
		method: 'POST',
	} );

	const handleCompleteSetup = useCallback( async () => {
		try {
			await fetchCompleteSetup();
			invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
		} catch ( error ) {}
	}, [ fetchCompleteSetup, invalidateResolution ] );

	return [ handleCompleteSetup, result ];
};

export default useSearchConsoleSetupCompleteCallback;
