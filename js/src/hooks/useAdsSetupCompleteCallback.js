/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useCompleteAdsSetup from './useCompleteAdsSetup';

export default function useAdsSetupCompleteCallback() {
	const { createAdsCampaign } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const { completeAdsSetup } = useCompleteAdsSetup();

	const handleFinishSetup = useCallback(
		(
			amount,
			countryCodes,
			hasConfirmedEuPoliticalContent,
			onCompleted
		) => {
			setLoading( true );
			setError( null );
			return createAdsCampaign(
				amount,
				countryCodes,
				hasConfirmedEuPoliticalContent
			)
				.then( completeAdsSetup )
				.then( onCompleted )
				.catch( ( err ) => {
					setError( err );
					throw err;
				} )
				.finally( () => setLoading( false ) );
		},
		[ createAdsCampaign, completeAdsSetup ]
	);

	return [ handleFinishSetup, loading, error ];
}
