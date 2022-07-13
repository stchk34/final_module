<?php

namespace Drupal\stchk34\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class that create table form.
 */
class TableForm extends FormBase {

  /**
   * Table headers.
   *
   * @var array
   */
  protected array $header;

  /**
   * An array with quarter and year cells.
   *
   * @var array
   */
  protected array $disabledCells;

  /**
   * Number of row.
   *
   * @var int
   */
  protected int $rows = 1;

  /**
   * Number of table.
   *
   * @var int
   */
  protected int $tables = 1;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'table_form';
  }

  /**
   * Create table head.
   */
  public function buildHeader() {
    $this->header = [
      'year' => $this->t("Year"),
      'jan' => $this->t("Jan"),
      'feb' => $this->t("Feb"),
      'mar' => $this->t("Mar"),
      'q1' => $this->t("Q1"),
      'apr' => $this->t("Apr"),
      'may' => $this->t("May"),
      'jun' => $this->t("Jun"),
      'q2' => $this->t("Q2"),
      'jul' => $this->t("Jul"),
      'aug' => $this->t("Aug"),
      'sep' => $this->t("Sep"),
      'q3' => $this->t("Q3"),
      'oct' => $this->t("Oct"),
      'nov' => $this->t("Nov"),
      'dec' => $this->t("Dec"),
      'q4' => $this->t("Q4"),
      'ytd' => $this->t("YTD"),
    ];
    $this->disabledCells = [
      'year' => $this->t("Year"),
      'q1' => $this->t("Q1"),
      'q2' => $this->t("Q2"),
      'q3' => $this->t("Q3"),
      'q4' => $this->t("Q4"),
      'ytd' => $this->t("YTD"),
    ];
  }

  /**
   * Function to building form.
   *
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->buildHeader();
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';

    for ($i = 0; $i < $this->tables; $i++) {
      $table_id = $i;
      $form[$table_id] = [
        '#type' => 'table',
        '#header' => $this->header,
        '#tree' => 'TRUE',
      ];
      $this->buildYear($table_id, $form[$table_id], $form_state);
    }

    $form['addYear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Year'),
      '#submit' => [
        '::addYear',
      ],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxReload',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Table'),
      '#submit' => [
        '::addTable',
      ],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxReload',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxReload',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    $form['#attached']['library'][] = 'stchk34/stchk34-style';
    return $form;
  }

  /**
   * Function to building rows.
   */
  public function buildYear($table_id, array &$table, FormStateInterface $form_state) {
    for ($i = $this->rows; $i > 0; $i--) {
      foreach ($this->header as $key => $value) {
        $table[$i][$key] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (array_key_exists($key, $this->disabledCells)) {
          $default_value = $form_state->getValue($table_id . '][' . $i . '][' . $key, 0);
          $table[$i][$key]['#disabled'] = TRUE;
          $table[$i][$key]['#default_value'] = round($default_value, 2);
        }
        $table[$i]['year']['#default_value'] = date('Y') - $i + 1;
      }
    }
  }

  /**
   * Button for adding a new row.
   */
  public function addYear(array $form, FormStateInterface $form_state) {
    $this->rows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Button for adding a new table.
   */
  public function addTable(array $form, FormStateInterface $form_state) {
    $this->tables++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Function that gets values from the table.
   */
  public function clearValues(array $value_table_cell): array {
    // For adding values from cells in the table.
    $values = [];
    // Call inactive cells of the table.
    $inactive_cells = $this->disabledCells;
    // Go through rows.
    for ($i = $this->rows; $i >= 0; $i--) {
      // Go through rows' values.
      foreach ($value_table_cell[$i] as $key => $active_cells) {
        if (!array_key_exists($key, $inactive_cells)) {
          $values[] = $active_cells;
        }
      }
    }
    return $values;
  }

  /**
   * Function that checks if value is not empty.
   */
  public function notEmpty($active_cells): bool {
    return ($active_cells || $active_cells == '0');
  }

  /**
   * Validating the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Getting values from the table.
    $table_values = $form_state->getValues();
    // An array in which are sorted values from the tables.
    $active_values = [];
    // Start point of validation.
    $start_point = NULL;
    // End point of validation.
    $end_point = NULL;
    // Loop for the all tables.
    for ($i = 0; $i < $this->tables; $i++) {
      // Call the function that gets values from the table.
      $cell_values = $this->ClearValues($table_values[$i]);
      // An array in which are saved and sorted values from the tables.
      $active_values[] = $cell_values;
      // Go through cells.
      foreach ($cell_values as $key => $active_cells) {
        // Comparing cells in the tables.
        for ($table_cell = 0; $table_cell <= count($active_values[$i]) - 1; $table_cell++) {
          if ($this->notEmpty($active_values[0][$table_cell]) !== $this->notEmpty($active_values[$i][$table_cell])) {
            $form_state->setErrorByName($i, 'Tables are different. Please, check.');
          }
        }
       // Value of the start point of the key if the cell is not empty.
        if (!empty($active_cells)) {
          $start_point = $key;
          break;
        }
      }
      // If value of the start point exist, run the loop.
      if ($start_point !== NULL) {
        // Going into all filled cells after start point.
        for ($filled_cell = $start_point; $filled_cell < count($cell_values); $filled_cell++) {
          // End point if filled cells are empty.
          if (($cell_values[$filled_cell] == NULL)) {
            $end_point = $filled_cell;
            break;
          }
        }
      }
      // If value of the end point exist, run the loop.
      if ($end_point !== NULL) {
        // Going into all filled cells after end point.
        for ($cell = $end_point; $cell < count($cell_values); $cell++) {
          // If value of the cell is not equal to null.
          if (($cell_values[$cell]) != NULL) {
            $form_state->setErrorByName("table-$i", 'Invalid');
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    for ($i = 0; $i < $this->tables; $i++) {
      foreach ($values[$i] as $row_key => $row) {
        $path = $i . '][' . $row_key . '][';

        // Operations with cell values.
        $q1 = ((intval($row['jan']) + intval($row['feb']) + intval($row['mar'])) + 1) / 3;
        $q2 = ((intval($row['apr']) + intval($row['may']) + intval($row['jun'])) + 1) / 3;
        $q3 = ((intval($row['jul']) + intval($row['aug']) + intval($row['sep'])) + 1) / 3;
        $q4 = ((intval($row['oct']) + intval($row['nov']) + intval($row['dec'])) + 1) / 3;
        $ytd = (($q1 + $q2 + $q3 + $q4) + 1) / 4;

        // Set values for inactive cells.
        $form_state->setValue($path . 'q1', $q1);
        $form_state->setValue($path . 'q2', $q2);
        $form_state->setValue($path . 'q3', $q3);
        $form_state->setValue($path . 'q4', $q4);
        $form_state->setValue($path . 'ytd', $ytd);
      }
    }
    $this->messenger()->addMessage('Form is valid!');
    $form_state->setRebuild();
  }

  /**
   * Function for refreshing form.
   */
  public function ajaxReload(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

}
