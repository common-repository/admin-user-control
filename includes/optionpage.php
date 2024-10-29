<div class="wrap">
	<h1><?php echo __( 'Admin User Control setting', 'admin-user-control' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'admin-user-control-settings-group' ); ?>
		<?php do_settings_sections( 'admin-user-control-settings-group' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">login monitor display user</th>
				<td>
					<?php $lm_display_user = get_option( 'auc_lm_display_user' ); ?>
					<label><input name="auc_lm_display_user"
					              type="radio"
					              size="50"
					              value="1" <?php if ( $lm_display_user == '1' ) {
							echo 'checked';
						} ?>
						/>Only Administrator</label>
					<label><input
								name="auc_lm_display_user"
								type="radio"
								style="margin-left: 2em"
								size="50"
								value="0" <?php if ( $lm_display_user != '1' ) {
							echo 'checked';
						} ?>
						/>All User</label>
				<td>
			</tr>
			<tr valign="top">
				<th scope="row">Maintenance function</th>
				<td>
					<?php $is_maintenance_enabled = get_option( 'auc_is_maintenance_enabled', '1' ); ?>
					<label><input name="auc_is_maintenance_enabled"
					              type="radio"
					              size="50"
					              value="1" <?php if ( $is_maintenance_enabled == '1' ) {
							echo 'checked';
						} ?>
						/>Enabled</label>
					<label><input
								name="auc_is_maintenance_enabled"
								type="radio"
								style="margin-left: 2em"
								size="50"
								value="0" <?php if ( $is_maintenance_enabled != '1' ) {
							echo 'checked';
						} ?>
						/>Disabled</label>
				<td>
			</tr>
			<tr valign="top">
				<th scope="row">Notification function</th>
				<td>
					<?php $is_notification_enabled = get_option( 'auc_is_notification_enabled', '1' ); ?>
					<label><input name="auc_is_notification_enabled"
					              type="radio"
					              size="50"
					              value="1" <?php if ( $is_notification_enabled != '0' ) {
							echo 'checked';
						} ?>
						/>Enabled</label>
					<label><input
								name="auc_is_notification_enabled"
								type="radio"
								style="margin-left: 2em"
								size="50"
								value="0" <?php if ( $is_notification_enabled == '0' ) {
							echo 'checked';
						} ?>
						/>Disabled</label>
				<td>
			</tr>
			<tr valign="top">
				<th scope="row">Login Monitor function</th>
				<td>
					<?php $is_loginmonitor_enabled = get_option( 'auc_is_loginmonitor_enabled', '1' ); ?>
					<label><input name="auc_is_loginmonitor_enabled"
					              type="radio"
					              size="50"
					              value="1" <?php if ( $is_loginmonitor_enabled == '1' ) {
							echo 'checked';
						} ?>
						/>Enabled</label>
					<label><input
								name="auc_is_loginmonitor_enabled"
								type="radio"
								style="margin-left: 2em"
								size="50"
								value="0" <?php if ( $is_loginmonitor_enabled != '1' ) {
							echo 'checked';
						} ?>
						/>Disabled</label>
				<td>
			</tr>
		</table>

		<?php submit_button(); ?>

	</form>
</div>