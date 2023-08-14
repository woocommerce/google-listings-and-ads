/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { __experimentalInputControl as InputControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import getCharacterCounter from '.~/utils/getCharacterCounter';
import './index.scss';

const baseClassName = 'app-input-control';

/**
 * Renders <InputControl> with a wrapper to be applicable extend additional class names.
 *
 * @param {Object} props React props to be forwarded to {@link InputControl}.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {boolean} [props.noPointerEvents=false] Whether disabled all pointer events. It will attach `pointer-events: none;` onto wrapper if true.
 * @param {number} [props.maxCharacterCount=0] The maximum number of character counter for showing a label below the input element to inform whether the input has exceeded the limit. 0 by default and it means no limit and no label is shown. When using this prop, it must also set the `kindCharacterCount`.
 * @param {'google-ads'} [props.kindCharacterCount] Kind of character counter to be used.
 * @param {import('react').MutableRefObject<HTMLInputElement>} ref React ref to be forwarded to the input element within this component.
 */
const AppInputControl = (
	{
		className,
		noPointerEvents = false,
		maxCharacterCount = 0,
		kindCharacterCount,
		...rest
	},
	ref
) => {
	const wrapperClassNames = [ baseClassName, className ];

	if ( noPointerEvents ) {
		wrapperClassNames.push( `${ baseClassName }--no-pointer-events` );
	}

	let countText;

	if ( maxCharacterCount > 0 && kindCharacterCount ) {
		const count = getCharacterCounter( kindCharacterCount );
		const characterCount = count( rest.value?.trim() || '' );

		countText = sprintf(
			// translators: 1: number of character count. 2: the maximum number of character count.
			__( '%1$d/%2$d characters', 'google-listings-and-ads' ),
			characterCount,
			maxCharacterCount
		);

		if ( characterCount > maxCharacterCount ) {
			wrapperClassNames.push(
				`${ baseClassName }--error-character-count`
			);
		}
	}

	return (
		<div className={ classnames( wrapperClassNames ) }>
			<InputControl ref={ ref } { ...rest } />
			{ countText && (
				<div className="app-input-control__character-count">
					{ countText }
				</div>
			) }
		</div>
	);
};

export default forwardRef( AppInputControl );
