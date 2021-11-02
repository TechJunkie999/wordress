<?php
namespace YaySMTPAmazonSES\Controller;

use YaySMTPAmazonSES\Helper\LogErrors;
use YaySMTPAmazonSES\Helper\Utils;

if (!defined('ABSPATH')) {
  exit;
}

class AmazonSESController {
  public $smtpObj;

  public function __construct($smtpObj) {
    $this->smtpObj = $smtpObj;
    // Set wp_mail_from && wp_mail_from_name - start
    $from_email = Utils::getCurrentFromEmail();
    $from_name = Utils::getCurrentFromName();
    $smtpObj->setFrom($from_email, $from_name, false);
    // Set wp_mail_from && wp_mail_from_name - end
  }

  /**
   * Use Amazon SES API Services to send emails.
   */
  public function send() {
    $emailTo = array();
    if (!empty($this->smtpObj->getToAddresses()) && is_array($this->smtpObj->getToAddresses())) {
      foreach ($this->smtpObj->getToAddresses() as $toEmail) {
        if (!empty($toEmail[0])) {
          $emailTo[] = $toEmail[0];
        }
      }
    }

    $dataLogsDB = array(
      'subject' => $this->smtpObj->Subject,
      'email_from' => $this->smtpObj->From,
      'email_to' => $emailTo, // require is array
      'mailer' => 'AmazonSES',
      'date_time' => current_time('mysql'),
      'status' => 0, // 0: false, 1: true, 2: waiting
      'content_type' => $this->smtpObj->ContentType,
      'body_content' => $this->smtpObj->Body,
    );

    try {
      $data = array(
        'RawMessage' => array(
          'Data' => $this->prepare(),
        ),
      );

      $amazonSevice = new AmazonWebServicesController();
      $result = $amazonSevice->getClient()->sendRawEmail($data);
      $messId = $result->get('MessageId');

      $sent = false;
      if (!empty($messId)) {
        $sent = true;
      }
      if ($sent) {
        LogErrors::clearErr();

        $dataLogsDB['date_time'] = current_time('mysql');
        $dataLogsDB['status'] = 1;
        Utils::insertEmailLogs($dataLogsDB);
      } else {
        $dataLogsDB['date_time'] = current_time('mysql');
        Utils::insertEmailLogs($dataLogsDB);
      }

      return $sent;

    } catch (\Exception $e) {
      $this->errMess = $e->getAwsErrorMessage();
      LogErrors::clearErr();
      LogErrors::setErr('Mailer: Amazon SES');
      LogErrors::setErr($this->errMess);
      return;
    }
  }

  private function prepare() {
    try {
      $this->smtpObj->preSend();
    } catch (\Exception $exception) {
      return $exception;
    }

    return $this->smtpObj->getSentMIMEMessage();
  }
}
