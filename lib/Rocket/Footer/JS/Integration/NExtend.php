<?php


namespace Rocket\Footer\JS\Integration;


class NExtend extends IntegrationAbstract {

	public function init() {
		if ( class_exists( 'N2Pluggable' ) ) {
			\N2Pluggable::addAction( 'systemglobal', [ $this, 'disable' ] );
			\N2Settings::init();
		}
	}

	public function disable( $referenceKey, &$rows ) {
		/** @noinspection ReferenceMismatchInspection */
		foreach ( array_keys( $rows ) as $key ) {
			if ( in_array( $rows[ $key ]['referencekey'], [
				'async',
				'combine-js',
				'minify-js',
				'protocol-relative',
				'curl',
			] ) ) {
				$rows[ $key ]['value'] = 0;
			}
		}
	}
}