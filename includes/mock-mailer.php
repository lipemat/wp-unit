<?php
require_once ABSPATH . 'wp-includes/PHPMailer/PHPMailer.php';
require_once ABSPATH . 'wp-includes/PHPMailer/Exception.php';

/**
 * @author Mat Lipe
 * @since  March 2025
 *
 * @phpstan-type SENT object{
 *     to: array<int, array{0: string, 1: string}>,
 *     cc: array<int, array{0: string, 1: string}>,
 *     bcc: array<int, array{0: string, 1: string}>,
 *     header: string,
 *     subject: string,
 *     body: string
 * }
 */
class MockPHPMailer extends PHPMailer\PHPMailer\PHPMailer {
	public array $mock_sent = [];

	public function preSend() {
		$this->Encoding = '8bit';
		return parent::preSend();
	}

	/**
	 * Override postSend() so mail isn't actually sent.
	 */
	public function postSend() {
		$this->mock_sent[] = array(
			'to'      => $this->to,
			'cc'      => $this->cc,
			'bcc'     => $this->bcc,
			'header'  => $this->MIMEHeader . $this->mailHeader,
			'subject' => $this->Subject,
			'body'    => $this->MIMEBody,
		);

		return true;
	}

	/**
	 * Decorator to return the information for a sent mock.
	 *
	 * @since 4.5.0
	 *
	 * @param int $index Optional. Array index of mock_sent value.
	 * @param key-of<SENT> $fields Optional. Limit results to specified fields.
	 *
	 * @return SENT|bool
	 */
	public function get_sent( $index = 0, $fields = [] ) {
		if ( isset( $this->mock_sent[ $index ] ) ) {
			$sent = $this->mock_sent[ $index ];
			if ( [] !== $fields ) {
				$sent = \array_intersect_key( $sent, \array_flip( $fields ) );
			}
			return (object) $sent;
		}
		return false;
	}

	/**
	 * Get a recipient for a sent mock.
	 *
	 * @since 4.5.0
	 *
	 * @param 'to'|'bcc'|'cc' $address_type The type of address for the email.
	 * @param int    $mock_sent_index Optional. The sent_mock index we want to get the recipient for.
	 * @param int    $recipient_index Optional. The recipient index in the array.
	 *
	 * @return bool|object{address: string, name: string} Returns object on success, or false if any of the indices don't exist.
	 */
	public function get_recipient( string $address_type, int $mock_sent_index = 0, int $recipient_index = 0 ) {
		$retval = false;
		$mock = $this->get_sent( $mock_sent_index );
		if ( \is_object( $mock ) ) {
			if ( 'to' === $address_type ) {
				$address_index = $mock->to[ $recipient_index ] ?? [];
			} elseif ( 'cc' === $address_type ) {
				$address_index = $mock->cc[ $recipient_index ] ?? [];
			} else {
				$address_index = $mock->bcc[ $recipient_index ] ?? [];
			}
			$recipient_data = [
				'address' => ( isset( $address_index[0] ) && '' !== $address_index[0] ) ? $address_index[0] : 'No address set',
				'name'    => ( isset( $address_index[1] ) && '' !== $address_index[1] ) ? $address_index[1] : 'No name set',
			];
			$retval = (object) $recipient_data;
		}

		return $retval;
	}
}

/**
 * Helper method to return the global phpmailer instance defined in the bootstrap
 *
 * @since 4.4.0
 *
 * @return MockPHPMailer|false
 */
function tests_retrieve_phpmailer_instance() {
	$mailer = false;
	if ( isset( $GLOBALS['phpmailer'] ) ) {
		$mailer = $GLOBALS['phpmailer'];
	}
	return $mailer;
}

/**
 * Helper method to reset the phpmailer instance.
 *
 * @since 4.3.0
 *
 * @return bool
 */
function tests_reset_phpmailer_instance() {
	$mailer = tests_retrieve_phpmailer_instance();
	if ( $mailer ) {
		$mailer             = new MockPHPMailer( true );
		$mailer::$validator = static function ( $email ) {
			return (bool) is_email( $email );
		};

		$GLOBALS['phpmailer'] = $mailer;
		return true;
	}

	return false;
}

/**
 * @deprecated in favor of tests_reset_phpmailer_instance()
 */
function reset_phpmailer_instance() {
	tests_reset_phpmailer_instance();
}
