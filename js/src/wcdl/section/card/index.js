/**
 * External dependencies
 */
import { Card as WPCard } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const Card = ( props ) => {
	const { children } = props;

	return <WPCard className="wcdl-section-card">{ children }</WPCard>;
};

export default Card;
