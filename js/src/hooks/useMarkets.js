/**
 * Placeholder hook for the markets list.
 *
 * Returns a hardcoded sample so the Markets dashboard can render a realistic
 * row in dev and reviews. The shape matches the contract the real selector is
 * expected to expose, so the consumer can stay unchanged when the API lands:
 *
 * - `data`: an array of market objects with the fields documented below.
 * - `hasFinishedResolution`: a boolean mirroring the `@wordpress/data`
 *   resolution flag used by `useAppSelectDispatch`.
 * - `invalidateResolution`: a no-op callback today; will trigger a refetch of
 *   the markets list once `useMarkets` is wired to a real selector.
 *
 * @return {{
 *   data: Array<{
 *     id: string,
 *     label: string,
 *     countries: string[],
 *     language: string,
 *     currency: string,
 *     feedLabel: string,
 *     shipping_rate: string,
 *     shipping_time: string,
 *     free_shipping: ?number,
 *   }>,
 *   hasFinishedResolution: boolean,
 *   invalidateResolution: () => void,
 * }} Markets data, resolution flag, and refetch callback.
 */
const useMarkets = () => {
	return {
		data: [
			{
				id: 'primary',
				label: 'Primary Market',
				countries: [ 'MU', 'ZW' ],
				language: 'en',
				currency: 'USD',
				feedLabel: 'ZW',
				shipping_rate: 'flat',
				shipping_time: 'flat',
				free_shipping: null,
			},
		],
		hasFinishedResolution: true,
		invalidateResolution: () => {},
	};
};

export default useMarkets;
