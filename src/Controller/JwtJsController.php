<?php

namespace Drupal\jwt_js\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jwt_js\EventSubscriber\JwtJsSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * UserProfileFormController class.
 */
class JwtJsController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\jwt_js\EventSubscriber\JwtJsSubscriber
   */
  protected $jwtJsSubscriber;

  /**
   * Constructs a new MyController object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  public function __construct(JwtJsSubscriber $jwtJsSubscriber) {
    $this->jwtJsSubscriber = $jwtJsSubscriber;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jwt_js.login_response_listener')
    );
  }

  /**
   * Refresh the JWT on token.
   */
  public function refreshToken() {
    $access_token = NULL;
    $response = new AjaxResponse();
    // Only refresh the token if the user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      $access_token = $this->jwtJsSubscriber->refreshAccessToken();
    }
    $command = new SettingsCommand([
      'user' => [
        'access_token' => $access_token,
      ],
    ], TRUE);
    $response->addCommand($command);
    return $response;
  }

  /**
   * Returns refresh token page.
   *
   * @return array
   *   A profile form.
   */
  public function refreshTokenPage() {
    $text = '<a href="/jwt-js/refresh-access-token" class="use-ajax">Refresh token</a>';
    $response['link'] = [
      '#markup' => $text,
      '#attached' => [
        'library' => [
          // 'core/drupal.dialog.ajax',
          // 'core/jquery.form',
        ],
      ],
    ];
    return $response;
  }
}
