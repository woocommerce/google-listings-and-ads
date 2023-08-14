/**
 * External dependencies
 */
import { Card as WPCard } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Body from './body';
import Footer from './footer';
import Title from './title';

const Card = ( { size = '', ...props } ) => {
	return <WPCard { ...props } size={ size } />;
};

Card.Body = Body;
Card.Footer = Footer;
Card.Title = Title;

export default Card;
