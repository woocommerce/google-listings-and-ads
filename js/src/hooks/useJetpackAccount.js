/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useJetpackAccount = () => {
	return useSelect( ( select ) => {
		const jetpack = select( STORE_KEY ).getJetpackAccount();
		const isResolving = select( STORE_KEY ).isResolving(
			'getJetpackAccount'
		);
		const hasFinishedResolution = select( STORE_KEY ).hasFinishedResolution(
			'getJetpackAccount'
		);

		return { jetpack, isResolving, hasFinishedResolution };
	}, [] );
};

export default useJetpackAccount;
