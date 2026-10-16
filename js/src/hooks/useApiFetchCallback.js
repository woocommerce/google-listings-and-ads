/**
 * External dependencies
 */
import { useCallback, useMemo, useReducer } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';
import { logWarning } from '~/utils/console';

/**
 * The only options that can be overwritten when calling the callback returned by `useApiFetchCallback`.
 * Everything else (e.g. `path`, `method`, `parse`) is bound to the hook instance.
 */
const OVERWRITABLE_OPTIONS = [ 'data', 'query' ];

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
 * Warns during development about overwrite options that were ignored, instead of
 * silently calling the wrong endpoint or method.
 *
 * @param {string[]} ignoredKeys The ignored option keys.
 */
const warnIgnoredOptions = ( ignoredKeys ) => {
	if ( process.env.NODE_ENV === 'production' ) {
		return;
	}

	logWarning(
		`useApiFetchCallback: only ${ OVERWRITABLE_OPTIONS.join(
			', '
		) } can be overwritten when calling the callback, and \`query\` needs a \`path\` or \`url\`. Ignored: ${ ignoredKeys.join(
			', '
		) }. Set these options when creating the hook instead.`
	);
};

/**
 * Merges the call-time overwrite options into the options the hook was created with.
 *
 * Only `data` and `query` are applied. `query` is not an `apiFetch` option, so it's
 * added to the `path` (or `url`) as query args, replacing any args with the same keys.
 *
 * @param {import('@wordpress/api-fetch').APIFetchOptions} options The options the hook was created with.
 * @param {Object} [overwriteOptions] The options passed when calling the callback.
 * @return {import('@wordpress/api-fetch').APIFetchOptions} The options to call `apiFetch` with.
 */
const mergeOverwriteOptions = ( options, overwriteOptions ) => {
	const mergedOptions = { ...options };

	if ( ! overwriteOptions ) {
		return mergedOptions;
	}

	// Anything other than a plain options object (e.g. a string) can't be merged, so ignore it
	// rather than throwing on the `in` checks below.
	if ( typeof overwriteOptions !== 'object' ) {
		warnIgnoredOptions( [ String( overwriteOptions ) ] );
		return mergedOptions;
	}

	const ignoredKeys = Object.keys( overwriteOptions ).filter(
		( key ) => ! OVERWRITABLE_OPTIONS.includes( key )
	);

	if ( 'data' in overwriteOptions ) {
		mergedOptions.data = overwriteOptions.data;
	}

	if ( overwriteOptions.query ) {
		const urlKey = [ 'path', 'url' ].find(
			( key ) => mergedOptions[ key ]
		);

		// Without a `path` or `url` there's nothing to add the query args to.
		if ( urlKey ) {
			mergedOptions[ urlKey ] = addQueryArgs(
				mergedOptions[ urlKey ],
				overwriteOptions.query
			);
		} else {
			ignoredKeys.push( 'query' );
		}
	}

	if ( ignoredKeys.length ) {
		warnIgnoredOptions( ignoredKeys );
	}

	return mergedOptions;
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
 * 		// you can provide `data` and `query` to override the request body or query args for this specific call.
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
 * @param {Object} [initialState=defaultState] initial state values merged with the default state `{ loading, error, data, response, options }`.
 * @return {Array} `[ apiFetchCallback, fetchResult ]`
 * 		- `apiFetchCallback` is the function to be called to trigger `apiFetch`.
 * 							You call apiFetchCallback in your event handler.
 * 		- `fetchResult` is an object containing things about the `apiFetchCallback` that you called:
 * 							`{ loading, error, data, response, options, reset }`.
 * 							`reset` is a function to reset things to initial state (clearing error, data, response and options).
 * 							You can optionally pass in a reset state and it will be merged with the initial state.
 * 		`apiFetchCallback` accepts an optional `overwriteOptions` argument with only `data` and `query` properties.
 * 		- `data` overrides the request body for the call.
 * 		- `query` is an object of query args added to the `path` (or `url`) for the call, replacing any with the same keys.
 * 		Any other property is ignored, with a warning in development.
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
			const mergedOptions = mergeOverwriteOptions(
				optionsRefValue,
				overwriteOptions
			);

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
