/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Placeholder hook for the markets list.
 *
 * Returns a hardcoded sample so the Markets dashboard can render a realistic
 * row in dev and reviews. The shape matches the contract the real selector is
 * expected to expose, so the consumer can stay unchanged when the API lands:
 *
 * - `data`: an array of market objects (`{ id, market, country, shipping }`).
 * - `hasFinishedResolution`: a boolean mirroring the `@wordpress/data`
 *   resolution flag used by `useAppSelectDispatch`.
 * - `invalidateResolution`: a no-op callback today; will trigger a refetch of
 *   the markets list once `useMarkets` is wired to a real selector.
 *
 * @return {{
 *   data: Array<{ id: string, market: string, country: string, shipping: string }>,
 *   hasFinishedResolution: boolean,
 *   invalidateResolution: () => void,
 * }} Markets data, resolution flag, and refetch callback.
 */
const useMarkets = () => {
	return {
		data: [
			{
				id: 'primary',
				market: __( 'Primary market', 'google-listings-and-ads' ),
				country: __( '20 Countries', 'google-listings-and-ads' ),
				shipping: __( 'Managed in Google', 'google-listings-and-ads' ),
			},
		],
		hasFinishedResolution: true,
		invalidateResolution: () => {},
	};
};

export default useMarkets;
