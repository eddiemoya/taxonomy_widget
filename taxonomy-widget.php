<?php
/*
Plugin Name: Taxonomy Widget
Description: A widget for displaying links by taxonomy
Version: 1.0
Author: Matthew Day
*/
class Taxonomy_Widget extends WP_Widget 
{
	public static $PLACEHOLDER_IMG = "http://local.nodomain.com/noimage.png";
	public static $TAX_PATH = "widgets/taxonomy_widget";
	
	var $widget_name = 'Taxonomy Widget';
	var $id_base = 'taxonomy_widget';
	
	public function __construct()
	{
		$widget_ops = array(
			'description' => "Taxonomy Widget",
			'classname' => "taxonomy-widget"
		);

		parent::WP_Widget($this->id_base, $this->widget_name, $widget_ops);
	}

	/**
	 * Self-registering widget method.
	 * 
	 * This can be called statically.
	 * 
	 * @author Eddie Moya
	 * @return void
	 */
	public static function register_widget() 
	{
		add_action('widgets_init', create_function('', 'register_widget("' . __CLASS__ . '");'));
	}
    
    /**
     * The front end of the widget. 
     * 
     * Do not call directly, this is called internally to render the widget.
     * 
     * @author Matthew Day
     * 
     * @param array $args       [Required] Automatically passed by WordPress - Settings defined when registering the sidebar of a theme
     * @param array $instance   [Required] Automatically passed by WordPress - Current saved data for the widget options.
     * @return void 
     */
    public function widget( $args, $instance )
	{
		$detail = (!empty($instance['tw_featured']) && $instance['tw_featured'] == 1) ? TRUE : FALSE;

		if($detail)
		{
			$this->taxDetail($args, $instance);
		}
		else
		{
			$this->taxList($args, $instance);
		}
    }

    function taxRender(&$data, $tp, $bw, $aw)
    {
    	extract($data);

		echo $bw;
    	include($tp);
    	echo $aw;
    }

    public function taxDetail(&$args, &$instance)
    {
    	extract($args);
        extract($instance);
        $data = array();

		$feat = (!empty($tw_featured)) ? $tw_featured : 0;
		$term = get_term($tw_category_1, $tw_taxonomy, "OBJECT");

		$args = array(
					'post_type'=> "attachment",
					'posts_per_page' => "1",
					'order'    => 'DESC',
					'category' => $term->term_id
				);
				
		$imgs = get_posts($args);

		$taxPath = "widgets/taxonomy_widget";			
		$template = locate_template(array("$taxPath/taxonomy-featured.php"));

		$data['list'] = FALSE;
		$data['dtai'] = TRUE;
		$data['info'] = $term;
		$data['imgs'] = $imgs;
		$data['link'] = get_term_link($term);

		$this->taxRender($data, $template, $before_widget, $after_widget);
    }

    public function taxList(&$args, &$instance)
    {
    	extract($args);
        extract($instance);

		$params = array('taxonomy' => $tw_taxonomy);
		$feat = (!empty($tw_featured)) ? $tw_featured : 0;

		if(!$tw_specify)
		{
			$g = get_term_by("slug", get_query_var("category_name"), $tw_taxonomy, "OBJECT");
			$params = array('child_of' => $g->term_id);
		}
	
		$cats = get_categories($params);
		$seld = array();
	
		if($tw_featured == "All")
		{			
			foreach($cats as $k)
			{
				$seld[] = $k->term_id;
			}
		}
		else
		{
			$mt = (!empty($tw_featured)) ? $tw_featured : 0;
			
			for($i = 1; $i <= $mt; $i++)
			{
				eval('$seld[] = $tw_category_' . $i . ';');
			}
		}

		$data = array();
		$disp = array();
		$drop = array();
		$sr = array();
		
		foreach($cats as $k)
		{
			if(in_array($k->term_id, $seld) || $tw_list_style == "list")
			{
				$args = array(
					'post_type'=> "attachment",
					'posts_per_page' => "1",
					'order'    => 'DESC',
					'category' => $k->term_id
				);
			
				$parent = ($k->parent != 0) ? get_term($k->parent, $tw_taxonomy) : NULL;
				$link = NULL;
			
				if(class_exists("WP_Node") && $tw_list_style == "list")
				{
					$node = new WP_Node($k->term_id, "skcategory");
					$catgroupid = $node->get_meta_data('catgroupid');
					
					$link = sprintf('http://www.sears.com/%s%s/cr-%s?sName=View+All', ((!empty($parent)) ? $parent->slug . "-" : ""), $k->slug, $catgroupid);
				}
				else
				{
					$link = get_category_link($k->term_id);
				}
				
				$imgs = get_posts($args);
				$disp[$k->name] = array('link' => $link, 'desc' => $k->category_description, 'img' => (!empty($imgs[0]->guid)) ? $imgs[0]->guid : self::$PLACEHOLDER_IMG);
			}
			
			$drop[$k->name] = array('link' => get_category_link($k->term_id), 'desc' => $k->category_description);
		}

		$dk = array_keys($disp);

		foreach($drop as $k => $d)
		{
			if(empty($k))
			{
				continue;
			}
			
			if(!in_array($k, $dk))
			{
				$sr[$k] = $d;
			}
		}
	
		$template = locate_template(array(self::$TAX_PATH . "/taxonomy-$tw_list_style.php", self::$TAX_PATH . "/taxonomy.php"));

		$data['list'] = TRUE;
		$data['dtai'] = FALSE;
		$data['disp'] = $disp;
		$data['drop'] = $drop;
		$data['desc'] = $tw_description;
		$data['dpdn'] = $tw_dropdown;
		$data['ftxt'] = (!empty($feat)) ? "More" : "Options";
		$data['nsts'] = $sr;

		$this->taxRender($data, $template, $before_widget, $after_widget);
    }
    
    /**
     * Data validation. 
     * 
     * Do not call directly, this is called internally to render the widget
     * 
     * @author [Widget Author Name]
     * 
     * @uses esc_attr() http://codex.wordpress.org/Function_Reference/esc_attr
     * 
     * @param array $new_instance   [Required] Automatically passed by WordPress
     * @param array $old_instance   [Required] Automatically passed by WordPress
     * @return array|bool Final result of newly input data. False if update is rejected.
     */
    public function update($new_instance, $old_instance)
	{
		// inherit the existing settings
		$instance = $old_instance;        

		foreach($new_instance as $key => $value)
		{
			$instance[$key] = $value;	
        }        
        
        foreach($instance as $key => $value)
		{
			if($value == 'on' && !isset($new_instance[$key]))
			{
				unset($instance[$key]);
			}
        }
        
		return $instance;
	}
    
	/**
	 * Generates the form for this widget, in the WordPress admin area.
	 * 
	 * The use of the helper functions form_field() and form_fields() is not
	 * neccessary, and may sometimes be inhibitive or restrictive.
	 * 
	 * @author Matthew Day
	 * 
	 * @uses wp_parse_args() http://codex.wordpress.org/Function_Reference/wp_parse_args
	 * @uses self::form_field()
	 * @uses self::form_fields()
	 * 
	 * @param array $instance [Required] Automatically passed by WordPress
	 * @return void 
	 */
	public function form($instance)
	{
        // Merge saved input values with default values
        $instance = wp_parse_args((array) $instance, $defaults);
		extract($instance);
		
		$tax = get_taxonomies(NULL, 'objects');
		$opts = array();
		
		foreach($tax as $t)
		{
			$opts[$t->name] = $t->name;
		}
				
		$fields = array(
			array(
				'field_id'		=> "tw_list_style",
				'type'			=> "select",
				'label'			=> "Dropdown",
				'options'		=> array(
					'list'			=> "List",
					'grid'			=> "Grid"
				)
			),
			array(
				'field_id'		=> "tw_dropdown",
				'type'			=> "checkbox",
				'label'			=> "Dropdown"
			),
			array(
				'field_id'		=> "tw_description",
				'type'			=> "checkbox",
				'label'			=> "Description"
			),
			array(
				'field_id'		=> "tw_specify",
				'type'			=> "checkbox",
				'label'			=> "Specify"
			),
			array(
				'field_id' => 'tw_taxonomy',
				'type' => 'select',
				'label' => 'Taxonomy',
				'options' => $opts
			)
		);
			
		// if an override is specified, display the override select boxes
		if($tw_specify)
		{
			$featured = (!empty($tw_featured)) ? $tw_featured : 1;
					
			if($tw_taxonomy)
			{
				$cats = get_categories(array('taxonomy' => $tw_taxonomy));
				$mt = ($tw_featured == "All") ? 0 : $tw_featured;
				
				$opts = array();				
				$fo = array();
				$fo['All'] = "All";
		
				for($i = 1; $i <= 9; $i++)
				{
					$fo[$i] = $i;
				}
				
				$fields[] = array(
					'field_id' => 'tw_featured',
					'type' => 'select',
					'label' => 'Featured',
					'options' => $fo
				);
				
				foreach($cats as $a)
				{
					$opts[$a->term_id] = $a->name;
				}
				
				for($i = 1; $i <= $mt; $i++)
				{
					$fields[] = array(
						'field_id' => 'tw_category_' . $i,
						'type' => 'select',
						'label' => 'Subcat ' . $i,
						'options' => $opts
					);
				}				
			}
		}

        $this->form_fields($fields, $instance);
	}
    

    /**
     * Helper function - does not need to be part of widgets, this is custom, but 
     * is helpful in generating multiple input fields for the admin form at once. 
     * 
     * This is a wrapper for the singular form_field() function.
     * 
     * @author Eddie Moya
     * 
     * @uses self::form_fields()
     * 
     * @param array $fields     [Required] Nested array of field settings
     * @param array $instance   [Required] Current instance of widget option values.
     * @return void
     */
    private function form_fields($fields, $instance, $group = false){
        
        if($group) {
            echo "<p>";
        }
            
        foreach($fields as $field){
            
            extract($field);
            $label = (!isset($label)) ? null : $label;
            $options = (!isset($options)) ? null : $options;
            $this->form_field($field_id, $type, $label, $instance, $options, $group);
        }
        
        if($group){
             echo "</p>";
        }
    }
    
    /**
     * Helper function - does not need to be part of widgets, this is custom, but 
     * is helpful in generating single input fields for the admin form at once. 
     *
     * @author Eddie Moya
     * 
     * @uses get_field_id() (No Codex Documentation)
     * @uses get_field_name() http://codex.wordpress.org/Function_Reference/get_field_name
     * 
     * @param string $field_id  [Required] This will be the CSS id for the input, but also will be used internally by wordpress to identify it. Use these in the form() function to set detaults.
     * @param string $type      [Required] The type of input to generate (text, textarea, select, checkbox]
     * @param string $label     [Required] Text to show next to input as its label.
     * @param array $instance   [Required] Current instance of widget option values. 
     * @param array $options    [Optional] Associative array of values and labels for html Option elements.
     * 
     * @return void
     */
    private function form_field($field_id, $type, $label, $instance, $options = array(), $group = false){
  
        if(!$group)
             echo "<p>";
            
        $input_value = (isset($instance[$field_id])) ? $instance[$field_id] : '';
        switch ($type){
            
            case 'text': ?>
            
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>: </label>
                    <input type="text" id="<?php echo $this->get_field_id( $field_id ); ?>" class="widefat" style="<?php echo (isset($style)) ? $style : ''; ?>" class="" name="<?php echo $this->get_field_name( $field_id ); ?>" value="<?php echo $input_value; ?>" />
                <?php break;
            
            case 'select': ?>
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>: </label>
                    <select id="<?php echo $this->get_field_id( $field_id ); ?>" class="widefat" name="<?php echo $this->get_field_name($field_id); ?>">
                        <?php
                            foreach ( $options as $value => $label ) :  ?>
                        
                                <option value="<?php echo $value; ?>" <?php selected($value, $input_value) ?>>
                                    <?php echo $label ?>
                                </option><?php
                                
                            endforeach; 
                        ?>
                    </select>
                    
				<?php break;
                
            case 'textarea':
                
                $rows = (isset($options['rows'])) ? $options['rows'] : '16';
                $cols = (isset($options['cols'])) ? $options['cols'] : '20';
                
                ?>
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>: </label>
                    <textarea class="widefat" rows="<?php echo $rows; ?>" cols="<?php echo $cols; ?>" id="<?php echo $this->get_field_id($field_id); ?>" name="<?php echo $this->get_field_name($field_id); ?>"><?php echo $input_value; ?></textarea>
                <?php break;
            
            case 'radio' :
                /**
                 * Need to figure out how to automatically group radio button settings with this structure.
                 */
                ?>
                    
                <?php break;
            

            case 'hidden': ?>
                    <input id="<?php echo $this->get_field_id( $field_id ); ?>" type="hidden" style="<?php echo (isset($style)) ? $style : ''; ?>" class="widefat" name="<?php echo $this->get_field_name( $field_id ); ?>" value="<?php echo $input_value; ?>" />
                <?php break;

            
            case 'checkbox' : ?>
                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id($field_id); ?>" name="<?php echo $this->get_field_name($field_id); ?>"<?php checked( (!empty($instance[$field_id]))); ?> />
                	<label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?></label>
                <?php
        }
        
        if(!$group)
             echo "</p>";
            
       
    }
}

Taxonomy_Widget::register_widget();