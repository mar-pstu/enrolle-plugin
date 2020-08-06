<?php

/**
*	Вывод списка сеторов знаний и образовательных программ
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuKnowledgeAreasEducationalPrograms' ) ) return;


class pstuKnowledgeAreasEducationalPrograms {


	private $name = 'knowledge_areas_educational_programs';


	private $educational_programs;


	private $specialties;


	function __construct() {
		add_shortcode( $this->name, array( $this, 'render_content' ) );
	}



	/**
	 * Формирует html код логотипа области знаний
	 *
	 * @param    WP_Term    $term
	 * @return   string
	 */
	public function get_logo( $term ) {
		$result = '';
		$attachment_id = get_term_meta( $term->term_id, 'pstu_specialties_logo', true );
		if ( $attachment_id ) {
			$attachment_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail', false );
			if ( $attachment_url ) {
				$result = sprintf(
					'<div class="thumbnail"><img src="%1$s" alt="%2$s"></div>',
					$attachment_url,
					esc_attr( $term->name )
				);
			}
		}
		return $result;
	}


	/**
	 * Возвращает описание сектора/специальности на основе поля description термина или цитаты страницы с описанием
	 *
	 * @param    WP_Term    $term
	 * @return   string
	 */
	public function get_description( $term ) {
		$result = $term->description;
		if ( empty( $result ) ) {
			$page_id = get_term_meta( $term->term_id, 'pstu_specialties_page_id', true );
			if ( has_excerpt( $post = 0 ) ) $result = get_the_excerpt( $page_id );
		}
		return ( empty( $result ) ) ? '' : '<p>' . strip_tags( $result ) . '</p>';
	}



	/**
	 * Формирует html код списка образовательных программ прикреплённых к специальности
	 *
	 * @param     WP_Term   область знаний
	 * @param     array     массив образовательных программ который нужно отфильтровать
	 * @return    string 
	 */
	public function get_education_programs_list( $id ) {
		$result = array();
		foreach ( $this->educational_programs as $educational_program ) {
			if ( is_object_in_term( $educational_program->ID, 'specialties', $id ) ) {
				setup_postdata( $educational_program );
				$okr = get_the_terms( $educational_program->ID, 'okr' );
				$features = trim( get_post_meta( $educational_program->ID, 'pstu_educational_program_features', true ) );
				$result[] = sprintf(
					'<li><a href="%1$s">%2$s</a> %3$s %4$s %5$s</li>',
					get_permalink( $educational_program->ID ),
					$educational_program->post_title,
					( is_array( $okr ) && ! empty( $okr ) ) ? '<small>(' . $okr[0]->name . ')</small>' : '',
					( empty( $features ) ) ? '' : '<b>' . $features . '</b>',
					$educational_program->post_excerpt
				);
			}
		}
		wp_reset_postdata();
		return ( empty( $result ) ) ? '' : '<ul>' . implode( "\r\n", $result ) . '</ul>';
	}



	/**
	 * Формирует html код списка специальностей и образовательных программ
	 *
	 * @param    int   id области знаний
	 * @return   string
	 */
	public function get_list_of_specialties( $knowledge_area_id ) {
		$result = array();
		foreach ( wp_list_filter( $this->specialties, array( 'parent' => $knowledge_area_id ) ) as $specialty ) {
			$list = $this->get_education_programs_list( $specialty->term_id );
			if ( ! empty( $list ) ) {
				$result[] = sprintf(
					'<h3>%1$s</h3> %2$s',
					apply_filters( 'single_term_title', $specialty->name ),
					$list
				);
			}
		}
		return implode( "\r\n", $result );
	}



	/**
	 * Формирует и возвращает области знаний и образовательные программы к ним
	 *
	 * @param      array     $atts Параметры шорткода
	 * @return     string
	 */
	public function render_content( $atts = array() ) {
		wp_enqueue_style( 'enrollee' );
		$atts = shortcode_atts( array(), $atts, $this->name );
		$result = array();
		$this->specialties = get_terms( array(
			'taxonomy'      => 'specialties',
			'orderby'       => 'meta_value', 
			'order'         => 'ASC',
			'get'           => 'all',
			'hide_empty'    => true,
			'meta_key'      => 'pstu_specialties_code'
		) );
		$this->educational_programs = get_posts( array(
			'numberposts'   => -1,
			'orderby'       => 'name',
			'order'         => 'ASC',
			'post_type'     => 'educational_program',
		) );
		if ( is_array( $this->specialties ) && ! empty( $this->specialties ) && is_array( $this->educational_programs ) && ! is_wp_error( $this->educational_programs ) ) {
			wp_enqueue_style( 'enrollee' );
			foreach ( wp_list_filter( $this->specialties, array( 'parent' => '0' ) ) as $knowledge_area ) {
				$list_of_specialties = $this->get_list_of_specialties( $knowledge_area->term_id );
				if ( ! empty( $list_of_specialties ) ) $result[] = wp_sprintf(
					'<div class="enrollee-sector"> %1$s <div class="sector-body"> <h2>%2$s</h2> %3$s %4$s</div></div>',
					$this->get_logo( $knowledge_area ),
					$knowledge_area->name,
					$this->get_description( $knowledge_area ),
					$list_of_specialties
				);
			}
		}
		return implode( "\r\n", $result );
	}

}