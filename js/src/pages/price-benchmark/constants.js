/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const TABLE_TYPE_SUGGESTIONS = 'suggestions';
export const TABLE_TYPE_ADJUSTMENTS = 'adjustments';

export const LABEL_PRICE_CHANGE_EFFECTIVENESS =
	'LABEL_PRICE_CHANGE_EFFECTIVENESS';
export const LABEL_PRICE_ON_GOOGLE = 'LABEL_PRICE_ON_GOOGLE';
export const LABEL_PRICE_GAP = 'LABEL_PRICE_GAP';
export const LABEL_SUGGESTED_PRICE = 'LABEL_SUGGESTED_PRICE';
export const LABEL_REGULAR_PRICE = 'LABEL_REGULAR_PRICE';
export const LABEL_ACTION = 'LABEL_ACTION';
export const EFFECTIVENESS_UNSPECIFIED = 0;
export const EFFECTIVENESS_LOW = 1;
export const EFFECTIVENESS_MEDIUM = 2;
export const EFFECTIVENESS_HIGH = 3;

export const LABELS = {
	[ LABEL_PRICE_CHANGE_EFFECTIVENESS ]: {
		title: __( 'Change Effectiveness', 'google-listings-and-ads' ),
		tooltip: __(
			'Effectiveness tells you which products would benefit most from price changes. This rating takes into consideration the performance boost predicted by adjusting the sale price and the difference between your current price and the suggested price. Price suggestions with “High” effectiveness are predicted to drive the largest increase in performance. Keep in mind that predictions do not guarantee improvements in future performance.',
			'google-listings-and-ads'
		),
	},
	[ LABEL_PRICE_ON_GOOGLE ]: {
		title: __( 'Avg. Price on Google', 'google-listings-and-ads' ),
		tooltip: __(
			'The effective price for a product across all retailers selling the same product weighted by customer clicks. Products are matched based on the GTIN you provide in the product details.',
			'google-listings-and-ads'
		),
	},
	[ LABEL_PRICE_GAP ]: {
		title: __( 'Price Gap %', 'google-listings-and-ads' ),
		tooltip: __(
			'The percentage difference between your price and the price on Google for this product.',
			'google-listings-and-ads'
		),
	},
	[ LABEL_SUGGESTED_PRICE ]: {
		title: __( 'Suggested Price', 'google-listings-and-ads' ),
		tooltip: __(
			'Suggested sale price predicted by Google for products that benefit most from pricing adjustments. It is based on advanced simulations at different price points over the past 7 days factoring in price elasticity, current performance and the performance impact on price changes for businesses similar to you. Use suggested sale prices as valuable directional guidance to help shape your pricing strategy. Learn more about how to change the sale price of your products. Keep in mind that predictions do not guarantee future performance outcomes.',
			'google-listings-and-ads'
		),
	},
	[ LABEL_REGULAR_PRICE ]: {
		title: __( 'Regular Price', 'google-listings-and-ads' ),
	},
	[ LABEL_ACTION ]: {
		title: __( 'Action', 'google-listings-and-ads' ),
	},
};
