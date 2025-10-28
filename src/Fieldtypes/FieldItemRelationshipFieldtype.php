<?php

namespace Eminos\StatamicFieldItemRelationship\Fieldtypes;

use Statamic\Fields\Fieldtype;

class FieldItemRelationshipFieldtype extends Fieldtype
{
    protected $categories = ['relationship'];

    protected $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M24 30v-6M6 30h36v12H6V30Zm0-17.5v-1M6 18v-1M6 7V6m36 6.5v-1m0 6.5v-1m0-10V6m0 12h-1M7 18H6M7 6H6m8 0h-1m8 0h-1m1 12h-1m8-12h-1M14 18h-1m15 0h-1m8-12h-1m1 12h-1m8-12h-1"/></svg>';

    /**
     * The blank/default value.
     *
     * @return array
     */
    public function defaultValue()
    {
        return null;
    }

    /**
     * Pre-process the data before it gets sent to the publish page.
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function preProcess($data)
    {
        return $data;
    }

    /**
     * Process the data before it gets saved.
     *
     * @param mixed $data
     * @return array|mixed
     */
    public function process($data)
    {
        return $data;
    }

    public function preload()
    {
        if ($this->config('source_type') === 'global') {
            $global = \Statamic\Facades\GlobalSet::find($this->config('source_global_set'));

            if (!$global) {
                return [
                    'source_value' => null,
                    'error_message' => "Global set \"{$this->config('source_global_set')}\" not found.",
                ];
            }
            
            $site = request()->query('site');

            $site = $this->field?->parent()?->locale() ?? $site;
            
            if ($site) {
              $sourceValue = $global->in($site)->get($this->config('source_field'));
            } else {
              $sourceValue = $global->inCurrentSite()->get($this->config('source_field'));
            }

            if (!$sourceValue) {
                return [
                    'source_value' => null,
                    'error_message' => "Field \"{$this->config('source_field')}\" not found in global set \"{$this->config('source_global_set')}\".",
                ];
            }

            return [
                'source_value' => $sourceValue,
            ];
        }
    }

    protected function configFieldItems(): array
    {
        $globalSets = collect(\Statamic\Facades\GlobalSet::all())
        ->mapWithKeys(function ($globalSet) {
            return [$globalSet->handle() => $globalSet->title()];
        })
        ->toArray();

        return [
            'source_type' => [
                'type' => 'button_group',
                'display' => 'Source type',
                'instructions' => 'Where is the source field located?',
                'options' => [
                    'sibling_or_ancestor' => 'Sibling or ancestor',
                    'global' => 'Global',
                ],
                'default' => 'sibling_or_ancestor',
            ],
            'source_global_set' => [
                'type' => 'select',
                'display' => 'Source global set',
                'instructions' => 'The global set where the source field is located.',
                'if' => [
                    'source_type' => 'global',
                ],
                'clearable' => true,
                'options' => $globalSets,
            ],
            'source_field' => [
                'type' => 'text',
                'display' => 'Source field',
                'instructions' => 'The handle for the field from which to retrieve the items.',
            ],
            'option_label_source' => [
                'type' => 'text',
                'display' => 'Option label source',
                'instructions' => 'If the source field saves its value as an array of objects, which object key should be the label.<br>You can use dot notation to access nested keys.<br>For more advanced use cases, you can use a string literal (\`back ticks\`) and use the `o` variable to access the option value object.',
            ],
            'save_as' => [
                'type' => 'button_group',
                'display' => 'Save as',
                'instructions' => 'What should be saved to the field?',
                'options' => [
                    'key' => 'Key',
                    'value' => 'Value',
                    'index' => 'Index',
                    'id' => 'Item ID',
                    'object_key' => 'Object key (field handle)',
                    'object' => 'Object',
                ],
            ],
            'object_key' => [
                'type' => 'text',
                'display' => 'Object key',
                'instructions' => 'The object key (field handle) to save and use when retrieving the object.',
                'if' => [
                    'save_as' => 'contains object',
                ],
            ],

        ];
    }
}
