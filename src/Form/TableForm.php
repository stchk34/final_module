<?php

namespace Drupal\stchk34\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateinterface;

/**
 * Main class for form table.
 */
class TableForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_table';
  }

  /**
   * Contain counts rows in tables.
   *
   * @var array
   *   Array with counts.
   */
  protected array $rows = [1];

  /**
   * Contain count tables on page.
   *
   * @var int
   *   Count tables.
   */
  protected int $countTable = 1;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Array that contain all table headers.
    $table_headers = [
      $this->t('Year'),
      $this->t('Jan'),
      $this->t('Feb'),
      $this->t('Mar'),
      $this->t('Q1'),
      $this->t('Apr'),
      $this->t('May'),
      $this->t('Jun'),
      $this->t('Q2'),
      $this->t('Jul'),
      $this->t('Aug'),
      $this->t('Sep'),
      $this->t('Q3'),
      $this->t('Oct'),
      $this->t('Nov'),
      $this->t('Dec'),
      $this->t('Q4'),
      $this->t('YTD'),
    ];

    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';

    // Loop by tables.
    for ($t = 0; $t < $this->countTable; $t++) {

      $form["add_row_{$t}"] = [
        '#type' => 'submit',
        '#value' => $this->t('Add year'),
        '#name' => $t,
        '#submit' => ['::addRow'],
      ];

      $form["table_{$t}"] = [
        '#type' => 'table',
        '#header' => $table_headers,
      ];

      // Loop by rows.
      for ($i = $this->rows[$t]; $i > 0; $i--) {

        // Loop by columns.
        foreach ($table_headers as $header) {
          $disabled = FALSE;
          $value = NULL;
          if ($header == 'Year') {
            $value = date('Y') - $i + 1;
          }
          if (in_array($header, ['Year', 'Q1', 'Q2', 'Q3', 'Q4', 'YTD'])) {
            $disabled = TRUE;
          }
          $form["table_{$t}"][$i]["{$header}"] = [ // just $header  dont work
            '#type' => 'number',
            '#disabled' => $disabled,
            '#default_value' => $value ?? '',
          ];
        }
      }
    }

    $form['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::addTable'],
      '#ajax' => [
        'wrapper' => 'form-wrapper',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#name' => 'submit',
      '#ajax' => [
        'event' => 'click',
        'callback' => '::updateFormAjax',
        'wrapper' => 'form-wrapper',
      ],
    ];

    $form['#attached']['library'][] = 'stchk34/stchk34-style';
    return $form;
  }

  /**
   * Callback function for add new table.
   */
  public function addTable(array &$form, FormStateInterface $form_state) {
    // Plus one table.
    $this->countTable++;
    // Set default row count to new table.
    $this->rows[] = 1;
    // Rebuild form.
    $form_state->setRebuild();
  }

  /**
   * Callback function for add row button.
   */
  public function addRow(array &$form, FormStateInterface $form_state) {
    // Get table id to add row.
    $t = $form_state->getTriggeringElement()['#name'];
    // Add one row.
    $this->rows[$t]++;
    print($t);
    // Rebuild form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Check submit.
    if ($form_state->getTriggeringElement()['#name'] !== 'submit') {
      return;
    }

    // Get all values from tables.
    $values = $form_state->getValues();

    $smallest_table = array_search(min($this->rows), $this->rows);

    // Loop by count tables.
    for ($t = 0; $t < $this->countTable; $t++) {
      $is_value = FALSE;
      $is_empty = FALSE;

      // Loop by rows in table.
      for ($r = $this->rows[$t]; $r > 0; $r--) {

        // Loop by table headers.
        foreach ($values["table_{$t}"][$r] as $head => $val) {

          // Validate on disabled columns.
          if (in_array($head, ['Year', 'Q1', 'Q2', 'Q3', 'Q4', 'YTD'])) {
            continue;
          }

          // Validate table size.
          if ($r <= $this->rows[$smallest_table]) {

            // Other validation.
            if (!$is_value && !$is_empty && $val !== '') {
              $is_value = TRUE;
            }
            if ($is_value && !$is_empty && $val == '') {
              $is_empty = TRUE;
            }
            if (!$is_value && $is_empty && $val !== '') {
              $form_state->setErrorByName('Invalid', $this->t('Invalid'));
            }
            if ($is_value && $is_empty && $val !== '') {
              $form_state->setErrorByName('Invalid', $this->t('Invalid'));
            }

            if ($values["table_{$smallest_table}"][$r][$head] == '' && $val !== '' ||
              $values["table_{$smallest_table}"][$r][$head] !== '' && $val == '') {
              $form_state->setErrorByName('Invalid', $this->t('Invalid'));
            }
          }
          else {
            $form_state->setErrorByName('Invalid', $this->t('Invalid'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    for ($t = 0; $t < $this->countTable; $t++) {
      for ($r = $this->rows[$t]; $r > 0; $r--) {
        $q1 = $q2 = $q3 = $q4 = 0;
        $val = $form_state->getValue(["table_{$t}", $r]);

        // Check month fields.
        if ((int) $val['Jan'] != '' || (int) $val['Feb'] != '' || (int) $val['Mar'] != '') {
          $q1 = round(((int) $val['Jan'] + (int) $val['Feb'] + (int) $val['Mar'] + 1) / 3, 2);
        }
        if ((int) $val['Apr'] != '' || (int) $val['May'] != '' || (int) $val['Jun'] != '') {
          $q2 = round(((int) $val['Apr'] + (int) $val['May'] + (int) $val['Jun'] + 1) / 3, 2);
        }
        if ((int) $val['Jul'] != '' || (int) $val['Aug'] != '' || (int) $val['Sep'] != '') {
          $q3 = round(((int) $val['Jul'] + (int) $val['Aug'] + (int) $val['Sep'] + 1) / 3, 2);
        }
        if ((int) $val['Oct'] != '' || (int) $val['Nov'] != '' || (int) $val['Dec'] != '') {
          $q4 = round(((int) $val['Oct'] + (int) $val['Nov'] + (int) $val['Dec'] + 1) / 3, 2);
        }

        // Set new values to quartets.
        $form["table_{$t}"][$r]['Q1']['#value'] = $q1;
        $form["table_{$t}"][$r]['Q2']['#value'] = $q2;
        $form["table_{$t}"][$r]['Q3']['#value'] = $q3;
        $form["table_{$t}"][$r]['Q4']['#value'] = $q4;

        // Find and set new value to YTD.
        $ytd = round(($q1 + $q2 + $q3 + $q4 + 1) / 4, 2);
        $form["table_{$t}"][$r]['YTD']['#value'] = $ytd;
      }
    }
    // Success message.
    $this->messenger()->addStatus('Valid');
  }

  /**
   * Callback function to update form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Return form to update.
   */
  public function updateFormAjax(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
