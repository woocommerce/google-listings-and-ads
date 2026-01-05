/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { getFormattedErrorMessage } from '~/utils/handleError';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import warningIconUrl from '~/images/icons/warning.svg';
import './index.scss';

const DetailedError = ( { errorSlots, className } ) => {
	const errors = useDetailedErrorBySlots( errorSlots );

	if ( ! errors || errors.length === 0 ) {
		return null;
	}

	const formattedErrors = getFormattedErrorMessage( errors );

	return (
		<>
			{ formattedErrors.map( ( { title, description }, idx ) => (
				<div
					key={ idx }
					className={ classNames( 'gla-detailed-error', className ) }
				>
					<div className="gla-detailed-error__title">
						<span>
							<img
								src={ warningIconUrl }
								alt=""
								width={ 16 }
								height={ 16 }
							/>
						</span>
						<h4>{ title }</h4>
					</div>
					<p className="gla-detailed-error__description">
						{ description }
					</p>
				</div>
			) ) }
		</>
	);
};

export default DetailedError;
