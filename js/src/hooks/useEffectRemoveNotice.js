/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useNotices from '.~/hooks/useNotices';
import { STORE_KEY } from '.~/data/constants';

/**
 * Search for a notice with specific label and remove it if the component is unmounted.
 *
 * @param {string} label the notice label
 * @param {string} [storeKey] the store
 *
 * @return {import('@wordpress/notices').Notice|null} The notice to be removed otherwise null if it is not found
 */
const useEffectRemoveNotice = ( label, storeKey = STORE_KEY ) => {
	const notices = useNotices( storeKey );
	const notice = notices.find( ( el ) => el.content === label );

	useEffect( () => {
		const { removeNotice } = dispatch( storeKey );

		return () => {
			if ( notice ) {
				removeNotice( notice.id );
			}
		};
	}, [ label, notice, storeKey ] );

	return notice || null;
};

export default useEffectRemoveNotice;
