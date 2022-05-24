const mockIssue = ( id, args ) => {
	return {
		product_id: id,
		issue: `#issue-${ id }`,
		code: `#code-${ id }`,
		product: `#product-${ id }`,
		severity: 'warning',
		action: `Action for ${ id }`,
		action_url: `example.com/${ id }`,
		type: 'product',
		...args,
	};
};

export default mockIssue;
