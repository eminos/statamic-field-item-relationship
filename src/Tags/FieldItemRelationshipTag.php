<?php

namespace Eminos\StatamicFieldItemRelationship\Tags;

use Statamic\Tags\Tags;
use Statamic\Fields\Value;
 
class FieldItemRelationshipTag extends Tags
{
    protected static $handle = 'fir';

    protected $path;
    protected $value;
    protected $rawSourceFieldValue;

    public function wildcard($inputString)
    {
        $inputParts = explode(':', $inputString);

        $fieldName = array_shift($inputParts);

        $this->path = implode('.', $inputParts);

        /**
         * @var Value $value
         */
        $this->value = array_get($this->context, $fieldName);

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
        $sourceType = $this->value->fieldtype()->field()->config()['source_type'];

        if ($sourceType === 'sibling_or_ancestor') {
            $sourceFieldHandle = $this->value->fieldtype()->field()->config()['source_field'];
            $sourceField = array_get($this->context, $sourceFieldHandle);
            if (!$sourceField) {
                return null;
            }
            $rawValue = $sourceField->raw();
        }

        if ($sourceType === 'global') {
            $global = \Statamic\Facades\GlobalSet::find($this->value->fieldtype()->field()->config()['source_global']);
            $rawValue = $global->inCurrentSite()->get($this->value->fieldtype()->field()->config()['source_field']);
        }

        return $rawValue;
    }

    private function getTargetValue()
    {
        $saveAs = $this->value->fieldtype()->field()->config()['save_as'];

        if ($saveAs === 'value') {
            return $this->value->raw();
        }

        $values = collect($this->rawSourceFieldValue);

        if ($saveAs === 'object_key') {
            $objectKey = $this->value->fieldtype()->field()->config()['object_key'];
            
            return $values->firstWhere($objectKey, $this->value->raw());
        }

        if ($saveAs === 'id') {
            $objectKey = 'id';
            
            return $values->firstWhere($objectKey, $this->value->raw());
        }

        if ($saveAs === 'object') {
            $objectKey = $this->value->fieldtype()->field()->config()['object_key'];
            
            return $values->firstWhere($objectKey, $this->value->raw()[$objectKey]);
        }

        if ($saveAs === 'index' || $saveAs === 'key') {
            $index = $this->value->raw();
            
            return $values->get($index);
        }
    }
}