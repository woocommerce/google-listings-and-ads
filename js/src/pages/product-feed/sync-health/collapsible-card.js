/**
 * External dependencies
 */
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * A card whose body can be expanded and collapsed.
 *
 * Mirrors the `CollapsibleCard` API from `@wordpress/ui` (`open`, `defaultOpen`,
 * `onOpenChange`) on top of the `Card` shipped in WordPress core, so it can be
 * swapped for the design-system component once core ships `@wordpress/ui`.
 *
 * @param {Object} props
 * @param {JSX.Element|string} props.title Header title.
 * @param {JSX.Element} [props.description] Shown next to the title.
 * @param {boolean} [props.open] Open state, when controlled by the parent.
 * @param {boolean} [props.defaultOpen=false] Initial state, when uncontrolled.
 * @param {Function} [props.onOpenChange] Called with the new open state.
 * @param {JSX.Element} props.children Body content, rendered only while open.
 * @param {string} [props.id] DOM id of the card, e.g. to scroll to it.
 */
const CollapsibleCard = ( {
	title,
	description,
	open,
	defaultOpen = false,
	onOpenChange = () => {},
	children,
	id,
} ) => {
	const [ uncontrolledOpen, setUncontrolledOpen ] = useState( defaultOpen );
	const isControlled = open !== undefined;
	const isOpen = isControlled ? open : uncontrolledOpen;
	const bodyId = useInstanceId(
		CollapsibleCard,
		'gla-collapsible-card__body'
	);

	const toggle = () => {
		if ( ! isControlled ) {
			setUncontrolledOpen( ! isOpen );
		}
		onOpenChange( ! isOpen );
	};

	return (
		<Card className="gla-collapsible-card" id={ id }>
			<CardHeader>
				<Button
					className="gla-collapsible-card__toggle"
					aria-expanded={ isOpen }
					aria-controls={ bodyId }
					icon={ isOpen ? chevronUp : chevronDown }
					iconPosition="right"
					onClick={ toggle }
				>
					<span className="gla-collapsible-card__title">
						{ title }
					</span>
				</Button>
				{ description && (
					<span className="gla-collapsible-card__description">
						{ description }
					</span>
				) }
			</CardHeader>
			{ isOpen && <CardBody id={ bodyId }>{ children }</CardBody> }
		</Card>
	);
};

export default CollapsibleCard;
