<?php


if ( ! defined( 'ABSPATH' ) ) {	exit; };


if ( class_exists( 'pstuFieldTaxonomy' ) ) return;


/**
	*	Заготовка для класа мета-поля
	*/

class pstuFieldTaxonomy {


	use pstuEnrolleeTrait; 


	protected $type;


	protected $name;


	protected $value;


	protected $label;


	protected $description;


	protected $placeholder;


	protected $mask;


	protected $required;


	protected $column;


	protected $attr = array();


	protected $sortable;


	protected $readonly;


	protected $disabled;


	protected $choises;


	private $default = array(
		'type'					=> 'text',
		'name'					=> 'text',
		'value'					=> '',
		'label'					=> 'Text',
		'description'		=> '',
		'placeholder'		=> '',
		'mask'					=> '',
		'required'			=> false,
		'column'				=> false,
		'attr'					=> array(),
		'sortable'			=> false,
		'readonly'			=> false,
		'disabled'			=> false,
		'choises'				=> array(),
	);


	protected $properties = array( 'type', 'name', 'value', 'label', 'description', 'placeholder', 'mask', 'required', 'column', 'attr', 'sortable', 'readonly', 'disabled', 'choises' );


	// protected $types = array( 'text', 'email', 'url', 'image', 'textarea', 'checkbox', 'radio', 'file', 'number', 'date', 'select', 'gallery', 'orderby' );
	protected $types = array( 'text', 'email', 'url', 'image', 'textarea', 'checkbox', 'select' );	


	/**
		*
		*/
	function __construct( $args ) {
		foreach ( $this->default as $name => $default ) {
			if ( isset( $args[ $name ] ) ) {
				$this->set( $name, $args[ $name ] );
			} else {
				$this->set( $name, $default );
			}
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'register_enqueue' ), 10, 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 10, 0 );
	}


	/**
		*
		*/
	public function register_enqueue() {
		wp_register_script(
			'enrollee-control-image',
			PSTU_ENROLLEE_URL . 'assets/scripts/control-image.js',
			array( 'jquery' ),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/control-image.js' ),
			true
		);
		wp_register_script(
			'maskedinput',
			PSTU_ENROLLEE_URL . 'assets/scripts/jquery.maskedinput.js',
			array( 'jquery' ),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/scripts/jquery.maskedinput.js' ),
			true
		);
		wp_register_style(
			'enrollee-control-image',
			PSTU_ENROLLEE_URL . 'assets/css/control-image.css',
			array(),
			filemtime( PSTU_ENROLLEE_DIR . 'assets/css/control-image.css' ),
			'all'
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
		*
		*/
	public function admin_enqueue() {
		switch ( $this->type ) {
			case 'image':
				wp_enqueue_media();
				wp_enqueue_script( 'enrollee-control-image' );
				wp_enqueue_style( 'enrollee-control-image' );
				break;
			default:
				break;
		}
	}


	/**
		*	
		*/
	public function set( $name, $value ) {
		$result = false;
		if ( in_array( $name, $this->properties ) ) {
			switch ( $name ) {
				case 'type':
					$result = ( in_array( $value, $this->types ) ) ? esc_attr( $value ) : $this->default[ $name ];
					break;
				case 'name':
				case 'value':
				case 'placeholder':
				case 'mask':
					$result = esc_attr( $value );
					break;
				case 'required':
				case 'column':
				case 'sortable':
				case 'readonly':
				case 'disabled':
					$result = boolval( $value );
					break;
				case 'attr':
					$result = ( is_array( $name ) ) ? $name : $this->default;
					foreach ( $value as $a => $v ) {
						$a = esc_attr( $a );
						$v = esc_attr( $v );
					}
					break;
				default:
					$result = $value;
					break;
			}
			$this->$name = $result;
		}
		return $result;
	}


	/**
		*
		*/
	public function get( $name ) {
		return ( in_array( $name, $this->properties ) ) ? $this->$name : false;
	}


	/**
		*
		*/
	protected function get_attr() {
		$result = array();
		foreach ( $this->attr as $key => $value ) {
			$result[] = sprintf(
				'%1$s="%2$s"',
				$key,
				$value
			);
		}
		return implode( ' ', $result );
	}


	/**
		*
		*/
	public function get_label() {
		$result = false;
		switch ( $this->type ) {
			case 'image':
				$result = sprintf(
					'<label data-image-role="add" data-image-input="#%1$s" for="%1$s">%2$s</label>',
					$this->name,
					$this->label
				);
				break;
			default:
				$result = sprintf(
					'<label for="%1$s">%2$s</label>',
					$this->name,
					$this->label
				);
				break;
		}
		return $result;
	}


	/**
		*
		*/
	public function get_control() {
		$result = array();
		switch ( $this->type ) {
			case 'image':
				$result[] = sprintf(
					'<div class="clearfix">
					<img id="%1$s-thumbnail" src="%2$s" data-image-role="add" data-image-input="#%1$s" class="enrollee-control-image-thumbnail" data-image-empty="%5$s">
					<button class="button" role="button" data-image-role="add" data-image-input="#%1$s">%3$s</button>
					<button class="button" role="button" data-image-role="remove" data-image-input="#%1$s">%4$s</button>
					<input id="%1$s" type="hidden" name="%1$s" value="%6$s" data-image-thumbnail="#%1$s-thumbnail">
					</div>',
					$this->name,
					( empty( $this->value ) ) ? PSTU_ENROLLEE_URL . 'assets/images/add-image.png' : wp_get_attachment_image_url( $this->value, 'thumbnail', false ),
					__( 'Добавить', 'pstu-enrollee' ),
					__( 'Удалить', 'pstu-enrollee' ),
					PSTU_ENROLLEE_URL . 'assets/images/add-image.png',
					$this->value
				);
				break;
			case 'checkbox':
				$result[] = sprintf(
					'<input type="%1$s" id="%2$s" value="%3$s" name="%2$s" %4$s %5$s %6$s> %7$s',
					$this->type,
					$this->name,
					'on',
					readonly( $this->readonly, true, false ),
					disabled( $this->disabled, true, false ),
					checked( $this->default, true, false ),
					( empty( $this->description ) ) ? '' : '<p>' . $this->description . '</p>'
				);
				break;
			case 'email':
			case 'url':
			case 'text':
				$mask = '';
				if ( ! empty( $this->mask ) ) {
					wp_enqueue_script( "maskedinput" );
					wp_add_inline_script( "maskedinput", "jQuery( '#{$this->name}' ).mask( '{$this->mask}' );", "after" );
				}
				$result[] = sprintf(
					'<input type="%1$s" id="%2$s" value="%3$s" placeholder="%4$s" name="%2$s" %5$s %6$s>%7$s',
					$this->type,
					$this->name,
					$this->value,
					$this->placeholder,
					readonly( $this->readonly, true, false ),
					disabled( $this->disabled, true, false ),
					( empty( trim( $this->description ) ) ) ? '' : '<p>' . $this->description . '</p>'
				);
				break;
			case 'textarea':
				$result[] = sprintf(
					'<textarea id="%1$s" name="%1$s" placeholder="%2$s" %4$s %5$s>%3$s</textarea>%6$s',
					$this->name,
					$this->placeholder,
					$this->value,
					readonly( $this->readonly, true, false ),
					disabled( $this->disabled, true, false ),
					( empty( trim( $this->description ) ) ) ? '' : '<p>' . $this->description . '</p>'
				);
			case 'select':
				if ( empty( $this->choises ) ) {
					$result[] = '-';
				} else {
					wp_enqueue_script( 'select2' );
					wp_add_inline_script( "select2", "jQuery( '#{$this->name}' ).select2();", "after" );
					wp_enqueue_style( 'select2' );
					$result[] = sprintf(
						'<select id="%1$s" class="widefat" name="%1$s" %2$s %3$s><option value="">%4$s</option>',
						$this->name,
						readonly( $this->readonly, true, false ),
						disabled( $this->disabled, true, false ),
						__( 'Не выбрано', 'pstu-enrollee' )
					);
					foreach ( $this->choises as $key => $value ) {
						$result[] = sprintf(
							'<option value="%1$s" %2$s>%3$s</option>',
							$key,
							selected( $key, $this->value, false ),
							$value
						);
					}
					$result[] = '</select>';
					if ( ! empty( trim( $this->description ) ) ) $result[] = '<p>' . $this->description . '</p>';
				}
				break;
			default:
				break;
		}
		return implode( "\r\n", $result );
	}


	/**
		*
		*/
	public function validate( $value ) {
		$result = '';
		switch ( $name ) {
			case 'image':
				$result = intval( $value );
				break;
			case 'url':
				$value = esc_url_raw( $value, array( 'http', 'https' ) );
				$result = ( $this->is_url() ) ? $value : false;
				break;
			case 'email':
				$value = sanitize_email( $value );
				$result = ( is_email( $email ) ) ? $email : false;
				break;
			case 'textarea':
				$result = sanitize_textarea_field( $value );
			case 'text':
			default:
				$result = sanitize_text_field( $value );
				break;
		}
		return $result;
	}

}




?>