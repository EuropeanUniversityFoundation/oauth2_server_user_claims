<?php

namespace Drupal\oauth2_server_user_claims\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\UserInterface;
use Drupal\oauth2_server\OAuth2StorageInterface;
use Drupal\oauth2_server\ScopeInterface;
use Drupal\oauth2_server\ServerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a OAuth2 Server user claims form.
 */
class UserClaimsForm extends FormBase {

  use StringTranslationTrait;

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A list of OAuth2 servers as options.
   *
   * @var array
   */
  protected $serverOptions;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OAuth2Storage.
   *
   * @var \Drupal\oauth2_server\OAuth2StorageInterface
   */
  protected $storage;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\oauth2_server\OAuth2StorageInterface $storage
   *   The OAuth2Storage.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    OAuth2StorageInterface $storage,
    TranslationInterface $string_translation
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->storage = $storage;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('oauth2_server.storage'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oauth2_server_user_claims_user_claims_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    if (! isset($this->user)) {
      $this->user = $user;
    }

    if (! isset($this->serverOptions)) {
      /** @var \Drupal\oauth2_server\ServerInterface[] $servers */
      $servers = $this->entityTypeManager
        ->getStorage('oauth2_server')
        ->loadMultiple();

      foreach ($servers as $key => $server) {
        $this->serverOptions[$key] = $server->label();
      }
    }

    $form['server'] = [
      '#type' => 'select',
      '#title' => $this->t('Select an OAuth2 server'),
      '#options' => $this->serverOptions,
      '#default_value' => '',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => '::showUserClaims',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'output',
      ],
    ];

    $form['output'] = [
      '#type' => 'markup',
      '#markup' => '<div class="output"></div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function showUserClaims(array &$form, FormStateInterface $form_state) {
    $server = $form_state->getValue('server');

    if ($server) {
      $data['server'] = $this->serverOptions[$server];

      /** @var \Drupal\oauth2_server\ScopeInterface[] $scopes */
      $scopes = $this->entityTypeManager
        ->getStorage('oauth2_server_scope')
        ->loadByProperties(['server_id' => $server]);

      $data['scopes'] = [];

      foreach ($scopes as $scope_key => $scope) {
        $data['scopes'][$scope->label()] = [];

        $claims = $this->storage
          ->getUserClaims($this->user->id(), $scope->label());

        foreach ($claims as $claim_key => $claim) {
          $data['scopes'][$scope->label()][$claim_key] = $claim;
        }
      }

      $markup = $this->formatData($data);
    }
    else {
      $markup = '';
    }

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.output', $markup));
    return $ajax_response;
  }

  /**
   * Format the data to display in the form.
   *
   * @param array $data
   */
  private function formatData(array $data): string {
    $empty = $this->t('%empty', ['%empty' => 'empty']);

    $output['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Claims returned by the %server server', [
        '%server' => $data['server']
      ])
    ];

    $header = [
      $this->t('Scope'),
      $this->t('Claim'),
      $this->t('Value'),
    ];

    $rows = [];

    foreach ($data['scopes'] as $scope => $claims) {
      if ($scope !== 'openid') {
        unset($claims['sub']);
      }
      foreach ($claims as $key => $value) {
        if (is_array($value)) {
          $value = (!empty($value)) ? $this->arrayToList($value) : $empty;
        }
        $rows[] = [$scope, $key, render($value)];
      }
    }

    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Nothing to display'),
    ];

    return render($output);
  }

  /**
   * Converts a PHP array to a zero-indexed HTML ordered list as a render array.
   *
   * @param array $data
   */
  private function arrayToList(array $data): array {
    $output = [
      '#type' => 'html_tag',
      '#tag' => 'ol',
      '#attributes' => [
        'start' => '0'
      ]
    ];

    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $value = (!empty($value)) ? $this->arrayToList($value) : $value;
      }

      $output[$key] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#value' => $value,
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
