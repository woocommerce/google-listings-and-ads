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
			};
		}
		case TYPES.ERROR: {
			return {
				...state,
				loading: false,
				error: action.error,
			};
		}
	}
};

const useApiFetch = () => {
	const [ state, dispatch ] = useReducer( reducer, initialState );

	const enhancedApiFetch = useCallback( async ( options ) => {
		let response;
		dispatch( { type: TYPES.START } );

		try {
			response = await apiFetch( options );
			dispatch( {
				type: TYPES.FINISH,
				data: response,
			} );
		} catch ( error ) {
			dispatch( { type: TYPES.ERROR, error } );
			throw error;
		}

		return response;
	}, [] );

	return [ enhancedApiFetch, state ];
};

export default useApiFetch;
