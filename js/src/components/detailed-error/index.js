/**
 * External dependencies
 */
import classNames from 'classnames';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import warningIconUrl from '~/images/icons/warning.svg';
import ErrorMaps from './error-maps';
import './index.scss';

const DetailedError = ( { errorSlots, className } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const showNoticeRef = useRef();
	const error = useDetailedErrorBySlots( errorSlots );

	if ( ! error ) {
		return null;
	}

	const detailedError =
		ErrorMaps?.[ error.type ]?.[ error.status ]?.[ error.code ];

	if ( ! detailedError ) {
		if ( ! showNoticeRef.current ) {
			showNoticeRef.current = true;
			createNotice( 'error', error?.fallback );
		}

		return null;
	}

	return (
		<div className={ classNames( 'gla-detailed-error', className ) }>
			<h4 className="gla-detailed-error__title">
				<img src={ warningIconUrl } alt="" width={ 16 } height={ 16 } />

				<span>{ detailedError.title }</span>
			</h4>
			<p className="gla-detailed-error__description">
				{ detailedError.description }
			</p>
		</div>
	);
};

export default DetailedError;
