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

	const handleFinishSetup = async () => {
		try {
			await fetchCompleteYouTubeSetup();
			invalidateResolution( 'getYouTubeAccount', [] );
		} catch ( error ) {}
	};

	return [ handleFinishSetup, result ];
}
