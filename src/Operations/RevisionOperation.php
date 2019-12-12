<?php

namespace Bozboz\BackpackRevisions\Operations;

use Illuminate\Support\Facades\Route;

trait RevisionOperation
{
    protected function revisionListingFunctionality()
    {
        $this->crud->addClause('current');

        $this->crud->addColumn([
            'name' => 'hasDrafts',
            'type' => 'check',
            'label' => 'Has Drafts'
        ]);
        $this->crud->addColumn([
            'name' => 'uuid',
            'type' => 'text',
            'label' => 'UUID'
        ]);
    }

    protected function revisionCreateFunctionality()
    {
        $this->data['draftAction'] = [
            'active' => [ 'value' => 'save_as_draft', 'label' => 'Save as draft' ],
            'options' => [],
        ];

        $this->crud->addField([
            'name' => 'hasDrafts',
            'label' => 'Has Drafts',
            'type' => 'text',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled'
            ]
        ]);
        $this->crud->addField([
            'name' => 'uuid',
            'label' => 'UUID',
            'type' => 'text',
            'attributes' => [
                'readonly' => 'readonly',
                // 'disabled' => 'disabled'
            ]
        ]);
    }

    protected function setupRevisionsRoutes($segment, $routeName, $controller)
    {
        Route::get(
            $segment.'/{id}/revisions',
            [
                'as'        => $routeName.'.revisions',
                'uses'      => $controller.'@revisions',
                'operation' => 'revisions',
            ]
        );

        Route::get(
            $segment.'/revisions/publish/{id}',
            [
                'as'        => $routeName.'.publishRevision',
                'uses'      => $controller.'@publishRevision',
                'operation' => 'publish',
            ]
        );
    }

    public function publishRevision($id)
    {
        $this->crud->getEntry($id)->setLive();

        return redirect()->to($this->crud->route . '/' . $id . '/revisions');
    }

    public function revisions()
    {
        $this->crud->hasAccessOrFail('revisions');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Revisions for '.$this->crud->entity_name;

        $this->crud->versions = $this->crud->entry->revisions;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view('backpack::crud.revisions', $this->data);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupRevisionsDefaults()
    {
        $this->crud->allowAccess('revisions');
        $this->crud->setOperationSetting('setFromDb', true);

        $this->crud->operation(
            'revisions',
            function () {
                $this->crud->loadDefaultOperationSettingsFromConfig();
            }
        );

        // $this->crud->operation(
        //     'list',
        //     function () {
        //         $this->crud->addButton('line', 'revisions', 'view', 'crud::buttons.revisions', 'beginning');
        //     }
        // );
    }
}
