<?php declare(strict_types=1);

/**
 * @file Custom Form to trigger the creation of some CiviCRM basic entities: a Group and a CustomGroup and its custom fields.
 */
namespace Drupal\designermakers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an admin form for creating custom groups and fields.
 */
class DesignermakersAdminForm extends FormBase {

  protected $messenger;

  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

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
    if (\Drupal::currentUser()->hasPermission('administer civicrm')) {
      \Drupal::service('civicrm')->initialize();

      try {
        $existingGroup = \Civi\Api4\CustomGroup::get()
          ->addWhere('title', '=', 'Product')
          ->execute();

        if ($existingGroup->count() > 0) {
          $this->messenger->addMessage($this->t('Product custom group already exists.'), 'warning');
          return;
        }

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
        ];

        foreach ($productFields as $field) {
          \Civi\Api4\CustomField::create()
            ->addValue('custom_group_id', $custom_group_id)
            ->addValue('label', $field['label'])
            ->addValue('data_type', $field['data_type'])
            ->addValue('html_type', $field['html_type'])
            ->execute();
        }

        // Create Option Group for 'Art Category'
        $optionGroup = \Civi\Api4\OptionGroup::create()
          ->addValue('title', 'Art Category')
          ->addValue('name', 'art_category')
          ->execute();

        $optionGroupId = $optionGroup[0]['id'];

        // Option values for 'Art Category'
        $artCategories = [
          'Painting' => 'painting',
          'Sculpture' => 'sculpture',
          'Photography' => 'photography',
          'Digital Art' => 'digital_art',
          'Mixed Media' => 'mixed_media',
        ];

        foreach ($artCategories as $label => $value) {
          \Civi\Api4\OptionValue::create()
            ->addValue('option_group_id', $optionGroupId)
            ->addValue('label', $label)
            ->addValue('value', $value)
            ->execute();
        }

        // Create 'Art Category' custom field using the option group
        \Civi\Api4\CustomField::create()
          ->addValue('custom_group_id', $custom_group_id)
          ->addValue('label', 'Art Category')
          ->addValue('data_type', 'String')
          ->addValue('html_type', 'Select')
          ->addValue('option_group_id', $optionGroupId)
          ->execute();

        $this->messenger->addMessage($this->t('Product custom group and fields created successfully!'), 'status');
      } catch (\Exception $e) {
        \Drupal::logger('designermakers')->error('Error creating Product custom group: @message', ['@message' => $e->getMessage()]);
        $this->messenger->addMessage($this->t('Error creating Product custom group: @message', ['@message' => $e->getMessage()]), 'error');
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

          $this->messenger->addMessage($this->t('Designer Makers group created successfully!'), 'status');
        } else {
          $this->messenger->addMessage($this->t('Designer Makers group already exists.'), 'warning');
        }
      } catch (\Exception $e) {
        \Drupal::logger('designermakers')->error('Error creating Designer Makers group: @message', ['@message' => $e->getMessage()]);
        $this->messenger->addMessage($this->t('Error creating Designer Makers group: @message', ['@message' => $e->getMessage()]), 'error');
      }

    } else {
      $this->messenger->addMessage($this->t('You do not have permission to perform this action.'), 'error');
    }
  }
}
