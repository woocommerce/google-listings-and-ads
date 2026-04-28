/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

/**
 * Ensures `@wordpress/dataviews` is available: uses an existing global or loads
 * the script from `glaData.dataViewsScriptUrl`.
 *
 * @return {{
 *   dataViewIsLoading: boolean,
 *   dataViewHasFailed: boolean,
 *   dataViewIsReady: boolean
 * }} Loading flags for UI.
 */
const useDataViewsScript = () => {
	const [ dataViewLoaded, setDataViewLoaded ] = useState(
		window.wp?.dataviews
	);
	const { dataViewsScriptUrl } = glaData;

	useEffect( () => {
		if ( dataViewLoaded === undefined && dataViewsScriptUrl ) {
			const script = document.createElement( 'script' );
			script.src = dataViewsScriptUrl;
			script.async = true;

			script.onload = () => {
				setDataViewLoaded(
					typeof window.wp?.dataviews?.filterSortAndPaginate ===
						'function'
				);
			};

			script.onerror = () => {
				setDataViewLoaded( false );
			};

			document.head.appendChild( script );
		}

		return () => {
			if ( dataViewLoaded === false ) {
				setDataViewLoaded( undefined );
			}
		};
	}, [ dataViewLoaded, dataViewsScriptUrl ] );

	return {
		dataViewIsLoading: dataViewLoaded === undefined,
		dataViewHasFailed: dataViewLoaded === false,
		dataViewIsReady: Boolean( dataViewLoaded ),
	};
};

export default useDataViewsScript;
