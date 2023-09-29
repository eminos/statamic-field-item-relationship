<?php

namespace Eminos\StatamicFieldItemRelationship;

use Statamic\Providers\AddonServiceProvider;
use Eminos\StatamicFieldItemRelationship\Fieldtypes\FieldItemRelationshipFieldtype;
use Eminos\StatamicFieldItemRelationship\Tags\FieldItemRelationshipTag;

class ServiceProvider extends AddonServiceProvider
{

    public function __construct()
    {
        $this->vite['hotFile'] = base_path('vendor/eminos/statamic-field-item-relationship/dist/vite.hot');

        parent::__construct(app());
    }

    protected $fieldtypes = [
        FieldItemRelationshipFieldtype::class,
    ];

    protected $tags = [
        FieldItemRelationshipTag::class,
    ];

    protected $vite = [
        'hotFile' => null, // set in the constructor for reasons
        'publicDirectory' => 'dist',
        'input' => [
            'resources/js/field-item-relationship-fieldtype.js'
        ],
    ];
}