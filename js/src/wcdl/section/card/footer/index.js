/**
 * External dependencies
 */
import { CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const Footer = ( props ) => {
	const { children, ...restProps } = props;

	return (
		<CardFooter className="wcdl-section-card-footer" { ...restProps }>
			{ children }
		</CardFooter>
	);
};

export default Footer;
