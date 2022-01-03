<?php

/**
 * Adds Foo_Widget widget.
 */
class SGWordPressWidget extends WP_Widget
{
	private $widgetIndex = 0;

	/**
	 * Register widget with WordPress.
	 */

	function __construct()
	{
		$widgetOptions = array(
			'classname' => 'sg_wordpress_widget',
			'description' => 'Widget for BackupGuard seal',
		);
		parent::__construct(
			'sg_wordpress_widget',
			esc_html__('BackupGuard Seal', 'text_domain'),
			$widgetOptions
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance)
	{
		$sgSealTheme = isset($instance['theme']) ? $instance['theme'] : '';
		$sgSealImage = isset($instance['theme-'.$sgSealTheme.'-image']) ? strip_tags($instance['theme-'.$sgSealTheme.'-image']) : '';

		if ($sgSealImage) {
			echo '<a href="https://backup-guard.com" style="text-decoration:none;" target="_blank"><img src="https://backup-guard.com/seal/'.$sgSealImage.'" alt="BackupGuard - backup your website in the cloud"></a>';
		}
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form($instance)
	{
		$sgSealTheme = isset($instance['theme']) ? $instance['theme'] : SG_SEAL_THEME_DARK;
		$sgSealImage = isset($instance['theme-'.$sgSealTheme.'-image']) ? $instance['theme-'.$sgSealTheme.'-image'] : '1';
		if ($this->number != "__i__") {
			$this->widgetIndex = $this->number;
		}
		else {
			$this->widgetIndex += 1;
		}

		?>
		<div class="backup-gaurd-seal-widget-option-container">
			<div style="margin-top: 13px;">
				<label for="<?php echo esc_attr($this->get_field_id('theme').'-'.$this->widgetIndex); ?>"><?php esc_attr_e('Theme:', 'text_domain'); ?></label>
				<select id="<?php echo esc_attr($this->get_field_id('theme').'-'.$this->widgetIndex); ?>" name="<?php echo esc_attr($this->get_field_name('theme')); ?>">
					<option value="<?php echo SG_SEAL_THEME_DARK ?>" <?php echo $sgSealTheme == SG_SEAL_THEME_DARK ? "selected" : "" ?>>Dark</option>
					<option value="<?php echo SG_SEAL_THEME_GREEN ?>" <?php echo $sgSealTheme == SG_SEAL_THEME_GREEN ? "selected" : "" ?>>Green</option>
					<option value="<?php echo SG_SEAL_THEME_WHITE ?>" <?php echo $sgSealTheme == SG_SEAL_THEME_WHITE ? "selected" : "" ?>>White</option>
				</select>
			</div>
			<div id="<?php echo esc_attr($this->get_field_id('theme-dark').'-'.$this->widgetIndex) ?>" <?php echo $sgSealTheme == SG_SEAL_THEME_DARK ? "" : "hidden" ?> style="margin-bottom: 13px; margin-top: 13px;">
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-dark-image')); ?>" value="1" checked>
					<img src="<?php echo SG_IMAGE_URL.'1.png' ?>">
				</div>
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-dark-image')); ?>" value="4" <?php echo ($sgSealImage == "4") ? "checked":""; ?>>
					<img src="<?php echo SG_IMAGE_URL.'4.png' ?>">
				</div>
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-dark-image')); ?>" value="5" <?php echo ($sgSealImage == "5") ? "checked":""; ?>>
					<img src="<?php echo SG_IMAGE_URL.'5.png' ?>">
				</div>
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-dark-image')); ?>" value="8" <?php echo ($sgSealImage == "8") ? "checked":""; ?>>
					<img src="<?php echo SG_IMAGE_URL.'8.png' ?>">
				</div>
			</div>
			<div id="<?php echo esc_attr($this->get_field_id('theme-green').'-'.$this->widgetIndex) ?>" <?php echo $sgSealTheme == SG_SEAL_THEME_GREEN ? "" : "hidden" ?> style="margin-bottom: 13px; margin-top: 13px;">
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-green-image')); ?>" value="2" checked>
					<img src="<?php echo SG_IMAGE_URL.'2.png' ?>">
				</div>
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-green-image')); ?>" value="6" <?php echo ($sgSealImage == "6") ? "checked":""; ?>>
					<img src="<?php echo SG_IMAGE_URL.'6.png' ?>">
				</div>
			</div>
			<div id="<?php echo esc_attr($this->get_field_id('theme-white').'-'.$this->widgetIndex) ?>" <?php echo $sgSealTheme == SG_SEAL_THEME_WHITE ? "" : "hidden" ?> style="margin-bottom: 13px; margin-top: 13px;">
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-white-image')); ?>" value="3" checked>
					<img src="<?php echo SG_IMAGE_URL.'3.png' ?>">
				</div>
				<div style="margin-bottom: 5px;">
					<input type="radio" name="<?php echo esc_attr($this->get_field_name('theme-white-image')); ?>" value="7" <?php echo ($sgSealImage == "7") ? "checked":""; ?>>
					<img src="<?php echo SG_IMAGE_URL.'7.png' ?>">
				</div>
			</div>
		</div>

		<style type="text/css">
			.backup-gaurd-seal-widget-option-container input, img {
				vertical-align: middle;
			}
		</style>

		<script type="text/javascript">
			jQuery("select[id*='theme-<?php echo $this->widgetIndex ?>']").on("change", function () {
				var theme = jQuery(this).val();

				if (theme == "dark") {
					jQuery( "div[id*='theme-dark-<?php echo $this->widgetIndex ?>']" ).show();
					jQuery( "div[id*='theme-green-<?php echo $this->widgetIndex ?>']" ).hide();
					jQuery( "div[id*='theme-white-<?php echo $this->widgetIndex ?>']" ).hide();
				}
				else if (theme == "green") {
					jQuery( "div[id*='theme-green-<?php echo $this->widgetIndex ?>']" ).show();
					jQuery( "div[id*='theme-dark-<?php echo $this->widgetIndex ?>']" ).hide();
					jQuery( "div[id*='theme-white-<?php echo $this->widgetIndex ?>']" ).hide();
				}
				else {
					jQuery( "div[id*='theme-white-<?php echo $this->widgetIndex ?>']" ).show();
					jQuery( "div[id*='theme-dark-<?php echo $this->widgetIndex ?>']" ).hide();
					jQuery( "div[id*='theme-green-<?php echo $this->widgetIndex ?>']" ).hide();
				}
			});
		</script>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance)
	{
		$instance = array();
		$instance['theme'] = isset($new_instance['theme']) ? strip_tags($new_instance['theme']) : '';
		$instance['theme-'.$instance['theme'].'-image'] = isset($new_instance['theme-'.$instance['theme'].'-image']) ? strip_tags($new_instance['theme-'.$instance['theme'].'-image']) : '';

		if (!$instance['theme-'.$instance['theme'].'-image']) {
			return false;
		}

		return $instance;
	}

}
