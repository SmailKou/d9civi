<?php declare(strict_types=1);

/**
 * @file DesignerMakerProfile controller that allows bringing data of a contact interacting with the api.
 */

namespace Drupal\designershub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DesignerMakerProfile extends ControllerBase {

  protected $civicrm;

  public function __construct($civicrm) {
    $this->civicrm = $civicrm;
  }

  /**
   * Injecting dependencies
   */
  public static function create(ContainerInterface $container) {
    return new static ($container->get('civicrm'));
  }

  /**
   * fetching data for modal.
   */
  public function modal($designer_maker_id) {
    $this->civicrm->initialize();

    $designer_maker = $this->fetchDesignerMakerDetails($designer_maker_id);

    return new JsonResponse($designer_maker);
  }

  /**
   * fetching data for full profile page.
   */
  public function profile($designer_maker_id) {
    $this->civicrm->initialize();

    $designer_maker = $this->fetchDesignerMakerDetails($designer_maker_id);

    return [
      '#theme' => 'designer_maker_profile',
      '#designer_maker' => $designer_maker,
    ];
  }

  protected function fetchDesignerMakerDetails($designer_maker_id) {
    try {
      // Fetching the Designer Maker details from CiviCRM using the API
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $designer_maker_id,
        'return' => ['id', 'display_name', 'email', 'phone', 'address'],
      ]);

      if (!empty($result['values'])) {
        return $result['values'][0];
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('designershub')->error($e->getMessage());
    }

    return [];
  }
}
