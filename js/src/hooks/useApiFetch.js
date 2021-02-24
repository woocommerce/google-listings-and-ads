/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const useApiFetch = () => {
	// TODO: consider useReducer so that we can
	// avoid multiple setState within enhancedApiFetch.
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState();
	const [ data, setData ] = useState();

	const enhancedApiFetch = async ( options ) => {
		let response;
		setLoading( true );
		setError();

		try {
			response = await apiFetch( options );
			setData( response );
		} catch ( e ) {
			setError( e );
			throw e;
		} finally {
			setLoading( false );
		}

		return response;
	};

	return [
		enhancedApiFetch,
		{
			loading,
			error,
			data,
		},
	];
};

export default useApiFetch;
