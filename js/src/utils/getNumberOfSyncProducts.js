/**
 * @typedef {import('.~/data/actions').ProductStatisticsDetails } ProductStatisticsDetails
 */

/**
 * Return the total number of syncing/synced products except for the `not_synced` category.
 *
 * @param {ProductStatisticsDetails} statistics The statistics data of scheduled synchronized products.
 * @return {number} Number of syncing/synced products.
 */
export default function getNumberOfSyncProducts( statistics ) {
	return Object.entries( statistics ).reduce( ( sum, [ key, num ] ) => {
		if ( key === 'not_synced' ) {
			return sum;
		}
		return sum + num;
	}, 0 );
}
