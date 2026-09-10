/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

/**
 * A hook that confirms a Search Console reconnect attempt actually completed, refreshes the
 * account data once it has, and cleans up the URL. Search Console shares its OAuth connection
 * with Merchant Center/Ads, so a cancelled attempt leaves that shared connection untouched —
 * the caller should only invoke this once it detects Woo's OAuth redirect confirming genuine
 * success, never on a cancellation.
 *
 * @return {[Function, Object]} Callback to trigger the confirmation, and the underlying fetch result.
 */
const useSearchConsoleReconnectConfirmation = () => {
	const { invalidateResolution } = useAppDispatch();
	const [ fetchConfirmReconnect, result ] = useApiFetchCallback( {
		path: addQueryArgs( `${ API_NAMESPACE }/search-console/connection`, {
			confirm_reconnect: true,
		} ),
	} );

	const handleConfirmReconnect = useCallback( async () => {
		try {
			await fetchConfirmReconnect();
			invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
			getHistory().replace( getNewPath( { 'google-mc': undefined } ) );
		} catch ( error ) {}
	}, [ fetchConfirmReconnect, invalidateResolution ] );

	return [ handleConfirmReconnect, result ];
};

export default useSearchConsoleReconnectConfirmation;
