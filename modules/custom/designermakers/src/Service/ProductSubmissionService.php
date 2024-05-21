<?php declare(strict_types=1);

namespace Drupal\designermakers\Service;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use CiviCRM_API3_Exception;
use Drupal\Core\Database\Database;

/**
 * A custom service that adds new products and links them to its owners based on authenticated designer maker.
 */
class ProductSubmissionService {

  protected $currentUser;
  protected $entityTypeManager;

  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function submitProduct(WebformSubmissionInterface $webform_submission) {
    try {
      $user_id = $this->currentUser->id();

      // Get the contact ID associated with the current user
      $contact = civicrm_api3('UFMatch', 'get', [
        'sequential' => 1,
        'uf_id' => $user_id,
      ]);

      if (empty($contact['values'])) {
        \Drupal::logger('designermakers')->error('No contact found for user ID: @uid', ['@uid' => $user_id]);
        \Drupal::messenger()->addError(t('No contact found for user ID: @uid', ['@uid' => $user_id]));
        return;
      }

      $contact_id = $contact['values'][0]['contact_id'];

      // Retrieve the submission values
      $values = $webform_submission->getData();
      $product_fields = [
        'business_logo_27' => $values['civicrm_1_contact_1_cg7_custom_27'],
        'tag_line_28' => $values['civicrm_1_contact_1_cg7_custom_28'],
        'product_images_29' => $values['civicrm_1_contact_1_cg7_custom_29'],
        'description_30' => $values['civicrm_1_contact_1_cg7_custom_30'],
        'art_category_31' => $values['civicrm_1_contact_1_cg7_custom_31'],
      ];

      // Insert a new record into the civicrm_value_product_7 table
      $connection = Database::getConnection();
      $connection->insert('civicrm_value_product_7')
        ->fields(array_merge(['entity_id' => $contact_id], $product_fields))
        ->execute();

      \Drupal::logger('designermakers')->info('Product data submitted for user ID: @uid', ['@uid' => $user_id]);
      \Drupal::messenger()->addStatus(t('Product data submitted for Designer Maker: @uid', ['@uid' => $user_id]));

    } catch (CiviCRM_API3_Exception $e) {
      \Drupal::logger('designermakers')->error('CiviCRM API Error: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError(t('CiviCRM API Error: @message', ['@message' => $e->getMessage()]));
    } catch (\Exception $e) {
      \Drupal::logger('designermakers')->error('Error: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError(t('Error: @message', ['@message' => $e->getMessage()]));
    }
  }
}
