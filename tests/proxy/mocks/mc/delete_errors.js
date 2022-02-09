'use strict';

module.exports.deleteErrors = ( requests ) => {
	const entries = requests.entries.map( ( entry ) => {
		return {
			kind: 'content#productsCustomBatchResponseEntry',
			batchId: entry.batchId,
			errors: {
				errors: [
					{
						domain: 'global',
						reason: 'internalError',
						message: 'internal error',
					}
				],
				code: 500,
				message: 'internal error',
			}
		}
	} );

	return {
		kind: 'content#productsCustomBatchResponse',
		entries
	};
};
