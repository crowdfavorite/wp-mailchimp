<?php
/**
 * Displays a MailChimp Signup Form
 **/
function mailchimpSF_signup_form($args = array()) {
	extract($args);

	$mv = get_option('mc_merge_vars');
	$igs = get_option('mc_interest_groups');
	
	// See if we have valid Merge Vars
	if (!is_array($mv)){
		echo $before_widget;
		?>
		<div class="mc_error_msg">
			<?php esc_html_e('There was a problem loading your MailChimp details. Please re-run the setup process under Settings->MailChimp Setup', 'mailchimp_i18n'); ?>
		</div>
		<?php
		echo $after_widget;
		return;
	}
	
	// Get some options
	$uid = get_option('mc_user_id');
	$list_name = get_option('mc_list_name');
	
	if (!empty($before_widget)) {
		echo $before_widget;
	}

	$header =  get_option('mc_header_content');
	// See if we have custom header content
	if (!empty($header)) {
		// See if we need to wrap the header content in our own div
		if (strlen($header) == strlen(strip_tags($header))){
			echo !empty($before_title) ? $before_title : '<div class="mc_custom_border_hdr">';
			echo $header; // don't escape $header b/c it may have HTML allowed
			echo !empty($after_title) ? $after_title : '</div><!-- /mc_custom_border_hdr -->';
		}
		else {
			echo $header; // don't escape $header b/c it may have HTML allowed
		}
	}
	
	$sub_heading = trim(get_option('mc_subheader_content'));
	?>
	
<div id="mc_signup">
	<form method="post" action="#mc_signup" id="mc_signup_form">
		<input type="hidden" id="mc_submit_type" name="mc_submit_type" value="html" />
		<input type="hidden" name="mcsf_action" value="mc_submit_signup_form" />
		<?php wp_nonce_field('mc_submit_signup_form', '_mc_submit_signup_form_nonce', false); ?>
		
	<?php 
	if ($sub_heading) { 
		?>
		<div id="mc_subheader">
			<?php echo $sub_heading; ?>
		</div><!-- /mc_subheader -->
		<?php
	} 
	?>
	
	<div class="mc_form_inside">
		
		<div class="updated" id="mc_message">
			<?php echo mailchimpSF_global_msg(); ?>
		</div><!-- /mc_message -->

		<?php
		//don't show the "required" stuff if there's only 1 field to display.
		$num_fields = 0;
		foreach((array)$mv as $var) {
			$opt = 'mc_mv_'.$var['tag'];
			if ($var['req'] || get_option($opt) == 'on') {
				$num_fields++;
			}
		}

		if (is_array($mv)) {
			// head on back to the beginning of the array
			reset($mv);
		}
		
		// Loop over our vars, and output the ones that are set to display
		foreach($mv as $var) {
			if (!$var['public']) {
				echo '<div style="display:none;">'.mailchimp_form_field($var, $num_fields).'</div>';
			}
			else {
				echo mailchimp_form_field($var, $num_fields);
			}
		}
		
		
		// Show an explanation of the * if there's more than one field
		if ($num_fields > 1) {
			?>
			<div id="mc-indicates-required">
				* = <?php esc_html_e('required field', 'mailchimp_i18n'); ?>
			</div><!-- /mc-indicates-required -->
			<?php
		}
		
		
		// Show our Interest groups fields if we have them, and they're set to on
		if (is_array($igs) && !empty($igs)) {
			foreach ($igs as $ig) {
				if (is_array($ig) && isset($ig['id'])) {
					if ($igs && get_option('mc_show_interest_groups_'.$ig['id']) == 'on') {
						if ($ig['form_field'] != 'hidden') {
						?>				
							<div class="mc_interests_header">
								<?php echo esc_html($ig['name']); ?>
							</div><!-- /mc_interests_header -->
							<div class="mc_interest">
						<?php
						}
						else {
						?>
							<div class="mc_interest" style="display: none;">
						<?php					
						}
					?>			

					<?php
						mailchimp_interest_group_field($ig);
					?>				
					</div><!-- /mc_interest -->
			
					<?php
					}
				}
			}
		}

		if (get_option('mc_email_type_option')) {
		?>
		<div class="mergeRow">
			<label><?php _e('Preferred Format', 'mailchimp_i18n'); ?></label>
		    <div class="field-group groups">
		        <ul class="mc_list">
			        <li><input type="radio" name="email_type" id="email_type_html" value="html" checked="checked"><label for="email_type_html"><?php _e('HTML', 'mailchimp_i18n'); ?></label></li>
			        <li><input type="radio" name="email_type" id="email_type_text" value="text"><label for="email_type_text"><?php _e('Text', 'mailchimp_i18n'); ?></label></li>
			        <li><input type="radio" name="email_type" id="email_type_mobile" value="mobile"><label for="email_type_mobile"><?php _e('Mobile', 'mailchimp_i18n'); ?></label></li>
		        </ul>
			</div>
		</div>	

		<?php
		}
		?>

		<div class="mc_signup_submit">
			<input type="submit" name="mc_signup_submit" id="mc_signup_submit" value="<?php echo esc_attr(get_option('mc_submit_text')); ?>" class="button" />
		</div><!-- /mc_signup_submit -->
	
	
		<?php
		if ( get_option('mc_use_unsub_link') == 'on') {
        	list($key, $dc) = explode("-",get_option('mc_apikey'),2);
        	if (!$dc) $dc = "us1";
        	$host = 'http://'.$dc.'.list-manage.com';
			?>
			<div id="mc_unsub_link" align="center">
				<a href="<?php echo esc_url($host.'/unsubscribe/?u='.get_option('mc_user_id').'&amp;id='.get_option('mc_list_id')); ?>" target="_blank"><?php esc_html_e('unsubscribe from list', 'mailchimp_i18n'); ?></a>
			</div><!-- /mc_unsub_link -->
			<?php
		}
		if ( get_option('mc_rewards') == 'on') {
			?>
			<br/>
			<div id="mc_display_rewards" align="center">
				<?php esc_html_e('powered by', 'mailchimp_i18n'); ?> <a href="<?php echo esc_url('http://www.mailchimp.com/affiliates/?aid='.get_option('mc_user_id').'&amp;afl=1'); ?>">MailChimp</a>!
			</div><!-- /mc_display_rewards -->
			<?php
		}
		?>
		
	</div><!-- /mc_form_inside -->
	</form><!-- /mc_signup_form -->
</div><!-- /mc_signup_container -->
	<?php
	if (!empty($before_widget)) {
		echo $after_widget;
	}
}

/**
 * Generate and display markup for Interest Groups
 * @param array $ig Set of Interest Groups to generate markup for
 * @return void
 */
function mailchimp_interest_group_field($ig) {
	if (!is_array($ig)) {
		return;
	}
	$html = '';
	$set_name = 'group['.$ig['id'].']';
	switch ($ig['form_field']) {
		case 'checkbox':
		case 'checkboxes':
			$i = 1;
			foreach($ig['groups'] as $interest){
				$interest = $interest['name'];
				$html .= '
				<input type="checkbox" name="'.esc_attr($set_name.'['.$i.']').'" id="'.esc_attr('mc_interest_'.$ig['id'].'_'.$interest).'" class="mc_interest" value="'.esc_attr($interest).'" />
				<label for="'. esc_attr('mc_interest_'.$ig['id'].'_'.$interest).'" class="mc_interest_label">'.esc_html($interest).'</label>
				<br/>';
				$i++;
			}
			break;
		case 'radio':
			foreach($ig['groups'] as $interest){
				$interest = $interest['name'];
				$html .= '
				<input type="radio" name="'.esc_attr($set_name).'" id="'.esc_attr('mc_interest_'.$ig['id'].'_'.$interest).'" class="mc_interest" value="'.esc_attr($interest).'"/>
				<label for="'.esc_attr('mc_interest_'.$ig['id'].'_'.$interest).'" class="mc_interest_label">'.esc_html($interest).'</label>
				<br/>';
			}
			break;
		case 'select':
		case 'dropdown':
			$html .= '
			<select name="'.esc_attr($set_name).'">
				<option value=""></option>';
				foreach($ig['groups'] as $interest){
					$interest = $interest['name'];
					$html .= '
					<option value="'.esc_attr($interest).'">'.esc_html($interest).'</option>';
				}
				$html .= '
			</select>';
			break;
		case 'hidden': 
			$i = 1;
			foreach($ig['groups'] as $interest) {
				$interest = $interest['name'];
				$html .= '
				<input type="checkbox" name="'.esc_attr($set_name.'['.$i.']').'" id="'.esc_attr('mc_interest_'.$ig['id'].'_'.$interest).'" class="mc_interest" value="'.esc_attr($interest).'" />
				<label for="'. esc_attr('mc_interest_'.$ig['id'].'_'.$interest).'" class="mc_interest_label">'.esc_html($interest).'</label>';
				$i++;
			}
			break;
	}
	echo $html;
}

/**
 * Generate and display markup for form fields
 * @param array $var Array containing informaoin about the field
 * @param int $num_fields The number of fields total we'll be generating markup for. Used in calculating required text logic
 * @return void
 */
function mailchimp_form_field($var, $num_fields) {
	$opt = 'mc_mv_'.$var['tag'];
	$html = '';
	// See if that var is set as required, or turned on (for display)
	if ($var['req'] || get_option($opt) == 'on') {
		$label = '<label for="'.esc_attr($opt).'" class="mc_var_label">'.esc_html($var['name']);
		if ($var['req'] && $num_fields > 1) {
			$label .= '<span class="mc_required">*</span>';
		}
		$label .= '</label>';
	
		$html .= '
<div class="mc_merge_var">
		'.$label;
	
		switch ($var['field_type']) {
			case 'date': 
				$html .= '
	<input type="text" size="18" value="'.esc_attr($var['default']).'" name="'.esc_attr($opt).'" id="'.esc_attr($opt).'" class="date-pick mc_input"/>';
				break;
			case 'radio':
				if (is_array($var['choices'])) {
					$html .= '
	<ul class="mc_list">';
					foreach ($var['choices'] as $key => $value) {
						$html .= '
		<li>
			<input type="radio" id="'.esc_attr($opt.'_'.$key).'" name="'.esc_attr($opt).'" class="mc_radio" value="'.$value.'"'.checked($var['default'], $value, false).' />
			<label for="'.esc_attr($opt.'_'.$key).'" class="mc_radio_label">'.esc_html($value).'</label>
		</li>';
					}
					$html .= '
	</ul>';
				}
				break;
			case 'dropdown':
				if (is_array($var['choices'])) {
					$html .= '
	<br /><select id="'.esc_attr($opt).'" name="'.esc_attr($opt).'" class="mc_select">';
					foreach ($var['choices'] as $value) {
						$html .= '
		<option value="'.esc_attr($value).'"'.selected($value, $var['default'], false).'>'.esc_html($value).'</option>';
					}
					$html .= '
	</select>';
				}
				break;
			case 'birthday':
				$html .= '
	<input type="text" size="18" value="'.esc_attr($var['default']).'" name="'.esc_attr($opt).'" id="'.esc_attr($opt).'" class="birthdate-pick mc_input"/>';
				break;
			case 'birthday-old':
				$days = range(1, 31);
				$months = array(__('January', 'mailchimp_i18n'), __('February', 'mailchimp_i18n'), __('March', 'mailchimp_i18n'), __('April', 'mailchimp_i18n'), __('May', 'mailchimp_i18n'), __('June', 'mailchimp_i18n'), __('July', 'mailchimp_i18n'), __('August', 'mailchimp_i18n'), __('September', 'mailchimp_i18n'), __('October', 'mailchimp_i18n'), __('November', 'mailchimp_i18n'), __('December', 'mailchimp_i18n'), );
				
				$html .= '
	<br /><select id="'.esc_attr($opt).'" name="'.esc_attr($opt.'[month]').'" class="mc_select">';
				foreach ($months as $month_key => $month) {
					$html .= '
		<option value="'.$month_key.'">'.$month.'</option>';
				}
				$html .= '
	</select>';
	
				$html .= '
	<select id="'.esc_attr($opt).'" name="'.esc_attr($opt.'[day]').'" class="mc_select">';
				foreach ($days as $day) {
						$html .= '
		<option value="'.$day.'">'.$day.'</option>';
					}			
				$html .= '
	</select>';
				break;
			case 'address':
			$countries = mailchimp_country_list();
			$html .= '
	<br />
	<label for="'.esc_attr($opt.'-addr1').'" class="mc_address_label">'.__('Street Address', 'mailchimp_i18n').'</label> <br />
	<input type="text" size="18" value="" name="'.esc_attr($opt.'[addr1]').'" id="'.esc_attr($opt.'-addr1').'" class="mc_input" /> <br />
	<label for="'.esc_attr($opt.'-addr2').'" class="mc_address_label">'.__('Address Line 2', 'mailchimp_i18n').'</label> <br />
	<input type="text" size="18" value="" name="'.esc_attr($opt.'[addr2]').'" id="'.esc_attr($opt.'-addr2').'" class="mc_input" /> <br />
	<label for="'.esc_attr($opt.'-city').'" class="mc_address_label">'.__('City', 'mailchimp_i18n').'</label>	<br />
	<input type="text" size="18" value="" name="'.esc_attr($opt.'[city]').'" id="'.esc_attr($opt.'-city').'" class="mc_input" /> <br />
	<label for="'.esc_attr($opt.'-state').'" class="mc_address_label">'.__('State', 'mailchimp_i18n').'</label> <br />
	<input type="text" size="18" value="" name="'.esc_attr($opt.'[state]').'" id="'.esc_attr($opt.'-state').'" class="mc_input" /> <br />
	<label for="'.esc_attr($opt.'-zip').'" class="mc_address_label">'.__('Zip / Postal', 'mailchimp_i18n').'</label> <br />
	<input type="text" size="18" value="" maxlength="5" name="'.esc_attr($opt.'[zip]').'" id="'.esc_attr($opt.'-zip').'" class="mc_input" /> <br />
	<label for="'.esc_attr($opt.'-country').'" class="mc_address_label">'.__('Country', 'mailchimp_i18n').'</label> <br />
	<select name="'.esc_attr($opt.'[country]').'" id="'.esc_attr($opt.'-country').'">';
			foreach ($countries as $country_code => $country_name) {
				$html .= '
		<option value="'.esc_attr($country_code).'"'.selected($country_code, $var['defaultcountry'], false).'>'.esc_html($country_name).'</option>';
			}
			$html .= '
	</select>';
				break;
			case 'zip':
				$html .= '
	<input type="text" size="18" maxlength="5" value="" name="'.esc_attr($opt).'" id="'.esc_attr($opt).'" class="mc_input" />';
				break;
			case 'phone':
				$html .= '<br />
	&#40; <input type="text" size="3" maxlength="3" value="" name="'.esc_attr($opt.'[area]').'" id="'.esc_attr($opt.'-area').'" class="mc_input mc_phone" /> &#41; &ndash;
	<input type="text" size="3" maxlength="3" value="" name="'.esc_attr($opt.'[detail1]').'" id="'.esc_attr($opt.'-detail1').'" class="mc_input mc_phone" /> &ndash;
	<input type="text" size="4" maxlength="4" value="" name="'.esc_attr($opt.'[detail2]').'" id="'.esc_attr($opt.'-detail2').'" class="mc_input mc_phone" />
			';
				break;
			case 'email':
			case 'url':
			case 'imageurl':
			case 'text':
			case 'number':
			default:
				$html .= '
	<input type="text" size="18" value="'.esc_html($var['default']).'" name="'.esc_attr($opt).'" id="'.esc_attr($opt).'" class="mc_input"/>';
				break;
		}
		if (!empty($var['helptext'])) {
			$html .= '<span class="mc_help">'.esc_html($var['helptext']).'</span>';
		}
		$html .= '
</div><!-- /mc_merge_var -->';
	}
	
	return $html;
}

/**
 * MailChimp Subscribe Box widget class
 */
class mailchimpSF_Widget extends WP_Widget {

	function mailchimpSF_Widget() {
		$widget_ops = array( 
			'description' => __('Displays a MailChimp Subscribe box', 'mailchimp_i18n')
		);
		$this->WP_Widget('mailchimpSF_widget', __('MailChimp Widget', 'mailchimp_i18n'), $widget_ops);
	}

	function widget( $args, $instance ) {
		if (!is_array($instance)) {
			$instance = array();
		}
		mailchimpSF_signup_form(array_merge($args, $instance));
	}
}

function mailchimp_country_list() {
	return array(
		'164' => __('USA', 'mailchimp_i18n'),
		'286' => __('Aaland Islands', 'mailchimp_i18n'),
		'274' => __('Afghanistan', 'mailchimp_i18n'),
		'2' => __('Albania', 'mailchimp_i18n'),
		'3' => __('Algeria', 'mailchimp_i18n'),
		'178' => __('American Samoa', 'mailchimp_i18n'),
		'4' => __('Andorra', 'mailchimp_i18n'),
		'5' => __('Angola', 'mailchimp_i18n'),
		'176' => __('Anguilla', 'mailchimp_i18n'),
		'175' => __('Antigua And Barbuda', 'mailchimp_i18n'),
		'6' => __('Argentina', 'mailchimp_i18n'),
		'7' => __('Armenia', 'mailchimp_i18n'),
		'179' => __('Aruba', 'mailchimp_i18n'),
		'8' => __('Australia', 'mailchimp_i18n'),
		'9' => __('Austria', 'mailchimp_i18n'),
		'10' => __('Azerbaijan', 'mailchimp_i18n'),
		'11' => __('Bahamas', 'mailchimp_i18n'),
		'12' => __('Bahrain', 'mailchimp_i18n'),
		'13' => __('Bangladesh', 'mailchimp_i18n'),
		'14' => __('Barbados', 'mailchimp_i18n'),
		'15' => __('Belarus', 'mailchimp_i18n'),
		'16' => __('Belgium', 'mailchimp_i18n'),
		'17' => __('Belize', 'mailchimp_i18n'),
		'18' => __('Benin', 'mailchimp_i18n'),
		'19' => __('Bermuda', 'mailchimp_i18n'),
		'20' => __('Bhutan', 'mailchimp_i18n'),
		'21' => __('Bolivia', 'mailchimp_i18n'),
		'22' => __('Bosnia and Herzegovina', 'mailchimp_i18n'),
		'23' => __('Botswana', 'mailchimp_i18n'),
		'24' => __('Brazil', 'mailchimp_i18n'),
		'180' => __('Brunei Darussalam', 'mailchimp_i18n'),
		'25' => __('Bulgaria', 'mailchimp_i18n'),
		'26' => __('Burkina Faso', 'mailchimp_i18n'),
		'27' => __('Burundi', 'mailchimp_i18n'),
		'28' => __('Cambodia', 'mailchimp_i18n'),
		'29' => __('Cameroon', 'mailchimp_i18n'),
		'30' => __('Canada', 'mailchimp_i18n'),
		'31' => __('Cape Verde', 'mailchimp_i18n'),
		'32' => __('Cayman Islands', 'mailchimp_i18n'),
		'33' => __('Central African Republic', 'mailchimp_i18n'),
		'34' => __('Chad', 'mailchimp_i18n'),
		'35' => __('Chile', 'mailchimp_i18n'),
		'36' => __('China', 'mailchimp_i18n'),
		'37' => __('Colombia', 'mailchimp_i18n'),
		'38' => __('Congo', 'mailchimp_i18n'),
		'183' => __('Cook Islands', 'mailchimp_i18n'),
		'268' => __('Costa Rica', 'mailchimp_i18n'),
		'275' => __('Cote D\'Ivoire', 'mailchimp_i18n'),
		'40' => __('Croatia', 'mailchimp_i18n'),
		'276' => __('Cuba', 'mailchimp_i18n'),
		'41' => __('Cyprus', 'mailchimp_i18n'),
		'42' => __('Czech Republic', 'mailchimp_i18n'),
		'43' => __('Denmark', 'mailchimp_i18n'),
		'44' => __('Djibouti', 'mailchimp_i18n'),
		'289' => __('Dominica', 'mailchimp_i18n'),
		'187' => __('Dominican Republic', 'mailchimp_i18n'),
		'233' => __('East Timor', 'mailchimp_i18n'),
		'45' => __('Ecuador', 'mailchimp_i18n'),
		'46' => __('Egypt', 'mailchimp_i18n'),
		'47' => __('El Salvador', 'mailchimp_i18n'),
		'48' => __('Equatorial Guinea', 'mailchimp_i18n'),
		'49' => __('Eritrea', 'mailchimp_i18n'),
		'50' => __('Estonia', 'mailchimp_i18n'),
		'51' => __('Ethiopia', 'mailchimp_i18n'),
		'191' => __('Faroe Islands', 'mailchimp_i18n'),
		'52' => __('Fiji', 'mailchimp_i18n'),
		'53' => __('Finland', 'mailchimp_i18n'),
		'54' => __('France', 'mailchimp_i18n'),
		'277' => __('French Polynesia', 'mailchimp_i18n'),
		'59' => __('Germany', 'mailchimp_i18n'),
		'60' => __('Ghana', 'mailchimp_i18n'),
		'194' => __('Gibraltar', 'mailchimp_i18n'),
		'61' => __('Greece', 'mailchimp_i18n'),
		'195' => __('Greenland', 'mailchimp_i18n'),
		'192' => __('Grenada', 'mailchimp_i18n'),
		'62' => __('Guam', 'mailchimp_i18n'),
		'198' => __('Guatemala', 'mailchimp_i18n'),
		'270' => __('Guernsey', 'mailchimp_i18n'),
		'65' => __('Guyana', 'mailchimp_i18n'),
		'200' => __('Haiti', 'mailchimp_i18n'),
		'66' => __('Honduras', 'mailchimp_i18n'),
		'67' => __('Hong Kong', 'mailchimp_i18n'),
		'68' => __('Hungary', 'mailchimp_i18n'),
		'69' => __('Iceland', 'mailchimp_i18n'),
		'70' => __('India', 'mailchimp_i18n'),
		'71' => __('Indonesia', 'mailchimp_i18n'),
		'278' => __('Iran', 'mailchimp_i18n'),
		'279' => __('Iraq', 'mailchimp_i18n'),
		'74' => __('Ireland', 'mailchimp_i18n'),
		'75' => __('Israel', 'mailchimp_i18n'),
		'76' => __('Italy', 'mailchimp_i18n'),
		'202' => __('Jamaica', 'mailchimp_i18n'),
		'78' => __('Japan', 'mailchimp_i18n'),
		'288' => __('Jersey  (Channel Islands)', 'mailchimp_i18n'),
		'79' => __('Jordan', 'mailchimp_i18n'),
		'80' => __('Kazakhstan', 'mailchimp_i18n'),
		'81' => __('Kenya', 'mailchimp_i18n'),
		'82' => __('Kuwait', 'mailchimp_i18n'),
		'83' => __('Kyrgyzstan', 'mailchimp_i18n'),
		'84' => __('Lao People\'s Democratic Republic', 'mailchimp_i18n'),
		'85' => __('Latvia', 'mailchimp_i18n'),
		'86' => __('Lebanon', 'mailchimp_i18n'),
		'281' => __('Libya', 'mailchimp_i18n'),
		'90' => __('Liechtenstein', 'mailchimp_i18n'),
		'91' => __('Lithuania', 'mailchimp_i18n'),
		'92' => __('Luxembourg', 'mailchimp_i18n'),
		'208' => __('Macau', 'mailchimp_i18n'),
		'93' => __('Macedonia', 'mailchimp_i18n'),
		'94' => __('Madagascar', 'mailchimp_i18n'),
		'95' => __('Malawi', 'mailchimp_i18n'),
		'96' => __('Malaysia', 'mailchimp_i18n'),
		'97' => __('Maldives', 'mailchimp_i18n'),
		'98' => __('Mali', 'mailchimp_i18n'),
		'99' => __('Malta', 'mailchimp_i18n'),
		'212' => __('Mauritius', 'mailchimp_i18n'),
		'101' => __('Mexico', 'mailchimp_i18n'),
		'102' => __('Moldova, Republic of', 'mailchimp_i18n'),
		'103' => __('Monaco', 'mailchimp_i18n'),
		'104' => __('Mongolia', 'mailchimp_i18n'),
		'290' => __('Montenegro', 'mailchimp_i18n'),
		'105' => __('Morocco', 'mailchimp_i18n'),
		'106' => __('Mozambique', 'mailchimp_i18n'),
		'242' => __('Myanmar', 'mailchimp_i18n'),
		'107' => __('Namibia', 'mailchimp_i18n'),
		'108' => __('Nepal', 'mailchimp_i18n'),
		'109' => __('Netherlands', 'mailchimp_i18n'),
		'110' => __('Netherlands Antilles', 'mailchimp_i18n'),
		'213' => __('New Caledonia', 'mailchimp_i18n'),
		'111' => __('New Zealand', 'mailchimp_i18n'),
		'112' => __('Nicaragua', 'mailchimp_i18n'),
		'113' => __('Niger', 'mailchimp_i18n'),
		'114' => __('Nigeria', 'mailchimp_i18n'),
		'272' => __('North Korea', 'mailchimp_i18n'),
		'116' => __('Norway', 'mailchimp_i18n'),
		'117' => __('Oman', 'mailchimp_i18n'),
		'118' => __('Pakistan', 'mailchimp_i18n'),
		'222' => __('Palau', 'mailchimp_i18n'),
		'282' => __('Palestine', 'mailchimp_i18n'),
		'119' => __('Panama', 'mailchimp_i18n'),
		'219' => __('Papua New Guinea', 'mailchimp_i18n'),
		'120' => __('Paraguay', 'mailchimp_i18n'),
		'121' => __('Peru', 'mailchimp_i18n'),
		'122' => __('Philippines', 'mailchimp_i18n'),
		'123' => __('Poland', 'mailchimp_i18n'),
		'124' => __('Portugal', 'mailchimp_i18n'),
		'126' => __('Qatar', 'mailchimp_i18n'),
		'58' => __('Republic of Georgia', 'mailchimp_i18n'),
		'128' => __('Romania', 'mailchimp_i18n'),
		'129' => __('Russia', 'mailchimp_i18n'),
		'130' => __('Rwanda', 'mailchimp_i18n'),
		'205' => __('Saint Kitts and Nevis', 'mailchimp_i18n'),
		'206' => __('Saint Lucia', 'mailchimp_i18n'),
		'132' => __('Samoa (Independent)', 'mailchimp_i18n'),
		'227' => __('San Marino', 'mailchimp_i18n'),
		'133' => __('Saudi Arabia', 'mailchimp_i18n'),
		'134' => __('Senegal', 'mailchimp_i18n'),
		'266' => __('Serbia', 'mailchimp_i18n'),
		'135' => __('Seychelles', 'mailchimp_i18n'),
		'137' => __('Singapore', 'mailchimp_i18n'),
		'138' => __('Slovakia', 'mailchimp_i18n'),
		'139' => __('Slovenia', 'mailchimp_i18n'),
		'223' => __('Solomon Islands', 'mailchimp_i18n'),
		'141' => __('South Africa', 'mailchimp_i18n'),
		'142' => __('South Korea', 'mailchimp_i18n'),
		'143' => __('Spain', 'mailchimp_i18n'),
		'144' => __('Sri Lanka', 'mailchimp_i18n'),
		'293' => __('Sudan', 'mailchimp_i18n'),
		'146' => __('Suriname', 'mailchimp_i18n'),
		'147' => __('Swaziland', 'mailchimp_i18n'),
		'148' => __('Sweden', 'mailchimp_i18n'),
		'149' => __('Switzerland', 'mailchimp_i18n'),
		'152' => __('Taiwan', 'mailchimp_i18n'),
		'153' => __('Tanzania', 'mailchimp_i18n'),
		'154' => __('Thailand', 'mailchimp_i18n'),
		'155' => __('Togo', 'mailchimp_i18n'),
		'232' => __('Tonga', 'mailchimp_i18n'),
		'234' => __('Trinidad and Tobago', 'mailchimp_i18n'),
		'156' => __('Tunisia', 'mailchimp_i18n'),
		'157' => __('Turkey', 'mailchimp_i18n'),
		'287' => __('Turks &amp; Caicos Islands', 'mailchimp_i18n'),
		'159' => __('Uganda', 'mailchimp_i18n'),
		'161' => __('Ukraine', 'mailchimp_i18n'),
		'162' => __('United Arab Emirates', 'mailchimp_i18n'),
		'262' => __('United Kingdom', 'mailchimp_i18n'),
		'163' => __('Uruguay', 'mailchimp_i18n'),
		'239' => __('Vanuatu', 'mailchimp_i18n'),
		'166' => __('Vatican City State (Holy See)', 'mailchimp_i18n'),
		'167' => __('Venezuela', 'mailchimp_i18n'),
		'168' => __('Vietnam', 'mailchimp_i18n'),
		'169' => __('Virgin Islands (British)', 'mailchimp_i18n'),
		'238' => __('Virgin Islands (U.S.)', 'mailchimp_i18n'),
		'173' => __('Zambia', 'mailchimp_i18n'),
		'174' => __('Zimbabwe', 'mailchimp_i18n'),
	);
}
?>
