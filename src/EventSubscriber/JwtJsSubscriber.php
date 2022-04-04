<?php

namespace Drupal\jwt_js\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class JwtJsSubscriber.
 *
 * Get the JWT on login.
 *
 * @package Drupal\jwt_js
 */
class JwtJsSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configfactory;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * JWT auth service.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  protected $jwtAuth;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructor function.
   *
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $jwt_auth
   *   JWT Auth service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current logged in user.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp storage service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(JwtAuth $jwt_auth,
    AccountProxyInterface $current_user,
    PrivateTempStoreFactory $temp_store_factory,
    ConfigFactoryInterface $config_factory) {
    $this->jwtAuth = $jwt_auth;
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory->get('jwt_js');
    $this->configfactory = $config_factory->get('jwt_js.settings');
  }

  /**
   * Add JWT access token to user login API response.
   */
  public function onHttpLoginResponse(FilterResponseEvent $event) {
    // Get response.
    $response = $event->getResponse();

    // Ensure not error response.
    if ($response->getStatusCode() !== 200 || $this->currentUser->isAnonymous()) {
      return;
    }

    // Store the JWT in temporary session.
    if ($access_token = $this->jwtAuth->generateToken()) {
      $this->tempStoreFactory->set('jwt_access_token', $access_token);
    }
  }

  /**
   * Sets the standard claims set for a JWT.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent $event
   *   The event.
   */
  public function setStandardClaims(JwtAuthGenerateEvent $event) {
    $event->addClaim('iat', time());
    if (!empty($this->configfactory->get('expiry_time'))) {
      $expiry = '+' . $this->configfactory->get('expiry_time') . ' hour';
      $event->addClaim('exp', strtotime($expiry));
    }
  }

  /**
   * Sets claims for a Drupal consumer on the JWT.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent $event
   *   The event.
   */
  public function setDrupalClaims(JwtAuthGenerateEvent $event) {
    $event->addClaim(
      ['drupal', 'email'],
      $this->currentUser->getEmail(),
    );
  }

  /**
   * The subscribed events.
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['onHttpLoginResponse'];
    $events[JwtAuthEvents::GENERATE][] = ['setStandardClaims', 98];
    $events[JwtAuthEvents::GENERATE][] = ['setDrupalClaims', 99];

    return $events;
  }

}
