import FieldItemRelationshipFieldtype from './components/FieldItemRelationshipFieldtype.vue';

Statamic.booting(() => {
    Statamic.$components.register('field_item_relationship-fieldtype', FieldItemRelationshipFieldtype);
});
