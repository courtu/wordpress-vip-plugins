<div class="wrap apple-news-json">
	<h1 id="apple_news_json_title"><?php esc_html_e( 'Customize Component JSON', 'apple-news' ) ?></h1>

	<form method="post" action="" id="apple-news-json-form">
		<input type="hidden" id="apple_news_action" name="apple_news_action" value="apple_news_save_json" />
		<?php wp_nonce_field( 'apple_news_json' ); ?>

		<?php if ( empty( $components ) ) : ?>
		<h2><?php esc_html_e( 'No components are available for customizing JSON', 'apple-news' ) ?></h2>
		<?php else : ?>
			<p><?php echo wp_kses(
				sprintf(
					__( 'Select a component to customize any of the specs for its JSON snippets. This will enable to you create advanced templates beyond what is supported by <a href="%s">themes</a>.', 'apple-news' ),
					esc_url( $theme_admin_url )
				),
				array(
					'a' => array(
						'href' => array()
					)
				)
			) ?></p>
			<p><?php esc_html_e( 'Tokens that will be replaced by dynamic values based on theme or post settings are denoted as #token#. You may remove tokens to suit your custom JSON but you cannot add new ones.', 'apple-news' ) ?></p>
			<p><?php echo wp_kses(
				sprintf(
					__( 'For more information on the Apple News format options for each component, please read the <a href="%s">Apple News Format Reference</a>.', 'apple-news' ),
					'https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/Component.html#//apple_ref/doc/uid/TP40015408-CH5-SW1'
				),
				array(
					'a' => array(
						'href' => array()
					)
				)
			) ?></p>
			<select id="apple_news_component" name="apple_news_component">
				<option name=""><?php esc_html_e( 'Select a component', 'apple-news' ) ?></option>
				<?php foreach ( $components as $component_key => $component_name ) : ?>
					<option value="<?php echo esc_attr( $component_key ) ?>" <?php selected( $component_key, $selected_component ) ?>><?php echo esc_html( $component_name ) ?></option>
				<?php endforeach; ?>
			</select>

			<?php if ( ! empty( $specs ) ) : ?>
				<?php foreach ( $specs as $spec ) :
					$field_name = $spec->key_from_name( $spec->name );
					$json_display = $spec->format_json( $spec->get_spec() );
					$rows = substr_count( $json_display, "\n" ) + 1;
					$editor_name = 'editor_' . str_replace( '-', '_', $field_name );
					$editor_style = sprintf(
						'width: %spx; height: %spx',
						500,
						absint( 17 * $rows )
					);
					?>
					<p>
						<label for="<?php echo esc_attr( $field_name ) ?>"><?php echo esc_html( $spec->label ) ?></label>
						<div id="<?php echo esc_attr( $editor_name ) ?>" style="<?php echo esc_attr( $editor_style ) ?>"></div>
						<textarea id="<?php echo esc_attr( $field_name ) ?>" name="<?php echo esc_attr( $field_name ) ?>"><?php echo esc_textarea( $json_display ) ?></textarea>
						<script type="text/javascript">
							var <?php echo esc_js( $editor_name ) ?> = ace.edit( '<?php echo esc_js( $editor_name ) ?>' );
							jQuery( function() {
								jQuery( '#<?php echo esc_js( $field_name ) ?>' ).hide();
								<?php echo esc_js( $editor_name ) ?>.setTheme( '<?php echo esc_js( apply_filters( 'apple_news_json_editor_ace_theme', 'ace/theme/textmate', $selected_component, $field_name ) ) ?>' );
								<?php echo esc_js( $editor_name ) ?>.getSession().setMode( 'ace/mode/json' );
								<?php echo esc_js( $editor_name ) ?>.getSession().setTabSize( 2 );
								<?php echo esc_js( $editor_name ) ?>.getSession().setUseSoftTabs( false );
								<?php echo esc_js( $editor_name ) ?>.setReadOnly( false );
								<?php echo esc_js( $editor_name ) ?>.getSession().setUseWrapMode( true );
								<?php echo esc_js( $editor_name ) ?>.getSession().setValue( jQuery( '#<?php echo esc_js( $field_name ) ?>' ).val() );
								<?php echo esc_js( $editor_name ) ?>.getSession().on( 'change', function() {
									jQuery( '#<?php echo esc_js( $field_name ) ?>' ).val( <?php echo esc_js( $editor_name ) ?>.getSession().getValue() );
								} );
							} );
						</script>
					</p>
				<?php endforeach; ?>
			<?php endif; ?>

		<?php endif; ?>

		<?php
			if ( ! empty( $selected_component ) ) {
				submit_button(
					__( 'Save JSON', 'apple-news' ),
					'primary',
					'apple_news_save_json',
					false
				);
				submit_button(
					__( 'Reset JSON', 'apple-news' ),
					'delete',
					'apple_news_reset_json',
					false
				);
			}
		?>
	</form>
</div>
