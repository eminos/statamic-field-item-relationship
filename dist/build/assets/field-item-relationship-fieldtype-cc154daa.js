function p(e,s,n,h,r,a,u,l){var t=typeof e=="function"?e.options:e;s&&(t.render=s,t.staticRenderFns=n,t._compiled=!0),h&&(t.functional=!0),a&&(t._scopeId="data-v-"+a);var o;if(u?(o=function(i){i=i||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,!i&&typeof __VUE_SSR_CONTEXT__<"u"&&(i=__VUE_SSR_CONTEXT__),r&&r.call(this,i),i&&i._registeredComponents&&i._registeredComponents.add(u)},t._ssrRegister=o):r&&(o=l?function(){r.call(this,(t.functional?this.parent:this).$root.$options.shadowRoot)}:r),o)if(t.functional){t._injectStyles=o;var d=t.render;t.render=function(_,c){return o.call(c),d(_,c)}}else{var f=t.beforeCreate;t.beforeCreate=f?[].concat(f,o):[o]}return{exports:e,options:t}}const m={mixins:[Fieldtype],data(){return{sourceComponent:null}},computed:{sourceValue(){if(this.config.source_type==="sibling_or_ancestor"&&this.sourceComponent)return this.sourceComponent.value;if(this.config.source_type==="global")return this.meta.source_value},errorMessage(){if(this.sourceValueIsObject&&!["key","value"].includes(this.config.save_as))return`The source field "${this.config.source_field}" data is an object. Please set the "Save as" to "Key" or "Value".`;if(this.itemsAreObjects===!1&&this.config.save_as!=="value")return`The source field "${this.config.source_field}" data is a simple array of strings. Please set the "Save as" to "Value".`;if(this.itemsAreObjects&&this.config.save_as==="value")return`The source field "${this.config.source_field}" data is an array of objects. To save the entire object, please set the "Save as" option to "Object" instead of "Value".`;if(this.config.source_type==="sibling_or_ancestor"&&!this.sourceComponent)return`The source field "${this.config.source_field}" could not be found.`;if(this.config.source_type==="global"&&!this.meta.source_value)return this.meta.error_message},sourceValueIsObject(){if(this.sourceValue)return typeof this.sourceValue=="object"&&!Array.isArray(this.sourceValue)},items(){return this.sourceValue&&this.sourceValueIsObject?Object.keys(this.sourceValue).map(e=>({key:e,value:this.sourceValue[e]})):this.sourceValue??[]},itemsAreObjects(){if(this.items)return this.items[0]&&typeof this.items[0]=="object"&&!Array.isArray(this.items[0])},selected(){if(!(this.value===null||this.value===void 0||this.value===""||!this.items||!this.sourceValue)){if(this.config.save_as==="key")return this.sourceValueIsObject?this.items.find(e=>e.key===this.value):this.items[this.value];if(this.config.save_as==="value")return this.items.find(e=>e===this.value);if(this.config.save_as==="index")return this.items[this.value];if(this.config.save_as==="id")return this.items.find(e=>e._id===(this.value._id??this.value));if(["object","object_key"].includes(this.config.save_as))return this.items.find(e=>e[this.config.object_key]===(this.value[this.config.object_key]??this.value))}}},methods:{onChange(e){if(!e){this.update(null);return}this.config.save_as==="key"&&(this.sourceValueIsObject?this.update(e.key):this.update(this.items.findIndex(s=>s===e))),this.config.save_as==="value"&&(this.sourceValueIsObject?this.update(e.value):this.update(e)),this.config.save_as==="object"&&this.update(e),this.config.save_as==="id"&&this.update(e._id),this.config.save_as==="index"&&(this.itemsAreObjects?this.update(this.items.findIndex(s=>s._id===e._id)):this.update(this.items.findIndex(s=>s===e))),this.config.save_as==="object_key"&&this.update(e[this.config.object_key])},getOptionLabel(e){return this.config.option_label_source?e[this.config.option_label_source]:e}},mounted(){if(this.config.source_type==="sibling_or_ancestor"){let e=this.$parent;for(;e&&(e.$children.forEach(s=>{s.field&&s.field.handle===this.config.source_field?this.sourceComponent=s:s.config&&s.config.handle===this.config.source_field&&(this.sourceComponent=s)}),!this.sourceComponent);)e=e.$parent}}};var g=function(){var s=this,n=s._self._c;return n("div",[n("v-select",{attrs:{"append-to-body":"",value:s.selected,searchable:!1,"get-option-label":s.getOptionLabel,options:s.items},on:{input:s.onChange},scopedSlots:s._u([{key:"no-options",fn:function(){return[n("div",{staticClass:"my-2"},[s._v(' Source field "'+s._s(s.config.source_field)+'" has no items. ')])]},proxy:!0}])}),s.errorMessage?n("div",{staticClass:"mt-2 text-red-500 text-sm",domProps:{textContent:s._s(s.errorMessage)}}):s._e()],1)},v=[],b=p(m,g,v,!1,null,null,null,null);const y=b.exports;Statamic.booting(()=>{Statamic.$components.register("field_item_relationship-fieldtype",y)});