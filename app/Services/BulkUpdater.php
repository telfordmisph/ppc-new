<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class BulkUpdater
{
  protected Model $model;
  protected array $dateColumns = [];
  protected array $columnRules = [];
  protected array $columnHandlers = [];

  /**
   * @param Model $model Eloquent model
   * @param array $columnRules Laravel validation rules per column
   * @param array $dateColumns Columns to normalize as dates
   * @param array $columnHandlers Optional custom normalization per column
   *        Format: 'column_name' => fn($value) => $normalizedValue|null
   */
  public function __construct(
    Model $model,
    array $columnRules = [],
    array $dateColumns = [],
    array $columnHandlers = []
  ) {
    $this->model = $model;
    $this->columnRules = $columnRules;
    $this->dateColumns = $dateColumns;
    $this->columnHandlers = $columnHandlers;
  }


  /**
   * Perform bulk update
   *
   * @param array $rows Array of rowId => fields
   * @param int|null $modifiedBy Optional user id for tracking
   * @return array Result summary with updated ids and errors
   */
  public function update(array $rows, ?int $modifiedBy = null): array
  {
    $errors = [];
    $updatedIds = [];

    DB::transaction(function () use ($rows, $modifiedBy, &$errors, &$updatedIds) {
      foreach ($rows as $id => $fields) {
        if (empty($fields)) continue;
        Log::info("Updating row: " . json_encode($fields));

        $modelInstance = $this->model->find($id);
        if (!$modelInstance) continue;

        $fieldsForValidation = $fields;

        foreach ($fieldsForValidation as $column => $value) {
          if (isset($this->columnHandlers[$column]) && is_callable($this->columnHandlers[$column])) {
            $fieldsForValidation[$column] = call_user_func($this->columnHandlers[$column], $value);
          }
        }

        $validator = Validator::make($fieldsForValidation, $this->columnRules, [
          '*.integer' => 'Invalid value ":input" for column ":attribute". Must be an integer.',
          '*.string' => 'Invalid value ":input" for column ":attribute". Must be a string.',
          '*.date' => 'Invalid date ":input" for column ":attribute".',
          '*.exists' => 'Value ":input" for column ":attribute" does not exist.',
          '*.unique' => 'Value ":input" for column ":attribute" already exists.',
        ]);

        Log::info("fields: " . json_encode($fields));

        if ($validator->fails()) {
          $errors[$id] = $validator->errors()->all();
          continue;
        }

        $updateData = $fieldsForValidation;

        foreach ($fields as $column => $value) {

          Log::info("value: " . json_encode($value));

          // Apply custom handler first, if defined
          // if (isset($this->columnHandlers[$column]) && is_callable($this->columnHandlers[$column])) {
          //   $value = call_user_func($this->columnHandlers[$column], $value);
          // }

          // Date normalization
          if (in_array($column, $this->dateColumns)) {
            $normalized = $this->normalizeDate($value);
            $updateData[$column] = $normalized;
            continue;
          }

          // Handle array FK for relations
          if (is_array($value) && isset($value['id'])) {
            $updateData[$column] = $value['id'];
            continue;
          }

          $updateData[$column] = $value;
        }

        if (!empty($updateData)) {
          if ($modifiedBy !== null) {
            $updateData['modified_by'] = $modifiedBy;
          }
          $modelInstance->update($updateData);
          $updatedIds[] = $id;
        }
      }
    });

    return [
      'updated' => $updatedIds,
      'errors' => $errors,
    ];
  }

  protected function normalizeDate(string|null $value): ?string
  {
    if ($value === null) return null;

    $value = trim($value);

    // date-only
    if (strlen($value) === 10) {
      $dt = \DateTime::createFromFormat('Y-m-d', $value);
      return $dt ? $dt->format('Y-m-d') : null;
    }

    // datetime with minutes
    if (strlen($value) === 16) {
      $dt = \DateTime::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $value));
      return $dt ? $dt->format('Y-m-d H:i:s') : null;
    }

    // datetime with seconds
    if (strlen($value) === 19) {
      $dt = \DateTime::createFromFormat('Y-m-d H:i:s', str_replace('T', ' ', $value));
      return $dt ? $dt->format('Y-m-d H:i:s') : null;
    }

    return null;
  }
}
