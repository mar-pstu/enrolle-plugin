<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuShortcodeCalculator' ) ) return;


/**
 *	Таксономия ЗНО
 */
class pstuShortcodeCalculator extends pstuTypeEducationalProgram {


	use pstuEnrolleeTrait;


	public $shortcode = 'ENROLLEE_CALCULATOR';


	protected $criteria = array();


	function __construct() {
		$this->criteria = array(
			'form_study'					=> array(
				'label'								=> __( 'Форма обучения', 'pstu-enrollee' ),
				'multiple'						=> false,
				'operator'						=> 'AND',
			),
			'okr'									=> array(
				'label'								=> __( 'ОКУ', 'pstu-enrollee' ),
				'multiple'						=> false,
				'operator'						=> 'AND',
			),
			'competitive_offers'	=> array(
				'label'								=> __( 'Конкурсные предложения', 'pstu-enrollee' ),
				'multiple'						=> false,
				'operator'						=> 'AND',
			),
			'test'								=> array(
				'label'								=> __( 'Условия поступления', 'pstu-enrollee' ),
				'multiple'						=> true,
				'operator'						=> 'AND',
			),
			'financing'						=> array(
				'label'								=> __( 'Источник финансирования', 'pstu-enrollee' ),
				'multiple'						=> true,
				'operator'						=> 'AND',
			),
			'based_education'			=> array(
				'label'								=> __( 'Поступление на основе...', 'pstu-enrollee' ),
				'multiple'						=> true,
				'operator'						=> 'AND',
			),
		);
		$this->get_criteria();
		add_shortcode( $this->shortcode, array( $this, 'render_content' ) );
	}


	/**
	*	Получает критерии выборки из GET
	*/
	protected function get_criteria() {
		foreach ( $this->criteria as $key => $param ) {
			if ( ! isset( $param[ 'selected' ] ) ) $this->criteria[ $key ][ 'selected' ] = array();
			if ( ( isset( $_GET[ $key ] ) ) && ( ! empty( $_GET[ $key ] ) ) && ( is_array( $_GET[ $key ] ) ) ) {
				$this->criteria[ $key ][ 'selected' ] = array();
				foreach ( $_GET[ $key ] as $value ) 
					$this->criteria[ $key ][ 'selected' ][] = sanitize_key( $value );
			}
		}
	}


	/**
	*	Формирует запрос tax_query
	*/
	protected function get_tax_query() {
		$result = array();
		foreach ( $this->criteria as $key => $param ) {
			if ( ! empty( $param[ 'selected' ] ) ) $result[] = array(
				'taxonomy'		=> $key,
				'field'				=> 'id',
				'terms'				=> $param[ 'selected' ],
				'operator'		=> $param[ 'operator' ],
			);
		}
		return $result;
	}


	/**
	 *	Вывод калькулятора
	 */
	public function render_content() {
		if ( ( isset( $_GET[ 'username' ] ) ) && ( ! empty( $_GET[ 'username' ] ) ) ) return;
		// echo "<pre>";
		// var_dump( $this->get_tax_query() );
		// echo "</pre>";
		wp_enqueue_script( 'select2' );
		wp_add_inline_script( "select2", "jQuery( '.enrollee-filter-select' ).select2();", "after" );
		wp_enqueue_style( 'select2' );
		$result = array();
		$result[] = '  <input type="text" style="display: block; width: 0px; height: 0px; opacity: 0;" name="username" value="">';
		foreach ( $this->criteria as $key => $param ) {
			$terms = get_terms( array(
				'taxonomy'      => $key,
				'orderby'       => 'name',
				'order'         => 'ASC',
				'get'           => 'all',
				'hierarchical'	=> true,
				'hide_empty'		=> true,
			) );
			if ( ( ! $terms ) || ( empty( $terms ) ) || ( is_wp_error( $terms ) ) ) continue;
			$result[] = '<div class="form-group">';
			$result[] = sprintf(
				'<label for="criterion-%1$s">%2$s</label><select style="width: 100%;" class="enrollee-filter-select" id="criterion-%1$s" name="%1$s[]" %3$s>',
				$key,
				$param[ 'label' ],
				( $param[ 'multiple' ] ) ? 'multiple="multiple"' : ''
			);
			$result[] = '<option value="">-</option>';
			switch ( $key ) {
				case 'competitive_offers':
					$optgroups = wp_list_filter( $terms, array( 'parent' => 0 ) );
					foreach ( $optgroups as $optgroup ) {
						$options = wp_list_filter( $terms, array( 'parent' => $optgroup->term_id ) );
						if ( ( ! $options ) && ( empty( $options ) ) ) continue;
						$result[] = sprintf( '<optgroup label="%1$s">', apply_filters( 'single_term_title', $optgroup->name ) );
						foreach ( $options as $option ) $result[] = sprintf(
							'<option id="criterion-%1$s-%2$s" value="%2$s" %3$s>%4$s %5$s</option>',
							$key,
							$option->term_id,
							selected( in_array( $option->term_id, $param[ 'selected' ] ), true, false ),
							apply_filters( 'single_term_title', $option->name ),
							( empty( trim( $option->description ) ) ) ? '' : '(' . $option->description . ')'
						);
						$result[] = "</optgroup>";
					}
					break;
				default:
					foreach ( $terms as $term ) $result[] = sprintf(
						'<option value="%1$s" %2$s>%3$s %4$s</option>',
						$term->term_id,
						selected( in_array( $term->term_id, $param[ 'selected' ] ), true, false ),
						$term->name,
						( empty( trim( $term->description ) ) ) ? '' : '(' . $term->description . ')'
					);
					break;
			}
			$result[] = '  </select>';
			$result[] = '</div>'; // .form-group
		}
		return ( ( empty( $result ) ) ? '' : sprintf(
			'<form method="get" action="%1$s">%2$s<div class="form-group"><button type="submit" class="btn btn-success">%3$s</button></div></form>',
			get_permalink( get_the_ID() ),
			implode( "\r\n", $result ),
			esc_attr__( 'Выполнить', 'pstu-enrollee' )
		) ) . $this->index_table( $this->get_tax_query() );
	}





}


?>