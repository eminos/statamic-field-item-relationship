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
        </v-select>

        <div v-if="errorMessage" v-text="errorMessage" class="mt-2 text-red-500 text-sm"></div>
    </div>

</template>

<script>
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
                return this.items.find(item => item._id === (this.value._id ?? this.value))
            }

            if (['object', 'object_key'].includes(this.config.save_as)) {
                return this.items.find(item => item[this.config.object_key] === (this.value[this.config.object_key] ?? this.value))
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
                this.update(value._id)
            }

            if (this.config.save_as === 'index') {
                if (this.itemsAreObjects) {
                    this.update(this.items.findIndex(item => item._id === value._id))
                } else {
                    this.update(this.items.findIndex(item => item === value))
                }
            }

            if (this.config.save_as === 'object_key') {
                this.update(value[this.config.object_key])
            }
        },
        getOptionLabel(option) {
            if (this.config.option_label_source) {
                return option[this.config.option_label_source]
            } else {
                return option
            }
        },
    },

    mounted() {
        if (this.config.source_type === 'sibling_or_ancestor') {
            let parent = this.$parent
    
            while (parent) {
                parent.$children.forEach((child) => {
                    if (child.field && child.field.handle === this.config.source_field) {
                        this.sourceComponent = child
                    } 
                    
                    else if (child.config && child.config.handle === this.config.source_field) {
                        this.sourceComponent = child
                    }
                })

                if (this.sourceComponent) {
                    break
                }
    
                parent = parent.$parent
            }
        }
    }
};
</script>