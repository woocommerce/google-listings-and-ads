/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

export default function useYouTubeSetupCompleteCallback() {
	const [ fetchCompleteYouTubeSetup, result ] = useApiFetchCallback( {
		path: '/wc/gla/youtube/setup/complete',
		method: 'POST',
	} );

	const handleFinishSetup = async () => {
		try {
			await fetchCompleteYouTubeSetup();
		} catch ( error ) {}
	};

	return [ handleFinishSetup, result ];
}
