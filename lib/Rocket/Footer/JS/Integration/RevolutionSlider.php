<?php


namespace Rocket\Footer\JS\Integration;


class RevolutionSlider extends IntegrationAbstract {

	public function init() {
		if ( function_exists( 'rev_slider_shortcode' ) ) {
			add_filter( 'option_revslider-global-settings', [ $this, 'modify_settings' ] );
		}
	}

	public function modify_settings( $options ) {
		$options                        = maybe_unserialize( $options );
		$options['load_all_javascript'] = 'on';

		return $options;
	}

}