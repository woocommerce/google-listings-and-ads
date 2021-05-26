/**
 * This file was cloned from @wordpress/components 12.0.8
 * https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/guide/icons.js
 *
 * To meet the requirement of
 * https://github.com/woocommerce/google-listings-and-ads/issues/555
 */
/**
 * External dependencies
 */
import { SVG, Circle } from '@wordpress/primitives';

export const PageControlIcon = ( { isSelected } ) => (
	<SVG width="8" height="8" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Circle
			cx="4"
			cy="4"
			r="4"
			fill={ isSelected ? '#419ECD' : '#E1E3E6' }
		/>
	</SVG>
);
