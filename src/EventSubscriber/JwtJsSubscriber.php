<?php

namespace Drupal\jwt_js\EventSubscriber;

use Drupal\jwt\Transcoder\JwtDecodeException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Drupal\jwt\Transcoder\JwtTranscoderInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
   * The JWT Transcoder service.
   *
   * @var \Drupal\jwt\Transcoder\JwtTranscoderInterface
   */
  protected $transcoder;

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
   * @param \Drupal\jwt\Transcoder\JwtTranscoderInterface $transcoder
   *   The JWT Transcoder service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current logged in user.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp storage service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(JwtAuth $jwt_auth,
    JwtTranscoderInterface $transcoder,
    AccountProxyInterface $current_user,
    PrivateTempStoreFactory $temp_store_factory,
    ConfigFactoryInterface $config_factory) {
    $this->jwtAuth = $jwt_auth;
    $this->transcoder = $transcoder;
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * The subscribed events.
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    $events[JwtAuthEvents::GENERATE][] = ['setStandardClaims', 98];
    $events[JwtAuthEvents::GENERATE][] = ['setDrupalClaims', 99];

    return $events;
  }

  /**
   * Add JWT access token to user login API response.
   */
  public function onResponse(FilterResponseEvent $event) {
    // Get response.
    $response = $event->getResponse();

    // Ensure not error response.
    if ($response->getStatusCode() !== 200 || $this->currentUser->isAnonymous()) {
      return;
    }

    // Check if JWT token has expired stored in user tempstore.
    // If token has expired then generate new token and store in user tempstore.
    $jwt = $this->getAccessToken();
    if ($this->isAccessTokenExpired($jwt)) {
      $access_token = $this->jwtAuth->generateToken();
      $this->setAccessToken('jwt_access_token', $access_token);
    }
  }

  /**
   * Check if JWT token has expired stored in user tempstore.
   */
  public function isAccessTokenExpired($jwt) {
    try {
      // Token will not decode if expired and it will throw an exception.
      $this->transcoder->decode($jwt);
      return FALSE;
    }
    catch (JwtDecodeException $e) {
      return TRUE;
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
    $configFactory = $this->configFactory->get('jwt_js.settings');
    $expire_after = $configFactory->get('expiry_time');
    $expiry_after = $expire_after ? "+$expire_after min" : "+1 min";
    $event->addClaim('exp', strtotime($expiry_after));
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
    $event->addClaim(
      ['drupal', 'uid'],
      $this->currentUser->id(),
    );
  }

  /**
   * Refresh the access token stored in the user tempstore.
   */
  public function refreshAccessToken() {
    $tempStoreFactory = $this->tempStoreFactory->get('jwt_js');
    $access_token = $this->jwtAuth->generateToken();
    $tempStoreFactory->set('jwt_access_token', $access_token);
    return $tempStoreFactory->get('jwt_access_token');
  }

  /**
   * Get the JWT access token stored in the user tempstore.
   */
  public function getAccessToken() {
    $tempStoreFactory = $this->tempStoreFactory->get('jwt_js');
    return $tempStoreFactory->get('jwt_access_token');
  }

  /**
   * Set the JWT access token stored in the user tempstore.
   */
  public function setAccessToken($access_token) {
    $tempStoreFactory = $this->tempStoreFactory->get('jwt_js');
    $tempStoreFactory->set('jwt_access_token', $access_token);
  }

}
