<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuTypeEducationalProgram' ) ) return;


class pstuTypeEducationalProgram {


	use pstuEnrolleeTrait;


	protected $name = 'educational_program';


	protected $slug = 'pstu_educational_program';


	protected $taxonomies = array( 'category' );


	protected $supports = array( 'title', 'excerpt', 'editor', 'thumbnail' );


	public function __construct() {
		add_action( 'init', array( $this, 'creatre_post_type' ), 10, 0 );
		add_action( 'template_redirect', array( $this, 'index_redirect' ), 10, 0 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'page' ), 10, 0	);
			add_action( 'admin_menu', array( $this, 'settings' ), 10, 0	);
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 10, 0 );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 1 );
			add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );
		}
	}


	/**
	 *	Добавление метабокса
	 */
	public function add_meta_box( $post_type ) {
		if ( $post_type == $this->name ) add_meta_box(
			$this->slug . '_features',
			__( 'Особенности образовательной программы', 'pstu-next-theme' ),
			array( $this, 'render_meta_box_content' ),
			$post_type,
			'side',
			'high',
			null
		);
	}



	/**
	 * Вывод контента метабокса на странице педактирования конкурсного предложения
	 *
	 * @param   WP_Post
	 */
	public function render_meta_box_content( $post ) {
		wp_nonce_field( $this->slug, "{$this->slug}_nonce" );
		$translation_id = $this->get_translation_id( $post->ID );
		$pstu_features = get_post_meta( $post->ID, $this->slug . '_features', true );
		if ( $translation_id && empty( $pstu_features ) ) $pstu_features = get_post_meta( $translation_id, $this->slug . '_features', true );
		printf(
			'<textarea id="%1$s_field" style="text-align: left; width: 100%%; display: block;" type="text" name="%1$s">%2$s</textarea>',
			$this->slug . '_features',
			$pstu_features
		);
	}



	/**
	 * Сохранение поста
	 *
	 * @param    Integer
	 */
	public function save_post( $post_id ) {
		if ( ! isset( $_POST[ "{$this->slug}_nonce" ] ) ) return;
		if ( ! wp_verify_nonce( $_POST[ "{$this->slug}_nonce" ], $this->slug ) ) { wp_nonce_ays(); return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;	
		if ( $this->name == $_POST[ 'post_type' ] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_nonce_ays();
			return;
		}
		if ( isset( $_POST[ $this->slug . '_features' ] ) ) {
			update_post_meta( $post_id, $this->slug . '_features', strip_tags( sanitize_textarea_field( $_POST[ $this->slug . '_features' ] ) ) );
		} else {
			delete_post_meta( $post_id, $this->slug . '_features' );
		}
	}




	/**
	 *	Редирект на страницу со списком образовательных программ
	 */
	public function index_redirect() {
		if ( is_post_type_archive( $this->name ) ) {
			$options = get_option( $this->slug );
			if ( ( isset( $options[ 'page_id' ] ) ) && ( ! empty( $options[ 'page_id' ] ) ) ) {
				if ( function_exists( 'pll_get_post' ) ) $options[ 'page_id' ] = pll_get_post( $options[ 'page_id' ] );
				wp_redirect( get_permalink( $options[ 'page_id' ] ), 301 );
				exit;
			}
		}
	}


	/**
	 *	Регистрация нового типа
	 */
	public function creatre_post_type() {
		register_post_type(
			$this->name,
			array(
				'labels'                   =>	array(
					'name'                   => __( 'Образовательные программы', 'pstu-enrollee' ),
					'singular_name'          => __( 'Образовательная программа', 'pstu-enrollee' ),
					'add_new'                => __( 'Добавить новую ОП', 'pstu-enrollee' ),
					'add_new_item'           => __( 'Новая ОП', 'pstu-enrollee' ),
					'edit_item'              => __( 'Редактировать образовательную программу', 'pstu-enrollee' ),
					'new_item'               => __( 'Новая образовательная программа', 'pstu-enrollee' ),
					'all_items'              => __( 'Все образовательные программы', 'pstu-enrollee' ),
					'view_item'              => __( 'Просмотр образовательных программ на сайте', 'pstu-enrollee' ),
					'search_items'           => __( 'Искать в списке', 'pstu-enrollee' ),
					'not_found'              => __( 'Записи не найдены', 'pstu-enrollee' ),
					'not_found_in_trash'     => __( 'В корзине нет образовательных программ', 'pstu-enrollee' ),
					'menu_name'              => __( 'Образовательные программы', 'pstu-enrollee' ),
				),
				'public'                   => true,
				'show_ui'                  => true,
				'exclude_from_search'      => false, 
				'has_archive'              => true,
				'show_in_rest'             => true,
				'menu_icon'                => 'dashicons-welcome-learn-more',
				'menu_position'            => '51',
				'supports'                 => $this->supports,
				'taxonomies'               => $this->taxonomies,
			)
		);
	}


	/**
	 *	Регистрация стилей и скриптов
	 */
	public function admin_enqueue() {
		wp_register_script(
			'maskedinput',
			PSTU_ENROLLEE_URL . 'assets/scripts/jquery.maskedinput.js',
			array( 'jquery' ),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/jquery.maskedinput.js' ),
			true
		);
		wp_register_script(
			'select2',
			PSTU_ENROLLEE_URL . 'assets/scripts/select2.js',
			array( 'jquery' ),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/select2.js' ),
			true
		);
		wp_register_style(
			'select2',
			PSTU_ENROLLEE_URL . 'assets/css/select2.css',
			array(),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/css/select2.css' ),
			'all'
		);
	}


	/**
	 *	Вывод страницы настроек
	 */
	public function page() {
		add_submenu_page(
			sprintf( 'edit.php?post_type=%1$s', $this->name ),
			__( 'Настройки', 'pstu-enrollee' ),
			__( 'Настройки', 'pstu-enrollee' ),
			'manage_options',
			$this->slug,
			function () {
				echo "<div class=\"wrap\">\r\n";
				echo "  <h1>" . get_admin_page_title() . "</h1>\r\n";
				// настройки ОП
				echo "  <form method=\"post\" enctype=\"multipart/form-data\" action=\"options.php\">\r\n";
				settings_fields( $this->slug );
				do_settings_sections( $this->slug );
				submit_button( __( 'Сохранить', 'pstu-enrollee' ) );
				echo "  </form>\r\n";
			}
		);
	}


	/**
	*	Регистрация настроек
	*/
	public function settings() {
		register_setting( $this->slug, $this->slug, array( $this, 'settins_validate' ) );
		add_settings_section( $this->slug, __( 'Образовательные программы', 'pstu-enrollee' ), '', $this->slug );
		add_settings_field(
			sprintf( '%1$s[page_id]', $this->slug ),
			__( 'Выбор страницы для вывода списка специализаций', 'pstu-enrollee' ),
			array( $this, 'get_control' ),
			$this->slug,
			$this->slug,
			array(
				'type'        => 'select',
				'id'          => 'page_id',
				'desc'        => '',
				'vals'        => $this->get_pages(),
				'label_for'   => 'page_id',
			)
		);
	}


	/**
	*	Валидация настроек
	*/
	public function settins_validate( $options ) {
		return $options;
	}


}
