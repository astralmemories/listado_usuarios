<?php

declare(strict_types=1);

namespace Drupal\listado_usuarios\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Implements the ajax form controller.
 *
 * This example demonstrates using ajax callbacks to populate the options of a
 * user list dynamically based on the value selected in the filter field.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ListadoUsuariosForm extends FormBase {

  use AutowireTrait;

  /**
   * HTTP client for making API requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Logger channel for logging messages.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * User session for persisting data across requests.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  public function __construct(ClientInterface $httpClient, SessionInterface $session) {
    $this->httpClient = $httpClient;
    $this->logger = $this->getLogger('listado_usuarios');
    $this->session = $session;
  }

  /**
   * Dependency injection for the form.
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('http_client'),
          $container->get('session')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_listado_usuarios';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $url = $_SERVER['SERVER_NAME'] . '/modules/custom/listado_usuarios/api/fetch_users.php';

    try {
      // Make a POST request to fetch the user data.
      $response = $this->httpClient->post($url, [
        'json' => [
      // Limit the number of results per page.
          'limit' => 5,
        ],
      ]);

      // Decode the response body.
      $data = json_decode($response->getBody()->getContents());

      // Extract the first 5 users if data is available.
      if ($data && isset($data->usuarios)) {
        $users = array_slice($data->usuarios, 0, 5);
      }
      else {
        $users = [];
      }
    }
    catch (\Exception $e) {
      // Log any errors and set users to an empty array.
      $this->logger->warning('Unable to complete the request. Error: ' . $e->getMessage());
      $users = [];
    }

    // Add a filter field for searching users.
    $form['filter_users'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filtrar listado de usuarios'),
      '#description' => $this->t('Filtrar el listado por: nombre, apellidos y correo electrónico.'),
    ];

    // Add a filter button with an AJAX callback.
    $form['filter_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Filtrar'),
      '#ajax' => [
        'callback' => '::updateList',
        'wrapper' => 'listado-usuarios-wrapper',
      ],
    ];

    // Add a wrapper that can be replaced with new HTML by the ajax callback.
    $form['listado_usuarios_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'listado-usuarios-wrapper'],
    ];

    // Build the table rows for the user list.
    $table_rows = [];
    if (!empty($users)) {
      foreach ($users as $user) {
        $table_rows[] = [
          $user->id,
          $user->email,
          $user->name,
          $user->surname1,
          $user->surname2,
        ];
      }
    }
    else {
      $table_rows[] = ['No se encontraron usuarios.'];
    }

    // Add the user list table to the wrapper.
    $form['listado_usuarios_wrapper']['listado_usuarios_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Correo electrónico'),
        $this->t('Nombre'),
        $this->t('Primer apellido'),
        $this->t('Segundo apellido'),
      ],
      '#rows' => $table_rows,
    ];

    // Calculate the total number of pages.
    $total_pages = $data->total / 5;
    $total_pages = ceil($total_pages);

    // Prepare the pager options.
    $pager_options = [];
    for ($i = 1; $i <= $total_pages; $i++) {
      $pager_options[$i] = $this->t('Page @num', ['@num' => $i]);
    }

    // Add a pager element to the form using a select dropdown.
    $form['listado_usuarios_wrapper']['pager'] = [
      '#type' => 'select',
      '#title' => $this->t('Pager'),
      '#default_value' => 1,
      '#options' => $pager_options,
      '#ajax' => [
        'callback' => '::updateList',
        'wrapper' => 'listado-usuarios-wrapper',
      ],
    ];

    // Return the form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No se necesita lógica de envío.
    // This form does not require traditional submission logic since it relies
    // entirely on AJAX.
  }

  /**
   * Ajax callback for the filter button.
   */
  public function updateList(array $form, FormStateInterface $form_state) {
    // Get the filter value and current page from the form.
    $filter = $form_state->getValue('filter_users');
    $page = $form_state->getValue('pager') ?? 1;

    // Retrieve the last filtered value from the session.
    $last_filtered_value = $this->session->get('last_filtered_value', '');

    // Check if the filter value has changed.
    if ($last_filtered_value !== $filter) {
      // Update the last filtered value in the session.
      $this->session->set('last_filtered_value', $filter);

      // Reset to the first page.
      $page = 1;
    }

    // Define the URL to the fetch_users.php script.
    $url = $_SERVER['SERVER_NAME'] . '/modules/custom/listado_usuarios/api/fetch_users.php';

    // Fetch the filtered user data and update the form elements.
    try {
      // Make a POST request to fetch the user data.
      $response = $this->httpClient->post($url, [
        'json' => [
      // Send the filter value.
          'filter' => $filter,
      // Limit the number of results per page.
          'limit' => 5,
      // Send the current page.
          'page' => $page,
        ],
      ]);

      // Decode the response body.
      $data = json_decode($response->getBody()->getContents());

      // If the response contains users, build the table rows.
      $table_rows = [];
      if ($data && isset($data->usuarios)) {
        foreach ($data->usuarios as $user) {
          $table_rows[] = [
            $user->id,
            $user->email,
            $user->name,
            $user->surname1,
            $user->surname2,
          ];
        }
      }
      else {
        $table_rows[] = ['No se encontraron usuarios.'];
      }

      // Update the table rows in the form.
      $form['listado_usuarios_wrapper']['listado_usuarios_table']['#rows'] = $table_rows;

      // Calculate new number of pages.
      $total_pages = $data->total_filtered / 5;
      $total_pages = ceil($total_pages);

      // Prepare the options for the pager.
      $pager_options = [];
      for ($i = 1; $i <= $total_pages; $i++) {
        $pager_options[$i] = $this->t('Page @num', ['@num' => $i]);
      }

      // Update the pager element.
      $form['listado_usuarios_wrapper']['pager']['#options'] = $pager_options;
      $form['listado_usuarios_wrapper']['pager']['#default_value'] = $page;
    }
    catch (\Exception $e) {
      // Log any errors.
      $this->logger->warning('Unable to complete the request. Error: ' . $e->getMessage());
      $form['listado_usuarios_wrapper']['listado_usuarios_table']['#rows'] = [
            ['No se encontraron usuarios debido a un error.'],
      ];
    }

    // Return the updated wrapper.
    return $form['listado_usuarios_wrapper'];
  }

}
