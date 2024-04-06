<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

/**
 * Trait HasDynamicFields
 */
trait HasDynamicFields
{
    protected $dynamicFields = [];

    /**
     * Set a single dynamic field mapping.
     */
    public function setField(string $fieldName, string $fieldKey): self
    {
        $this->dynamicFields[$fieldName] = $fieldKey;

        return $this;
    }

    /**
     * Set multiple dynamic field mappings.
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
     */
    public function getFields(): array
    {
        return $this->dynamicFields;
    }

    /**
     * Get the value of a dynamic field for a given item.
     */
    protected function getField(array $item, string $fieldName)
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
     */
    protected function getDefaultFields(): array
    {
        return config('discountify.fields', []);
    }
}
