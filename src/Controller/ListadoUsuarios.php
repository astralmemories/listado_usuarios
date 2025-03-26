<?php

declare(strict_types=1);

namespace Drupal\listado_usuarios\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for listado_usuarios.listado_usuarios_page route.
 */
class ListadoUsuarios extends ControllerBase {

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

    /**
     * Pager manager.
     *
     * @var \Drupal\Core\Pager\PagerManagerInterface
     */
    protected $pagerManager;

    public function __construct(ClientInterface $httpClient, PagerManagerInterface $pagerManager) {
        $this->httpClient = $httpClient;
        $this->logger = $this->getLogger('listado_usuarios');
        $this->pagerManager = $pagerManager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client'),
            $container->get('pager.manager')
        );
    }

    /**
     * Builds the response with pagination.
     */
    public function build(): array {
        $url = $_SERVER['SERVER_NAME'] . '/modules/custom/listado_usuarios/data/usuarios.json';

        try {
            $response = $this->httpClient->get($url);
            $data = json_decode($response->getBody()->getContents());
        }
        catch (\Exception $e) {
            $this->logger->warning('Unable to complete the request. Error: ' . $e->getMessage());
            $data = null;
        }

        if ($data) {
            $users = $data->usuarios;

            // Set up pagination.
            $current_page = $this->pagerManager->createPager(count($users), 5)->getCurrentPage();
            $paged_users = array_slice($users, $current_page * 5, 5);

            $table = '<table>';
            foreach ($paged_users as $user) {
                $table .= "<tr><td>$user->id</td> <td>$user->email</td> <td>$user->name</td> <td>$user->surname1</td> <td>$user->surname2</td></tr>";
            }
            $table .= '</table>';
        }
        else {
            $table = '<p>Could not get the users list.</p>';
        }

        $output = "<p>Listado de usuarios.</p>";
        $output .= $table;

        return [
            '#type' => 'markup',
            '#markup' => $output,
            '#attached' => [
                'library' => ['core/drupal.ajax'],
            ],
            // Add the pager render array.
            '#pager' => [
                '#type' => 'pager',
            ],
        ];
    }

    /**
     * AJAX callback for pagination.
     */
    public function ajaxCallback(): AjaxResponse {
        $response = new AjaxResponse();
        $build = $this->build();
        $html = render($build);
        $response->addCommand(new HtmlCommand('#listado-usuarios-container', $html));
        return $response;
    }
}