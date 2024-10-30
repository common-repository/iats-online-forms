( function ( registerBlockType, el, SelectControl, RichText ) {
	var available_forms = aura_ajax_object.available_forms;
	var default_value   = available_forms && available_forms.length && available_forms[0]['value'];

	registerBlockType(
		'core/aura-form',
		{
			title: 'iATS Online Form',
			icon: 'tablet',
			category: 'embed',
			attributes: {
				id: {
					default: default_value,
				},
			},
			edit: function( props ) {
				var select = el(
					SelectControl,
					{
						value: props.attributes.id,
						label: 'Select a form',
						onChange: function( value ) {
							props.setAttributes(
								{
									id: value
								}
							);
						},
						options: available_forms
					}
				);

				var text = 'No items found. Navigate to ‘iATS Online Forms’ on the WP Admin Dashboard to create your first form.';

				return el(
					'div',
					{},
					available_forms && available_forms.length ? [select] : text
				);
			},
			save: function (props) {
				return el(
					RichText.Content,
					{
						tagName: 'div',
						value: '[aura-form id="' + props.attributes.id + '"]',
						'data-id': props.attributes.id,
					}
				);
			},
		}
	);
} )(
	wp.blocks.registerBlockType,
	wp.element.createElement,
	wp.components.SelectControl,
	wp.editor.RichText
);
