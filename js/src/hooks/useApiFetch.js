/**
 * External dependencies
 */
import { useCallback, useReducer } from '@wordpress/element';
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

const useApiFetch = () => {
	const [ state, dispatch ] = useReducer( reducer, initialState );

	const enhancedApiFetch = useCallback( async ( options ) => {
		dispatch( { type: TYPES.START, options } );

		try {
			const response = await apiFetch( { ...options, parse: false } );
			const data = response.json && ( await response.json() );

			dispatch( {
				type: TYPES.FINISH,
				data,
				response,
				options,
			} );

			return { data, response };
		} catch ( e ) {
			const response = e;
			const error = response.json
				? await response.json()
				: new Error( 'No content in fetch response.' );

			dispatch( { type: TYPES.ERROR, error, response, options } );
			throw error;
		}
	}, [] );

	const reset = useCallback( () => {
		dispatch( { type: TYPES.RESET } );
	}, [] );

	const fetchResult = {
		...state,
		reset,
	};

	return [ enhancedApiFetch, fetchResult ];
};

export default useApiFetch;
