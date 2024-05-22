<?php declare(strict_types=1);

/**
 * @file A subscriber on user login event that verifies if the authenticated user is a designer maker or not.
 */

namespace Drupal\designershub\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserLoginSubscriber implements EventSubscriberInterface {

  protected $currentUser;
  protected $sessionManager;
  protected $session;

  public function __construct(AccountProxyInterface $current_user, SessionManagerInterface $session_manager, SessionInterface $session) {
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
    $this->session = $session;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 28];
    return $events;
  }

  public function onKernelRequest(RequestEvent $event) {

    if ($this->currentUser->isAnonymous()) {
      $this->session->remove('designer_maker_message_displayed');
      return;
    }

    if ($this->session->get('designer_maker_message_displayed')) {
      return;
    }

    $this->session->set('designer_maker_message_displayed', TRUE);

    \Drupal::service('civicrm')->initialize();

    $user = $this->currentUser;
    if ($user->isAuthenticated()) {
      $contact = $this->getDesignerMakerContact($user->id());
      if ($contact) {
        // Create a link to the Designer Maker profile
        $url = Url::fromRoute('designershub.designer_maker_profile', ['designer_maker_id' => $contact['id']]);
        $link = Link::fromTextAndUrl($contact['display_name'], $url)->toString();

        \Drupal::messenger()->addMessage(t('Welcome back! View your Designer Maker profile: @link', ['@link' => $link]));
      } else {

        \Drupal::messenger()->addWarning(t('No corresponding Designer Maker contact found.'));
      }
    }
  }

  /**
   * a functions that looks for a corresponding CiviCRM contact for the authenticated user.
   */
  protected function getDesignerMakerContact($user_id) {
    try {
      $result = civicrm_api3('UFMatch', 'get', [
        'sequential' => 1,
        'uf_id' => $user_id,
        'return' => ['contact_id'],
      ]);

      if (!empty($result['values'])) {
        $contact_id = $result['values'][0]['contact_id'];
        $contact = civicrm_api3('Contact', 'get', [
          'sequential' => 1,
          'id' => $contact_id,
          'return' => ['id', 'display_name'],
        ]);
        if (!empty($contact['values'])) {
          return $contact['values'][0];
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('designershub')->error($e->getMessage());
    }

    return NULL;
  }
}
