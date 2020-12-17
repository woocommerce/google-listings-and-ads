/**
 * External dependencies
 */
import { Card as WPCard } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Body from './body';
import Footer from './footer';
import './index.scss';

const Card = ( props ) => {
	const { children } = props;

	return (
		<WPCard className="wcdl-section-card" size="none">
			{ children }
		</WPCard>
	);
};

Card.Body = Body;
Card.Footer = Footer;

export default Card;
