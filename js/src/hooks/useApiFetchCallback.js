/**
 * External dependencies
 */
import { useCallback, useMemo, useReducer } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';

const TYPES = {
	START: 'START',
	FINISH: 'FINISH',
	ERROR: 'ERROR',
	RESET: 'RESET',
};

const defaultState = {
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
				error: undefined,
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
			return action.state;
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
 * 		path: `/wc/gla/mc/accounts/switch-url`,
 * 		method: 'POST',
 * 		data: { id },
 * } );
 *
 * const handleSwitch = async () => {
 * 		// make the apiFetch call here.
 * 		// account is the response body.
 * 		// you can provide `data` and `query` to override those options for this specific call.
 * 		const account = await fetchMCAccountSwitchUrl();
 *
 * 		receiveMCAccount( account );
 * };
 *
 * if (response.status === 403) {
 * 		return (
 * 			<span>Oops, HTTP status 403.</span>
 * 		)
 * }
 *
 * return (
 * 		<AppButton
 * 			isSecondary
 * 			loading={ loading }  // show loading indicator when loading is true.
 * 			onClick={ handleSwitch }
 * 		>
 * 			{ __(
 * 				'Switch to my new URL',
 * 				'google-listings-and-ads'
 * 			) }
 * 		</AppButton>
 * )
 * ```
 *
 * @param {import('@wordpress/api-fetch').APIFetchOptions} [options] options to be forwarded to `apiFetch`.
 * @param {Object} [initialState] initial state values merged with the default state `{ loading, error, data, response, options }`.
 * @return {Array} `[ apiFetchCallback, fetchResult ]`
 * 		- `apiFetchCallback` is the function to be called to trigger `apiFetch`.
 * 							You call apiFetchCallback in your event handler.
 * 		- `fetchResult` is an object containing things about the `apiFetchCallback` that you called:
 * 							`{ loading, error, data, response, options, reset }`.
 * 							`reset` is a function to reset things to initial state (clearing error, data, response and options).
 * 							You can optionally pass in a reset state and it will be merged with the initial state.
 * 		`apiFetchCallback` accepts an optional `overwriteOptions` argument with only `data` and `query` properties.
 * 		- `data` overrides the request body for the call.
 * 		- `query` overrides the URL query parameters for the call.
 */
const useApiFetchCallback = ( options, initialState = defaultState ) => {
	const optionsRefValue = useIsEqualRefValue( options );
	const initialStateRefValue = useIsEqualRefValue( initialState );
	const mergedState = useMemo(
		() => ( { ...defaultState, ...initialStateRefValue } ),
		[ initialStateRefValue ]
	);
	const [ state, dispatch ] = useReducer( reducer, mergedState );

	const enhancedApiFetch = useCallback(
		async ( overwriteOptions ) => {
			const mergedOptions = { ...optionsRefValue };
			if ( overwriteOptions ) {
				if ( 'data' in overwriteOptions ) {
					mergedOptions.data = overwriteOptions.data;
				}
				if ( 'query' in overwriteOptions ) {
					mergedOptions.query = overwriteOptions.query;
				}
			}

			dispatch( { type: TYPES.START, options: mergedOptions } );

			try {
				const response = await apiFetch( {
					...mergedOptions,
					parse: false,
				} );

				const responseClone = response.clone();
				const data =
					responseClone.json && ( await responseClone.json() );

				dispatch( {
					type: TYPES.FINISH,
					data,
					response,
					options: mergedOptions,
				} );

				return shouldReturnResponseBody( mergedOptions )
					? data
					: response;
			} catch ( e ) {
				/**
				 * If browser is offline.
				 * See: https://github.com/WordPress/gutenberg/blob/bbbe3263a5ca72464e2b713e57639c628fc8d66d/packages/api-fetch/src/index.js#L126-L131
				 */
				if ( e.code === 'fetch_error' ) {
					dispatch( {
						type: TYPES.ERROR,
						error: e,
						response: undefined,
						options: mergedOptions,
					} );
					throw e;
				}

				const response = e;
				let error;

				try {
					const responseClone = response.clone();
					error = responseClone.json
						? await responseClone.json()
						: new Error( 'No content body in fetch response.' );
				} catch ( exception ) {
					error = new Error( 'Error parsing response.' );
				}

				dispatch( {
					type: TYPES.ERROR,
					error,
					response,
					options: mergedOptions,
				} );

				throw shouldReturnResponseBody( mergedOptions )
					? error
					: response;
			}
		},
		[ optionsRefValue ]
	);

	const reset = useCallback(
		( resetState ) => {
			dispatch( {
				type: TYPES.RESET,
				state: { ...mergedState, ...resetState },
			} );
		},
		[ mergedState ]
	);

	const fetchResult = {
		...state,
		reset,
	};

	return [ enhancedApiFetch, fetchResult ];
};

export default useApiFetchCallback;
