<?php
/*
Plugin Name: ACF Flexible Columns
Description: Replace the regular single editor with responsive multiple column editors, use actions - 'flexible_columns_pre', 'flexible_columns_post', flexible_columns_row_prepend, flexible_columns_row_append and filter 'flexible_layout', 'flexible_columns_row_class'
Version:     1.1.7
Author:      imageDESIGN
Author URI:  http://imagedesign.pro
Text Domain: acf-flexible-columns
*/
include_once( ABSPATH . 'wp-includes/pluggable.php' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
class IDP_ACF_FlexCols{
    private $options;
    
	public function __construct(){
		    add_action('plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'plugin_action_links'));
        if(  is_plugin_active( 'acf-flexible-columns-pro/acf-flexible-columns-pro.php' ) ):
            //ALERT and DEACTIVATE - OLD Pro version is still active
            add_action( 'admin_init', array($this, 'plugin_deactivate2') );
            add_action( 'admin_notices', array( $this, 'plugin_admin_notice2') );
        elseif(  is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ):
            //Migrate Content on Activation
            /*if ( isset($_GET['acffc_nonce']) && wp_verify_nonce($_GET['acffc_nonce'], 'migrate_content')){
                add_action( 'init', array( $this, 'migrate_thecontent'));
                add_action( 'admin_notices', array($this, 'admin_notice') );
            } */
            //Options Page
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
			//Remove Regular Content Editor
			add_action('admin_head', array($this, 'noeditor') );
            //Check if Synced and Set Option defaults
            if( get_option('acf_flexcol') == 'synced' && !$this->SyncCheck() ) 
                delete_option('acf_flexcol');
			if( get_option('acf_flexcol') != 'synced' ) 
                add_filter('acf/settings/load_json', array($this, 'acf_json_load_point') );
            if( get_option('acf_flexcol') != 'synced' && $this->SyncCheck() ) {
                update_option('acf_flexcol', 'synced'); 
                update_option('acf_flexcol_opts', array( 'loadboots' => 1, 'hideposteditor' => 1, 'hidepageeditor' => 1 )); 
            }
            //Override the_content() to display columns
			add_filter('the_content', array($this, 'new_the_content') );
            //Bootstrap Grid CSS
			add_action('wp_enqueue_scripts', array($this, 'styles_and_scripts') );
            //LI Column CSS
            add_action('wp_head', array($this, 'li_columns') );
            //LI Column Editor Styles
            add_action( 'admin_init', array($this, 'add_editor_style') );
            //LI Column Editor Scripts
            add_action( 'after_wp_tiny_mce', array($this, 'add_editor_scripts') );
        
            //Carousel Image Sizes
            add_image_size( 'Mobile', 768, 1024 );
            add_image_size( 'Tablet', 992, 1200 );
            add_image_size( 'Desktop', 1280, 1080 );
            add_image_size( 'Widescreen', 2000, 1280 );
		else:
			//ALERT and DEACTIVATE - no ACF PRO	
			add_action( 'admin_init', array($this, 'plugin_deactivate') );
			add_action( 'admin_notices', array( $this, 'plugin_admin_notice') );
		endif;
	}
	
  function plugin_action_links( $links ){
    $dl = plugin_dir_url( __FILE__  ).'acf-json/acf-flexible-columns.json';
    $links = array_merge( array( '<a href="'.$dl.'" target="_blank">JSON Import File</a>'), $links);
    return $links;
  }
  
	function plugin_deactivate(){
		deactivate_plugins( plugin_basename( __FILE__ ) );	
	}
	
	function plugin_admin_notice(){
        $url = 'https://www.advancedcustomfields.com/pro/';
		echo '<div class="updated"><p><strong>'.__('ACF Flexible Columns', 'acf-flexible-columns').'</strong> '.sprintf( wp_kses( __('requires the <a href="%s" target="_blank">Advanced Custom Fields Pro</a> plugin in order to function; the plug-in has been <strong>deactivated</strong>', 'acf-flexible-columns'), array( 'a' => array( 'href' => array() ) ) ), esc_url( $url ) ).'</p></div>';
               if ( isset( $_GET['activate'] ) )
                    unset( $_GET['activate'] );
	}
    function plugin_deactivate2(){
        $oldplugin = 'acf-flexible-columns-pro/acf-flexible-columns-pro.php';
		deactivate_plugins( $oldplugin );	
	}
    function plugin_admin_notice2(){
		echo '<div class="updated"><p><strong>'.__('ACF Flexible Columns', 'acf-flexible-columns').'</strong> '. __(' is now activated.  ACF Flexible Columns PRO has been deactivated to prevent conflicts.', 'acf-flexible-columns').'</p></div>';
               if ( isset( $_GET['activate'] ) )
                    unset( $_GET['activate'] );
	}
    
    function migrate_thecontent(){
        global $wpdb;
         if (isset($_GET['acffc_nonce']) && wp_verify_nonce($_GET['acffc_nonce'], 'migrate_content')):
        $args = array(
            'post_type' => array( 'post', 'page')
        );
        $query = new WP_Query( $args );
        if( $query->have_posts() ):
            while( $query->have_posts() ):
                $query->the_post();
                $oldContent = get_the_content();
                $field_key = "field_574881bf81a25";
                update_post_meta($query->post->ID, '_rows', $field_key );
                update_post_meta($query->post->ID, 'rows', array ('full_width_row') );
                update_post_meta($query->post->ID, '_rows_0_column', 'field_574881bf9302c' );
                update_post_meta($query->post->ID, 'rows_0_column', array('content') );
                update_post_meta($query->post->ID, '_rows_0_column_0_list_layout', 'field_574881bf9dd99' );
                update_post_meta($query->post->ID, 'rows_0_column_0_list_layout', 1 );
                update_post_meta($query->post->ID, '_rows_0_column_0_content', 'field_574881bf9dd23' );
                update_post_meta($query->post->ID, 'rows_0_column_0_content', $oldContent );
            endwhile;
        endif;
       wp_reset_postdata();
        endif;
    }
    
    function add_plugin_page(){
        add_options_page(
            //'edit.php?post_type=acf-field-group',
            'ACF Flexible Columns', 
            'Flexible Columns', 
            'manage_options', 
            'acf-flexible-columns', 
            array( $this, 'create_admin_page' )
        );
    }
    
    function create_admin_page(){
        $this->options = get_option( 'acf_flexcol_opts' );
        ?>
        <div class="wrap">
            <h2>ACF Flexible Columns Settings</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'acf_flexcol_options' );   
                do_settings_sections( 'acf-fc-setting-admin' );
                submit_button(); 
            ?>
            </form>
            <?php include_once('layout-function-samples.txt'); ?>
        </div>
        <?php
    }
    
    function page_init(){
        register_setting(
            'acf_flexcol_options', // Option group
            'acf_flexcol_opts', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
        
        add_settings_section(
            'acffxin', // ID
            __('Content Migration', 'acf-flexible-columns'), // Title
            array( $this, 'print_section_info' ), // Callback
            'acf-fc-setting-admin' // Page
        ); 
        
        add_settings_field(
            'migrate', // ID
            __('Migrate Content', 'acf-flexible-columns'), // Title 
            array( $this, 'migrate_button' ), // Callback
            'acf-fc-setting-admin', // Page
            'acffxin' // Section           
        ); 
       
        add_settings_section(
            'acffxopts', // ID
            __('Default Overrides', 'acf-flexible-columns'), // Title
            '',//array( $this, 'print_section_info' ), // Callback
            'acf-fc-setting-admin' // Page
        );  

        add_settings_field(
            'bootstrap', // ID
            __('Load Bootstrap Grid CSS', 'acf-flexible-columns'), // Title 
            array( $this, 'load_bootstrap' ), // Callback
            'acf-fc-setting-admin', // Page
            'acffxopts' // Section           
        ); 
        
        add_settings_field(
            'aos', // ID
            __('Load AOS Library', 'acf-flexible-columns'), // Title 
            array( $this, 'load_aos' ), // Callback
            'acf-fc-setting-admin', // Page
            'acffxopts' // Section           
        ); 
        
        add_settings_field(
            'slick', // ID
            __('Load Slick Library', 'acf-flexible-columns'), // Title 
            array( $this, 'load_slick' ), // Callback
            'acf-fc-setting-admin', // Page
            'acffxopts' // Section           
        ); 

        add_settings_field(
            'hideposteditor', 
            __('Hide Regular Post Content Editor', 'acf-flexible-columns'), 
            array( $this, 'hide_post_editor' ), 
            'acf-fc-setting-admin', 
            'acffxopts'
        );  
        
         add_settings_field(
            'hidepageeditor', 
            __('Hide Regular Page Content Editor', 'acf-flexible-columns'), 
            array( $this, 'hide_page_editor' ), 
            'acf-fc-setting-admin', 
            'acffxopts'
        );  
		
		add_settings_field(
            'enablecontainers', 
            __('Enable Row Containers', 'acf-flexible-columns'), 
            array( $this, 'enable_containers' ), 
            'acf-fc-setting-admin', 
            'acffxopts'
        );  
		
		add_settings_field(
            'disablelicolstyles', 
            __('Disable List Column Styles', 'acf-flexible-columns'), 
            array( $this, 'disable_licol_styles' ), 
            'acf-fc-setting-admin', 
            'acffxopts'
        );  
    }
    
    function print_section_info(){
        
    }
    function admin_notice(){

            ?>
            <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Previous page content has been successfully migrated to the Flexible Column system!', 'acf-flixible-columns' ); ?></p>
    </div><?php

        
    }
    function sanitize( $input ){
        $new_input = array();
        if( isset( $input['bootstrap'] ) )
            $new_input['bootstrap'] = absint( $input['bootstrap'] );
        
        if( isset( $input['aos'] ) )
            $new_input['aos'] = absint( $input['aos'] );
        
        if( isset( $input['slick'] ) )
            $new_input['slick'] = absint( $input['slick'] );

        if( isset( $input['hideposteditor'] ) )
            $new_input['hideposteditor'] = sanitize_text_field( $input['hideposteditor'] );
        
        if( isset( $input['hidepageeditor'] ) )
            $new_input['hidepageeditor'] = sanitize_text_field( $input['hidepageeditor'] );
		
		if( isset( $input['enablecontainers'] ) )
            $new_input['enablecontainers'] = sanitize_text_field( $input['enablecontainers'] );
		
		if( isset( $input['disablelicolstyles'] ) )
            $new_input['disablelicolstyles'] = sanitize_text_field( $input['disablelicolstyles'] );

        return $new_input;
    }
    
    function migrate_button(){
        echo '<p><a href="'.wp_nonce_url(admin_url('options-general.php?page=acf-flexible-columns'), 'migrate_content', 'acffc_nonce').'" class="button button-secondary">Migrate to Column System</a><br><small>This will copy over your previous page content into the new column content system</small></p>';
    }
    
    function load_bootstrap(){
        printf(
            '<label><input type="checkbox" id="bootstrap" name="acf_flexcol_opts[bootstrap]" value="1" %s /> Bootstrap Grids</label>',
            isset( $this->options['bootstrap'] ) ? esc_attr( 'checked="checked"') : ''
        );
        echo '<p><small>'.__('Columns rely on Bootstrap 3 grid styles to function, if you already have Bootstrap 3 styles loaded in your template you can safely disable loading Bootstrap. (Note this CSS file only contains the basic grid functionality from Bootstrap, not the entire library)', 'acf-flexible-columns').'</small><p>';
    }
    function load_aos(){
        printf(
            '<label><input type="checkbox" id="aos" name="acf_flexcol_opts[aos]" value="1" %s /> AOS (Animated On Scroll) Library CSS & JS</label>',
            isset( $this->options['aos'] ) ? esc_attr( 'checked="checked"') : ''
        );
        echo '<p><small>'.__('Animations on each column block rely on the AOS Library to function.  If you are already including this library in your theme you may safely disable loading these files.', 'acf-flexible-columns').'</small><p>';
    }
    function load_slick(){
        printf(
            '<label><input type="checkbox" id="aos" name="acf_flexcol_opts[slick]" value="1" %s /> Slick Library CSS & JS</label>',
            isset( $this->options['slick'] ) ? esc_attr( 'checked="checked"') : ''
        );
        echo '<p><small>'.__('The photo carousel relies on the Slick Library to function.  If you are already including this library in your theme you may safely disable loading these files.', 'acf-flexible-columns').'</small><p>';
    }
    
    function hide_post_editor(){
        printf(
            '<input type="checkbox" id="hideposteditor" name="acf_flexcol_opts[hideposteditor]" value="1" %s />',
            isset( $this->options['hideposteditor'] ) ? esc_attr( 'checked="checked"') : ''
        );
         echo '<p><small>'.__('The regular Post editor is replaced by the column editor, if you need it back for any reason, uncheck this box', 'acf-flexible-columns').'</small><p>';
    }
    
    function hide_page_editor(){
        printf(
            '<input type="checkbox" id="hidepageeditor" name="acf_flexcol_opts[hidepageeditor]" value="1" %s />',
            isset( $this->options['hidepageeditor'] ) ? esc_attr( 'checked="checked"') : ''
        );
        echo '<p><small>'.__('The regular Page editor is replaced by the column editor, if you need it back for any reason, uncheck this box', 'acf-flexible-columns').'</small><p>';
    }
	
	function enable_containers(){
        printf(
            '<input type="checkbox" id="enablecontainers" name="acf_flexcol_opts[enablecontainers]" value="1" %s />',
            isset( $this->options['enablecontainers'] ) ? esc_attr( 'checked="checked"') : ''
        );
        echo '<p><small>'.__('You can now add .container & .container-fluid around each row, if upgrading, add a \'container\' field above the first Column in each main Layout where 1 = .container and 0 = .container-fluid', 'acf-flexible-columns').'</small><p>';
    }
	function disable_licol_styles(){
        printf(
            '<input type="checkbox" id="disablelicolstyles" name="acf_flexcol_opts[disablelicolstyles]" value="1" %s />',
            isset( $this->options['disablelicolstyles'] ) ? esc_attr( 'checked="checked"') : ''
        );
        echo '<p><small>'.__('Remove the embeded styles added to the page for the list item columns:', 'acf-flexible-columns').'</small><p><code>.li-col-2{-webkit-column-count:2;-moz-column-count:2;column-count:2;}.li-col-3{-webkit-column-count:3;-moz-column-count:3;column-count:3;}.li-col-4{-webkit-column-count:4;-moz-column-count:4;column-count:4;}.li-col-5{-webkit-column-count:5;-moz-column-count:5;column-count:5;}.li-col-6{-webkit-column-count:6;-moz-column-count:6;column-count:6;}</code>';
    }
    
    function SyncCheck(){
        global $wpdb;
        $args = array(
            'name'          =>  'group_574881bf6b7db',
            'post_type'     =>  'acf-field-group',
            'post_status'   =>  'publish',
            'numberposts'   =>  1  
        );
        $synced = get_posts($args);
        if( $synced ) return true;
        else return false;
    }
	
	function noeditor(){
        $opts = get_option('acf_flexcol_opts');
		if( isset($opts['hideposteditor']) && $opts['hideposteditor'] == true) remove_post_type_support('post', 'editor');	
        if( isset($opts['hidepageeditor']) && $opts['hidepageeditor'] == true ) remove_post_type_support('page', 'editor');	
	}
	
	function acf_json_load_point( $paths ){
		$paths[] = 	plugin_dir_path(__FILE__).'acf-json/';
		return $paths;
	}
	
	function styles_and_scripts(){
		//CSS Styles
		$opts = get_option('acf_flexcol_opts');
        if( $opts['bootstrap'] ) wp_enqueue_style( 'BootstrapGrids', plugins_url('css/bootstrap.grids.min.css', __FILE__), 'all' );
        if( $opts['aos'] ) wp_enqueue_style( 'AOS', plugins_url('css/aos.min.css', __FILE__), 'all' );
        if( $opts['slick'] ) wp_enqueue_style( 'Slick', plugins_url('css/slick.css', __FILE__), 'all' );
        if( $opts['slick'] ) wp_enqueue_style( 'Slick-Theme', plugins_url('css/slick-theme.css', __FILE__), 'all' );
		//JS Scripts
        if( $opts['aos'] ) wp_enqueue_script( 'AOS', plugins_url('js/aos.min.js', __FILE__), array('jquery') );
        if( $opts['slick'] ) wp_enqueue_script( 'Slick', plugins_url('js/slick.min.js', __FILE__), array('jquery') );
	}
    
    function li_columns(){
        $opts = get_option('acf_flexcol_opts');
        if( !$opts['bootstrap'] && !$opts['disablelicolstyles'] ){
            ?>
<style type="text/css">
.li-col-2{-webkit-column-count:2;-moz-column-count:2;column-count:2;}.li-col-3{-webkit-column-count:3;-moz-column-count:3;column-count:3;}.li-col-4{-webkit-column-count:4;-moz-column-count:4;column-count:4;}.li-col-5{-webkit-column-count:5;-moz-column-count:5;column-count:5;}.li-col-6{-webkit-column-count:6;-moz-column-count:6;column-count:6;}     
</style>
            <?php
        }
    }
	
    function add_editor_scripts(){
        printf( '<script type="text/javascript" src="%s"></script>',  plugins_url('/js/tinymce-licols.js', __FILE__) );
    }
    
    function add_editor_style(){
        add_editor_style(plugins_url('css/editor-li-style.css', __FILE__) );
    }
    
	function new_the_content($content){
		global $post;
		$opts = get_option('acf_flexcol_opts');
		$content = '';
		if( have_rows('rows') ):
		while( have_rows('rows') ): 
			the_row();
			$content .= apply_filters( 'flexible_columns_wrap_outer', false );
			if( isset($opts['enablecontainers']) && $opts['enablecontainers'] == true):
		  	  $container = get_sub_field('container');
		
			  $nocontainer = get_sub_field('full_width_row'); 
				if( $nocontainer ){ $container = false; }
				if( $container == 1 ) $content .= '<div class="container">';	else $content .= '<div class="container-fluid">';
			endif;
			$content .= apply_filters( 'flexible_columns_wrap_inner', false );
            $rowclass = array('row');
            $rowclass = apply_filters('flexible_columns_row_class', $rowclass);
            $rowclasses = implode(' ', $rowclass);
			
            if( get_row_layout() == 'full_width_row' ):
                
                $content .= apply_filters( 'flexible_column_pre', false );
                //SINGLE COLUMN
				if( have_rows('column') ):
					 while( have_rows('column') ): the_row();
											
						$content .= '<div class="'.$rowclasses.'">'; 
                        $content .= apply_filters( 'flexible_columns_row_prepend', false );
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
                        $content .= apply_filters( 'flexible_columns_row_append', false );
						$content .=  '</div>';
						
					
					endwhile; 
				endif; 
				$content .= apply_filters( 'flexible_column_post', false );
				
			elseif( get_row_layout() == '2_column_row' ):
				
                $content .= apply_filters( 'flexible_column_pre', false );
                //TWO COLUMNS - COL #1
				if( have_rows('column_1') ):
					 while( have_rows('column_1') ): the_row();
						$content .=  '<div class="'.$rowclasses.'">';
                        $content .= apply_filters( 'flexible_columns_row_prepend', false );
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
					endwhile; 
				endif;
				//TWO COLUMNS - COL #2
				if( have_rows('column_2') ):
					 while( have_rows('column_2') ): the_row();
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
                       $content .=  apply_filters( 'flexible_columns_row_append', false );
						$content .=  '</div>';
					endwhile;	
				endif;
				$content .= apply_filters( 'flexible_column_post', false );
				
			elseif( get_row_layout() == '3_column_row' ):
				
                $content .= apply_filters( 'flexible_column_pre', false );		
                //THREE COLUMNS - COL #1
				if( have_rows('column_1') ):
					 while( have_rows('column_1') ): the_row();
				        $content .= '<div class="'.$rowclasses.'">';
                       $content .=  apply_filters( 'flexible_columns_row_prepend', false );
						if( get_row_layout() )$content .= $this->flexible_layout(get_row_layout());
					endwhile; 
				endif;
				//THREE COLUMNS - COL #2	
				if( have_rows('column_2') ):
					 while( have_rows('column_2') ): the_row();
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
					endwhile;
				endif;
				//THREE COLUMNS - COL #3	
				if( have_rows('column_3') ):
					 while( have_rows('column_3') ): the_row();
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
                        $content .= apply_filters( 'flexible_columns_row_append', false );
						$content .= '</div>';
					endwhile;
				endif;
               $content .= apply_filters( 'flexible_column_post', false );
				
			elseif( get_row_layout() == '4_column_row' ):
				
                $content .= apply_filters( 'flexible_column_pre', false );
				//FOUR COLUMNS - COL #1
				if( have_rows('column_1') ):
					 while( have_rows('column_1') ): the_row();
				        $content .= '<div class="'.$rowclasses.'">';
                       $content .=  apply_filters( 'flexible_columns_row_prepend', false );
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
					endwhile; 
				endif;
				//FOUR COLUMNS - COL #2	
				if( have_rows('column_2') ):
					 while( have_rows('column_2') ): the_row();
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
					endwhile;
				endif;
				//FOUR COLUMNS - COL #3	
				if( have_rows('column_3') ):
					 while( have_rows('column_3') ): the_row();
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
					endwhile;
				endif;
				//FOUR COLUMNS - COL #4	
				if( have_rows('column_4') ):
					 while( have_rows('column_4') ): the_row();
						if( get_row_layout() ) $content .= $this->flexible_layout(get_row_layout());
                        $content .= apply_filters( 'flexible_columns_row_append', false );
						$content .= '</div>';
					endwhile;
				endif;
				$content .= apply_filters( 'flexible_columns_post', false );	
			
			endif;	
			$content .= apply_filters( 'flexible_columns_wrap_inner_end', false );
			if( isset($opts['enablecontainers']) && $opts['enablecontainers'] == true) $content .= '</div>'; //End .container/container-fluid
			$content .= apply_filters( 'flexible_columns_wrap_outer_end', false );
		endwhile; //end rows
		endif;
		
		if ( !post_password_required() ) {
			return apply_filters( 'flexible_columns_content', $content );
		}else{
			return get_the_password_form();	
		}
	}
	
	function flexible_layout($type){

        switch($type):	
	       #CONTENT
			case 'content':
				//$image  = get_sub_field('image');
				$content = get_sub_field('content');
				$md = ''; $sm = ''; $xs = '';
				$width = get_sub_field('width');
				if( !$width ):
					$col = 'col-sm-12';
				else:
					$mdw = $width[0]['desktop'];
					$smw = $width[0]['tablet'];
					$xsw = $width[0]['mobile'];
					$xs = ' col-xs-'.$xsw;
					if( $smw != $xsw ) $sm = ' col-sm-'.$smw;
					if( (!$sm && $mdw != $xsw) || ($sm && $mdw != $smw) ) $md = ' col-md-'.$mdw;
					$col = ltrim($md.$sm.$xs);
				endif;
				
                $animate = get_sub_field('animate');
       // print_r($animate);
                $atype = strtolower($animate[0]['type']);
                $animate_data = false;
                if( $atype ):
                    $dir = strtolower($animate[0][$atype.'_direction']);
                    $dur = $animate[0]['duration']*1000;
                    $delay = $animate[0]['delay']*1000;
                    $animate_data = ' data-aos="'.$atype.'-'.$dir.'" data-aos-duration="'.$dur.'" data-aos-delay="'.$delay.'" ';
                endif;
				
				$licols = get_sub_field('list_layout');
				$listyle = false;
				if( $licols != 1){ 
					$listyle = 'li-col-'.$licols;
				}
				//if( $listyle ) $content = str_replace($lifind, $lirep, $content);
        if( $listyle ) 
          $content = preg_replace('/<([uo]l)(\\sclass="([a-z0-9.])*'.$listyle.'([a-z0-9.])*")*>/m', '<$1 class="$3'.$listyle.'$4">', $content);
			
				//if ( $image ) $columndata .= '<div class="'.$col.'"'.$animate_data.'><div class="full-width-photo" style="background-image:url('.$image['sizes']['Desktop'].')"></div>' . do_shortcode($content) .'</div>';
				//else 
		
				
				$colclasses = explode(" ", $col);
				$colclasses = apply_filters('flexible_columns_col_class', $colclasses, get_row(true));
            	$colclasses = implode(' ', $colclasses);

				$columndata .= '<div class="'.$colclasses.'"'.$animate_data.'>' . do_shortcode($content) .'</div>';
				
			break;
        case 'image_carousel':
                $carousel = get_sub_field('carousel');
               
                $settings = get_sub_field('carousel_settings');
                
                $autoplay = $settings[0]['autoplay']*1000;
                $arrows = $settings[0]['show_arrows'];
                $dots = $settings[0]['show_dots'];
                $fade = $settings[0]['enable_fade'];
                $ratio = $settings[0]['aspect_ratio'];
                $data = array();
                if( $autoplay ) $data[] = '"autoplay" : true, "autoplaySpeed" : '.$autoplay;
                if( $arrows ) $data[] = '"arrows" : true'; else $data[] = '"arrows" : false';
                if( $dots ) $data[] = '"dots" : true'; else $data[] = '"dots" : false';
                if( $fade ) $data[] = '"fade" : true'; else $data[] = '"fade" : false';

                
                $data = implode(', ', $data);
        
                $columndata .= '<div class="carousel ratio_'.$ratio.'" data-slick=\'{'.$data.'}\'>';

                foreach( $carousel as $slide ):
                    $xsmimg = $slide['image']['sizes']['Mobile'];
                    $smimg = $slide['image']['sizes']['Tablet'];
                    $mdimg = $slide['image']['sizes']['Desktop'];
                    $wsimg = $slide['image']['sizes']['Widescreen'];
                    $focus = strtolower($slide['focus']);
                    $loader = plugins_url('assets/loader.gif', __FILE__);
                    $txtarea = false;
                    $textarea = $slide['text_area'][0];
                    if( count($textarea) > 0 ):
                        $text = $textarea['text'];
                        if( count($text) >= 1 ) :
                            $lines = array();
                            foreach( $text as $t ):
                                $lines[] = '<span data-txt-color="'.$t['color'].'" data-txt-size="'.$t['size'].'">'.$t['line'].'</span>';
                            endforeach;
                            $align = strtolower($textarea['text_align']);
                            $bg = $textarea['background'];
                            $opacity = $textarea['background_opacity'];
                            $loc = strtolower(str_replace(' ','_',$textarea['location']));

                            $width = $textarea['width'];

                            $txtarea = '<div class="text_area" data-txt-align="'.$align.'" data-width="'.$width.'" data-bg="'.$bg.'" data-opacity="'.$opacity.'" data-location="'.$loc.'">'.implode('<br>', $lines).'</div>';
                        endif;
                    endif;
                    
                    $columndata .= '<div class="slide">
                            <picture>
                                <source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-srcset="'.$wsimg.'" media="(min-width: 1200px)" />
								<source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-srcset="'.$mdimg.'" media="(min-width: 992px)" />
								<source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-srcset="'.$smimg.'" media="(min-width: 768px)" />
								<source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-srcset="'.$xsmimg.'" media="(min-width: 100px)" />
								<img class="'.$focus.'"	data-src="'.$mdimg.'" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="" />
                            </picture>
                            '.$txtarea.'
                        </div>';
                endforeach;    
                $columndata .= '</div>';
            break;
        default:
            $columndata .= apply_filters('flexible_layout', $type);
            
		endswitch;
		return $columndata;
	}

}

$idp_acf_flexcols = new IDP_ACF_FlexCols();