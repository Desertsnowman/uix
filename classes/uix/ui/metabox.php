<?php
/**
 * UIX Metaboxes
 *
 * @package   ui
 * @author    David Cramer
 * @license   GPL-2.0+
 * @link
 * @copyright 2016 David Cramer
 */
namespace uix\ui;

/**
 * Metabox class for adding metaboxes to post types in the post editor
 * @package uix\ui
 * @author  David Cramer
 */
class metabox extends panel {

    /**
     * The type of object
     *
     * @since 1.0.0
     * @access public
     * @var      string
     */
    public $type = 'metabox';

    /**
     * Holds the current post object
     *
     * @since 1.0.0
     * @access public
     * @var      WP_Post
     */
    public $post = null;

    /**
     * Status of the metabox to determin if assets should be loaded
     *
     * @since 1.0.0
     * @access public
     * @var      bool
     */
    public $is_active = false;


    /**
     * setup actions and hooks to add metaboxes and save metadata
     *
     * @since 1.0.0
     * @access protected
     */
    protected function actions() {

        // run parent to keep init and enqueuing assets
        parent::actions();
        // set screen activation
        add_action( 'current_screen', array( $this, 'set_active_status'), 25 );
        // add metaboxes
        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes'), 25 );        
        // save metabox
        add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );

    }

    /**
     * Setup submission data
     *
     * @since 1.0.0
     * @access public
     */
    public function setup(){
        // do parent
        parent::setup();
        if( !isset( $this->struct['screen'] ) ){
            $this->struct['screen'] = null;
        }
    }

    /**
     * set metabox styles
     *
     * @since 1.0.0
     * @see \uix\ui\uix
     * @access public
     */
    public function uix_styles() {
        parent::uix_styles();
        // Add metabox style
        $styles = array(
            'metabox'        =>  $this->url . 'assets/css/uix-metabox' . UIX_ASSET_DEBUG . '.css'
        );
        $this->styles( $styles );
    }


    /**
     * Enqueues specific tabs assets for the active pages
     *
     * @since 1.0.0
     * @access protected
     */
    protected function enqueue_active_assets(){
        ?><style type="text/css">
        #<?php echo $this->id(); ?>.uix-top-tabs > .uix-panel-tabs > li[aria-selected="true"] a,
        #side-sortables #<?php echo $this->id(); ?> > .uix-panel-tabs > li[aria-selected="true"] a{
        box-shadow: 0 3px 0 <?php echo $this->base_color(); ?> inset;
        }
        #<?php echo $this->id(); ?> > .uix-panel-tabs > li[aria-selected="true"] a {
        box-shadow: 3px 0 0 <?php echo $this->base_color(); ?> inset;
        }
        </style>
        <?php
    }

    /**
     * Checks the screen object to determin if the metabox should load assets
     *
     * @since 1.0.0
     * @access public
     * @uses "current_screen" hook
     * @param screen $screen The current screen object;
     */
    public function set_active_status( $screen ){

        if( $screen->base == 'post' && ( null === $this->struct['screen'] || in_array( $screen->id, ( array ) $this->struct['screen'] ) ) )
            $this->is_active = true;

    }
    /**
     * Add metaboxes to screen
     *
     * @since 1.0.0
     * @access public
     * @uses "add_meta_boxes" hook
     */
    public function add_metaboxes(){

        // metabox defaults
        $defaults = array(
            'screen' => null,
            'context' => 'advanced',
            'priority' => 'default',
        );
                    
        $metabox = array_merge( $defaults, $this->struct );

        add_meta_box(
            $this->slug,
            $metabox['name'],
            array( $this, 'create_metabox' ),
            $metabox['screen'],
            $metabox['context'],
            $metabox['priority']
        );

    }
    
    /**
     * Callback for the `add_meta_box` that sets the metabox data and renders it
     *
     * @since 1.0.0
     * @uses "add_meta_box" function
     * @access public
     * @param wp_post $post Current post for the metabox
     */
    public function create_metabox( $post ){

        $this->post = $post;    
        
        $data = get_post_meta( $post->ID );
        
        $this->set_data( $data );

        $this->render();

    }

    /**
     * Render the Metabox
     *
     * @since 1.0.0
     * @access public
     */
    public function render(){

        if( !empty( $this->struct['template'] ) ){
            
            $this->render_template();

        }else{
             // render fields setup
            parent::render();
        
        }
        ?>
        <script type="text/javascript">
            jQuery('#<?php echo $this->slug; ?>').addClass('uix-metabox');
        </script>        
        <?php
        
    }
    

    /**
     * Saves a metabox data
     *
     * @uses "save_post" hook
     * @since 1.0.0
     * @access public
     * @param int $post_id ID of the current post being saved
     * @param wp_post $post Current post being saved
     */
    public function save_meta( $post_id, $post ){
        $this->post = $post;
        if( ! $this->is_active() ){ return; }

        foreach( $this->child as $section ){
            $section_data = $section->get_data();
            if( null === $section_data ){ continue; }
            foreach( (array) $section_data as $meta_key=>$meta_value ){
                $this->save_meta_data( $meta_key, $meta_value );
            }
        }

    }

    /**
     * Save the meta data for the post
     *
     * @since 1.0.0
     * @access public
     * @param string $slug slug of the meta_key
     * @param mixed $data Data to be saved
     */
    public function save_meta_data( $slug, $data ){

        $prev = get_post_meta( $this->post->ID, $slug, true );

        if ( null === $data && $prev ){
            delete_post_meta( $this->post->ID, $slug );
        }elseif ( $data !== $prev ) {
            update_post_meta( $this->post->ID, $slug, $data );
        }    

    }

    /**
     * Determin which metaboxes are used for the current screen and set them active
     * @since 1.0.0
     * @todo Need to not use global $post in here.
     * @access public
     */
    public function is_active(){
        return $this->is_active;
    }

}