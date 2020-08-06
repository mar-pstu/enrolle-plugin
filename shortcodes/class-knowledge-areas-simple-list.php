<?php

/**
*	Вывод секторов знаний в виде списка
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuKnowledgeAreas' ) ) return;


class pstuKnowledgeAreasSimpleList {


	protected $name;


	function __construct() {
		$this->name = 'knowledge_areas_simple_list';
		add_shortcode( $this->name, array( $this, 'render_content' ) );
		add_shortcode( strtoupper( $this->name ), array( $this, 'render_content' ) );
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}
	}


	public function notice() {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%1$s <input type="text" class="code" style="display: inline-block;" readonly="readonly" onfocus="this.select();" value="[%2$s]"></p></div>',
			__( 'Вывод простого списка областей знаний', 'pstu-enrollee' ),
			$this->name
		);
	}

	public function render_content() {
		$result = array();
		$knowledge_areas = get_terms( array(
			'taxonomy'      => 'specialties',
			'hide_empty'    => false,
			'parent'        => 0,
			'order'         => 'ASC',
			'orderby'       => 'meta_value',
			'meta_key'      => 'pstu_specialties_code',
		) );
		if ( is_array( $knowledge_areas ) && ! empty( $knowledge_areas ) ) {
			foreach ( $knowledge_areas as $knowledge_area ) {
				$result[] = sprintf(
					'<li><a href="%1$s">%2$s</a></li>',
					get_term_link( $knowledge_area->term_id, 'specialties' ),
					apply_filters( 'single_term_title', $knowledge_area->name )
				);
			}
		}
		return ( empty( $result ) ) ? '' : '<ul>' . implode( "\r\n", $result ) . '</ul>';
	}


}