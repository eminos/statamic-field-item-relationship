<template>

    <div>
        <Combobox
            :model-value="selectedValue"
            :options="selectOptions"
            :searchable="false"
            :clearable="true"
            :label-html="true"
            @update:modelValue="onChange"
        >
            <template #no-options>
                <div class="my-2">
                    Source field "{{ config.source_field }}" has no items.
                </div>
            </template>
            <template #option="option">
                <span v-html="option.label"></span>
            </template>
            <template #selected-option="{ option }">
                <span v-html="option.label"></span>
            </template>
        </Combobox>

        <div v-if="errorMessage" v-text="errorMessage" class="mt-2 text-red-500 text-sm"></div>
    </div>

</template>

<script setup>
import { computed, inject, getCurrentInstance } from 'vue';
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Combobox } from '@statamic/cms/ui';
import { get } from '../helpers.js';

defineOptions({ mixins: [Fieldtype] });

const { proxy } = getCurrentInstance();
const publishContainer = inject('PublishContainerContext', null);

const sourceValue = computed(() => {
    if (proxy.config.source_type === 'sibling_or_ancestor') {
        return publishContainer?.values.value[proxy.config.source_field];
    }

    if (proxy.config.source_type === 'global') {
        return proxy.meta.source_value;
    }
});

const sourceValueIsObject = computed(() => {
    if (sourceValue.value) {
        return typeof sourceValue.value === 'object' && !Array.isArray(sourceValue.value);
    }
});

const items = computed(() => {
    if (sourceValue.value && sourceValueIsObject.value) {
        return Object.keys(sourceValue.value).map(key => {
            return {
                key: key,
                value: sourceValue.value[key]
            };
        });
    }

    return sourceValue.value ?? [];
});

const itemsAreObjects = computed(() => {
    if (items.value) {
        return items.value[0] && typeof items.value[0] === 'object' && !Array.isArray(items.value[0]);
    }
});

const errorMessage = computed(() => {
    if (sourceValueIsObject.value && !['key', 'value'].includes(proxy.config.save_as)) {
        return `The source field "${proxy.config.source_field}" data is an object. Please set the "Save as" to "Key" or "Value".`;
    }

    if (itemsAreObjects.value === false && !['value', 'index'].includes(proxy.config.save_as)) {
        return `The source field "${proxy.config.source_field}" data is a simple array of strings. Please set the "Save as" to "Value" or "Index".`;
    }

    if (itemsAreObjects.value && proxy.config.save_as === 'value') {
        return `The source field "${proxy.config.source_field}" data is an array of objects. To save the entire object, please set the "Save as" option to "Object" instead of "Value".`;
    }

    if (proxy.config.source_type === 'sibling_or_ancestor' && publishContainer?.values.value[proxy.config.source_field] === undefined) {
        return `The source field "${proxy.config.source_field}" could not be found.`;
    }

    if (proxy.config.source_type === 'global' && !proxy.meta.source_value) {
        return proxy.meta.error_message;
    }
});

function getOptionLabel(option) {
    if (proxy.config.option_label_source) {
        if (proxy.config.option_label_source.startsWith('`') && proxy.config.option_label_source.endsWith('`')) {
            const o = option;
            return (function() {
                return eval(proxy.config.option_label_source);
            }).call({ config: proxy.config }, o);
        }

        return get(option, proxy.config.option_label_source, option);
    }

    return option;
}

const selectOptions = computed(() => {
    return items.value.map((item, index) => {
        const label = String(getOptionLabel(item));
        return {
            label: label,
            value: String(index),
        };
    });
});

const selectedValue = computed(() => {
    if (proxy.value === null || proxy.value === undefined || proxy.value === '' || !items.value || !sourceValue.value) {
        return null;
    }

    if (proxy.config.save_as === 'key') {
        if (sourceValueIsObject.value) {
            const idx = items.value.findIndex(item => item.key === proxy.value);
            return idx >= 0 ? String(idx) : null;
        } else {
            return typeof proxy.value === 'number' && proxy.value < items.value.length ? String(proxy.value) : null;
        }
    }

    if (proxy.config.save_as === 'value') {
        const idx = items.value.findIndex(item => item === proxy.value);
        return idx >= 0 ? String(idx) : null;
    }

    if (proxy.config.save_as === 'index') {
        return typeof proxy.value === 'number' && proxy.value < items.value.length ? String(proxy.value) : null;
    }

    if (proxy.config.save_as === 'id') {
        const idx = items.value.findIndex(item => item._id === (proxy.value._id ?? proxy.value.id ?? proxy.value) || item.id === (proxy.value._id ?? proxy.value.id ?? proxy.value));
        return idx >= 0 ? String(idx) : null;
    }

    if (['object', 'object_key'].includes(proxy.config.save_as)) {
        const objectKey = proxy.config.object_key;
        const val = get(proxy.value, objectKey, proxy.value);
        const idx = items.value.findIndex(item => get(item, objectKey) === val);
        return idx >= 0 ? String(idx) : null;
    }

    return null;
});

function onChange(selectedIndex) {
    if (selectedIndex === null || selectedIndex === undefined) {
        proxy.update(null);
        return;
    }

    const idx = parseInt(selectedIndex, 10);
    const value = items.value[idx];

    if (!value && value !== 0) {
        proxy.update(null);
        return;
    }

    if (proxy.config.save_as === 'key') {
        if (sourceValueIsObject.value) {
            proxy.update(value.key);
        } else {
            proxy.update(items.value.findIndex(item => item === value));
        }
    }

    if (proxy.config.save_as === 'value') {
        if (sourceValueIsObject.value) {
            proxy.update(value.value);
        } else {
            proxy.update(value);
        }
    }

    if (proxy.config.save_as === 'object') {
        proxy.update(value);
    }

    if (proxy.config.save_as === 'id') {
        proxy.update(value._id ?? value.id);
    }

    if (proxy.config.save_as === 'index') {
        if (itemsAreObjects.value) {
            proxy.update(items.value.findIndex(item => {
                const itemId = item._id ?? item.id;
                const valueId = value._id ?? value.id;
                return itemId === valueId;
            }));
        } else {
            proxy.update(items.value.findIndex(item => item === value));
        }
    }

    if (proxy.config.save_as === 'object_key') {
        proxy.update(get(value, proxy.config.object_key));
    }
}


</script>
