<?php
/**
 * Test class for McpPagesTools
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpPagesTools;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Test class for McpPagesTools
 */
final class McpPagesToolsTest extends WP_UnitTestCase {

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

		// Get the MCP instance.
		$this->mcp = WPMCP();

		// Initialize the REST API and MCP.
		do_action( 'rest_api_init' );
	}

	/**
	 * Test the wp_pages_search tool.
	 */
	public function test_wp_pages_search_tool(): void {
		$this->factory->post->create(
			array(
				'post_title'   => 'Test Page',
				'post_content' => 'Test Content',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_pages_search',
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

		// Get the first page from the response.
		$pages = json_decode( $response->get_data()['content'][0]['text'], true );
		$page  = $pages[0];

		// Assert page data.
		$this->assertEquals( 'Test Page', $page['title']['rendered'] );
		$this->assertEquals( '<p>Test Content</p>', trim( $page['content']['rendered'] ) );
		$this->assertEquals( 'publish', $page['status'] );
		$this->assertEquals( 'page', $page['type'] );
	}

	/**
	 * Test the wp_get_page tool.
	 */
	public function test_wp_get_page_tool(): void {
		// Create a test page.
		$page_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Page',
				'post_content' => 'Test Content',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_get_page',
					'arguments' => array(
						'id' => $page_id,
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
		$this->assertStringContainsString( 'Test Page', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_add_page tool.
	 */
	public function test_wp_add_page_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_add_page',
					'arguments' => array(
						'title'   => 'New Test Page',
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
		$this->assertStringContainsString( 'New Test Page', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test the wp_update_page tool.
	 */
	public function test_wp_update_page_tool(): void {
		// Create a test page.
		$page_id = $this->factory->post->create(
			array(
				'post_title'   => 'Original Title',
				'post_content' => 'Original Content',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_update_page',
					'arguments' => array(
						'id'      => $page_id,
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
	 * Test the wp_delete_page tool.
	 */
	public function test_wp_delete_page_tool(): void {
		// Create a test page.
		$page_id = $this->factory->post->create(
			array(
				'post_title'   => 'Page to Delete',
				'post_content' => 'Content to Delete',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_delete_page',
					'arguments' => array(
						'id' => $page_id,
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

		// Verify the page is deleted.
		$page = get_post( $page_id );
		$this->assertNotNull( $page );
		$this->assertEquals( 'trash', $page->post_status );
	}
}
