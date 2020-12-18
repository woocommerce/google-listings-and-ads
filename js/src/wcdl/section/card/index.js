/**
 * External dependencies
 */
import { Card as WPCard } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Body from './body';
import Footer from './footer';

const Card = ( props ) => {
	const { children } = props;

	return <WPCard size="none">{ children }</WPCard>;
};

Card.Body = Body;
Card.Footer = Footer;

export default Card;
