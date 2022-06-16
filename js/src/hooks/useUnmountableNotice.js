/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { uniqueId } from 'lodash';

/**
 * Hook to create a wp notice that will be removed when the parent component is unmounted
 *
 * @param {import('@wordpress/notices').Status} type Notice status.
 * @param {string} content Notice content.
 * @param {import('@wordpress/notices').Options} [options] Notice options.
 *
 * @return {Function} a function that will create the notice.
 */
const useUnmountableNotice = ( type, content, options = {} ) => {
	const { createNotice, removeNotice } = dispatch( 'core/notices2' );
	const idRef = useRef( options.id || uniqueId() );

	//remove notice when the component is unmounted
	useEffect( () => () => removeNotice( idRef.current ), [ removeNotice ] );

	return () =>
		createNotice( type, content, { ...options, id: idRef.current } );
};

export default useUnmountableNotice;
