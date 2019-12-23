<?php
/**
 * TextArea class for Formation field.
 *
 * @package Formation
 */

namespace Formation\Component\Field;

use Formation;

/**
 * Class TextArea
 */
class Repeater extends FieldAbstract {

	/**
	 * The field type.
	 *
	 * @var string
	 */
	public $type = 'repeater';

	/**
	 * Inner Fields
	 *
	 * @var array
	 */
	public $fields;
	/**
	 * Flags the field as valid or not.
	 *
	 * @var bool
	 */
	private $valid = true;

	/**
	 * Initiate the Field.
	 *
	 * @param array             $args   Field instance args.
	 * @param \Formation\Plugin $plugin Instance of the main plugin.
	 * @param array             $block  The field block object.
	 */
	public function __construct( $args, $plugin, $block ) {
		parent::__construct( $args, $plugin, $block );
		$field_types = array_keys( $this->field->fields );

		foreach ( $block['innerBlocks'] as $inner_block ) {
			if ( in_array( $inner_block['blockName'], $field_types, true ) ) {
				$this->fields[] = $inner_block['attrs']['_unique_id'];
			}
		}
	}

	/**
	 * Get the attributes for this fields input tag.
	 *
	 * @return array
	 */
	public function get_input_attributes() {

		$attributes = array(
			'type'        => 'hidden',
			'name'        => $this->args['slug'],
			'id'          => $this->args['slug'],
			'placeholder' => $this->args['placeholder'],
			'required'    => $this->args['required'],
			'value'       => wp_json_encode( $this->args['value'] ),
			'data-parent' => $this->args['_unique_id'],
		);

		return $attributes;
	}

	/**
	 * Validates a value.
	 *
	 * @param \WP_Error|mixed $value The value to validate.
	 *
	 * @return mixed
	 */
	protected function validate_value( $value ) {

		foreach ( $value as &$item ) {
			foreach ( $this->fields as $field ) {
				$field_instance = $this->field->instances[ $field ];
				$field_name     = $field_instance->get_base_name();
				$proposed_value = $field_instance->validate_value( $item[ $field_name ] );
				if ( is_wp_error( $proposed_value ) ) {
					$this->set_notice( $proposed_value->get_error_code() );
					$this->valid = false;
				}
				$item[ $field_name ] = $proposed_value;
			}
		}

		return $proposed_value;
	}

	/**
	 * Renders a field.
	 *
	 * @param string $content Content created by block renderer.
	 *
	 * @return string
	 */
	public function render( $content = null ) {
		$template = array();

		$template['repeatable_wrapper_start']          = '<div class="formation-field">';
		$template['repeatable_template_wrapper_start'] = '<div data-template="' . esc_attr( $this->args['_unique_id'] ) . '" style="display:none;visibility:hidden;">';
		$template['repeatable_template_start']         = '<div class="formation-repeatable">';
		$template['repeatable_template_closer']        = '<button type="button" data-closer="true">&times;</button>';
		$template['repeatable_template']               = $content;
		$template['repeatable_template_end']           = '</div>';
		$template['repeatable_template_wrapper_end']   = '</div>';
		$template['repeatable_container_start']        = '<div class="formation-repeatable-container" data-container="' . esc_attr( $this->args['_unique_id'] ) . '"></div>';
		$template['repeatable_add_button']             = '<button type="button" class="button" data-repeater="' . esc_attr( $this->args['_unique_id'] ) . '">' . esc_html( $this->args['label'] ) . '</button>';
		$template['repeatable_entry_input']            = $this->render_input();
		$template['repeatable_container_end']          = '</div>';

		$html = apply_filters( 'formation_field_structure', $template );
		$html = apply_filters( 'formation_field_structure_' . $this->type, $html );


		$html = array_filter( $html );

		return implode( $html );

		return $template;
	}

	/**
	 * Get submitted value.
	 *
	 * @return mixed
	 */
	public function get_submitted_value() {

		$value = filter_input( INPUT_POST, $this->get_base_name(), FILTER_DEFAULT );

		return json_decode( $value, true );
	}

}