<?php declare(strict_types=1);

namespace Drupal\designermakers\Service;

use CRM_Core_Config;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Civi\Api4\CustomValue;

require_once './sites/default/civicrm.settings.php';
require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();
require_once 'api/api.php';

class ProductSubmissionService {

  protected $currentUser;
  protected $entityTypeManager;

  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function submitProduct(WebformSubmissionInterface $webform_submission) {
    $user_id = $this->currentUser->id();

    // Get the contact ID associated with the current user
    $contact = civicrm_api3('UFMatch', 'get', [
      'sequential' => 1,
      'uf_id' => $user_id,
    ]);

    if (empty($contact['values'])) {
      \Drupal::logger('mymodule')->error('No contact found for user ID: @uid', ['@uid' => $user_id]);
      return;
    }

    $contact_id = $contact['values'][0]['contact_id'];

    // Retrieve the submission values
    $values = $webform_submission->getData();
    dump($values);
    $product_fields = [
      'custom_27' => $values['civicrm_1_contact_1_cg7_custom_27'],
      'custom_28' => $values['civicrm_1_contact_1_cg7_custom_28'],
      'custom_29' => $values['civicrm_1_contact_1_cg7_custom_29'],
      'custom_30' => $values['civicrm_1_contact_1_cg7_custom_30'],
      'custom_31' => $values['civicrm_1_contact_1_cg7_custom_31'],
    ];

    // Save the custom values to the contact's product custom group
    foreach ($product_fields as $field_name => $field_value) {
      civicrm_api3('CustomValue', 'create', [
        'entity_id' => $contact_id,
        'entity_table' => 'civicrm_contact',
        $field_name => $field_value,
      ]);
    }

    \Drupal::logger('designermakers')->info('Product data submitted for contact ID: @cid', ['@cid' => $contact_id]);
    \Drupal::messenger()->addMessage('Product data submitted for contact ID: @cid');
  }
}
