import FieldItemRelationshipFieldtype from './compontents/FieldItemRelationshipFieldtype.vue';

Statamic.booting(() => {
    Statamic.$components.register('field_item_relationship-fieldtype', FieldItemRelationshipFieldtype);
});
