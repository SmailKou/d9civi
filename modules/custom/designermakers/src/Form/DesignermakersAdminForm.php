<?php

/**
 * @file Custom Form to trigger the creation of some CiviCRM basic entities: a Group and a CustomGroup and its custom fields.
 */
namespace Drupal\designermakers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an admin form for creating custom groups and fields.
 */
class DesignermakersAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'designermakers_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['create_custom_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Product Custom Group and Fields'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Ensure the user has the necessary permissions
    if (\Drupal::currentUser()->hasPermission('administer civicrm')) {
      try {
        // Check if 'Product' custom group already exists
        $existingGroup = \Civi\Api4\CustomGroup::get()
          ->addWhere('title', '=', 'Product')
          ->execute();

        if ($existingGroup->count() > 0) {
          \Drupal::messenger()->addMessage('Product custom group already exists.', 'warning');
          return;
        }

        // Create 'Product' custom field group
        $group = \Civi\Api4\CustomGroup::create()
          ->addValue('title', 'Product')
          ->addValue('extends', 'Individual') // Extend 'Individual' contact type
          ->execute();

        // Retrieve the custom group ID
        $custom_group_id = $group[0]['id'];

        // Custom fields for 'Product'
        $productFields = [
          ['label' => 'Business Logo', 'data_type' => 'String', 'html_type' => 'File'],
          ['label' => 'Tag Line', 'data_type' => 'String', 'html_type' => 'Text'],
          ['label' => 'Product Images', 'data_type' => 'String', 'html_type' => 'File'],
          ['label' => 'Description', 'data_type' => 'Memo', 'html_type' => 'TextArea'],
          ['label' => 'Art Category', 'data_type' => 'String', 'html_type' => 'Select'],
        ];

        foreach ($productFields as $field) {
          \Civi\Api4\CustomField::create()
            ->addValue('custom_group_id', $custom_group_id)
            ->addValue('label', $field['label'])
            ->addValue('data_type', $field['data_type'])
            ->addValue('html_type', $field['html_type'])
            ->execute();
        }

        \Drupal::messenger()->addMessage('Product custom group and fields created successfully!', 'status');
      } catch (\Exception $e) {
        \Drupal::logger('designermakers')->error('Error creating Product custom group: @message', ['@message' => $e->getMessage()]);
        \Drupal::messenger()->addMessage('Error creating Product custom group: ' . $e->getMessage(), 'error');
      }

      // Check if the "Designer Makers" group already exists
      try {
        $existingGroup = \Civi\Api4\Group::get()
          ->addWhere('title', '=', 'Designer Makers')
          ->execute();

        if ($existingGroup->count() == 0) {
          // Create the "Designer Makers" group
          \Civi\Api4\Group::create()
            ->addValue('title', 'Designer Makers')
            ->addValue('name', 'designer_makers')
            ->addValue('description', 'Group for Designer Makers')
            ->addValue('is_active', 1)
            ->execute();

          \Drupal::messenger()->addMessage('Designer Makers group created successfully!', 'status');
        } else {
          \Drupal::messenger()->addMessage('Designer Makers group already exists.', 'warning');
        }
      } catch (\Exception $e) {
        \Drupal::logger('designermakers')->error('Error creating Designer Makers group: @message', ['@message' => $e->getMessage()]);
        \Drupal::messenger()->addMessage('Error creating Designer Makers group: ' . $e->getMessage(), 'error');
      }

    } else {
      \Drupal::messenger()->addMessage('You do not have permission to perform this action.', 'error');
    }
  }
}
