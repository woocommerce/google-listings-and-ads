/**
 * External dependencies
 */
import classNames from 'classnames';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getFormattedErrorMessage } from '~/utils/handleError';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import warningIconUrl from '~/images/icons/warning.svg';
import './index.scss';

const DetailedError = ( { errorSlots, className } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const hasShownNoticeRef = useRef( false );
	const error = useDetailedErrorBySlots( errorSlots );

	// If there's no mapped detailed error, show a core notice once, but do it in an effect
	useEffect( () => {
		if ( error && ! hasShownNoticeRef.current ) {
			hasShownNoticeRef.current = true;
			if ( error?.fallback ) {
				createNotice( 'error', error.fallback );
			}
		}
	}, [ error, createNotice ] );

	if ( ! error ) {
		return null;
	}

	const { title, description } = getFormattedErrorMessage( error );

	return (
		<div className={ classNames( 'gla-detailed-error', className ) }>
			<h4 className="gla-detailed-error__title">
				<img src={ warningIconUrl } alt="" width={ 16 } height={ 16 } />
				<span>{ title }</span>
			</h4>
			<p className="gla-detailed-error__description">{ description }</p>
		</div>
	);
};

export default DetailedError;
