/**
 * External dependencies
 */
import { useReducer } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const TYPES = {
	START: 'START',
	FINISH: 'FINISH',
	ERROR: 'ERROR',
	RESET: 'RESET',
};

const initialState = {
	loading: false,
	error: undefined,
	data: undefined,
	response: undefined,
	options: undefined,
};

const reducer = ( state, action ) => {
	switch ( action.type ) {
		case TYPES.START: {
			return {
				...state,
				loading: true,
				options: action.options,
			};
		}
		case TYPES.FINISH: {
			return {
				...state,
				loading: false,
				data: action.data,
				response: action.response,
				options: action.options,
			};
		}
		case TYPES.ERROR: {
			return {
				...state,
				loading: false,
				error: action.error,
				response: action.response,
				options: action.options,
			};
		}
		case TYPES.RESET: {
			return initialState;
		}
	}
};

const shouldReturnResponseBody = ( options ) => {
	const { parse = true } = options;
	return parse;
};

/**
 * Hook to make a WordPress API request and handle HTTP status codes.
 *
 * ## Motivation
 *
 * Handling the status code with `wp-data` would require a store structure for the status code *and*
 * the response body. Also, the HTTP response status code and data are ephemeral intermediary data
 * to decide the next action. We don’t need it for the whole application lifetime.
 * Storing it into wp-data store seems unnecessary.
 *
 * ## Example
 *
 * ```jsx
 * const [ fetchMCAccountSwitchUrl, { loading, response } ] = useApiFetchCallback( {
 * 	path: `/wc/gla/mc/accounts/switch-url`,
 * 	method: 'POST',
 * 	data: { id },
 * } );
 *
 * const handleSwitch = async () => {
 *         // make the apiFetch call here.
 *         // account is the response body.
 *         // you can provide option in the function call and it will be merged with the original option.
 * 	const account = await fetchMCAccountSwitchUrl();
 *
 * 	receiveMCAccount( account );
 * };
 *
 * if (response.status === 403) {
 *     return (
 *         <span>Oops, HTTP status 403.</span>
 *     )
 * }
 *
 * return (
 *     <AppButton
 *         isSecondary
 *         loading={ loading }  // show loading indicator when loading is true.
 *         onClick={ handleSwitch }
 *     >
 *         { __(
 *             'Switch to my new URL',
 *             'google-listings-and-ads'
 *         ) }
 *     </AppButton>
 * )
 * ```
 *
 * @param {module:wordpress/api-fetch.APIFetchOptions} [options] options to be forwarded to `apiFetch`.
 *
 * @return {Array} `[ apiFetchCallback, fetchResult ]`
 * 		- `apiFetchCallback` is the function to be called to trigger `apiFetch`.
 * 							You call apiFetchCallback in your event handler.
 * 		- `fetchResult` is an object containing things about the `apiFetchCallback` that you called:
 * 							`{ loading, error, data, response, options, reset }`.
 * 							`reset` is a function to reset things to initial state (clearing error, data, response and options).
 */
const useApiFetchCallback = ( options ) => {
	const [ state, dispatch ] = useReducer( reducer, initialState );

	const enhancedApiFetch = async ( overwriteOptions ) => {
		const combinedOptions = {
			...options,
			...overwriteOptions,
		};

		dispatch( { type: TYPES.START, options: combinedOptions } );

		try {
			const response = await apiFetch( {
				...combinedOptions,
				parse: false,
			} );

			const responseClone = response.clone();
			const data = responseClone.json && ( await responseClone.json() );

			dispatch( {
				type: TYPES.FINISH,
				data,
				response,
				options: combinedOptions,
			} );

			return shouldReturnResponseBody( combinedOptions )
				? data
				: response;
		} catch ( e ) {
			const response = e;

			const responseClone = response.clone();
			const error = responseClone.json
				? await responseClone.json()
				: new Error( 'No content body in fetch response.' );

			dispatch( {
				type: TYPES.ERROR,
				error,
				response,
				options: combinedOptions,
			} );

			throw shouldReturnResponseBody( combinedOptions )
				? error
				: response;
		}
	};

	const reset = () => {
		dispatch( { type: TYPES.RESET } );
	};

	const fetchResult = {
		...state,
		reset,
	};

	return [ enhancedApiFetch, fetchResult ];
};

export default useApiFetchCallback;
