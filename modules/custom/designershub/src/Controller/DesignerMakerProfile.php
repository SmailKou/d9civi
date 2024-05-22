<?php declare(strict_types=1);

/**
 * @file DesignerMakerProfile controller that allows bringing data of a contact interacting with the api.
 */

namespace Drupal\designershub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class DesignerMakerProfile extends ControllerBase {

  protected $requestStack;
  protected $civicrm;

  public function __construct(RequestStack $request_stack, $civicrm) {
    $this->requestStack = $request_stack;
    $this->civicrm = $civicrm;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('civicrm')
    );
  }

  public function modal($designer_maker_id) {
    // Initialize CiviCRM
    $this->civicrm->initialize();

    // Fetching Designer Maker details from CiviCRM
    $designer_maker = $this->fetchDesignerMakerDetails($designer_maker_id);

    // Return a JSON response with the data
    return new JsonResponse($designer_maker);
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
