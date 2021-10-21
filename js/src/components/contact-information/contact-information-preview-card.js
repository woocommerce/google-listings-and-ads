/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, warning as warningIcon } from '@wordpress/icons';
import { getPath, getQuery } from '@woocommerce/navigation';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import AccountCard from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import './contact-information-preview-card.scss';

/**
 * Renders a contact information card component.
 * It adds loading & warning state to the regular `AccountCard`, and an edit button link.
 *
 * @param {Object} props React props
 * @param {import('.~/components/account-card').APPEARANCE}  props.appearance
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
			isSecondary
			href={ editHref }
			text={ __( 'Edit', 'google-listings-and-ads' ) }
			eventName={ editEventName }
			eventProps={ { path: getPath(), subpath } }
		/>
	);
	let description = content;

	if ( loading ) {
		description = (
			<div title={ __( 'Loading…', 'google-listings-and-ads' ) }></div>
		);
	} else if ( warning ) {
		appearance = {
			title: (
				<>
					<Icon
						icon={ warningIcon }
						size={ 24 }
						className="gla-contact-info-preview-card__notice-icon"
					/>
					{ warning }
				</>
			),
		};
	}

	return (
		<AccountCard
			appearance={ appearance }
			aria-busy={ loading }
			className={ classNames( 'gla-contact-info-preview-card', {
				'state--loading': loading,
				'state--warning': warning,
			} ) }
			description={ description }
			hideIcon
			indicator={ editButton }
		></AccountCard>
	);
}
