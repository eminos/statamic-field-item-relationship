<?php

use Statamic\Facades\Antlers;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;

beforeEach(function () {
    $this->listItems = ['Item 1', 'Item 2', 'Item 3'];

    $this->objectItems = [
        ['id' => 'a', 'slug' => 'alpha', 'name' => 'Alpha'],
        ['id' => 'b', 'slug' => 'beta', 'name' => 'Beta'],
        ['id' => 'c', 'slug' => 'gamma', 'name' => 'Gamma'],
    ];
});

function createCollectionWithBlueprint(string $saveAs, array $extraFirConfig = []): void
{
    Collection::make('pages')->save();

    $firConfig = array_merge([
        'type' => 'field_item_relationship',
        'source_type' => 'sibling_or_ancestor',
        'source_field' => 'list_field',
        'save_as' => $saveAs,
    ], $extraFirConfig);

    $blueprint = Blueprint::makeFromFields([
        'list_field' => ['type' => 'yaml'],
        'fir_field' => $firConfig,
    ]);

    $blueprint->setHandle('pages')->setNamespace('collections.pages')->save();
}

function createEntry(array $data): \Statamic\Entries\Entry
{
    $entry = Entry::make()
        ->collection('pages')
        ->slug('test')
        ->data($data);

    $entry->save();

    return $entry;
}

function renderTag(string $template, \Statamic\Entries\Entry $entry): string
{
    return trim((string) Antlers::parse($template, $entry->toAugmentedArray()));
}

// --- save_as: value ---

it('returns the value for save_as value', function () {
    createCollectionWithBlueprint('value');

    $entry = createEntry([
        'list_field' => $this->listItems,
        'fir_field' => 'Item 2',
    ]);

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('Item 2');
});

// --- save_as: index ---

it('returns the item at index for save_as index', function () {
    createCollectionWithBlueprint('index');

    $entry = createEntry([
        'list_field' => $this->listItems,
        'fir_field' => 1,
    ]);

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('Item 2');
});

it('returns the first item when index is 0', function () {
    createCollectionWithBlueprint('index');

    $entry = createEntry([
        'list_field' => $this->listItems,
        'fir_field' => 0,
    ]);

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('Item 1');
});

// --- save_as: key ---

it('returns the item at key for save_as key', function () {
    createCollectionWithBlueprint('key');

    $entry = createEntry([
        'list_field' => $this->listItems,
        'fir_field' => 2,
    ]);

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('Item 3');
});

// --- save_as: id ---

it('returns the object by id for save_as id', function () {
    createCollectionWithBlueprint('id');

    $entry = createEntry([
        'list_field' => $this->objectItems,
        'fir_field' => 'b',
    ]);

    $result = renderTag('{{ fir:fir_field:name }}', $entry);

    expect($result)->toBe('Beta');
});

// --- save_as: object_key ---

it('returns the object by object_key for save_as object_key', function () {
    createCollectionWithBlueprint('object_key', ['object_key' => 'slug']);

    $entry = createEntry([
        'list_field' => $this->objectItems,
        'fir_field' => 'alpha',
    ]);

    $result = renderTag('{{ fir:fir_field:name }}', $entry);

    expect($result)->toBe('Alpha');
});

// --- save_as: object ---

it('returns the full object for save_as object', function () {
    createCollectionWithBlueprint('object', ['object_key' => 'slug']);

    $entry = createEntry([
        'list_field' => $this->objectItems,
        'fir_field' => ['id' => 'b', 'slug' => 'beta', 'name' => 'Beta'],
    ]);

    $result = renderTag('{{ fir:fir_field:name }}', $entry);

    expect($result)->toBe('Beta');
});

// --- null value ---

it('returns empty string when value is null', function () {
    createCollectionWithBlueprint('value');

    $entry = createEntry([
        'list_field' => $this->listItems,
        'fir_field' => null,
    ]);

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('');
});

// --- path parameter ---

it('returns a nested value via path in tag syntax', function () {
    createCollectionWithBlueprint('id');

    $entry = createEntry([
        'list_field' => $this->objectItems,
        'fir_field' => 'a',
    ]);

    $result = renderTag('{{ fir:fir_field:slug }}', $entry);

    expect($result)->toBe('alpha');
});

it('returns a nested value using field and path parameters', function () {
    createCollectionWithBlueprint('id');

    $entry = createEntry([
        'list_field' => $this->objectItems,
        'fir_field' => 'c',
    ]);

    $result = renderTag('{{ fir field="fir_field" path="name" }}', $entry);

    expect($result)->toBe('Gamma');
});

// --- global source ---

it('resolves value from a global set source', function () {
    // Create the global set
    $globalSet = GlobalSet::make('my_globals')->title('My Globals');
    $globalSet->save();

    $variables = $globalSet->makeLocalization('default');
    $variables->data(['source_list' => ['Global Item 1', 'Global Item 2', 'Global Item 3']]);
    $variables->save();

    // Blueprint for the global set with the source field
    $globalBlueprint = Blueprint::makeFromFields([
        'source_list' => ['type' => 'yaml'],
    ]);
    $globalBlueprint->setHandle('my_globals')->setNamespace('globals')->save();

    // Create collection with FIR field pointing to the global
    Collection::make('pages')->save();

    $blueprint = Blueprint::makeFromFields([
        'fir_field' => [
            'type' => 'field_item_relationship',
            'source_type' => 'global',
            'source_global' => 'my_globals',
            'source_field' => 'source_list',
            'save_as' => 'value',
        ],
    ]);
    $blueprint->setHandle('pages')->setNamespace('collections.pages')->save();

    $entry = Entry::make()
        ->collection('pages')
        ->slug('test')
        ->data(['fir_field' => 'Global Item 2']);
    $entry->save();

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('Global Item 2');
});

it('resolves index from a global set source', function () {
    $globalSet = GlobalSet::make('test_globals')->title('Test Globals');
    $globalSet->save();

    $variables = $globalSet->makeLocalization('default');
    $variables->data(['items' => ['First', 'Second', 'Third']]);
    $variables->save();

    $globalBlueprint = Blueprint::makeFromFields([
        'items' => ['type' => 'yaml'],
    ]);
    $globalBlueprint->setHandle('test_globals')->setNamespace('globals')->save();

    Collection::make('articles')->save();

    $blueprint = Blueprint::makeFromFields([
        'fir_field' => [
            'type' => 'field_item_relationship',
            'source_type' => 'global',
            'source_global' => 'test_globals',
            'source_field' => 'items',
            'save_as' => 'index',
        ],
    ]);
    $blueprint->setHandle('articles')->setNamespace('collections.articles')->save();

    $entry = Entry::make()
        ->collection('articles')
        ->slug('test')
        ->data(['fir_field' => 0]);
    $entry->save();

    $result = renderTag('{{ fir:fir_field }}', $entry);

    expect($result)->toBe('First');
});

// --- global source via field parameter syntax ---

it('resolves value from global via field parameter', function () {
    $globalSet = GlobalSet::make('settings')->title('Settings');
    $globalSet->save();

    $variables = $globalSet->makeLocalization('default');
    $variables->data([
        'options' => ['Option A', 'Option B'],
        'chosen_option' => 'Option A',
    ]);
    $variables->save();

    $globalBlueprint = Blueprint::makeFromFields([
        'options' => ['type' => 'yaml'],
        'chosen_option' => [
            'type' => 'field_item_relationship',
            'source_type' => 'sibling_or_ancestor',
            'source_field' => 'options',
            'save_as' => 'value',
        ],
    ]);
    $globalBlueprint->setHandle('settings')->setNamespace('globals')->save();

    // Use the field parameter: {{ fir field="global_handle:field_name" }}
    Collection::make('posts')->save();
    $postsBlueprint = Blueprint::makeFromFields([]);
    $postsBlueprint->setHandle('posts')->setNamespace('collections.posts')->save();

    $entry = Entry::make()
        ->collection('posts')
        ->slug('test')
        ->data([]);
    $entry->save();

    $result = renderTag('{{ fir field="settings:chosen_option" }}', $entry);

    expect($result)->toBe('Option A');
});

// --- global source via three-part tag syntax (fir:global:field:path) ---

it('resolves nested value from global via three-part tag syntax', function () {
    $globalSet = GlobalSet::make('config')->title('Config');
    $globalSet->save();

    $variables = $globalSet->makeLocalization('default');
    $variables->data([
        'items' => [
            ['id' => 'x', 'slug' => 'x-item', 'label' => 'X Label'],
            ['id' => 'y', 'slug' => 'y-item', 'label' => 'Y Label'],
        ],
        'selected_item' => 'x',
    ]);
    $variables->save();

    $globalBlueprint = Blueprint::makeFromFields([
        'items' => ['type' => 'yaml'],
        'selected_item' => [
            'type' => 'field_item_relationship',
            'source_type' => 'sibling_or_ancestor',
            'source_field' => 'items',
            'save_as' => 'id',
        ],
    ]);
    $globalBlueprint->setHandle('config')->setNamespace('globals')->save();

    Collection::make('posts')->save();
    $postsBlueprint = Blueprint::makeFromFields([]);
    $postsBlueprint->setHandle('posts')->setNamespace('collections.posts')->save();

    $entry = Entry::make()
        ->collection('posts')
        ->slug('test')
        ->data([]);
    $entry->save();

    // Three-part syntax: fir:global_handle:field_name:path
    $result = renderTag('{{ fir:config:selected_item:label }}', $entry);

    expect($result)->toBe('X Label');
});
