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
	return <WPCard { ...props } size="none" />;
};

Card.Body = Body;
Card.Footer = Footer;

export default Card;
