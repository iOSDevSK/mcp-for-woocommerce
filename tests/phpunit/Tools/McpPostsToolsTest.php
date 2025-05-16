<?php
/**
 * Test class for McpPostsTools
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpPostsTools;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Test class for McpPostsTools
 */
final class McpPostsToolsTest extends WP_UnitTestCase {

	/**
	 * The MCP instance.
	 *
	 * @var WpMcp
	 */
	private WpMcp $mcp;

	/**
	 * The admin user.
	 *
	 * @var WP_User
	 */
	private WP_User $admin_user;

	/**
	 * Set up before any tests run.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Enable MCP in settings before any tests run.
		update_option(
			'wordpress_mcp_settings',
			array(
				'enabled' => true,
			)
		);
	}

	/**
	 * Set up the test.
	 */
	public function set_up(): void {
		parent::set_up();

		// Create an admin user.
		$this->admin_user = $this->factory->user->create_and_get(
			array(
				'role' => 'administrator',
			)
		);

		$settings = get_option( 'wordpress_mcp_settings', array() );
		error_log( 'Settings: ' . $settings['enabled'] );

		// Get the MCP instance.
		$this->mcp = WpMcp::instance();

		// Initialize WordPress core.
		do_action( 'init' );

		// Initialize the REST API and MCP.
		do_action( 'rest_api_init' );
	}

	/**
	 * Test the wp_posts_search tool.
	 */
	public function test_wp_posts_search_tool(): void {

		error_log( 'Start the test' );

		// Debug log the registered tools.
		error_log( 'Registered tools: ' . print_r( $this->mcp->get_tools(), true ) );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_posts_search',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
	}

	/**
	 * Test the wp_get_post tool.
	 */
	public function test_wp_get_post_tool(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test Content',
				'post_status'  => 'publish',
			)
		);

		error_log( 'Post ID: ' . $post_id );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_get_post',
					'arguments' => array(
						'id' => $post_id,
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// error_log( 'Response: ' . print_r( $response, true ) );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'Test Post', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_add_post tool.
	 */
	public function test_wp_add_post_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_add_post',
					'arguments' => array(
						'title'   => 'New Test Post',
						'content' => '<!-- wp:paragraph --><p>New Test Content</p><!-- /wp:paragraph -->',
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'New Test Post', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_update_post tool.
	 */
	public function test_wp_update_post_tool(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Original Title',
				'post_content' => 'Original Content',
				'post_status'  => 'publish',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_update_post',
					'arguments' => array(
						'id'      => $post_id,
						'title'   => 'Updated Title',
						'content' => '<!-- wp:paragraph --><p>Updated Content</p><!-- /wp:paragraph -->',
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'Updated Title', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_delete_post tool.
	 */
	public function test_wp_delete_post_tool(): void {
		// Create a test post.
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Post to Delete',
				'post_content' => 'Content to Delete',
				'post_status'  => 'publish',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_delete_post',
					'arguments' => array(
						'id' => $post_id,
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );

		// Verify the post is deleted.
		$this->assertNull( get_post( $post_id ) );
	}

	/**
	 * Test the wp_list_categories tool.
	 */
	public function test_wp_list_categories_tool(): void {
		// Create a test category.
		$this->factory->category->create(
			array(
				'name' => 'Test Category',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_list_categories',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'Test Category', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_add_category tool.
	 */
	public function test_wp_add_category_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_add_category',
					'arguments' => array(
						'name' => 'New Test Category',
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'New Test Category', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_list_tags tool.
	 */
	public function test_wp_list_tags_tool(): void {
		// Create a test tag.
		$this->factory->tag->create(
			array(
				'name' => 'Test Tag',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_list_tags',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'Test Tag', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_add_tag tool.
	 */
	public function test_wp_add_tag_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_add_tag',
					'arguments' => array(
						'name' => 'New Test Tag',
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );
		$this->assertStringContainsString( 'New Test Tag', $response->get_data()['content'][0]['text'] );
	}
}
