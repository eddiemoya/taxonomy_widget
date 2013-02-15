<?php
/*
Plugin Name: Taxonomy Widget
Description: A widget for displaying links by taxonomy
Version: 1.0
Author: Matthew Day
*/
class Taxonomy_Widget extends WP_Widget 
{
	public static $TAX_DISPLAY = 2;
	public static $PLACEHOLDER_IMG = "http://local.nodomain.com/noimage.png";
	
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
		extract($args);
        extract($instance);

		$ctgy = NULL;

		if($tw_specify)
		{
			$ctgy = $tw_category;
		}
		else
		{
			$g = get_term_by("slug", get_query_var("category_name"), "category", "OBJECT");
			$ctgy = $g->term_id;
		}
		
		$cats = get_categories(array('child_of' => $ctgy));
		
		$disp = array();
		$drop = array();
		
		for($i=0; $i<count($cats); $i++)
		{
			if($i < self::$TAX_DISPLAY)
			{
				$args = array(
					'post_type'=> "attachment",
					'posts_per_page' => "1",
					'order'    => 'DESC',
					'category' => $cats[$i]->term_id
				);
				
				$imgs = get_posts($args);
				$disp[$cats[$i]->name] = array('link' => get_category_link($cats[$i]->term_id), 'desc' => $cats[$i]->category_descrption, 'img' => (!empty($imgs[0]->guid)) ? $imgs[0]->guid : self::$PLACEHOLDER_IMG);
			}
			else
			{
				$drop[$cats[$i]->name] = array('link' => get_category_link($cats[$i]->term_id), 'desc' => $cats[$i]->category_descrption);
			}
		}
		
		echo $before_widget;
		
		foreach($disp as $k => $d)
		{
			echo sprintf('<img src="%s" /><a href="%s">%s</a>%s<br />', $d['img'], $d['link'], $k, (($tw_description && !empty($d['desc'])) ? " - <span>" . $d['desc'] . "</span>" : ""));
		}
				
		if(!empty($drop) && $tw_dropdown)
		{
			echo "<br />";
			echo "<select>";
			echo "<option value=\"\">--Click for More--</option>";
			
			foreach($drop as $k => $d)
			{
				if(empty($k))
				{
					continue;
				}
				
				echo sprintf('<option value="%s">%s</option>', $d['link'], $k);
			}
			
			echo "</select>";
		}	
	   
        echo $after_widget;
       
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
		
		$fields = array(
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
				'label'			=> "Taxonomy"
			)
		);
				
		// if an override is specified, display the override select boxes
		if($tw_specify)
		{
			$tax = get_taxonomies(NULL, 'objects');
			$opts = array();
		
			foreach($tax as $t)
			{
				$opts[$t->name] = $t->name;
			}

			$fields[] = array(
				'field_id' => 'tw_taxonomy',
				'type' => 'select',
				'label' => 'Taxonomy',
				'options' => $opts
			);
		
			if($tw_taxonomy)
			{
				$cats = get_categories(array('taxonomy' => $tw_taxonomy));
				$opts = array();
				
				foreach($cats as $a)
				{
					$opts[$a->term_id] = $a->name;
				}
				
				$fields[] = array(
					'field_id' => 'tw_category',
					'type' => 'select',
					'label' => 'Taxonomy',
					'options' => $opts
				);
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