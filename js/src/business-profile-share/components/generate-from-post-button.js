/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';

/**
 * Renders the "Generate from post" button. When the field already has text, it asks the merchant
 * to confirm before that text is replaced.
 *
 * @param {Object} props Component props.
 * @param {string} props.summary The field's current text.
 * @param {() => void} props.onGenerate Called when the field should be filled from the post.
 * @param {boolean} [props.disabled] Whether the button is disabled.
 * @return {JSX.Element} The button, and the confirmation while it's open.
 */
export default function GenerateFromPostButton( {
	summary,
	onGenerate,
	disabled = false,
} ) {
	const [ isConfirming, setIsConfirming ] = useState( false );

	const handleClick = () => {
		if ( summary.trim() ) {
			setIsConfirming( true );
			return;
		}

		onGenerate();
	};

	const handleRequestClose = () => {
		setIsConfirming( false );
	};

	const handleReplaceClick = () => {
		setIsConfirming( false );
		onGenerate();
	};

	return (
		<>
			<Button
				variant="secondary"
				onClick={ handleClick }
				disabled={ disabled }
			>
				{ __( 'Generate from post', 'google-listings-and-ads' ) }
			</Button>
			{ isConfirming && (
				<AppModal
					title={ __(
						'Replace your text?',
						'google-listings-and-ads'
					) }
					onRequestClose={ handleRequestClose }
					buttons={ [
						<Button
							key="cancel"
							variant="tertiary"
							onClick={ handleRequestClose }
						>
							{ __( 'Cancel', 'google-listings-and-ads' ) }
						</Button>,
						<Button
							key="replace"
							variant="primary"
							onClick={ handleReplaceClick }
						>
							{ __( 'Replace', 'google-listings-and-ads' ) }
						</Button>,
					] }
				>
					<p>
						{ __(
							'Your edits will be replaced with a new version from your post.',
							'google-listings-and-ads'
						) }
					</p>
				</AppModal>
			) }
		</>
	);
}
