/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import styles from './editor.module.scss';

/**
 * @typedef {Object} ProductOnboardingPromptAttributes
 * @property {string} startUrl The plugin start URL.
 */

/**
 * Custom block for prompting the user to complete the onboarding.
 *
 * @param {Object} props React props.
 * @param {ProductOnboardingPromptAttributes} props.attributes
 */
export default function Edit( { attributes } ) {
	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<div className={ styles.wrapper }>
				<p className={ styles.content }>
					{ __(
						'Complete setup to get your products listed on Google for free.',
						'google-listings-and-ads'
					) }
				</p>
				<Button isPrimary href={ attributes.startUrl }>
					{ __( 'Get Started', 'google-listings-and-ads' ) }
				</Button>
			</div>
		</div>
	);
}
