/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const FullScreen = ( props ) => {
	const { children } = props;

	useEffect( () => {
		document.body.classList.add( 'woocommerce-admin-full-screen' );
		document.body.classList.add( 'gla-full-screen' );

		return () => {
			document.body.classList.remove( 'woocommerce-admin-full-screen' );
			document.body.classList.remove( 'gla-full-screen' );
		};
	}, [] );

	return children;
};

export default FullScreen;
