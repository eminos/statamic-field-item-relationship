<?php

namespace Eminos\StatamicFieldItemRelationship\Tags;

use Statamic\Tags\Tags;
 
class FieldItemRelationshipTag extends Tags
{
    protected static $handle = 'fir';

    protected $value;
    protected $field;
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

            $this->field = $this->global->blueprint()->field($fieldName);
            $this->value = $this->global->inCurrentSite()->get($fieldName);
        } else {
            $this->field = $this->context->get($fieldName);
            $this->value = array_get($this->context, $fieldName)->raw();
        }

        if (!$this->value) {
            return null;
        }

        $this->rawSourceFieldValue = $this->getRawSourceFieldValue();

        $targetValue = $this->getTargetValue();

        if ($this->path) {
            return array_get($targetValue, $this->path);
        }

        return $targetValue;
    }

    private function getRawSourceFieldValue()
    {
        $sourceType = $this->field->config()['source_type'];

        if ($sourceType === 'sibling_or_ancestor') {
            $sourceFieldHandle = $this->field->config()['source_field'];

            if ($this->global) {
                return $this->global->inCurrentSite()->get($sourceFieldHandle);
            } else {
                return array_get($this->context, $sourceFieldHandle)->raw();
            }
        }

        if ($sourceType === 'global') {
            $global = \Statamic\Facades\GlobalSet::find($this->field->config()['source_global']);
            return $global->inCurrentSite()->get($this->field->config()['source_field']);
        }
    }

    private function getTargetValue()
    {
        $values = collect($this->rawSourceFieldValue);

        return match ($this->field->config()['save_as']) {
            'value'         => $this->value,
            'object_key'    => $values->firstWhere($this->field->config()['object_key'], $this->value),
            'id'            => $values->firstWhere('id', $this->value),
            'object'        => $values->firstWhere($this->field->config()['object_key'], $this->value[$this->field->config()['object_key']]),
            'index', 'key'  => $values->get($this->value) ?? null,
            default         => null,
        };
    }
}