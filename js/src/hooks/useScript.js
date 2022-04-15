/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Load an external script and call the provided callback function when the script is loaded.
 *
 * @param {string}   url      The script's url.
 * @param {Function} callback A function to be called after the script is loaded.
 */
const useScript = ( url, callback = () => {} ) => {
	useEffect( () => {
		const script = document.createElement( 'script' );

		script.src = url;
		script.async = true;

		document.body.appendChild( script );

		script.onload = () => {
			callback();
		};

		return () => {
			document.body.removeChild( script );
		};
	}, [ url, callback ] );
};

export default useScript;
