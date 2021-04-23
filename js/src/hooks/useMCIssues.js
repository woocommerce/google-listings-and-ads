/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import useApiFetchEffect from './useApiFetchEffect';

// TODO: remove this dummy data.
const data = {
	issues: [
		{
			type: 'account',
			product: 'Long Sleeve Tee',
			product_id: 22,
			code: 'policy_enforcement_account_disapproval',
			issue: 'Suspended account for policy violation',
			action:
				'Remove products that violate our policies, or request a manual review',
			action_link: 'https://support.google.com/merchants/answer/2948694',
			applicable_countries: [ 'ES' ],
		},
		{
			type: 'product',
			product: 'Long Sleeve Tee',
			product_id: 22,
			code: 'invalid_currency_for_country',
			issue: 'Unsupported currency',
			action:
				'Use the currency of the country of sale in your product data',
			action_link: 'https://support.google.com/merchants/answer/160637',
			applicable_countries: [ 'KR' ],
		},
		{
			type: 'product',
			product: 'Long Sleeve Tee',
			product_id: 22,
			code: 'missing_shipping',
			issue: 'Missing value [shipping]',
			action: 'Add shipping costs for your product',
			action_link: 'https://support.google.com/merchants/answer/6239383',
			applicable_countries: [ 'AR', 'AT', 'AU', 'BE', 'BH', 'BR' ],
		},
		{
			type: 'product',
			product: 'Long Sleeve Tee',
			product_id: 22,
			code: 'missing_item_attribute_for_product_type',
			issue: 'Missing value [size]',
			action: 'Add this attribute to your product data',
			action_link: 'https://support.google.com/merchants/answer/9762223',
			applicable_countries: [ 'BR', 'DE', 'FR', 'GB', 'JP', 'US' ],
		},
		{
			type: 'product',
			product: 'Long Sleeve Tee',
			product_id: 22,
			code: 'url_does_not_match_homepage',
			issue: 'Mismatched domains [link]',
			action:
				'Use the same domain for product landing page URLs as in your Merchant Center website setting',
			action_link: 'https://support.google.com/merchants/answer/160050',
			applicable_countries: [ 'AE', 'AR', 'AT', 'AU' ],
		},
	],
	total: 20,
	page: 1,
};

const useMCIssues = ( page, perPage ) => {
	const result = useApiFetchEffect( {
		path: addQueryArgs( '/wc/gla/mc/issues', { page, per_page: perPage } ),
	} );

	return {
		...result,
		data, // TODO: remove this dummy data.
	};
};

export default useMCIssues;
