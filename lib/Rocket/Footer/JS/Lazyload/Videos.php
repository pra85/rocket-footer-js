<?php


namespace Rocket\Footer\JS\Lazyload;


use Rocket\Footer\JS\DOMElement;

class Videos extends LazyloadAbstract {
	/**
	 * @param string  $content
	 *
	 * @param  string $src
	 *
	 * @return void
	 */
	protected function do_lazyload( $content, $src ) {

	}

	protected function after_do_lazyload() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$a3_lazy_load_global_settings = $this->a3_lazy_load_global_settings;
		if ( ! $a3_lazy_load_global_settings['a3l_apply_to_videos'] ) {
			return;
		}
		$oembed = _wp_oembed_get_object();
		$tags   = $this->get_tag_collection( 'iframe' );
		foreach ( $tags as $tag ) {
			if ( $this->is_no_lazyload( $tag ) ) {
				continue;
			}
			$src      = $tag->getAttribute( 'data-src' );
			$data_src = true;
			if ( empty( $src ) ) {
				$src      = $tag->getAttribute( 'src' );
				$data_src = false;
			}
			$original_src = $src;
			$src          = $this->maybe_translate_url( $src );
			$info         = $oembed->get_data( $src );

			$classes = $tag->getAttribute( 'class' );
			$classes = explode( ' ', $classes );
			$classes = array_map( 'trim', $classes );
			$classes = array_filter( $classes );

			$classes [] = 'lazyloaded-video';

			$tag->setAttribute( ( $data_src ? 'data-' : '' ) . 'src', $this->maybe_set_autoplay( $original_src, $tag ) );
			if ( ! empty( $info ) && 'video' === $info->type ) {
				$thumbnail_url = $this->maybe_translate_thumbnail_url( $info->thumbnail_url );
				$img           = $this->create_tag( 'img' );
				$img->setAttribute( 'data-src', $this->plugin->util->download_remote_file( $thumbnail_url ) );
				$img->setAttribute( 'width', $info->thumbnail_width );
				$img->setAttribute( 'data-lazy-video-embed', "lazyload-video-{$this->instance}" );
				$img->setAttribute( 'data-lazy-video-embed-type', $this->get_video_type( $src ) );

				$video_id = $this->get_video_id( $src );

				if ( ! empty( $video_id ) ) {
					$img->setAttribute( 'class', "video-id-{$video_id}" );
				}

				$linked = preg_grep( '/video-size-linked-to-[\w\__]+/', $classes );

				if ( ! empty( $linked ) ) {
					$linked_id = str_replace( 'video-size-linked-to-', '', end( $linked ) );
					$img->setAttribute( 'data-size-linked-to', $linked_id );
					unset( $classes[ array_search( $classes, end( $linked ) ) ] );
				}

				$tag->parentNode->insertBefore( $img, $tag );
				$this->lazyload_script( $this->get_tag_content( $tag ), "lazyload-video-{$this->instance}", $tag );
				$tags->flag_removed();
				$this->instance ++;
			}

			$tag->setAttribute( 'class', implode( ' ', $classes ) );
		}
		$tags = $this->get_tag_collection( 'source' );
		foreach ( $tags as $tag ) {
			if ( $this->is_no_lazyload( $tag ) ) {
				continue;
			}
			$src = $tag->getAttribute( 'src' );
			if ( empty( $src ) ) {
				continue;
			}
			$tag->setAttribute( 'data-src', $src );
			$tag->removeAttribute( 'src' );
		}
	}

	/**
	 * @param string $url
	 */
	private function maybe_translate_url( $url ) {
		$url = set_url_scheme( $url );
		$url = parse_url( $url );
		if ( 'youtube.com' === $url['host'] || 'www.youtube.com' === $url['host'] ) {
			if ( false !== strpos( $url['path'], 'embed' ) ) {
				$video_id = pathinfo( $url['path'], PATHINFO_FILENAME );

				$url['path']  = '/watch';
				$url['query'] = http_build_query( [ 'v' => $video_id ] );
			}
		}
		$url = http_build_url( $url );

		return $url;
	}

	private function maybe_set_autoplay( $url, DOMElement $tag ) {
		$url = set_url_scheme( $url );
		$url = parse_url( $url );
		if ( in_array( $url['host'], [ 'youtube.com', 'www.youtube.com', 'player.vimeo.com' ] ) ) {
			$query = [];
			parse_str( $url['query'], $query );

			$url['query'] = http_build_query( array_merge( $query, [ 'autoplay' => '1' ] ) );
			$allow        = explode( ';', $tag->getAttribute( 'allow' ) );
			$allow        = array_map( 'trim', $allow );
			$allow        = array_filter( $allow );
			$allow[]      = 'autoplay';
			$allow        = array_unique( $allow );
			$tag->setAttribute( 'allow', implode( ';', $allow ) );
		}
		$url = http_build_url( $url );

		return $url;
	}

	private function maybe_translate_thumbnail_url( $url ) {
		$url  = set_url_scheme( $url );
		$url  = parse_url( $url );
		$urls = [];
		if ( 'i.ytimg.com' === $url['host'] ) {
			$size_url = $url;
			$video_id = basename( pathinfo( $url['path'], PATHINFO_DIRNAME ) );
			foreach ( [ 'maxresdefault', 'hqdefault', 'sddefault', 'mqdefault' ] as $size ) {
				$size_url['path'] = "/vi/{$video_id}/{$size}.jpg";
				$urls[]           = http_build_url( $size_url );
				$size_url['path'] = "/vi/{$video_id}/{$size}.webp";
				$urls[]           = http_build_url( $size_url );
			}
		}
		if ( ! empty( $urls ) ) {
			return $urls;
		}
		$url = http_build_url( $url );

		return $url;
	}

	private function get_video_type( $url ) {
		$url  = parse_url( $url );
		$type = null;
		if ( 'youtube.com' === $url['host'] || 'www.youtube.com' === $url['host'] ) {
			$type = 'youtube';
		}
		if ( null !== $type ) {
			return $type;
		}

		return 'generic';
	}

	private function get_video_id( $url ) {
		$url = parse_url( $url );
		if ( 'youtube.com' === $url['host'] || 'www.youtube.com' === $url['host'] ) {
			if ( false !== strpos( $url['path'], 'embed' ) ) {
				return pathinfo( $url['path'], PATHINFO_FILENAME );
			}
			$query = [];
			parse_str( $url['query'], $query );

			return $query['v'];
		}

		return false;
	}

	protected function is_match( $content, $src ) {
		return false;
	}
}
