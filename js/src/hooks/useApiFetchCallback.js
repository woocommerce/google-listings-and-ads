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
			const data = response.json && ( await response.json() );

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
			const error = response.json
				? await response.json()
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
