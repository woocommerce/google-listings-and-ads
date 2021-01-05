/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

const More = ( props ) => {
	const { count } = props;

	if ( count === 0 ) {
		return <span>hi</span>;
	}

	return createInterpolateElement(
		__( ` + <count /> more`, 'google-listings-and-ads' ),
		{
			count: <>{ count }</>,
		}
	);
};

export default More;
