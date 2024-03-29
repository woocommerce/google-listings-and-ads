/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const useRestAPIAuthURLRedirect = () => {
	const { createNotice } = useDispatchCoreNotices();

	const nextPageName = glaData.mcSetupComplete ? 'settings' : 'setup-mc';
	const query = { next_page_name: nextPageName };
	const path = addQueryArgs( `${ API_NAMESPACE }/rest-api/authorize`, query );
	const [ fetchRestAPIAuthorize, { loading } ] = useApiFetchCallback( {
		path,
	} );

	const handleRestAPIAuthURLRedirect = useCallback( async () => {
		try {
			if ( ! loading ) {
				const d = await fetchRestAPIAuthorize();
				window.location.href = d.auth_url;
			}
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to enable new product sync. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	}, [ fetchRestAPIAuthorize, createNotice, loading ] );

	return [ handleRestAPIAuthURLRedirect ];
};

export default useRestAPIAuthURLRedirect;
