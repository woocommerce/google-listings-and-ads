/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

export default function useYouTubeSetupCompleteCallback() {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchCompleteYouTubeSetup, result ] = useApiFetchCallback( {
		path: '/wc/gla/youtube/setup/complete',
		method: 'POST',
	} );

	const handleFinishSetup = async () => {
		try {
			await fetchCompleteYouTubeSetup();
		} catch ( error ) {
			const message =
				error.message ||
				__(
					'Unable to complete your YouTube setup. Please try again later.',
					'google-listings-and-ads'
				);
			createNotice( 'error', message );
		}
	};

	return [ handleFinishSetup, result ];
}
