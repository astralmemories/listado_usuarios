<?php

declare(strict_types=1);

namespace Drupal\listado_usuarios\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure listado_usuarios settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Name for module's configuration object.
   */
  const SETTINGS = 'listado_usuarios.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return self::SETTINGS;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Text input field for the API URL.
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL API'),
      '#description' => $this->t('URL para obtener datos del usuario.'),
      '#default_value' => $this->config(self::SETTINGS)->get('api_url'),
      '#placeholder' => 'https://drupal-listado-usuarios.ddev.site/modules/custom/listado_usuarios/api/fetch_users.php',
    ];

    // Number input field to select the number of users per page for pagination.
    $form['users_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Numero de usuarios por pagina'),
      '#description' => $this->t('Seleccione el número de usuarios a mostrar por página (5 - 100).'),
      '#default_value' => $this->config(self::SETTINGS)->get('users_per_page'),
      '#min' => 5,
      '#max' => 100,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate the API URL field.
    $api_url = $form_state->getValue('api_url');
    if (empty($api_url)) {
      // Set an error on the specific field. This will halt form processing
      // and re-display the form with errors for the user to correct.
      $form_state->setErrorByName('api_url', $this->t('La URL de la API no puede estar vacía.'));
    }
    elseif (!filter_var($api_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('api_url', $this->t('Formato de URL no válido.'));
    }

    // Validate the users per page field.
    $users_per_page = $form_state->getValue('users_per_page');
    if ($users_per_page < 1 || $users_per_page > 100) {
      $form_state->setErrorByName('users_per_page', $this->t('El número de usuarios por página debe estar entre 1 y 100.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save the configuration settings using the values from the form state.
    $this->config(self::SETTINGS)
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('users_per_page', $form_state->getValue('users_per_page'))
      ->save();

    $this->messenger()->addMessage($this->t('Configuración de Listado De Usuarios actualizado.'));
  }

}
