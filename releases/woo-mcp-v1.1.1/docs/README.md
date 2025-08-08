# WordPress Model Context Protocol (MCP) Plugin

## Overview

The WordPress MCP plugin enables external AI systems to interact with your WordPress site by defining:

-   **Tools:** Actions the AI can request the site to perform (e.g., `create_post`, `get_user_details`).
-   **Resources:** Data the AI can request from the site (e.g., `list_published_posts`, `get_site_options`).
-   **Prompts:** Pre-defined, structured interaction templates for common AI tasks related to your site.

This plugin implements the server-side MCP endpoint within your WordPress installation.

## Documentation Structure

Detailed documentation for developers:

-   [RegisterMcpTools API](register-mcp-tools.md) - Registering and using MCP tools
-   [RegisterMcpResources API](register-mcp-resources.md) - Registering and using MCP resources
-   [RegisterMcpPrompt API](register-mcp-prompt.md) - Registering and using MCP prompts
-   [Registered Tools](registered-tools.md) - List of default available tools
-   [Registered Resources](registered-resources.md) - List of default available resources
-   [Registered Prompts](registered-prompts.md) - List of default available prompts

## Getting Started

1.  **Download:** Download the latest `.zip` release from the [GitHub Releases page](https://github.com/Automattic/wordpress-mcp/releases).
2.  **Install:** Install and activate the WordPress MCP plugin via your WordPress Admin dashboard (Plugins > Add New > Upload Plugin).
3.  **Configure:** Enable MCP functionality in the plugin settings (WordPress Admin -> Settings -> MCP Settings - _adjust path if needed_).
4.  **Connect a Client:** See the "Connecting MCP Clients" section below.

## Connecting MCP Clients

To interact with your WordPress site via MCP, you need an MCP client application or service. For detailed instructions on connecting your MCP client (e.g., Claude Desktop), check the [connection documentation](https://github.com/Automattic/mcp-wordpress-remote).

## Requirements

-   WordPress 6.4 or higher
-   PHP 8.0 or higher
-   Administrator access to install and configure the plugin.

## Security Considerations

-   **Permissions:** All MCP operations initiated through this plugin require administrator privileges by default within WordPress. Access control relies on standard WordPress user capabilities.
-   **Trust:** Only connect trusted MCP clients to your WordPress site.

## Support
