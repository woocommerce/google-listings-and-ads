/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, warning as warningIcon } from '@wordpress/icons';
import { getPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import AppButton from '~/components/app-button';
import './contact-information-preview-card.scss';

/**
 * Renders a contact information card component.
 * It adds loading & warning state to the regular `AccountCard`, and an edit button link.
 *
 * @param {Object} props React props
 * @param {import('~/components/account-card').APPEARANCE}  props.appearance
 * @param {string} props.editHref URL where Edit button should point to.
 * @param {string} props.editEventName Tracing event name used when the "Edit" button is clicked.
 * @param {boolean} props.loading Set to `true` if the card should be rendered in the loading state.
 * @param {JSX.Element} props.content Main content of the card to be rendered once the data is loaded.
 * @param {string} [props.warning] Warning title, to be used instead of the default one.
 * @return {JSX.Element} Filled AccountCard component.
 */
export default function ContactInformationPreviewCard( {
	editHref,
	editEventName,
	loading,
	content,
	appearance,
	warning,
} ) {
	const { subpath } = getQuery();
	const editButton = (
		<AppButton
			eventName={ editEventName }
			eventProps={ { path: getPath(), subpath } }
			href={ editHref }
			text={ __( 'Edit', 'google-listings-and-ads' ) }
			isSecondary
		/>
	);
	let description;
	let title;

	if ( loading ) {
		description = (
			<span
				aria-busy="true"
				className="gla-contact-info-preview-card__placeholder"
				title={ __( 'Loading…', 'google-listings-and-ads' ) }
			></span>
		);
	} else if ( warning ) {
		title = (
			<>
				<Icon
					className="gla-contact-info-preview-card__notice-icon"
					icon={ warningIcon }
					size={ 24 }
				/>
				{ warning }
			</>
		);
		description = (
			<span className="gla-contact-info-preview-card__notice-details">
				{ content }
			</span>
		);
	} else {
		description = content;
	}

	return (
		<AccountCard
			appearance={ appearance }
			className="gla-contact-info-preview-card"
			description={ description }
			icon={ null }
			indicator={ editButton }
			title={ title }
		></AccountCard>
	);
}
