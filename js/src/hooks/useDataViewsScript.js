/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

const isDataViewsReady = () =>
	typeof window.wp?.dataviews?.filterSortAndPaginate === 'function';

/**
 * Module-level singleton: deduplicates concurrent loads across hook instances
 * and across tab switches that happen mid-load.
 */
let loadPromise;

const ensureDataViewsScript = ( url ) => {
	if ( isDataViewsReady() ) {
		return Promise.resolve( true );
	}

	if ( loadPromise ) {
		return loadPromise;
	}

	if ( ! url ) {
		return Promise.resolve( false );
	}

	loadPromise = new Promise( ( resolve ) => {
		const script = document.createElement( 'script' );
		script.src = url;
		script.async = true;
		script.onload = () => resolve( isDataViewsReady() );
		script.onerror = () => {
			loadPromise = undefined; // Reset so a future mount can retry.
			resolve( false );
		};
		document.head.appendChild( script );
	} );

	return loadPromise;
};

/**
 * Ensures `@wordpress/dataviews` is available: uses an existing global or loads
 * the script from `glaData.dataViewsScriptUrl`. The script URL is expected to
 * match the `wp-dataviews` version shipped by the host WordPress instance.
 *
 * @return {'loading' | 'ready' | 'failed'} Current load status.
 */
const useDataViewsScript = () => {
	const [ status, setStatus ] = useState( () =>
		isDataViewsReady() ? 'ready' : 'loading'
	);
	const { dataViewsScriptUrl } = glaData;

	useEffect( () => {
		if ( status === 'ready' ) {
			return;
		}

		let isMounted = true;

		ensureDataViewsScript( dataViewsScriptUrl ).then( ( ok ) => {
			if ( ! isMounted ) {
				return;
			}

			setStatus( ok ? 'ready' : 'failed' );
		} );

		return () => {
			isMounted = false;
		};
	}, [ status, dataViewsScriptUrl ] );

	return status;
};

export default useDataViewsScript;
