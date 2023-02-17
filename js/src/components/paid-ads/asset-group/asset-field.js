/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, _x, _n, sprintf } from '@wordpress/i18n';
import { useReducedMotion } from '@wordpress/compose';
import {
	useState,
	useRef,
	useImperativeHandle,
	forwardRef,
} from '@wordpress/element';
import { chevronUp, chevronDown } from '@wordpress/icons';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import HelpPopover from '.~/components/help-popover';
import './asset-field.scss';

/**
 * @typedef {Object} AssetFieldHandler
 * @property {() => void} scrollIntoComponent Scroll to the nearest alignable position to show this component in the visible area.
 */

/**
 * Renders an expandable wrapper for editing asset fields.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {JSX.Element} props.heading Heading.
 * @param {JSX.Element} [props.subheading] Subheading.
 * @param {JSX.Element} props.help Help content to be shown after clicking on the help icon.
 * @param {number} [props.numOfIssues=0] The number of issues used to label on UI. It only labels when the number is greater than 0.
 * @param {boolean} [props.initialExpanded=false] Whether the UI is initialized expanded.
 * @param {boolean} [props.markOptional=false] Whether mark this field as optional.
 * @param {boolean} [props.disabled=false] Whether display the UI in disabled style. It will collapse the content when disabled.
 * @param {JSX.Element} [props.children] Content to be rendered.
 * @param {import('react').MutableRefObject<AssetFieldHandler>} ref React ref to be attached to the handler of this component.
 */
function AssetField(
	{
		className,
		heading,
		subheading,
		help,
		numOfIssues = 0,
		initialExpanded = false,
		markOptional = false,
		disabled = false,
		children,
	},
	ref
) {
	const containerRef = useRef();
	const [ expanded, setExpanded ] = useState( initialExpanded );

	const isReducedMotion = useReducedMotion();

	useImperativeHandle( ref, () => ( {
		scrollIntoComponent() {
			containerRef.current.scrollIntoView( {
				behavior: isReducedMotion ? 'auto' : 'smooth',
				inline: 'nearest',
				block: 'nearest',
			} );
		},
	} ) );

	const handleToggle = () => {
		setExpanded( ! expanded );
	};

	const issuePillText = sprintf(
		// translators: %d: number of issues in an asset field.
		_n( '%d issue', '%d issues', numOfIssues, 'google-listings-and-ads' ),
		numOfIssues
	);

	const wrapperClassName = classnames(
		'gla-asset-field',
		className,
		disabled ? 'gla-asset-field--is-disabled' : false
	);

	const shouldExpand = expanded && ! disabled;

	return (
		<div className={ wrapperClassName } ref={ containerRef }>
			<header className="gla-asset-field__header">
				<div className="gla-asset-field__heading-part">
					<h2 className="gla-asset-field__heading">
						{ heading }
						{ markOptional && (
							<span className="gla-asset-field__optional-label">
								{ _x(
									'(Optional)',
									'A label behind the heading to indicate a field is optional',
									'google-listings-and-ads'
								) }
							</span>
						) }
						<HelpPopover
							className="gla-asset-field__help-popover"
							position="top"
							iconSize={ 20 }
							disabled={ disabled }
						>
							<div className="gla-asset-field__help-popover__content">
								{ help }
							</div>
						</HelpPopover>
					</h2>
					{ subheading && (
						<h3 className="gla-asset-field__subheading">
							{ subheading }
						</h3>
					) }
				</div>
				{ numOfIssues > 0 && (
					<Pill className="gla-asset-field__issue-pill">
						{ issuePillText }
					</Pill>
				) }
				<div className="gla-asset-field__toggle-button-anchor">
					<AppButton
						className="gla-asset-field__toggle-button"
						icon={ shouldExpand ? chevronUp : chevronDown }
						aria-expanded={ shouldExpand }
						aria-label={ __(
							'Toggle asset',
							'google-listings-and-ads'
						) }
						disabled={ disabled }
						onClick={ handleToggle }
					/>
				</div>
			</header>
			<div className="gla-asset-field__content">
				{ shouldExpand && children }
			</div>
		</div>
	);
}

export default forwardRef( AssetField );
