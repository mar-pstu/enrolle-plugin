<?php

/**
*	Вывод списка образовательный программ
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuSpecialtyTable' ) ) return;


class pstuSpecialties {


	use pstuEnrolleeTrait;


	private $name;


	private $educational_programs;


	function __construct() {
		$this->name = 'specialties';
		add_shortcode( $this->name, array( $this, 'render_content' ) );
		add_shortcode( strtoupper( $this->name ), array( $this, 'render_content' ) );
		if ( is_admin() ) {
			add_filter( "manage_edit-specialties_columns" , array( $this, 'columns' ) );
			add_filter( "manage_specialties_custom_column" , array( $this, 'custom_column' ), 10, 3 );
		}
	}


	private function get_the_educational_programs_items( $term_id ) {
		$result = array();
		foreach ( $this->educational_programs as $educational_program ) {
			if ( is_object_in_term( $educational_program->ID, 'specialties', $term_id ) ) {
				setup_postdata( $educational_program );
				$okr = get_the_terms( $educational_program->ID, 'okr' );
				$result[] = sprintf(
					'<li><a href="%1$s">%2$s</a> %3$s</li>',
					get_permalink( $educational_program->ID ),
					apply_filters( 'the_title', $educational_program->post_title ),
					( is_array( $okr ) && ! empty( $okr ) ) ? '<small>(' . $okr[0]->name . ')</small>' : ''
				);
			}
		}
		return implode( "\r\n", $result );
	}


	public function render_content( $atts ) {
		$atts = shortcode_atts( array(
			'term_id'  => false,
			'headers'  => 'h2',
		), $atts, $this->name );
		$result = array();
		if ( $atts[ 'term_id' ] ) {
			$this->educational_programs = get_posts( array(
				'numberposts'    => -1,
				'post_type'      => 'educational_program',
				'tax_query'      => array(
					'relation'     => 'OR',
					array(
						'taxonomy' => 'specialties',
						'field'    => 'term_id',
						'terms'    => $atts[ 'term_id' ],
						'operator' => 'IN',
					),
				),
			) );
			if ( is_array( $this->educational_programs ) && ! empty( $this->educational_programs ) ) {
				$terms = get_terms( array(
					'taxonomy'      => 'specialties',
					'parent'        => $atts[ 'term_id' ],
					'orderby'       => 'meta_value', 
					'order'         => 'ASC',
					'get'           => 'all',
					'meta_key'      => 'pstu_specialties_code'
				) );
				if ( is_array( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( 0 == $term->count ) continue;
						$items = $this->get_the_educational_programs_items( $term->term_id );
						$result[] = sprintf(
							'<%1$s>%2$s</%1$s> %3$s <ul>%4$s</ul>',
							$atts[ 'headers' ],
							apply_filters( 'single_term_title', $term->name ),
							( empty( $term->description ) ) ? '' : '<p>' . $term->description . '</p>',
							$items
						);
					}
				} else {
					$result[] = '<ul>' . $this->get_the_educational_programs_items( $atts[ 'term_id' ] ) . '</ul>';
				}
			}
		}
		return implode( "\r\n", $result );
	}


	/**
	 * Добавлние шапки столбца в таблице постов админки
	 *
	 * @param    array     $colums
	 * @return   array
	 */
	public function columns( $columns ) {
		$columns[ "{$this->name}_shortcode" ] = __( 'Шорткод', 'pstu-enrollee' );
		return $columns;
	}


	/**
	 * Заполнение ячеек в таблице таксономии админки
	 *
	 * @var       String     $out
	 * @var       String     $column_name
	 * @var       Int        $term_id
	 *
	 * @return    String
	 */
	public function custom_column( $out, $column_name, $term_id ) {
		if ( "{$this->name}_shortcode" == $column_name ) {
			$out = sprintf(
				'<span><input type="text" class="large-text code" readonly="readonly" onfocus="this.select();" value="[%1$s term_id=%2$s][/%1$s]"></span>',
				$this->name,
				$term_id
			);
		}
		return $out;
	}


}