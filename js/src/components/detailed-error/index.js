/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getFormattedErrorMessage } from '~/utils/handleError';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import warningIconUrl from '~/images/icons/warning.svg';
import './index.scss';

/**
 * Component to display detailed error messages based on provided error slots.
 *
 * This component uses the `useDetailedErrorBySlots` hook to retrieve the first
 * error for each specified slot. It then formats and displays these errors in a
 * user-friendly manner, including an icon, title, and description for each error.
 *
 * @param {Object} props - Component props.
 * @param {Array<string>} props.errorSlots - The error slots to display errors for.
 * @return {JSX.Element|null} The rendered component or null if no errors.
 */
const DetailedError = ( { errorSlots } ) => {
	const errors = useDetailedErrorBySlots( errorSlots );

	if ( ! errors || errors.length === 0 ) {
		return null;
	}

	const formattedErrors = getFormattedErrorMessage( errors );

	return (
		<>
			{ formattedErrors.map( ( { title, description } ) => (
				<div
					className="gla-detailed-error"
					key={ `${ title }-${ description }` }
				>
					<Flex align="center" gap={ 1 } wrap="nowrap">
						<FlexItem>
							<img
								alt=""
								className="gla-detailed-error__icon"
								height={ 16 }
								src={ warningIconUrl }
								width={ 16 }
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
