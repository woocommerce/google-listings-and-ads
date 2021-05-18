/**
 * Internal dependencies
 */
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';

/**
 * This hook calls the API side to prefetch the product feed data
 * (product status statistics, issues and product feed) from Google API
 * and store the data into cache on the server side.
 *
 * Under the hood, it is essentially calling product status statistics API
 * in useMCProductStatistics.
 *
 * This hook is a "quick win" solution to address
 * the Product Feed triplicated requests issue:
 * https://github.com/woocommerce/google-listings-and-ads/issues/615
 *
 * In the future, when we have a better stable long term solution,
 * this hook would become unnecessary and can be removed.
 *
 * @return {useMCProductStatistics} Result of useMCProductStatistics.
 */
const useProductFeedPrefetch = () => {
	return useMCProductStatistics();
};

export default useProductFeedPrefetch;
