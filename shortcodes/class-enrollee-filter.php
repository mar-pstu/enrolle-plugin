<?php

/**
*	Вывод Фильтр абитуриента для посиска конкурсного предложения
*/


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuEnrolleeFilter' ) ) return;


class pstuEnrolleeFilter {


	use pstuEnrolleeTrait;


	private $name;


	private $fields;


	function __construct() {
		$this->name = 'enrollee_filter';
		$this->set_fields();
		if( wp_doing_ajax() ) {
			add_action( "wp_ajax_enrollee_filter", array( $this, 'ajax_result' ) );
	   		add_action( "wp_ajax_nopriv_enrollee_filter", array( $this, 'ajax_result' ) );
		} else {
			add_shortcode( $this->name, array( $this, 'render_content' ) );
			add_shortcode( strtoupper( $this->name ), array( $this, 'render_content' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		}
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}
	}



	private function set_fields() {
		$this->fields = array(
			'okr'             => array(
				'label'           => __( 'Выберите ОКУ', 'pstu-enrollee' ),
				'description'     => __( 'ОКУ (образовательно-квалификационный уровень) - это характеристика высшего образования. В Украине существует 5 уровней высшего образования, это младший бакалавр, бакалавр, магистр, доктор философии / доктор искусства и доктор наук. Выберите ОКУ который Вы хотите получить в нашем университете.', 'pstu-enrollee' ),
				'type'            => 'radio',
				'value'           => array(),
				'operator'        => 'AND',
				'required'        => false,
			),
			'specialties'     => array(
				'label'           => __( 'Области знаний', 'pstu-enrollee' ),
				'description'     => __( 'Группа родственных специальностей, по которым осуществляется профессиональная подготовка. Так, специальность, которую вы ищете, имеет трёхзначний шифр, первые две цифры составляют отрасль знаний. Выбирая только нужную область знаний, Вы сужаете круг поиска.', 'pstu-enrollee' ),
				'type'            => 'checkbox',
				'value'           => array(),
				'operator'        => 'IN',
				'required'        => false,
			),
			'form_study'      => array(
				'label'           => __( 'Выберите форму обучения', 'pstu-enrollee' ),
				'description'     => __( 'Обучение студентов в университете осуществляется по указанным формам, выберите нужную.', 'pstu-enrollee' ),
				'type'            => 'checkbox',
				'value'           => array(),
				'operator'        => 'IN',
				'required'        => false,
			),
			'based_education' => array(
				'label'           => __( 'Базовое образование', 'pstu-enrollee' ),
				'description'     => __( 'Уровень предыдущего образования, на основе которого Вы поступаете в университет.', 'pstu-enrollee' ),
				'type'            => 'checkbox',
				'value'           => array(),
				'operator'        => 'IN',
				'required'        => false,
			),
			'type_of_offer'   => array(
				'label'           => __( 'Вид конкурсного предложения', 'pstu-enrollee' ),
				'description'     => __( 'Конкурсное предложение может быть фиксированным - то есть финансирование осуществляется за средства государственного бюджета или за счет физических или юридических лиц, а может быть небюджетной - финансируется только за средства физических или юридических лиц.', 'pstu-enrollee' ),
				'type'            => 'checkbox',
				'value'           => array(),
				'operator'        => 'IN',
				'required'        => false,
			),
			'components_of_competitive_points' => array(
				'label'           => __( 'Компоненты', 'pstu-enrollee' ),
				'description'     => __( 'Компоненты - составные части конкурсного балла согласно правилам приёма. Из каких компонентов рассчитывается конкурсный балл Вы можете посмотреть на странице образовательной программы/специальности, это могут быть сертификаты ВНО, экзамены и тому подобное.', 'pstu-enrollee' ),
				'type'            => 'checkbox',
				'value'           => array(),
				'operator'        => 'IN',
				'required'        => true,
			),
		);
	}



	public function ajax_result() {
		$query = json_decode( wp_unslash( sanitize_text_field( $_GET[ 'query' ] ) ), true );
		if ( is_array( $query ) ) {
			$this->set_query( $query );
			echo $this->get_result();
		}
	}



	/**
	 * Устанавливает поля формы
	 */
	private function set_query( $query ) {
		foreach ( $this->fields as $key => $value ) {
			if ( isset( $query[ $key ] ) ) {
				$this->fields[ $key ][ 'value' ] = wp_parse_id_list( $query[ $key ] );
			}
		}
	}



	/**
	 * Регистрация скриптов
	 *
	 */
	public function register_scripts() {
		wp_register_script(
			'pstu-checkedall',
			PSTU_ENROLLEE_URL . 'assets/scripts/jquery.checkedall.js',
			array( 'jquery' ),
			fileatime( PSTU_ENROLLEE_DIR . 'assets/scripts/jquery.checkedall.js' ),
			'in_footer'
		);
		wp_register_script(
			'pstu-enrollee-filter',
			PSTU_ENROLLEE_URL . 'assets/scripts/enrollee-filter.js',
			array( 'jquery' ),
			fileatime( PSTU_ENROLLEE_DIR . 'assets/scripts/enrollee-filter.js' ),
			'in_footer'
		);
	}



	public function notice() {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%1$s <input type="text" class="code" style="display: inline-block;" readonly="readonly" onfocus="this.select();" value="[%2$s ajax=1]"></p></div>',
			__( 'Фильтр абитуриента', 'pstu-enrollee' ),
			$this->name
		);
	}




	/**
	 *
	 */
	public function render_content( $atts ) {
		$atts = shortcode_atts( array(
			'ajax'  => 1
		), $atts, $this->name );
		$result = '';
		if ( $atts[ 'ajax' ] == 1 ) {
			wp_enqueue_script( 'pstu-enrollee-filter' );
			wp_localize_script( 'pstu-enrollee-filter', $this->name, array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'action'      => $this->name,
				'fields'      => array_keys( $this->fields ),
				'translates'  => array(
					'search'      => esc_attr__( 'Найти', 'pstu-enrollee' ),
					'searching'   => esc_attr__( 'Поиск...', 'pstu-enrollee' ),
				),
			) );
		}
		if ( isset( $_GET[ $this->name ] ) ) {
			$this->set_query( $_GET[ $this->name ] );
			$result = $this->get_result();
		}
		return sprintf(
			'%1$s <div id="%2$s-result" class="%2$s-result">%3$s</div>',
			$this->get_form(),
			$this->name,
			$result
		);
	}


	/**
	 * Формирует html-код элементов управления формой
	 *
	 * @param    WP_Term
	 *
	 * @return   String
	 */
	private function get_fieldset_controls( $terms, $key ) {
		$result = __return_empty_array();
		$other = __return_empty_array();
		switch( $key ) {
			case 'components_of_competitive_points';
				foreach ( wp_list_filter( $terms, array( 'parent' => 0 ) ) as $term ) {
					$children = wp_list_filter( $terms, array( 'parent' => $term->term_id ) );
					if ( count( $children ) > 0 ) {
						$result[] = sprintf(
							'<fieldset><legend>%1$s</legend>%2$s %3$s</fieldset>',
							// '<div><b>%1$s</b>%2$s %3$s</div>',
							$term->name,
							( empty( trim( $term->description ) ) ) ? '' : '<div><i>' . strip_tags( $term->description ) . '</i></div>',
							implode( " ", array_map( function( $term ) use( $key ) { return $this->get_control( $term, $key ); }, $children ) )
						);
					} else {
						$other[] = $this->get_control( $term, $key );
					}
				}
				break;
			default:
				$result = array_map( function( $term ) use( $key ) { return $this->get_control( $term, $key ); }, $terms );
				break;
		}
		return implode( " ", $other ) . implode( " ", $result );
	}



	/**
	 * Формирует html-код формы
	 *
	 * @return string
	 */
	private function get_form() {
		$result = __return_empty_array();
		$count = __return_zero();
		foreach( $this->fields as $key => $value ) {
			$term_args = array(
				'taxonomy'    => $key,
				'hide_empty'  => true,
				'meta_query'  => array(
					'relation'    => 'OR',
					array(
						'key'       => 'pstu_dont_use_in_filter',
						'compare'   => 'NOT EXISTS',
					),
					array(
						'key'       => 'pstu_dont_use_in_filter',
						'value'     => '0',
						'compare'   => '=',
						'type'      => 'NUMERIC',
					),
				),
			);
			if ( 'specialties' == $key ) $term_args = array_merge( $term_args, array( 'parent' => 0 ) );
			$terms = get_terms( $term_args );
			if ( is_array( $terms ) && ! empty( $terms ) && count( $terms ) > 1 ) {
				$result[] = sprintf(
					'<fieldset id="fieldset-%1$s-%2$s" class="fields-group"><legend class="lead"><b>%7$d.&nbsp;%3$s</b></legend>%6$s %5$s %4$s</fieldset>',
					$this->name,
					$key,
					$value[ 'label' ],
					$this->get_fieldset_controls( $terms, $key ),
					( $this->fields[ $key ][ 'type' ] == 'checkbox' && count( $terms ) > 1 && 'components_of_competitive_points' != $key ) ? $this->get_select_all_control( $key ) : '',
					( empty( $this->fields[ $key ][ 'description' ] ) ) ? '' : '<p>' . $this->fields[ $key ][ 'description' ] . '</p>',
					++$count
				);
			}
		}
		return ( empty( $result ) ) ? '' : sprintf(
			'<form id="%4$s-form" method="GET" action="%1$s">%2$s <br><input id="%4$s-submit" type="submit" value="%3$s"></form>',
			get_permalink( get_the_ID() ),
			implode( "\r\n", $result ),
			esc_attr__( 'Найти', 'pstu-enrollee' ),
			$this->name
		);
	}


	/**
	 * Формирует html код "выбрать все" и подключает скрипт для его работы
	 *
	 * @return string
	 */
	private function get_select_all_control( $key ) {
		wp_enqueue_script( 'pstu-checkedall' );
		wp_add_inline_script( 'pstu-checkedall', "jQuery( '#item-{$key}-select_all' ).checkedall();", 'after' );
		return sprintf(
			'<label for="item-%1$s-select_all"><input id="item-%1$s-select_all" data-name="%4$s[%1$s][]" type="%2$s" value="1"> %3$s</label>',
			$key,
			$this->fields[ $key ][ 'type' ],
			__( 'Выбрать всё', 'pstu-enrollee' ),
			$this->name
		);
	}



	/**
	 * Формирует html элемент формы (checkbox/radio)
	 *
	 * @param    WP_Term
	 * @param    String
	 *
	 * @return   String
	 */
	private function get_control( $term, $key ) {
		return sprintf(
			'<label for="item-%1$s-%2$s"><input id="item-%1$s-%2$s" type="%3$s" value="%2$s" name="%7$s[%1$s][]" %6$s> %4$s %5$s</label>',
			$key,
			$term->term_id,
			$this->fields[ $key ][ 'type' ],
			apply_filters( 'single_term_title', $term->name, $term->term_title ),
			( empty( $term->description ) ) ? '' : '<small>('.strip_tags( $term->description ).')</small>',
			checked( true, in_array( $term->term_id, $this->fields[ $key ][ 'value' ] ), false ),
			$this->name
		);
	}


	/**
	 * Возвращает массив для запроса по таксономиям tax_query
	 *
	 * @return Array
	 */
	private function get_tax_query() {
		$result = array(
			'relation' => 'AND'
		);
		foreach ( array( 'form_study', 'based_education', 'type_of_offer', 'components_of_competitive_points' ) as $key ) {
			if ( ! empty( $this->fields[ $key ][ 'value' ] ) ) {
				$result[] = array(
					'taxonomy' => $key,
					'field'    => 'id',
					'terms'    => $this->fields[ $key ][ 'value' ],
					'operator' => $this->fields[ $key ][ 'operator' ],
				);
			}
		}
		return $result;
	}



	/**
	 * Возвращает массив для запроса по метаполям
	 *
	 * @return Array
	 */
	public function get_meta_query() {
		$result = array();
		if ( empty( $this->fields[ 'okr' ][ 'value' ] ) && empty( $this->fields[ 'specialties' ][ 'value' ] ) ) return __return_null();
		$tax_query = array( 'relation' => 'AND' );
		foreach ( array( 'okr', 'specialties' ) as $key ) {
			if ( empty( $this->fields[ $key ][ 'value' ] ) ) continue;
			$tax_query[] = array(
				'taxonomy' => $key,
				'field'    => 'id',
				'terms'    => $this->fields[ $key ][ 'value' ],
				'operator' => $this->fields[ $key ][ 'operator' ],
			);
		}
		$entries = get_posts( array(
			'numberposts' => -1,
			'post_type'   => 'educational_program',
			'tax_query'   => $tax_query,
		) );
		if ( $entries && ! empty( $entries ) && ! is_wp_error( $entries ) ) {
			$result = array(
				'relation' => 'AND',
				array(
					'key'      => 'pstu_educational_program',
					'value'    => wp_list_pluck( $entries, 'ID' ),
					'compare'  => 'IN',
					'type'     => 'NUMERIC',
				),
			);
		}
		return $result;
	}



	/**
	 *	Фильтр по предметам / компонентам
	 *
	 * @return Bool
	 */
	private function components_filter( $arguments, $exclude = array() ) {
		if ( is_array( $arguments ) ) {
			foreach ( $arguments as $argument ) {
				if ( strval( $argument[ 'coefficient' ] ) == 0 ) continue;
				$argument[ 'components' ] = array_diff( $argument[ 'components' ], $exclude );
				if ( empty( $argument[ 'components' ] ) ) continue;
				if ( count( array_intersect( $argument[ 'components' ], $this->fields[ 'components_of_competitive_points' ][ 'value' ] ) ) == 0 ) {
					return false;
				}
			}
		}
		return true;
	}



	// /**
	//  * Формирует имя конкурсного предложения
	//  *
	//  * @param    WP_Post
	//  * @param    integer|string
	//  *
	//  * @return   String
	//  */
	// private function get_entry_name( $entry, $educational_program_id ) {
	// 	$result = '';
	// 	$edbo_name = get_post_meta( $entry->ID, 'pstu_edbo_name', true );
	// 	if ( empty( trim( $edbo_name ) ) ) {
	// 		$result = sprintf(
	// 			'%1$s %2$s %3$s',
	// 			get_the_title( $educational_program_id ),
	// 			apply_filters( 'the_title', $entry->post_title, $entry->ID ),
	// 			( empty( trim( $entry->post_excerpt ) ) ) ? '' : '<b>('.$entry->post_excerpt.')</b>'
	// 		);
	// 	} else {
	// 		$result = sprintf(
	// 			'%1$s %2$s',
	// 			get_post_meta( $educational_program_id, 'pstu_specialties_code', true ),
	// 			$edbo_name
	// 		);
	// 	}
	// 	return $result;
	// }



	/**
	 * Формирует html-код выборки
	 *
	 * @return String
	 */
	private function get_result() {
		$result = array();
		$entries_args = array(
			'numberposts' => -1,
			'post_type'   => 'competitive_offers',
			'meta_key'    => 'pstu_educational_program',
			'orderby'     => 'meta_value',
			'order'       => 'ASC',
			'tax_query'   => $this->get_tax_query(),
			'meta_query'  => $this->get_meta_query(),
		);
		$entries = get_posts( $entries_args );
		if ( is_array( $entries ) && ! empty( $entries ) ) {
			$exclude_arguments = get_terms( array(
				'taxonomy'    => 'components_of_competitive_points',
				'hide_empty'  => true,
				'fields'      => 'ids',
				'meta_query'  => array(
					'relation'    => 'AND',
					array(
						'key'       => 'pstu_dont_use_in_filter',
						'value'     => '1',
						'compare'   => '=',
						'type'      => 'NUMERIC',
					),
				),
			) );
			$count = 0;
			foreach ( $entries as $entry ) {
				setup_postdata( $entry );
				$arguments = get_post_meta( $entry->ID, 'pstu_components_of_competitive_points', true );
				if ( ! $this->components_filter( $arguments, $exclude_arguments ) ) continue;
				$count++;

				$training_term = get_post_meta( $entry->ID, 'pstu_training_term', true );
				if ( ! is_array( $training_term ) ) $training_term = array();

				$educational_program_id = get_post_meta( $entry->ID, 'pstu_educational_program', true );

				$result[] = wp_sprintf(
					'<tr><td>%1$s</td><td><div><a href="%2$s">%3$s</a></div><div>%4$s %5$s</div></td><td>%6$s %7$s</td><td>%8$s</td></tr>',
					$count,
					get_permalink( $educational_program_id ),
					get_the_title( $educational_program_id ),
					apply_filters( 'the_title', $entry->post_title, $entry->ID ),
					( empty( trim( $entry->post_excerpt ) ) ) ? '' : '<b>('.$entry->post_excerpt.')</b>',
					( empty( $training_term[ 'years' ] ) ) ? '' : $this->num_decline( $training_term[ 'years' ], array( __( 'год', 'pstu-enrollee' ), __( 'года', 'pstu-enrollee' ), __( 'лет', 'pstu-enrollee' ) ) ),
					( empty( $training_term[ 'months' ] ) ) ? '' : $this->num_decline( $training_term[ 'months' ], array( __( 'месяц', 'pstu-enrollee' ), __( 'месяца', 'pstu-enrollee' ), __( 'месяцев', 'pstu-enrollee' ) ) ),
					( has_term( '', 'components_of_competitive_points', $entry->ID ) ) ? pstuTaxonomyComponentsOfCompetitivePoints::get_components_list( $arguments ) : ''
				);
				// $result[] = wp_sprintf(
				// 	'<tr><td>%1$s</td><td><p><a href="%2$s">%3$s</a></p></td><td>%4$s %5$s</td><td>%6$s</td></tr>',
				// 	$count,
				// 	get_permalink( $educational_program_id ),
				// 	$this->get_entry_name( $entry, $educational_program_id ),
				// 	( empty( $training_term[ 'years' ] ) ) ? '' : $this->num_decline( $training_term[ 'years' ], array( __( 'год', 'pstu-enrollee' ), __( 'года', 'pstu-enrollee' ), __( 'лет', 'pstu-enrollee' ) ) ),
				// 	( empty( $training_term[ 'months' ] ) ) ? '' : $this->num_decline( $training_term[ 'months' ], array( __( 'месяц', 'pstu-enrollee' ), __( 'месяца', 'pstu-enrollee' ), __( 'месяцев', 'pstu-enrollee' ) ) ),
				// 	( has_term( '', 'components_of_competitive_points', $entry->ID ) ) ? pstuTaxonomyComponentsOfCompetitivePoints::get_components_list( $arguments ) : ''
				// );
			}
			wp_reset_postdata();
		}
		if ( empty( $result ) ) {
			return sprintf(
				'<h3>%1$s %2$s</h3>',
				__( 'Ничего не найдено.', 'pstu-enrollee' ),
				( empty( $this->fields[ 'components_of_competitive_points' ][ 'value' ] ) ) ? __( 'Выберите компоненты конкурсного предложения', 'pstu-enrollee' ) : __( 'Попробуйте другие параметры.', 'pstu-enrollee' )
			);
		} else {
			return sprintf(
				'<br><p>%7$s <b>%8$s</b></p><table><thead><th>%1$s</th><th>%2$s / %3$s</th><th>%4$s</th><th>%5$s</th></thead><tbody>%6$s</tbody></table>',
				__( '№ п/п', 'pstu-enrollee' ),
				__( 'Образовательная программа', 'pstu-enrollee' ),
				__( 'Описание', 'pstu-enrollee' ),
				__( 'Срок обучения', 'pstu-enrollee' ),
				__( 'Компоненты', 'pstu-enrollee' ),
				implode( "\r\n" , $result ),
				__( 'Найдено', 'pstu-enrollee' ),
				$this->num_decline( count( $result ), array( __( 'конкурсное предложение', 'pstu-enrollee' ), __( 'конкурсных предложения', 'pstu-enrollee' ), __( 'конкурсных предложений', 'pstu-enrollee' ) ) )
			);
		}
	}



}