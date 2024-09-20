<?php
/**
 * ApiEmailTest
 * Unit test for api_email.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use Services\Email\Client\EmailClientFactory;
use Services\Email\Client\MorpheusPHPMailerSMTPClient;
use testing\unit\Services\Email\Client\MorpheusPHPMailerSMTPClientTest;

/**
 * Api DialPlans Unit Test class
 */
class ApiEmailUnitTest extends AbstractPhpunitUnitTest
{
	public static $privateKey = "-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAO/Kz0dYLMFsK3JT
2Cp33LJEa3N8lUMyGQrJ8hGNB/DsFiZfVSZpqtvcnDQb6LqnZQrm2a65sIi6xz4U
kMpa/EhCattHk1zwwZNOFzLP8iFEfpK1zmwGrT1R6j/3HR8jy/S1sUYb59jmBNVf
U7bCUHme5Te7HA/fqqrD5gsP8/3dAgMBAAECgYEAmRZxnqq8aAAW/LZqmzJKw8TK
lMBEytGBC1JCKNJQ747J1VWnlw5+9j6xutLWkdOsvnkDIHmMKr6T1R5sEcRkqsDa
q6cl3T+zJBQbXRPQa+ssW/AIn9RsFU7k6q4FhKK/+yLoBw9LKKxQymDjcLtmnw8E
YEmYg93MbDrCeU8cgEECQQD6yE0ofRF7pAuabbl2GnNVK0ZiALQJZMqjKgfpmieB
0kyEYE4LabdRciydreAX4LzzknYJAo4zN8vd4B76caLLAkEA9Mf5HIhRTBgfZb1W
/oUnc6GScYlKKtR6i4sZr9K8pfXzKfRlRbwmIsJLQTh6WzFpIPlhktEUFqo4Cdx8
qvZE9wJBAIZita4frzGxS6J6b+rQ68LVCMdVlyR9hXT//fN5bvhLaEN7k/bbtKeJ
Zk0ssqw4+ygO8P+NBgR+Ptnr0s3j/RcCQCqG25bDcCxiPII2hPivNY13UrP7ADG0
vJ2lyw6q9C95LYfqd/XHnHCJhP2NzGQvj241JWiWfoD5jcbY0af6JT0CQQC/mbz+
ETiMun0fSDJemJ5+I9fhyvQXb4oTZGem2l4ntmNMKlg47OIISVbfW6ZxC7sa5xg/
/l020YF/RJdYeogZ
-----END PRIVATE KEY-----";

	public static $publicKey = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDvys9HWCzBbCtyU9gqd9yyRGtz
fJVDMhkKyfIRjQfw7BYmX1Umaarb3Jw0G+i6p2UK5tmuubCIusc+FJDKWvxIQmrb
R5Nc8MGTThcyz/IhRH6Stc5sBq09Ueo/9x0fI8v0tbFGG+fY5gTVX1O2wlB5nuU3
uxwP36qqw+YLD/P93QIDAQAB
-----END PUBLIC KEY-----";

	private $rand = '123abc';
	private $microtime = '1234.56';
	private $date = 'Fri, 12 Oct 2018 12:00:00 +1000';

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!defined("HOSTNAME")) {
			define("HOSTNAME", "localhost");
		}
		if (!defined('EMAIL_DEFAULT_DOMAIN')) {
			define('EMAIL_DEFAULT_DOMAIN', 'reachtel.com.au');
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_email_filetype_data() {
		return [
			// default value
			['application/octet-stream', 'whatever'],
			['application/octet-stream', 'whatever.png.bak'],

			// default value
			['image/png', 'image.png'],
			['image/jpeg', 'image.jpg'],
			['image/gif', 'image.gif'],
			['application/octet-stream', 'file.csv'],
			['application/vnd.ms-excel', 'file.xls'],
			['application/vnd.ms-powerpoint', 'file.ppt'],
			['application/msword', 'file.doc'],
			['application/pdf', 'file.pdf'],
			['application/zip', 'file.zip'],
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_email_prepare_for_queue_data() {
		// rand and microtime are mocked to provide a consistent boundary
		$boundary = '=_' . md5($this->rand . $this->microtime);
		$default_options = [
			'to' => 'bob@bob.com',
			'cc' => 'cc@bob.com',
			'bcc' => 'bcc@bob.com',
			'from' => 'from@bob.com',
			'subject' => 'Test Subject',
			'textcontent' => 'This is the text',
			'htmlcontent' => '<html><body>This is the HTML</body></html>',
			'Message-Id' => '<message-id@broadcast.reachtel.com.au>',
		];

		$default_result = [
			'recipients' => 'bob@bob.com, cc@bob.com, bcc@bob.com',
			'headers' => [
				'MIME-Version' => '1.0',
				'Content-Type' => "multipart/alternative;\r\n boundary=\"$boundary\"",
				'From' => 'from@bob.com',
				'To' => 'bob@bob.com',
				'Subject' => 'Test Subject',
				'Date' => 'Fri, 12 Oct 2018 12:00:00 +1000',
				'X-Report-Abuse-To' => 'support@ReachTEL.com.au',
				'Sender' => 'webapp@broadcast.reachtel.com.au',
				'Message-Id' => '<message-id@broadcast.reachtel.com.au>',
				'Return-Path' => 'webapp@broadcast.reachtel.com.au',
				'Cc' => 'cc@bob.com'
			],
			'body' => str_replace(
				"\n",
				"\r\n",
				<<<EOF
--$boundary
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=utf-8

This is the text
--$boundary
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=utf-8

<html><body>This is the HTML</body></html>
--$boundary--\n
EOF
			),
		];

		$filename = 'file.csv';
		$file_content = "one, two\n1,2";
		$base64_content = base64_encode($file_content);
		$attachment_options = array_merge(
			$default_options,
			[
				'attachments' => [
					[
						'filename' => $filename,
						'content' => $file_content,
					]
				]
			]
		);

		$attachment_result = $default_result;
		$attachment_result['headers']['Content-Type'] = "multipart/mixed;\r\n boundary=\"$boundary\"";
		$attachment_result['body'] = str_replace(
			"\n",
			"\r\n",
			<<<EOF
--$boundary
Content-Type: multipart/alternative;
 boundary="$boundary"

--$boundary
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=utf-8

This is the text
--$boundary
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=utf-8

<html><body>This is the HTML</body></html>
--$boundary--

--$boundary
Content-Transfer-Encoding: base64
Content-Type: application/octet-stream;
 name=$filename
Content-Disposition: attachment;
 filename=$filename;
 size=12

$base64_content
--$boundary--\n
EOF
		);

		return [
			[$default_options, 	$default_result],
			[$attachment_options, 	$attachment_result],
		];
	}

	/**
	 * @dataProvider api_email_filetype_data
	 * @param mixed  $expected_value
	 * @param string $filename
	 * @return void
	 */
	public function test_api_email_filetype($expected_value, $filename) {
		$this->assertSameEquals($expected_value, api_email_filetype($filename));
	}

	/**
	 * @dataProvider api_email_prepare_for_queue_data
	 * @param array 		$email
	 * @param array|boolean $expected
	 * @return void
	 */
	public function test_api_email_prepare_for_queue(array $email, $expected) {
		$this->mock_function_value('date', $this->date);

		// the boundary is set using microtime and rand
		$this->mock_function_value('microtime', $this->microtime);
		$this->mock_function_value('rand', $this->rand);

		$result = api_email_prepare_for_queue($email);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function test_api_email_generate_message() {
		$this->mock_function_value('date', $this->date);

		// the boundary is set using microtime and rand
		$this->mock_function_value('microtime', $this->microtime);
		$this->mock_function_value('rand', $this->rand);
		$this->mock_function_value('api_email_merge', '<html><body>test content</body></html>');
		$this->mock_function_value('api_misc_uniqueid', '11111');
		$this->mock_function_value('api_data_callresult_add', false);
		$this->mock_function_value('api_data_responses_add', false);
		$this->mock_function_value('api_campaigns_update_lastsend', false);

		$this->mock_function_value('file_get_contents', '<html><body>test content</body></html>');

		$this->mock_function_param_value(
			'api_data_merge_get_single',
			[
				['params' => [101, 1, "rt-email-cc"], 'return' => "cc@test.test"],
				['params' => [101, 1, "rt-email-bcc"], 'return' => "bcc@test.test"],
				['params' => [101, "key123", "rt-remoteattachments"], 'return' => false],
				['params' => [101, "template"], 'return' => "test-campaign-template"],
			],
			[]
		);

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [101, "template"], 'return' => "campaign-attachment-file"],
			],
			[]
		);

		$this->mock_function_param_value(
			'api_emailtemplates_setting_getsingle',
			[
				['params' => ['test-template', "name"], 'return' => "template-file"],
			],
			[]
		);

		$settings = [
			"subject" => "test subject",
			"from" => "Test <test@test.test>",
			"replyto" => "Reply <reply@reply.test>",
			"dkim" => "dkim-selector",
			"groupowner" => 1,
			"removelistunsub" => "on",
			'template' => 'test-template',
		];

		$message = [
			'targetid' => 1,
			'targetkey' => 'key123',
			'subject' => "test subject",
			'campaignid' => 101,
			'destination' => "Test <test-to@test.test>"
		];

		$output = null;
		$closure = function($queue, $email) use (&$output){
			$output = $email;
		};
		runkit_function_redefine("api_queue_add", $closure);

		$details = api_email_generatemessage($message, $settings);
		$this->assertArrayHasKey("recipients", $output);
		$this->assertEquals("Test <test-to@test.test>", $output['recipients']);
		$this->assertEquals("test subject", $output['headers']['Subject']);
		$this->assertEquals("Fri, 12 Oct 2018 12:00:00 +1000", $output['headers']['Date']);
		$this->assertEquals("Reply <reply@reply.test>", $output['headers']['Reply-To']);
	}

	/**
	 * @return void
	 */
	public function test_api_queue_email_send()	{

		$details["recipients"] = [
				"to" => "Test <test-to@test.test>",
				"cc" => "CC test <cctest@test.test>",
				"bcc" => "BCC test <bcctest@test.test>"
		];
		$details["headers"] = [
			"MIME-Version" => "1.0",
			"Content-Type" => 'multipart/alternative;
 boundary="=_3243252f68f0ccc5a965b460f9f547c6"',
			"From" => "Test From <test-from@test.test>",
			"To" => "Test-To <test-to@test.test>",
			"Subject" => "test subject",
			"Date" => "Fri, 12 Oct 2018 12:00:00 +1000",
			"X-Report-Abuse-To" => "support@ReachTEL.com.au",
			"Sender" => "webapp@broadcast.reachtel.com.au",
			"X-Tracking-Id" => "4aeb7ffb498441ac62ce22b8050d443a",
			"Message-Id" => "<1234.56-5daf903f9b601@broadcast.reachtel.com.au>",
			"Return-Path" => "4aeb7ffb498441ac62ce22b8050d443a@fake.broadcast.reachtel.com.au",
			"Reply-To" => "Reply <reply@reply.test>"
		];

		$details["body"] = "--=_3243252f68f0ccc5a965b460f9f547c6
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=utf-8

<html><body>test content</body></html>
--=_3243252f68f0ccc5a965b460f9f547c6
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=utf-8

<html><body>test content</body></html>
--=_3243252f68f0ccc5a965b460f9f547c6--
";
		$details["target"] = [
			"targetid" => 1,
			"targetkey" => "key123",
			"subject" => "test subject",
			"campaignid" => 101,
			"destination" => "Test Destination <test-to@test.test>",
			"eventid" => "11111"
		];

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [101, "dkim"], 'return' => "testselector"],
				['params' => [101, "groupowner"], 'return' => "1"],
			],
			[]
		);

		$this->mock_function_value('api_email_get_dkim_keys', [0 => ["value" => self::$privateKey]]);

		$connectionString = [
			'host' => "mailhog",
			'port' => "1025",
			'pipelining' => true,
			'persist' => true,
			'localhost' => 'localhost',
			'timeout' => 30,
			'socket_options' => array('ssl' => array('verify_peer_name' => false)),
		];

		$params = null;
		$smtp = \Phake::partialMock(\Mail_smtp::class, $connectionString);
		\Phake::when($smtp)->send(\Phake::anyParameters())->thenReturn(true);
		$details['smtp_connection'] = $smtp;

		$this->assertTrue(api_queue_process_email($details));
	}

	/**
	 * @return void
	 */
	public function test_api_queue_email_send_system() {

		$details["recipients"] = [
			"to" => "Test <test-to@test.test>",
			"cc" => "CC test <cctest@test.test>",
			"bcc" => "BCC test <bcctest@test.test>"
		];
		$details["headers"] = [
			"MIME-Version" => "1.0",
			"Content-Type" => 'multipart/alternative;
 boundary="=_3243252f68f0ccc5a965b460f9f547c6"',
			"From" => "Test From <test-from@reachtel.com.au>",
			"To" => "Test-To <test-to@test.test>",
			"Subject" => "test subject",
			"Date" => "Fri, 12 Oct 2018 12:00:00 +1000",
			"X-Report-Abuse-To" => "support@ReachTEL.com.au",
			"Sender" => "webapp@broadcast.reachtel.com.au",
			"X-Tracking-Id" => "4aeb7ffb498441ac62ce22b8050d443a",
			"Message-Id" => "<1234.56-5daf903f9b601@broadcast.reachtel.com.au>",
			"Return-Path" => "4aeb7ffb498441ac62ce22b8050d443a@fake.broadcast.reachtel.com.au",
			"Reply-To" => "Reply <reply@reply.test>"
		];

		$details["body"] = "--=_3243252f68f0ccc5a965b460f9f547c6
Content-Transfer-Encoding: quoted-printable
Content-Type: text/plain; charset=utf-8

<html><body>test content</body></html>
--=_3243252f68f0ccc5a965b460f9f547c6
Content-Transfer-Encoding: quoted-printable
Content-Type: text/html; charset=utf-8

<html><body>test content</body></html>
--=_3243252f68f0ccc5a965b460f9f547c6--
";

		$this->mock_function_value("api_data_responses_add", true);

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [101, "dkim"], 'return' => false],
				['params' => [101, "groupowner"], 'return' => "1"],
			],
			[]
		);

		$this->mock_function_param_value(
			'api_system_setting_getsingle',
			[
				['params' => ["EMAIL_DEFAULT_DKIM_SELECTOR"], 'return' => "systemselector"],
			],
			[]
		);

		$this->mock_function_value('api_email_get_dkim_keys', [0 => ["value" => self::$privateKey]]);

		$connectionString = [
			'host' => "mailhog",
			'port' => "1025",
			'pipelining' => true,
			'persist' => true,
			'localhost' => 'localhost',
			'timeout' => 30,
			'socket_options' => array('ssl' => array('verify_peer_name' => false)),
		];

		$params = null;
		$smtp = \Phake::mock(\Mail_smtp::class, $params);

		$details['smtp_connection'] = $smtp;

		$this->assertTrue(api_queue_process_email($details));
		$headers = null;
		$body = null;
		\Phake::verify($smtp)->send(\Phake::captureAll($recipients), \Phake::capture($header), \Phake::capture($body));
		$this->assertContains("d=reachtel.com.au;", $header['DKIM-Signature']);
		$this->assertContains("s=systemselector;", $header['DKIM-Signature']);
	}

	/**
	 * @return array
	 */
	public function api_email_extract_domain_provider() {
		return [
			["test@reachtel.com.au", "reachtel.com.au"],
			["\<Support Test\> test@reachtel.com.au", "reachtel.com.au"],
			["simple@example.com", "example.com"],
			["very.common@example.com", "example.com"],
			["disposable.style.email.with+symbol@example.com", "example.com"],
			["other.email-with-hyphen@example.com", "example.com"],
			["fully-qualified-domain@example.com", "example.com"],
			["user.name+tag+sorting@example.com", "example.com"],
			["x@example.com", "example.com"],
			["example-indeed@strange-example.com", "strange-example.com"],
			["example@s.example", "s.example"],
			["john..doe\"@example.org", "example.org"],
			["mailhost!username@example.org", "example.org"],
			["user%example.com@example.org", "example.org"],
		];
	}

	/**
	 * @dataProvider api_email_extract_domain_provider
	 * @param string $email
	 * @param string $domain
	 * @return void
	 */
	public function test_api_email_extract_domain($email, $domain) {
		$this->assertEquals($domain, api_email_extract_domain($email));
	}
}
