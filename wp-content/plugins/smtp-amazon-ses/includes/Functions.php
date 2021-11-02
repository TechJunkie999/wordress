<?php
namespace YaySMTPAmazonSES;

use YaySMTPAmazonSES\Helper\LogErrors;
use YaySMTPAmazonSES\Helper\Utils;

defined('ABSPATH') || exit;

class Functions {
  protected static $instance = null;

  public static function getInstance() {
    if (null == self::$instance) {
      self::$instance = new self;
      self::$instance->doHooks();
    }

    return self::$instance;
  }

  private function doHooks() {
    add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_save_settings', array($this, 'saveSettings'));
    add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_send_mail', array($this, 'sendTestMail'));
    // add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_gmail_remove_auth', array($this, 'gmailRemoveAuth'));
    // add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_yoho_remove_auth', array($this, 'yohoRemoveAuth'));
    add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_email_logs', array($this, 'getListEmailLogs'));
    add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_set_email_logs_setting', array($this, 'setYaySmtpEmailLogSetting'));
    add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_delete_email_logs', array($this, 'deleteEmailLogs'));
    add_action('wp_ajax_' . YAY_SMTP_AMAZONSES_PREFIX . '_detail_email_logs', array($this, 'getEmailLog'));
  }

  private function __construct() {}

  public function saveSettings() {
    try {
      Utils::checkNonce();
      if (isset($_POST['settings'])) {
        $settings = Utils::saniValArray($_POST['settings']);
        $yaysmtpSettingsDB = get_option(YAY_SMTP_AMAZONSES_PREFIX . '_settings');

        $yaysmtpSettings = array();
        if (!empty($yaysmtpSettingsDB) && is_array($yaysmtpSettingsDB)) {
          $yaysmtpSettings = $yaysmtpSettingsDB;

          // Update "succ_sent_mail_last" option to SHOW/HIDE Debug Box on main page.
          if (isset($yaysmtpSettings['currentMailer'])) {
            $currentMailerDB = $yaysmtpSettings['currentMailer'];
            if (!empty($currentMailerDB) && $currentMailerDB != $settings['mailerProvider']) {
              $yaysmtpSettings['succ_sent_mail_last'] = true;
            }
          }
        }

        $yaysmtpSettings['fromEmail'] = $settings['fromEmail'];
        $yaysmtpSettings['fromName'] = $settings['fromName'];
        $yaysmtpSettings['currentMailer'] = $settings['mailerProvider'];
        if (!empty($settings['mailerProvider'])) {
          $mailerSettings = !empty($settings['mailerSettings']) ? $settings['mailerSettings'] : array();

          if (!empty($mailerSettings)) {
            foreach ($mailerSettings as $key => $val) {
              if ($key == "pass") {
                $yaysmtpSettings[$settings['mailerProvider']][$key] = Utils::encrypt($val, 'smtppass');
              } else {
                $yaysmtpSettings[$settings['mailerProvider']][$key] = $val;
              }
            }
          }
        }

        update_option(YAY_SMTP_AMAZONSES_PREFIX . '_settings', $yaysmtpSettings);

        wp_send_json_success(array('mess' => __('Settings saved.', 'yay-smtp-amazonses')));
      }
      wp_send_json_error(array('mess' => __('Settings Failed.', 'yay-smtp-amazonses')));
    } catch (\Exception $ex) {
      LogErrors::getMessageException($ex, true);
    } catch (\Error $ex) {
      LogErrors::getMessageException($ex, true);
    }
  }

  public function sendTestMail() {
    try {
      Utils::checkNonce();
      if (isset($_POST['emailAddress'])) {
        $emailAddress = sanitize_email($_POST['emailAddress']);
        // check email
        if (!is_email($emailAddress)) {
          wp_send_json_error(array('mess' => __('Invalid email format!', 'yay-smtp-amazonses')));
        }

        $headers = "Content-Type: text/html\r\n";
        $subjectEmail = __('YaySMTP - Test email was sent successfully!', 'yay-smtp-amazonses');
        $html = __('Yay! Your test email was sent successfully! Thanks for using <a href="https://yaycommerce.com/yaysmtp-wordpress-mail-smtp/">YaySMTP</a><br><br>Best regards,<br>YayCommerce', 'yay-smtp-amazonses');

        if (!empty($emailAddress)) {
          $sendMailSucc = wp_mail($emailAddress, $subjectEmail, $html, $headers);
          if ($sendMailSucc) {
            Utils::setYaySmtpSetting('succ_sent_mail_last', true);
            wp_send_json_success(array('mess' => __('Email has been sent.', 'yay-smtp-amazonses')));
          } else {
            Utils::setYaySmtpSetting('succ_sent_mail_last', false);
            if (Utils::getCurrentMailer() == "smtp") {
              LogErrors::clearErr();
              LogErrors::setErr('This error may be caused by: Incorrect From email, SMTP Host, Post, Username or Password.');
              $debugText = implode("<br>", LogErrors::getErr());
            } else {
              $debugText = implode("<br>", LogErrors::getErr());
            }
            wp_send_json_error(array('debugText' => $debugText, 'mess' => __('Email sent failed.', 'yay-smtp-amazonses')));
          }
        }
      } else {
        wp_send_json_error(array('mess' => __('Email Address is not empty.', 'yay-smtp-amazonses')));
      }
      wp_send_json_error(array('mess' => __('Error send mail!', 'yay-smtp-amazonses')));
    } catch (\Exception $ex) {
      LogErrors::getMessageException($ex, true);
    } catch (\Error $ex) {
      LogErrors::getMessageException($ex, true);
    }
  }

  // public function gmailRemoveAuth() {
  //   $setting = Utils::getYaySmtpSetting();

  //   if (!empty($setting) && !empty($setting['gmail'])) {
  //     $oldGmailSetting = $setting['gmail'];
  //     foreach ($oldGmailSetting as $key => $val) {
  //       if (!in_array($key, array('client_id', 'client_secret'), true)) {
  //         unset($oldGmailSetting[$key]);
  //       }
  //     }

  //     Utils::setYaySmtpSetting('gmail', $oldGmailSetting);
  //   }

  // }

  // public function yohoRemoveAuth() {
  //   $setting = Utils::getYaySmtpSetting();

  //   if (!empty($setting) && !empty($setting['zoho'])) {
  //     $oldSetting = $setting['zoho'];

  //     foreach ($oldSetting as $key => $val) {
  //       // Unset everything except Client ID and Client Secret.
  //       if (!in_array($key, array('client_id', 'client_secret'), true)) {
  //         unset($oldSetting[$key]);
  //       }
  //     }

  //     Utils::setYaySmtpSetting('zoho', $oldSetting);

  //   }
  // }

  public function getListEmailLogs() {
    try {
      Utils::checkNonce();
      if (isset($_POST['params'])) {
        $params = Utils::saniValArray($_POST['params']);
        global $wpdb;

        $yaySmtpEmailLogSetting = Utils::getYaySmtpEmailLogSetting();
        $showSubjectColumn = isset($yaySmtpEmailLogSetting) && isset($yaySmtpEmailLogSetting['show_subject_cl']) ? (int) $yaySmtpEmailLogSetting['show_subject_cl'] : 1;
        $showToColumn = isset($yaySmtpEmailLogSetting) && isset($yaySmtpEmailLogSetting['show_to_cl']) ? (int) $yaySmtpEmailLogSetting['show_to_cl'] : 1;
        $showStatusColumn = isset($yaySmtpEmailLogSetting) && isset($yaySmtpEmailLogSetting['show_status_cl']) ? (int) $yaySmtpEmailLogSetting['show_status_cl'] : 1;
        $showDatetimeColumn = isset($yaySmtpEmailLogSetting) && isset($yaySmtpEmailLogSetting['show_datetime_cl']) ? (int) $yaySmtpEmailLogSetting['show_datetime_cl'] : 1;
        $showActionColumn = isset($yaySmtpEmailLogSetting) && isset($yaySmtpEmailLogSetting['show_action_cl']) ? (int) $yaySmtpEmailLogSetting['show_action_cl'] : 1;
        $showStatus = isset($yaySmtpEmailLogSetting) && isset($yaySmtpEmailLogSetting['status']) ? $yaySmtpEmailLogSetting['status'] : "all";

        $showColSettings = array(
          'showSubjectCol' => $showSubjectColumn,
          'showToCol' => $showToColumn,
          'showStatusCol' => $showStatusColumn,
          'showDatetimeCol' => $showDatetimeColumn,
          'showActionCol' => $showActionColumn,
        );

        $limit = !empty($params['limit']) && is_numeric($params['limit']) ? (int) $params['limit'] : 10;
        $page = !empty($params['page']) && is_numeric($params['page']) ? (int) $params['page'] : 1;
        $offset = ($page - 1) * $limit;

        $valSearch = !empty($params['valSearch']) ? $params['valSearch'] : "";
        $sortField = !empty($params['sortField']) ? $params['sortField'] : "date_time";
        $sortVal = "DESC";
        if (!empty($params['sortVal']) && $params['sortVal'] == 'ascending') {
          $sortVal = "ASC";
        }

        $status = !empty($params['status']) ? $params['status'] : $showStatus;
        if ($status == 'sent') {
          $statusWhere = 'status = 1';
        } elseif ($status == 'not_send') {
          $statusWhere = 'status = 0 OR status =2';
        } elseif ($status == 'empty') {
          $statusWhere = 'status <> 1 AND status <> 0 and status <> 2';
        } else {
          $statusWhere = 'status = 1 OR status = 0 OR status = 2';
        }

        // Result ALL
        //SELECT * FROM `wp_yaysmtp_email_logs` WHERE subject LIKE "%khoata91%" OR email_to LIKE "%khoata91%"
        $table = YAY_SMTP_AMAZONSES_PREFIX . '_email_logs';
        if (!empty($valSearch)) {
          $subjectWhere = 'subject LIKE "%' . $valSearch . '%"';
          $toEmailWhere = 'email_to LIKE "%' . $valSearch . '%"';
          $whereQuery = "{$subjectWhere} OR {$toEmailWhere}";
          if (!empty($statusWhere)) {
            $whereQuery = "(" . $whereQuery . ") AND (" . $statusWhere . ")";
          }
          $sqlRepareAll = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}$table WHERE $whereQuery ORDER BY $sortField $sortVal");

          $sqlRepare = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}$table WHERE $whereQuery ORDER BY $sortField $sortVal LIMIT %d OFFSET %d",
            $limit,
            $offset);
        } else {
          $sqlRepareAll = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}$table WHERE $statusWhere ORDER BY $sortField $sortVal");

          $sqlRepare = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}$table WHERE $statusWhere ORDER BY $sortField $sortVal LIMIT %d OFFSET %d",
            $limit,
            $offset);
        }
        $resultQueryAll = $wpdb->get_results($sqlRepareAll);
        $totalItems = count($resultQueryAll);

        // Result Custom
        $results = $wpdb->get_results($sqlRepare);

        $emailLogsList = array();
        foreach ($results as $result) {
          $emailTo = maybe_unserialize($result->email_to);
          $emailEl = array(
            'id' => $result->id,
            'subject' => $result->subject,
            'email_from' => $result->email_from,
            'email_to' => $emailTo,
            'mailer' => $result->mailer,
            'date_time' => $result->date_time,
            'status' => $result->status,
          );
          $emailLogsList[] = $emailEl;
        }

        wp_send_json_success(array(
          'data' => $emailLogsList,
          'totalItem' => $totalItems,
          'totalPage' => $limit < 0 ? 1 : ceil($totalItems / $limit),
          'currentPage' => $page,
          'limit' => $limit,
          'showColSettings' => $showColSettings,
          'mess' => __('Successful', 'yay-smtp-amazonses'),
        ));
      }
      wp_send_json_error(array('mess' => __('Failed.', 'yay-smtp-amazonses')));
    } catch (\Exception $ex) {
      LogErrors::getMessageException($ex, true);
    } catch (\Error $ex) {
      LogErrors::getMessageException($ex, true);
    }
  }

  public function setYaySmtpEmailLogSetting() {
    try {
      Utils::checkNonce();
      if (isset($_POST['params'])) {
        $params = Utils::saniValArray($_POST['params']);
        foreach ($params as $key => $val) {
          Utils::setYaySmtpEmailLogSetting($key, $val);
        }
        wp_send_json_success(array(
          'mess' => __('Save Settings Successful', 'yay-smtp-amazonses'),
        ));
      }
      wp_send_json_error(array('mess' => __('Save Settings Failed.', 'yay-smtp-amazonses')));
    } catch (\Exception $ex) {
      LogErrors::getMessageException($ex, true);
    } catch (\Error $ex) {
      LogErrors::getMessageException($ex, true);
    }
  }

  public function deleteEmailLogs() {
    try {
      Utils::checkNonce();
      if (isset($_POST['params'])) {
        global $wpdb;
        $params = Utils::saniValArray($_POST['params']);
        $ids = isset($params['ids']) ? $params['ids'] : ""; // '1,2,3'

        if (empty($ids)) {
          wp_send_json_error(array('mess' => __('No email log id found', 'yay-smtp-amazonses')));
        }

        $table = $wpdb->prefix . YAY_SMTP_AMAZONSES_PREFIX . '_email_logs';
        $deleted = $wpdb->query("DELETE FROM $table WHERE ID IN ($ids)");

        if ($wpdb->last_error !== '') {
          wp_send_json_error(array('mess' => __($wpdb->last_error, 'yay-smtp-amazonses')));
        }

        if (!$deleted) {
          wp_send_json_error(array('mess' => __('Something wrong, Email logs not deleted', 'yay-smtp-amazonses')));
        }

        wp_send_json_success(array(
          'mess' => __('Delete successful.', 'yay-smtp-amazonses'),
        ));
      }
      wp_send_json_error(array('mess' => __('No email log id found.', 'yay-smtp-amazonses')));

    } catch (\Exception $ex) {
      LogErrors::getMessageException($ex, true);
    } catch (\Error $ex) {
      LogErrors::getMessageException($ex, true);
    }
  }

  public function getEmailLog() {
    try {
      Utils::checkNonce();
      if (isset($_POST['params'])) {
        global $wpdb;
        $params = Utils::saniValArray($_POST['params']);
        $id = isset($params['id']) ? (int) $params['id'] : "";

        if (empty($id)) {
          wp_send_json_error(array('mess' => __('No email log id found', 'yay-smtp-amazonses')));
        }

        $table = $wpdb->prefix . YAY_SMTP_AMAZONSES_PREFIX . '_email_logs';
        $sqlRepare = $wpdb->prepare("Select * FROM $table WHERE id = $id");
        $resultQuery = $wpdb->get_row($sqlRepare);

        if ($wpdb->last_error !== '') {
          wp_send_json_error(array('mess' => __($wpdb->last_error, 'yay-smtp-amazonses')));
        }

        if (!empty($resultQuery)) {
          $emailTo = maybe_unserialize($resultQuery->email_to);
          $resultArr = array(
            'id' => $resultQuery->id,
            'subject' => $resultQuery->subject,
            'email_from' => $resultQuery->email_from,
            'email_to' => $emailTo,
            'mailer' => $resultQuery->mailer,
            'date_time' => $resultQuery->date_time,
            'status' => $resultQuery->status,
          );

          if (!empty($resultQuery->content_type)) {
            $resultArr['content_type'] = $resultQuery->content_type;
            $resultArr['body_content'] = maybe_serialize($resultQuery->body_content);
          }

          if (!empty($resultQuery->reason_error)) {
            $resultArr['reason_error'] = $resultQuery->reason_error;
          }

          wp_send_json_success(array(
            'mess' => __('Get email log #' . $id . ' successful.', 'yay-smtp-amazonses'),
            'data' => $resultArr,
          ));
        } else {
          wp_send_json_error(array('mess' => __('No email log found.', 'yay-smtp-amazonses')));
        }

      }
      wp_send_json_error(array('mess' => __('No email log id found.', 'yay-smtp-amazonses')));

    } catch (\Exception $ex) {
      LogErrors::getMessageException($ex, true);
    } catch (\Error $ex) {
      LogErrors::getMessageException($ex, true);
    }
  }

}
