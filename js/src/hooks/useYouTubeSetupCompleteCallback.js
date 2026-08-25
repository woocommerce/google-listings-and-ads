/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

export default function useYouTubeSetupCompleteCallback() {
	const { invalidateResolution } = useAppDispatch();
	const [ fetchCompleteYouTubeSetup, result ] = useApiFetchCallback( {
		path: '/wc/gla/youtube/setup/complete',
		method: 'POST',
	} );

	const handleFinishSetup = useCallback( async () => {
		try {
			await fetchCompleteYouTubeSetup();
			invalidateResolution( 'getYouTubeAccount', [] );
		} catch ( error ) {}
	}, [ fetchCompleteYouTubeSetup, invalidateResolution ] );

	return [ handleFinishSetup, result ];
}
