<?php

/**
*	Вывод списка образовательный программ
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuOKRList' ) ) return;


class pstuOKRList {


	use pstuEnrolleeTrait;


	private $name = 'okr';


	function __construct() {
		add_shortcode( $this->name, array( $this, 'render_content' ) );
		if ( is_admin() ) {
			add_filter( "manage_edit-{$this->name}_columns" , array( $this, 'columns' ) );
			add_filter( "manage_{$this->name}_custom_column" , array( $this, 'custom_column' ), 10, 3 );
		}
	}


	public function render_content( $atts = array() ) {
		$atts = shortcode_atts( array(
			'id'	=> false,
		), $atts, $this->name );
		$result = array();
		$okr = get_term( $atts[ 'id' ], $this->name, OBJECT );
		if ( $okr && ! empty( $okr ) && ! is_wp_error( $okr ) ) {
			$education_programs = get_posts( array(
				'numberposts' => -1,
				'orderby'     => 'name',
				'order'       => 'ASC',
				'post_type'   => 'educational_program',
				$this->name 	=> $okr->slug,
				'suppress_filters' => true,
			) );
			if ( $education_programs && ! empty( $education_programs ) && ! is_wp_error( $education_programs ) ) {
				$result[] = '<ul>';
				foreach ( $education_programs as $education_program ) {
					setup_postdata( $education_program );
					$result[] = sprintf(
						'<li><a href="%1$s" title="%2$s - %3$s">%4$s</a> %5$s</li>',
						get_the_permalink( $education_program->ID ),
						__( 'Подробней', 'pstu-enrollee' ),
						esc_attr( $education_program->post_title ),
						apply_filters( 'the_title', $education_program->post_title, $education_program->ID, false ),
						( empty( trim( $education_program->post_excerpt ) ) ) ? '' : sprintf( '<i><small>(%1$s)</small></i>', strip_tags( $education_program->post_excerpt ) )
					);
				}
				wp_reset_postdata();
				$result[] = '</ul>';
			} else {
				$result[] = sprintf( '<p class="lead text-warning font-bold">%1$s</p>', __( 'Образовательные программы не найдены', 'pstu-enrollee' ) );
			}
		}
		return implode( "\r\n", $result );
	}

	public function columns( $columns ) {
		$columns[ $this->name ] = __( 'Шорткод', 'pstu-enrolle' );
		return $columns;
	}


	public function custom_column( $content, $column_name, $term_id ) {
		if ( $this->name == $column_name ) {
			$content = sprintf(
				'<span><input type="text" class="large-text code" readonly="readonly" onfocus="this.select();" value="[%1$s id=%2$s]"></span>',
				$this->name,
				$term_id
			);
		}
		return $content;
	}


}