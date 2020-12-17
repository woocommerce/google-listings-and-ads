/**
 * External dependencies
 */
import { CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const Footer = ( props ) => {
	const { children } = props;

	return (
		<CardFooter className="wcdl-section-card-footer">
			{ children }
		</CardFooter>
	);
};

export default Footer;
