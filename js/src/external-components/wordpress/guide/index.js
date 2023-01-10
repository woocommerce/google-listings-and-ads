/**
 * This file was cloned from @wordpress/components 12.0.8
 * https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/guide/index.js
 *
 * To meet the requirement of
 * https://github.com/woocommerce/google-listings-and-ads/issues/555
 */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, KeyboardShortcuts } from '@wordpress/components';
import { Button } from 'extracted/@wordpress/components';

/**
 * Internal dependencies
 */
import PageControl from './page-control';
import FinishButton from './finish-button';

/**
 * @callback renderFinishCallback
 * @param {JSX.Element} finishButton The built-in finish button of this Guide component.
 * @return {JSX.Element} React element for rendering.
 */

/**
 * `Guide` is a React component that renders a user guide in a modal.
 * The guide consists of several pages which the user can step through one by one.
 * The guide is finished when the modal is closed or when the user clicks *Finish* on the last page of the guide.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] A custom class to add to the modal.
 * @param {string} props.contentLabel This property is used as the modal's accessibility label.
 *                                    It is required for accessibility reasons.
 * @param {string} [props.backButtonText] Use this to customize the label of the *Previous* button shown at the end of the guide.
 * @param {string} [props.finishButtonText] Use this to customize the label of the *Finish* button shown at the end of the guide.
 * @param {renderFinishCallback} [props.renderFinish] A function for rendering custom finish block shown at the end of the guide.
 * @param {Function} props.onFinish A function which is called when the guide is finished.
 *                                  The guide is finished when the modal is closed
 *                                  or when the user clicks *Finish* on the last page of the guide.
 * @param {Array<Object>} props.pages A list of objects describing each page in the guide.
 *                                    Each object must contain a 'content' property and may optionally contain a 'image' property.
 */
export default function Guide( {
	className,
	contentLabel,
	backButtonText,
	finishButtonText,
	renderFinish = ( finishButton ) => finishButton,
	onFinish,
	pages,
} ) {
	const [ currentPage, setCurrentPage ] = useState( 0 );

	const canGoBack = currentPage > 0;
	const canGoForward = currentPage < pages.length - 1;

	const goBack = () => {
		if ( canGoBack ) {
			setCurrentPage( currentPage - 1 );
		}
	};

	const goForward = () => {
		if ( canGoForward ) {
			setCurrentPage( currentPage + 1 );
		}
	};

	if ( pages.length === 0 ) {
		return null;
	}

	let finishBlock = null;

	if ( ! canGoForward ) {
		const finishButton = (
			<FinishButton
				className="components-guide__finish-button"
				onClick={ onFinish }
			>
				{ finishButtonText || __( 'Finish' ) }
			</FinishButton>
		);

		finishBlock = renderFinish( finishButton );
	}

	return (
		<Modal
			className={ classnames( 'components-guide', className ) }
			contentLabel={ contentLabel }
			onRequestClose={ onFinish }
		>
			<KeyboardShortcuts
				key={ currentPage }
				shortcuts={ {
					left: goBack,
					right: goForward,
				} }
			/>

			<div className="components-guide__container">
				<div className="components-guide__page">
					{ pages[ currentPage ].image }

					{ pages.length > 1 && (
						<PageControl
							currentPage={ currentPage }
							numberOfPages={ pages.length }
							setCurrentPage={ setCurrentPage }
						/>
					) }

					{ pages[ currentPage ].content }
				</div>

				<div className="components-guide__footer">
					{ canGoBack && (
						<Button
							className="components-guide__back-button"
							onClick={ goBack }
						>
							{ backButtonText || __( 'Previous' ) }
						</Button>
					) }
					{ canGoForward && (
						<Button
							className="components-guide__forward-button"
							onClick={ goForward }
						>
							{ __( 'Next' ) }
						</Button>
					) }
					{ finishBlock }
				</div>
			</div>
		</Modal>
	);
}
