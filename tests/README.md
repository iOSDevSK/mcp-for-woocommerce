# Tests

- `e2e/` — end-to-end checks of the public Streamable HTTP endpoint against a real
  WordPress + WooCommerce install. This is the suite to run before a release; see
  `e2e/README.md`.
- `phpunit/` — PHPUnit tests for tool registration. They need the WordPress test
  library and dev dependencies, which are not installed in this repository.

Versions before 1.3.0 also had PHPUnit suites for JWT authentication and the
authenticated transports. Authentication was removed in 1.3.0 together with those
suites; the endpoint is now public and read-only.
