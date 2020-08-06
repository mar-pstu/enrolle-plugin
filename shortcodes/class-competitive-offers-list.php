<?php

/**
*	Вывод списка образовательный программ
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuCompetitiveOffersList' ) ) return;


class pstuCompetitiveOffersList {


	use pstuEnrolleeTrait;


	static private $name = 'competitive_offers_list';


	function __construct() {
		add_shortcode( self::$name, array( $this, 'render_content' ) );
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}
	}


	/**
	*	Вывод предупреждений в админке
	*/
	public function notice() {
		$post_type = get_post_type();
		if ( in_array( $post_type, array( 'competitive_offers', 'educational_program' ) ) ) printf(
			'<div class="notice notice-info"><p>%1$s <input type="text" class="code" style="display: inline-block;" readonly="readonly" onfocus="this.select();" value="[%2$s headers=h3 %3$s]"></p></div>',
			__( 'Вывод всех конкурсных предложений на странице образовательной программы', 'pstu-enrollee' ),
			self::$name,
			( 'educational_program' == $post_type ) ? 'id=' . get_the_ID() : ''
		);
	}



	/**
	*	Возвращает форматированный список свойств
	*/
	public function get_properties( $post_id ) {
		$result = array();
		foreach ( array(
			'edbo_name'                         => __( 'Название в ЕГЭБО', 'pstu-enrollee' ),
			'type_of_offer'                     => __( 'Вид предложения', 'pstu-enrollee' ),
			'based_education'                   => __( 'Предыдущее образование', 'pstu-enrollee' ),
			'form_study'                        => __( 'Форма обучения', 'pstu-enrollee' ),
			'training_term'                     => __( 'Срок обучения', 'pstu-enrollee' ),
			'components_of_competitive_points'  => __( 'Компоненты и их вес', 'pstu-enrollee' ),
		) as $key => $label ) {
			switch ( $key ) {
				case 'edbo_name':
					$edbo_name = get_post_meta( $post_id, 'pstu_edbo_name', true );
					if ( ! empty( trim( $edbo_name ) ) ) {
						$result[] = sprintf(
							'<li>%1$s: <b>%2$s</b></li>',
							$label,
							$edbo_name
						);
					}
					break;
				case 'components_of_competitive_points':
					if ( has_term( '', $key, $post_id ) ) {
						$components = get_post_meta( $post_id, 'pstu_components_of_competitive_points', true );
						if ( ! empty( $components ) ) {
							$result[] = sprintf( '<li>%1$s: %2$s</li>', $label, pstuTaxonomyComponentsOfCompetitivePoints::get_components_list( $components ) );
						}
					}
					break;
				case 'training_term':
					$training_term = get_post_meta( $post_id, 'pstu_training_term', true );
					if ( ! empty( $training_term ) ) {
						$result[] = sprintf(
							__( '<li>%1$s: <b>%2$s %3$s</b></li>', 'pstu-enrollee' ),
							$label,
							( empty( $training_term[ 'years' ] ) ) ? '' : $this->num_decline( $training_term[ 'years' ], array( __( 'год', 'pstu-enrollee' ), __( 'года', 'pstu-enrollee' ), __( 'лет', 'pstu-enrollee' ) ) ),
							( empty( $training_term[ 'months' ] ) ) ? '' : $this->num_decline( $training_term[ 'months' ], array( __( 'месяц', 'pstu-enrollee' ), __( 'месяца', 'pstu-enrollee' ), __( 'месяцев', 'pstu-enrollee' ) ) )
						);
					}
					break;
				default:
					if ( has_term( '', $key, $post_id ) ) {
						$properties = get_terms( array(
							'taxonomy'      => $key,
							'object_ids'    => $post_id,
							'fields'        => 'names',
						) );
						if ( $properties && ! empty( $properties ) && ! is_wp_error( $properties ) ) {
							$result[] = sprintf(
								'<li>%1$s: <b>%2$s</b></li>', $label, $this->pstu_sprint_array( 'OR', $properties )
							);
						}
					}
					break;
			}
		}
		return ( empty( $result ) ) ? '' : '<ul>' . implode( "\r\n", $result ) . '</ul>';
	}



	public function render_content( $atts = array() ) {
		$atts = shortcode_atts( array(
			'id'        => ( 'educational_program' == get_post_type( get_the_ID() ) ) ? get_the_ID() : false,
			'headers'   => 'h3',
		), $atts, self::$name );
		$result = array();
		if ( $atts[ 'id' ] ) {
			$competitive_offers_args = array(
				'numberposts'      => -1,
				'orderby'          => 'name',
				'order'            => 'ASC',
				'meta_key'         => 'pstu_course',
				'post_type'        => 'competitive_offers',
				'meta_query'       => array(
					'relation'       => 'AND',
					array(
						'key'        => 'pstu_educational_program',
						'value'      => sanitize_key( $atts[ 'id' ] ),
						'compare'    => '=',
					),
				),
			);
			$competitive_offers = get_posts( $competitive_offers_args );
			if ( $competitive_offers && ! empty( $competitive_offers ) && ! is_wp_error( $competitive_offers ) ) {
				foreach ( $competitive_offers as $competitive_offer ) {
					$result[] = sprintf(
						'<%1$s>%2$s %4$s</%1$s>%3$s',
						$atts[ 'headers' ],
						apply_filters( 'the_title', $competitive_offer->post_title, $competitive_offer->ID ),
						$this->get_properties( $competitive_offer->ID ),
						( empty( trim( $competitive_offer->post_excerpt ) ) ) ? '' : ' (' . strip_tags( $competitive_offer->post_excerpt ) . ')'
					);
					if ( current_user_can( 'edit_posts' ) ) {
						$result[] = sprintf(
							'<div style="text-align: right; font-size: 85%;">&#9650; <small>ID %3$s</small> <a href="%1$s">%2$s</a></div>',
							get_edit_post_link( $competitive_offer->ID ),
							__( 'Редактировать', 'pstu-enrollee' ),
							$competitive_offer->ID
						);
					}
				}
			}
		}
		return implode( "\r\n", $result );
	}


}