<?php //phpcs:ignore
declare( strict_types=1 );

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class for managing MCP Posts Tools functionality.
 */
class McpPostsTools {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wordpress_mcp_init', array( $this, 'register_tools' ) );
	}

	/**
	 * Register the tools.
	 */
	public function register_tools(): void {
		new RegisterMcpTool(
			array(
				'name'        => 'wp_posts_search',
				'description' => 'Search and filter WordPress posts with pagination',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/posts',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Search Posts',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_get_post',
				'description' => 'Get a WordPress post by ID',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/posts/(?P<id>[\d]+)',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get Post',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_add_post',
				'description' => 'Add a new WordPress post',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'                   => '/wp/v2/posts',
					'method'                  => 'POST',
					'inputSchemaReplacements' => array( // this will replace the defined elements in the default input schema with the new ones.
						'properties' => array(
							'title'   => array(
								'type' => 'string',
							),
							'content' => array(
								'type'        => 'string',
								'description' => 'The content of the post in a valid Guttenberg block format',
							),
							'excerpt' => array(
								'type' => 'string',
							),
						),
						'required'   => array(
							'title',
							'content',
						),
					),
				),
				'annotations' => array(
					'title'           => 'Add Post',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			),
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_post',
				'description' => 'Update a WordPress post by ID',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wp/v2/posts/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update Post',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_delete_post',
				'description' => 'Delete a WordPress post by ID',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wp/v2/posts/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete Post',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// list all categories.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_list_categories',
				'description' => 'List all WordPress post categories',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/categories',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'List Categories',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		// add a new category.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_add_category',
				'description' => 'Add a new WordPress post category',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'  => '/wp/v2/categories',
					'method' => 'POST',
				),
				'annotations' => array(
					'title'           => 'Add Category',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			)
		);

		// update category.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_category',
				'description' => 'Update a WordPress post category',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wp/v2/categories/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update Category',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// delete category.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_delete_category',
				'description' => 'Delete a WordPress post category',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wp/v2/categories/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete Category',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// list all tags.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_list_tags',
				'description' => 'List all WordPress post tags',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/tags',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'List Tags',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		// add new tag.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_add_tag',
				'description' => 'Add a new WordPress post tag',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'  => '/wp/v2/tags',
					'method' => 'POST',
				),
				'annotations' => array(
					'title'           => 'Add Tag',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			)
		);

		// update tag.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_tag',
				'description' => 'Update a WordPress post tag',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wp/v2/tags/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update Tag',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// delete tag.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_delete_tag',
				'description' => 'Delete a WordPress post tag',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wp/v2/tags/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete Tag',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);
	}
}
