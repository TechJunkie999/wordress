<?php
namespace YaySMTPAmazonSES;

// use YaySMTPAmazonSES\Controller\GmailServiceVendController;
// use YaySMTPAmazonSES\Controller\ZohoServiceVendController;
use YaySMTPAmazonSES\Helper\Utils;

defined('ABSPATH') || exit;

class PluginCore {
  protected static $instance = null;

  public static function getInstance() {
    if (null == self::$instance) {
      self::$instance = new self;
      self::$instance->doHooks();
    }
    return self::$instance;
  }

  private function doHooks() {
    $this->getProcessor();
    global $phpmailer;
    $phpmailer = new PhpMailerExtends();
    // add_action('init', array($this, 'actionForSmtpsHasAuth'));
  }

  private function __construct() {}

  public function getProcessor() {
    add_action('phpmailer_init', array($this, 'doSmtperInit'));
    // add_filter('wp_mail_from', array($this, 'getFromAddress'));
    // add_filter('wp_mail_from_name', array($this, 'getFromName'));
  }

  // public function actionForSmtpsHasAuth() {
  //   if (is_admin()) {
  //     $currentEmail = Utils::getCurrentMailer();
  //     if ($currentEmail === 'gmail') {
  //       $gmailService = new GmailServiceVendController();
  //       $gmailService->processAuthorizeServive();
  //     } elseif ($currentEmail === 'zoho') {
  //       $zohoServiceVend = new ZohoServiceVendController();
  //       $zohoServiceVend->processAuthorizeServive();
  //     }
  //   }

  // }

  // public function getFromAddress() {
  //   return Utils::getCurrentFromEmail();
  // }

  // public function getFromName($name) {
  //   return Utils::getCurrentFromName();
  // }

  public function doSmtperInit($obj) {
    $currentMailer = Utils::getCurrentMailer();
    $obj->Mailer = $currentMailer;

    // $settings = Utils::getYaySmtpSetting();
    // $smtpSettings = (!empty($settings) && !empty($settings['smtp'])) ? $settings['smtp'] : array();

    // if ('smtp' == $currentMailer) {
    //   if (!empty($smtpSettings['host'])) {
    //     $obj->Host = $smtpSettings['host'];
    //   }

    //   if (!empty($smtpSettings['port'])) {
    //     $obj->Port = (int) $smtpSettings['port'];
    //   }

    //   if (!empty($smtpSettings['encryption'])) {
    //     $obj->SMTPSecure  = $smtpSettings['encryption'];
    //   }

    //   if (!empty($smtpSettings['auth']) && $smtpSettings['auth'] == 'yes') {
    //     $obj->SMTPAuth = true;

    //     if (!empty($smtpSettings['user'])) {
    //       $obj->Username = $smtpSettings['user'];
    //     }

    //     if (!empty($smtpSettings['pass'])) {
    //       $obj->Password = Utils::decrypt($smtpSettings['pass'], 'smtppass');
    //     }
    //   }
    // } else {
    $obj->SMTPSecure  = '';
    // }
  }
}
