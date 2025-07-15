<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Utils;

/**
 * Handle prompt get.
 */
class HandlePromptGet {

	/**
	 * Handle the prompt get.
	 *
	 * @param array $prompt The prompt.
	 * @param array $messages The messages.
	 * @param array $arguments The arguments.
	 * @return array
	 */
	public static function run( array $prompt, array $messages, array $arguments ): array {
		foreach ( $messages as $message_key => $message ) {
			if ( 'text' === $message['content']['type'] && isset( $message['content']['text'] ) ) {
				foreach ( $arguments as $argument_key => $argument_value ) {
					$messages[ $message_key ]['content']['text'] = str_replace( '{{' . $argument_key . '}}', $argument_value, $messages[ $message_key ]['content']['text'] );
				}
			}
		}

		return array(
			'description' => $prompt['description'],
			'messages'    => $messages,
		);
	}
}
