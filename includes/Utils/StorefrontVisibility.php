<?php
declare(strict_types=1);

namespace McpForWoo\Utils;

/**
 * Decides whether a product or post may be returned by the public MCP endpoint.
 *
 * The endpoint does not authenticate callers, so it may only return what an
 * anonymous shop visitor can already see on the storefront. Every tool that
 * looks up an object by ID runs it through these checks first.
 */
class StorefrontVisibility {

	/**
	 * Whether a product is visible to an anonymous shop visitor.
	 *
	 * A variation is visible when it is published and its parent product is.
	 *
	 * @param mixed $product The product.
	 * @return bool
	 */
	public static function is_product_public( $product ): bool {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		if ( $product->is_type( 'variation' ) ) {
			if ( 'publish' !== $product->get_status() ) {
				return false;
			}
			return self::is_product_public( wc_get_product( $product->get_parent_id() ) );
		}

		if ( 'publish' !== $product->get_status() || '' !== (string) $product->get_post_password() ) {
			return false;
		}

		return 'hidden' !== $product->get_catalog_visibility();
	}

	/**
	 * Whether a post or page is visible to an anonymous visitor.
	 *
	 * @param mixed  $post      The post.
	 * @param string $post_type The expected post type.
	 * @return bool
	 */
	public static function is_post_public( $post, string $post_type ): bool {
		return $post instanceof \WP_Post
			&& $post_type === $post->post_type
			&& 'publish' === $post->post_status
			&& '' === $post->post_password;
	}
}
