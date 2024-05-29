<?php declare(strict_types=1);

namespace Drupal\designermakers\Service;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use CiviCRM_API3_Exception;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * A custom service that adds new products and links them to its owners based on authenticated designer maker.
 */
class ProductSubmissionService {

  protected $currentUser;
  protected $entityTypeManager;
  protected $logger;

  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('designermakers');
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
        $this->logger->error('No contact found for user ID: @uid', ['@uid' => $user_id]);
        \Drupal::messenger()->addError(t('No contact found for user ID: @uid', ['@uid' => $user_id]));
        return;
      }

      $contact_id = $contact['values'][0]['contact_id'];

      // Retrieve the submission values
      $values = $webform_submission->getData();

      // Log the values for debugging
      $this->logger->debug('Webform submission values: @values', ['@values' => print_r($values, TRUE)]);

      $product_fields = [
        'entity_id' => $contact_id,
        'business_logo_32' => $values['civicrm_1_contact_1_cg8_custom_32'],
        'tag_line_33' => $values['civicrm_1_contact_1_cg8_custom_33'],
        'product_images_34' => $values['civicrm_1_contact_1_cg8_custom_34'],
        'description_35' => $values['civicrm_1_contact_1_cg8_custom_35'],
        'art_category_36' => $values['civicrm_1_contact_1_cg8_custom_36'],
      ];

      // Insert a new product record
      $connection = Database::getConnection();
      $connection->insert('civicrm_value_product_8')
        ->fields($product_fields)
        ->execute();

      $this->logger->info('Created product data for Designer Maker: @dmid', ['@dmid' => $contact_id]);
      \Drupal::messenger()->addStatus(t('Created product data for Designer Maker: @dmid', ['@dmid' => $contact_id]));

    } catch (CiviCRM_API3_Exception $e) {
      $this->logger->error('CiviCRM API Error: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError(t('CiviCRM API Error: @message', ['@message' => $e->getMessage()]));
    } catch (\Exception $e) {
      $this->logger->error('Error: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError(t('Error: @message', ['@message' => $e->getMessage()]));
    }
  }
}
