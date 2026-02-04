<?php

namespace Eminos\StatamicFieldItemRelationship\Tags;

use Statamic\Tags\Tags;
 
class FieldItemRelationshipTag extends Tags
{
    protected static $handle = 'fir';

    protected $value;
    protected $fieldtype;
    protected $global;
    protected $path;
    protected $rawSourceFieldValue;

    public function wildcard($inputString)
    {
        if ($inputString === 'index') {
            $fieldName = $this->params->get('field');
            $this->path = $this->params->get('path');
        } else {
            $inputParts = explode(':', $inputString, 3);

            if (count($inputParts) === 3) {
                $fieldName = $inputParts[0] . ':' . $inputParts[1];
                $this->path = str_replace(':', '.', $inputParts[2]);
            } else {
                $fieldName = array_shift($inputParts);
                $this->path = implode('.', $inputParts);
            }
        }

        if (str_contains($fieldName, ':')) {
            [$globalHandle, $fieldName] = explode(':', $fieldName, 2);

            $this->global = \Statamic\Facades\GlobalSet::find($globalHandle);

            $this->fieldtype = $this->global->blueprint()->field($fieldName)->fieldtype();
            $this->value = $this->global->inCurrentSite()->get($fieldName);
        } else {
            $this->fieldtype = $this->context->get($fieldName)->fieldtype();
            $this->value = $this->context->raw($fieldName);
        }

        if ($this->value === null) {
            return null;
        }

        $this->rawSourceFieldValue = $this->getRawSourceFieldValue();

        $targetValue = $this->getTargetValue();

        if ($this->path) {
            return data_get($targetValue, $this->path);
        }

        return $targetValue;
    }

    private function getRawSourceFieldValue()
    {
        $sourceType = $this->fieldtype->config()['source_type'];

        if ($sourceType === 'sibling_or_ancestor') {
            $sourceFieldHandle = $this->fieldtype->config()['source_field'];

            if ($this->global) {
                return $this->global->inCurrentSite()->get($sourceFieldHandle);
            } else {
                return $this->context->raw($sourceFieldHandle);
            }
        }

        if ($sourceType === 'global') {
            $global = \Statamic\Facades\GlobalSet::find($this->fieldtype->config()['source_global']);
            return $global->inCurrentSite()->get($this->fieldtype->config()['source_field']);
        }
    }

    private function getTargetValue()
    {
        $values = collect($this->rawSourceFieldValue);

        return match ($this->fieldtype->config()['save_as']) {
            'value'         => $this->value,
            'object_key'    => $values->firstWhere($this->fieldtype->config()['object_key'], $this->value),
            'id'            => $values->firstWhere('id', $this->value),
            'object'        => $values->firstWhere($this->fieldtype->config()['object_key'], $this->value[$this->fieldtype->config()['object_key']]),
            'index', 'key'  => $values->get($this->value) ?? null,
            default         => null,
        };
    }
}