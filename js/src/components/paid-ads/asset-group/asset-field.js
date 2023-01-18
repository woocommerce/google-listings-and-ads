/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, _n, sprintf } from '@wordpress/i18n';
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
 * @param {JSX.Element} props.heading Heading.
 * @param {JSX.Element} props.subheading Subheading.
 * @param {JSX.Element} props.help Help content to be shown after clicking on the help icon.
 * @param {number} props.numOfIssues The number of issues to be marked as a label on UI. It only shows the label when the number is greater than 0.
 * @param {boolean} [props.initialExpanded=false] Whether the UI is initialized expanded.
 * @param {boolean} [props.disabled=false] Whether display the UI in disabled style. It will collapse the content when disabled.
 * @param {JSX.Element} [props.children] Content to be rendered.
 * @param {import('react').MutableRefObject<AssetFieldHandler>} ref React ref to be attached to the handler of this component.
 */
function AssetField(
	{
		heading,
		subheading,
		help,
		numOfIssues,
		initialExpanded = false,
		disabled = false,
		children,
	},
	ref
) {
	const containerRef = useRef();
	const [ isExpanded, setExpanded ] = useState( initialExpanded );

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
		setExpanded( ! isExpanded );
	};

	const issuePillText = sprintf(
		// translators: %d: number of issues in an asset field.
		_n( '%d issue', '%d issues', numOfIssues, 'google-listings-and-ads' ),
		numOfIssues
	);

	const className = classnames(
		'gla-asset-field',
		disabled ? 'gla-asset-field--is-disabled' : false
	);

	const shouldExpanded = isExpanded && ! disabled;

	return (
		<div className={ className } ref={ containerRef }>
			<header className="gla-asset-field__header">
				<div className="gla-asset-field__heading-part">
					<h2 className="gla-asset-field__heading">
						{ heading }
						<HelpPopover
							className="gla-asset-field__help-popover"
							position="top"
							iconSize={ 20 }
							disabled={ disabled }
						>
							{ help }
						</HelpPopover>
					</h2>
					<h3 className="gla-asset-field__subheading">
						{ subheading }
					</h3>
				</div>
				{ numOfIssues > 0 && (
					<Pill className="gla-asset-field__issue-pill">
						{ issuePillText }
					</Pill>
				) }
				<div className="gla-asset-field__toggle-button-anchor">
					<AppButton
						className="gla-asset-field__toggle-button"
						icon={ shouldExpanded ? chevronUp : chevronDown }
						aria-expanded={ shouldExpanded }
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
				{ shouldExpanded && children }
			</div>
		</div>
	);
}

export default forwardRef( AssetField );
