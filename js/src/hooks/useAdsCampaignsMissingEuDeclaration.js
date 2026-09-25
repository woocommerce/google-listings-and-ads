/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';
import { glaData } from '~/constants';

const selectorName = 'getAdsCampaignsMissingEuDeclaration';

/**
 * @typedef {import('~/data/actions').Campaign} Campaign
 *
 * @typedef {Object} AdsCampaignsMissingEuDeclarationPayload
 * @property {Array<{id: number, name: string}>|null} data Campaigns missing EU political advertising declaration, or `null` before load finished.
 * @property {boolean} loaded Whether the `data` is finished loading.
 */

/**
 * A hook that calls `getAdsCampaignsMissingEuDeclaration` selector to load
 * campaigns that are missing the EU political advertising declaration.
 *
 * @return {AdsCampaignsMissingEuDeclarationPayload} The data and its state.
 */
const useAdsCampaignsMissingEuDeclaration = () => {
	return useSelect( ( select ) => {
		const { adsSetupComplete } = glaData;

		if ( ! adsSetupComplete ) {
			return {
				loaded: true,
				data: [],
			};
		}

		const selector = select( STORE_KEY );
		const data = selector[ selectorName ]();
		const loaded = selector.hasFinishedResolution( selectorName, [] );

		return {
			loaded,
			data,
		};
	}, [] );
};

export default useAdsCampaignsMissingEuDeclaration;
