<?php declare(strict_types=1);

/**
 * @file designershub service used to fetch data for designer makers.
 */

namespace Drupal\designershub\Service;

use Civi\Api4\Contact;

class CiviCrmService {

  /**
   * Fetches Designer Maker details from CiviCRM.
   *
   * @param int $designer_maker_id
   *   The ID of the Designer Maker to fetch.
   *
   * @return array
   *   An array of Designer Maker details.
   */
  public function fetchDesignerMakerDetails($designer_maker_id) {
    try {
      // Fetch the Designer Maker details from CiviCRM using the API.
      $result = Contact::get()
        ->addWhere('id', '=', $designer_maker_id)
        ->execute();

      // Convert the result to an array.
      if ($result->count() > 0) {
        return $result->first();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('designershub', $e);
    }

    return [];
  }
}
