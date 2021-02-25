/**
 * External dependencies
 */
import { useCallback, useReducer } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const TYPES = {
	START: 'START',
	FINISH: 'FINISH',
	ERROR: 'ERROR',
};

const initialState = {
	loading: false,
	error: undefined,
	data: undefined,
	response: undefined,
};

const reducer = ( state, action ) => {
	switch ( action.type ) {
		case TYPES.START: {
			return {
				...state,
				loading: true,
			};
		}
		case TYPES.FINISH: {
			return {
				...state,
				loading: false,
				data: action.data,
				response: action.response,
			};
		}
		case TYPES.ERROR: {
			return {
				...state,
				loading: false,
				error: action.error,
				response: action.response,
			};
		}
	}
};

const useApiFetch = () => {
	const [ state, dispatch ] = useReducer( reducer, initialState );

	const enhancedApiFetch = useCallback( async ( options ) => {
		dispatch( { type: TYPES.START } );

		try {
			const response = await apiFetch( { ...options, parse: false } );
			const data = response.json && ( await response.json() );

			dispatch( {
				type: TYPES.FINISH,
				data,
				response,
			} );

			return { data, response };
		} catch ( e ) {
			const response = e;
			const error = response.json
				? await response.json()
				: new Error( 'No content in fetch response.' );

			dispatch( { type: TYPES.ERROR, error, response } );
			throw error;
		}
	}, [] );

	return [ enhancedApiFetch, state ];
};

export default useApiFetch;
