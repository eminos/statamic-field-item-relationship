<template>

    <div>
        <v-select
        append-to-body
        :value="selected"
        :searchable="false"
        :get-option-label="getOptionLabel"
        :options="items"
        @input="onChange">
            <template #no-options>
                <div class="my-2">
                    Source field "{{ config.source_field }}" has no items.
                </div>
            </template>
            <template #option="option">
                <div v-html="getOptionLabel(option)"></div>
            </template>
            <template #selected-option="option">
                <div v-html="getOptionLabel(option)"></div>
            </template>
        </v-select>

        <div v-if="errorMessage" v-text="errorMessage" class="mt-2 text-red-500 text-sm"></div>
    </div>

</template>

<script>
import { get } from '../helpers.js'

export default {

    mixins: [Fieldtype],

    data() {
        return {
            sourceComponent: null,
        };
    },

    computed: {
        sourceValue() {
            if (this.config.source_type === 'sibling_or_ancestor' && this.sourceComponent) {
                return this.sourceComponent.value
            }

            if (this.config.source_type === 'global') {
                return this.meta.source_value
            }
        },
        errorMessage() {
            if (this.sourceValueIsObject && !['key', 'value'].includes(this.config.save_as)) {
                return `The source field "${this.config.source_field}" data is an object. Please set the "Save as" to "Key" or "Value".`
            }

            if (this.itemsAreObjects === false && this.config.save_as !== 'value') {
                return `The source field "${this.config.source_field}" data is a simple array of strings. Please set the "Save as" to "Value".`
            }

            if (this.itemsAreObjects && this.config.save_as === 'value') {
                return `The source field "${this.config.source_field}" data is an array of objects. To save the entire object, please set the "Save as" option to "Object" instead of "Value".`
            }

            if (this.config.source_type === 'sibling_or_ancestor' && !this.sourceComponent) {
                return `The source field "${this.config.source_field}" could not be found.`
            }

            if (this.config.source_type === 'global' && !this.meta.source_value) {
                return this.meta.error_message
            }
        },
        sourceValueIsObject() {
            if (this.sourceValue) {
                return typeof this.sourceValue === 'object' && !Array.isArray(this.sourceValue)
            }
        },
        items() {
            if (this.sourceValue && this.sourceValueIsObject) {
                return Object.keys(this.sourceValue).map(key => {
                    return {
                        key: key,
                        value: this.sourceValue[key]
                    }
                })
            }

            return this.sourceValue ?? []
        },
        itemsAreObjects() {
            if (this.items) {
                return this.items[0] && typeof this.items[0] === 'object' && !Array.isArray(this.items[0])
            }
        },
        selected() {
            if (this.value === null || this.value === undefined || this.value === '' || !this.items || !this.sourceValue) {
                return
            }

            if (this.config.save_as === 'key') {
                if (this.sourceValueIsObject) {
                    return this.items.find(item => item.key === this.value)
                } else {
                    return this.items[this.value]
                }
            }

            if (this.config.save_as === 'value') {
                return this.items.find(item => item === this.value)
            }

            if (this.config.save_as === 'index') {
                return this.items[this.value]
            }

            if (this.config.save_as === 'id') {
                return this.items.find(item => item._id === (this.value._id ?? this.value.id ?? this.value) || item.id === (this.value._id ?? this.value.id ?? this.value));
            }

            if (['object', 'object_key'].includes(this.config.save_as)) {
                const objectKey = this.config.object_key
                const value = get(this.value, objectKey, this.value)
                return this.items.find(item => get(item, objectKey) === value)
            }
        },
    },

    methods: {
        onChange(value) {
            if (!value) {
                this.update(null)
                return
            }

            if (this.config.save_as === 'key') {
                if (this.sourceValueIsObject) {
                    this.update(value.key)
                } else { 
                    // if the source value is not an object, we assume it's an array and we treat key as index
                    this.update(this.items.findIndex(item => item === value))
                }
            }

            if (this.config.save_as === 'value') {
                if (this.sourceValueIsObject) {
                    this.update(value.value)
                } else {
                    this.update(value)
                }
            }

            if (this.config.save_as === 'object') {
                this.update(value)
            }

            if (this.config.save_as === 'id') {
                this.update(value._id ?? value.id)
            }

            if (this.config.save_as === 'index') {
                if (this.itemsAreObjects) {
                    this.update(this.items.findIndex(item => {
                        const itemId = item._id ?? item.id
                        const valueId = value._id ?? value.id
                        return itemId === valueId
                    }))
                } else {
                    this.update(this.items.findIndex(item => item === value))
                }
            }

            if (this.config.save_as === 'object_key') {
                this.update(get(value, this.config.object_key))
            }
        },
        getOptionLabel(option) {
            if (this.config.option_label_source) {
                if (this.config.option_label_source.startsWith('`') && this.config.option_label_source.endsWith('`')) {
                    const o = option // just a shorter option
                    return (function() {
                        return eval(this.config.option_label_source)
                    }).call(this, o)
                }
                
                return get(option, this.config.option_label_source, option)
            }

            return option
        }
    },

    mounted() {
        if (this.config.source_type === 'sibling_or_ancestor') {

            function cssEscape(string) {
                return string.replace(/[!"#$%&'()*+,\/:;<=>?@[\\\]^`{|}~]/g, '\\$&')
            }

            const element = document.querySelector(cssEscape(`.publish-field__${this.config.source_field}`))

            if (
                element && element.__vue__ &&
                (
                    (element.__vue__.field && element.__vue__.field.handle === this.config.source_field) ||
                    (element.__vue__.config && element.__vue__.config.handle === this.config.source_field)
                )
            ) {
                this.sourceComponent = element.__vue__
            }

        }
    }
};
</script>
