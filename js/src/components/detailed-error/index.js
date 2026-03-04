/**
 * External dependencies
 */
import classNames from 'classnames';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

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
			{ formattedErrors.map( ( { title, description } ) => (
				<div
					key={ `${ title }-${ description }` }
					className={ classNames( 'gla-detailed-error', className ) }
				>
					<Flex align="center" wrap="nowrap" gap={ 1 }>
						<FlexItem>
							<img
								src={ warningIconUrl }
								alt=""
								width={ 16 }
								height={ 16 }
								className="gla-detailed-error__icon"
							/>
						</FlexItem>
						<FlexBlock>
							<h4 className="gla-detailed-error__title">
								{ title }
							</h4>
						</FlexBlock>
					</Flex>
					<p className="gla-detailed-error__description">
						{ description }
					</p>
				</div>
			) ) }
		</>
	);
};

export default DetailedError;
