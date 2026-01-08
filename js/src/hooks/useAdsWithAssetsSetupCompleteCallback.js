/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useCompleteAdsSetup from './useCompleteAdsSetup';

export default function useAdsWithAssetsSetupCompleteCallback() {
	const { createAdsWithAssetsCampaign } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );
	const { completeAdsSetup } = useCompleteAdsSetup();

	const handleFinishSetup = useCallback(
		(
			amount,
			countryCodes,
			finalUrl,
			assets,
			hasConfirmedEuPoliticalContent,
			onCompleted
		) => {
			setLoading( true );
			return createAdsWithAssetsCampaign(
				amount,
				countryCodes,
				finalUrl,
				assets,
				hasConfirmedEuPoliticalContent
			)
				.then( completeAdsSetup )
				.then( onCompleted )
				.catch( () => setLoading( false ) );
		},
		[ createAdsWithAssetsCampaign, completeAdsSetup ]
	);

	return [ handleFinishSetup, loading ];
}
