const stylelint = require( 'stylelint' );
const { annotateToGitHubActions } = require( './utils' );

function toAnnotationModels( errorMetadata ) {
	const truncationPath = process.cwd();
	const models = [];

	errorMetadata.forEach( ( file ) => {
		const filePath = file.source.replace( truncationPath, '.' );

		file.warnings.forEach( ( lintError ) => {
			const { severity, line, column, text } = lintError;

			models.push( {
				command: severity,
				filePath,
				line,
				column,
				message: text,
			} );
		} );
	} );

	return models;
}

/**
 * @type {import('stylelint').Formatter}
 *
 * Ref: https://stylelint.io/developer-guide/formatters/
 */
module.exports = function ( results, returnValue ) {
	const errorMetadata = results.filter( ( { warnings } ) => warnings.length );
	const models = toAnnotationModels( errorMetadata );
	annotateToGitHubActions( models );

	// Still outputting the original CLI logs by default format.
	return stylelint.formatters.string( results, returnValue );
};
