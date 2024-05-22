<?php declare(strict_types=1);

namespace Drupal\designershub\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LatestDesignerMakersBlock' block.
 *
 * @Block(
 *   id = "latest_designer_makers_block",
 *   admin_label = @Translation("Latest Designer Makers Block")
 * )
 */
class LatestDesignerMakersBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $civicrm;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $civicrm) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrm = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->civicrm->initialize();

    $designer_makers = $this->fetchLatestDesignerMakers();

    return [
      '#theme' => 'latest_designer_makers',
      '#designer_makers' => $designer_makers,
    ];
  }

  /**
   * Fetches the latest 5 Designer Makers.
   */
  protected function fetchLatestDesignerMakers() {
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'type_name' => 'Individual',
        'options' => ['limit' => 5, 'sort' => 'created_date DESC'],
        'return' => ['id', 'display_name'],
      ]);

      if (!empty($result['values'])) {
        return $result['values'];
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('designershub')->error($e->getMessage());
    }

    return [];
  }
}
