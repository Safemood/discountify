<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

/**
 * Trait HasDynamicFields
 *
 * This trait provides methods for managing dynamic fields within Discountify.
 */
trait HasDynamicFields
{
    /**
     * The array to store dynamic field mappings.
     *
     * @var array
     */
    protected $dynamicFields = [];

    /**
     * Set a single dynamic field mapping.
     *
     * @param  string  $fieldName  The name of the field.
     * @param  string  $fieldKey  The key representing the field.
     * @return self Returns the instance of the class implementing the trait.
     */
    public function setField(string $fieldName, string $fieldKey): self
    {
        $this->dynamicFields[$fieldName] = $fieldKey;

        return $this;
    }

    /**
     * Set multiple dynamic field mappings.
     *
     * @param  array  $fields  An associative array of field mappings.
     * @return self Returns the instance of the class implementing the trait.
     */
    public function setFields(array $fields): self
    {
        foreach ($fields as $fieldName => $fieldKey) {
            $this->setField($fieldName, $fieldKey);
        }

        return $this;
    }

    /**
     * Get all dynamic field mappings.
     *
     * @return array An associative array containing dynamic field mappings.
     */
    public function getFields(): array
    {
        return $this->dynamicFields;
    }

    /**
     * Get the value of a dynamic field for a given item.
     *
     * @param  array  $item  The item for which the dynamic field value is retrieved.
     * @param  string  $fieldName  The name of the dynamic field.
     * @return mixed|null The value of the dynamic field or null if not found.
     */
    public function getField(array $item, string $fieldName)
    {
        $fieldMapping = $this->dynamicFields + $this->getDefaultFields();

        $fieldKey = $fieldMapping[$fieldName] ?? null;

        if ($fieldKey !== null) {
            return $item[$fieldKey] ?? null;
        }

        return $item[$fieldName] ?? null;
    }

    /**
     * Get the default dynamic field configuration from the package's configuration.
     *
     * @return array An associative array containing default dynamic field configurations.
     */
    protected function getDefaultFields(): array
    {
        return config('discountify.fields', []);
    }
}
