<?php

/**
*	Вывод списка сеторов знаний
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuKnowledgeAreas' ) ) return;


class pstuKnowledgeAreas {


	private $name = 'KNOWLEDGE_AREAS';


	function __construct() {
		add_shortcode( $this->name, array( $this, 'render_content' ) );
	}


	public function render_content( $atts = array() ) {
		wp_enqueue_style( 'enrollee' );
		$atts = shortcode_atts( array(), $atts, $this->name );
		$result = array();
		$terms = get_terms( array(
			'taxonomy'      => 'specialties',
			'orderby'       => 'name', 
			'order'         => 'ASC',
			'orderby'       => 'meta_value',
			'meta_key'      => 'pstu_specialties_code',
			'get'           => 'all',
			'hide_empty'	=> true,
			'hierarchical'	=> false,

		) );
		if ( ( $terms ) && ( ! empty( $terms ) ) && ( ! is_wp_error( $terms ) ) ) {
			$sectors = wp_list_filter( $terms, array( 'parent' => 0 ) );
			if ( ( $sectors ) && ( ! empty( $sectors ) ) ) {
				foreach ( $sectors as $sector ) {
					$logo_src = false;
					if ( $logo_id = get_term_meta( $sector->term_id, "pstu_specialties_logo", true ) )
						$logo_src = wp_get_attachment_image_url( $logo_id, 'thumbnail', false );
					$result[] = "<div class=\"enrollee-sector\">";
					if ( $logo_src ) $result[] = sprintf(
						'<div class="thumbnail"><img src="%1$s" alt="%2$s"></div>',
						$logo_src,
						esc_attr( $sector->name )
					);
					$result[] = "  <div class=\"sector-body\">";
					$result[] = "    <h4>" . apply_filters( 'single_term_title', $sector->name ) . "</h4>";
					if ( ! empty( trim( $sector->description ) ) ) $result[] = apply_filters( 'term_description', $sector->description );
					$spesialties = wp_list_filter( $terms, array( 'parent' => $sector->term_id ) );
					if ( ( $spesialties ) && ( ! empty( $spesialties ) ) ) {
						$result[] = "<ul>";
						foreach ( $spesialties as $specialty ) $result[] = sprintf(
							'<li><a href="%1$s">%2$s</a> <small>(%3$s)</small> %4$s</li>',
							get_term_link( $specialty->term_id, 'specialties' ),
							apply_filters( 'single_term_title', $specialty->name ),
							$specialty->count,
							( empty( trim( $specialty->description ) ) ) ? '' : '<div class="small">' . apply_filters( 'term_description', $specialty->description ) . '</div>'
						);
						$result[] = "</ul>";
					}
					$result[] = "	</div>";
					$result[] = "</div>";
				}
			}
		}
		return implode( "\r\n", $result );
	}

}