<?php

declare(strict_types=1);

namespace Drupal\listado_usuarios\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the ajax demo form controller.
 *
 * This example demonstrates using ajax callbacks to populate the options of a
 * color select element dynamically based on the value selected in another
 * select element in the form.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ListadoUsuariosForm extends FormBase {

    use AutowireTrait;

    /**
     * HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Logger channel.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(ClientInterface $httpClient) {
        $this->httpClient = $httpClient;
        $this->logger = $this->getLogger('listado_usuarios');
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client'),
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
            // Make a POST request with the 'json' option.
            $response = $this->httpClient->post($url, [
                'json' => [
                    'limit' => 5, // Specify the limit in the POST request body.
                ],
            ]);

            // Decode the response body.
            $data = json_decode($response->getBody()->getContents());

            // If the server doesn't handle the limit, filter the data manually.
            if ($data && isset($data->usuarios)) {
                $users = array_slice($data->usuarios, 0, 5); // Get the first 5 users.
            }
            else {
                $users = [];
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('Unable to complete the request. Error: ' . $e->getMessage());
            $users = [];
        }

        // Add a filter field.
        $form['filter_users'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Filtrar listado de usuarios'),
            '#description' => $this->t('Filtrar el listado por: nombre, apellidos y correo electrónico.'),
        ];

        // Add a filter button
        $form['filter_button'] = [
            '#type' => 'button',
            '#value' => $this->t('Filtrar'),
            '#ajax' => [
                'callback' => '::updateList',
                'wrapper' => 'listado-usuarios-wrapper',
            ],
        ];

        // Add a wrapper that can be replaced with new HTML by the ajax callback.
        // This is given the ID that was passed to the ajax callback in the '#ajax'
        // element above.
        $form['listado_usuarios_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'listado-usuarios-wrapper'],
        ];

        // Build the table rows.
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

        // Build the table of users inside the listado_usuarios_wrapper.
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

        // Calculate number of pages.
        $total_pages = $data->total / 5;
        $total_pages = ceil($total_pages);

        // Prepare the options for the pager.
        $pager_options = [];
        for ($i = 1; $i <= $total_pages; $i++) {
            $pager_options[$i] = $i;
        }

        // Add a pager element to the form using radios.
        $form['listado_usuarios_wrapper']['pager'] = [
            '#type' => 'radios',
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
     * No se necesita lógica de envío.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // No se necesita lógica de envío.
    }

    /**
     * Ajax callback for the filter button.
     */
    public function updateList(array $form, FormStateInterface $form_state) {
        // Get the filter value and current page from the form.
        $filter = $form_state->getValue('filter_users');
        $page = $form_state->getValue('pager') ?? 1;

        // Define the URL to the fetch_users.php script.
        $url = $_SERVER['SERVER_NAME'] . '/modules/custom/listado_usuarios/api/fetch_users.php';

        try {
            // Make a POST request with the 'json' option.
            $response = $this->httpClient->post($url, [
                'json' => [
                    'filter' => $filter, // Send the filter value.
                    'limit' => 5,        // Limit the number of results per page.
                    'page' => $page,     // Send the current page.
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

            // Calculate the total number of pages based on the filtered results.
            $total_users = isset($data->usuarios) ? count($data->usuarios) : 0;
            $total_pages = ceil($total_users / 5);

            // Prepare the options for the pager.
            $pager_options = [];
            for ($i = 1; $i <= $total_pages; $i++) {
                $pager_options[$i] = $i;
            }

            // Update the pager element.
            $form['listado_usuarios_wrapper']['pager']['#title'] = $this->t('NEW Pager');
            $form['listado_usuarios_wrapper']['pager']['#default_value'] = $page;
            $form['listado_usuarios_wrapper']['pager']['#options'] = $pager_options;
        }
        catch (\Exception $e) {
            $this->logger->warning('Unable to complete the request. Error: ' . $e->getMessage());
            $form['listado_usuarios_wrapper']['listado_usuarios_table']['#rows'] = [
                ['No se encontraron usuarios debido a un error.'],
            ];
        }

        // Return the updated wrapper.
        return $form['listado_usuarios_wrapper'];
    }

}
