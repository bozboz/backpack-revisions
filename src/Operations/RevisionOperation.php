<?php

namespace Bozboz\BackpackRevisions\Operations;

use Illuminate\Support\Facades\Route;

trait RevisionOperation
{
    protected function revisionListingFunctionality()
    {
        $this->crud->addClause('current');

        $this->crud->addColumn([
            'name' => 'is_published',
            'type' => 'check',
            'label' => 'Published?'
        ]);
    }

    protected function revisionCreateFunctionality()
    {

        $this->crud->replaceSaveActions(
            [
                [
                    'name' => 'save_as_draft',
                    'visible' => function ($crud) {
                        return true;
                    },
                    'button_text' => 'Save as Draft'
                ],
                [
                    'name' => 'save_and_back',
                    'visible' => function ($crud) {
                        return true;
                    },
                    'button_text' => 'Publish and Back'
                ],
                [
                    'name' => 'save_and_edit',
                    'visible' => function ($crud) {
                        return true;
                    },
                    'button_text' => 'Publish and Edit',
                    'redirect' => function ($crud, $request, $itemId) {
                        return $crud->route . '/' . $itemId . '/edit';
                    },
                ]
            ]
        );

        $this->crud->addField([
            'name' => 'hasDrafts',
            'label' => 'Has Drafts',
            'type' => 'text',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled'
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

    public function getHasDraftsAttribute()
    {
        if ($this->is_published) {
            return 'no';
        } else {
            return 'yes';
        }
        // return ($this->is_published ? '' : '✅');
    }

    public function publishRevision($id)
    {
        $this->crud->getEntry($id)->setLive();

        return redirect()->to($this->crud->route . '/' . $id . '/revisions');
    }

    public function revisions()
    {
        // $this->crud->hasAccessOrFail('revisions');

        $id = $this->crud->getCurrentEntryId();

        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Revisions for '.$this->crud->entity_name;

        $this->crud->versions = $this->crud->entry->revisions->merge([$this->data['entry']]);
        $this->crud->versions = $this->crud->versions->sortBy('created_at');

        return view('backpack-revisions::crud.revisions', $this->data);
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

        $this->crud->operation(
            'list',
            function () {
                $this->crud->addButton(
                    'line',
                    'revisions',
                    'view',
                    'backpack-revisions::crud.buttons.revisions',
                    'beginning'
                );
            }
        );
    }
}
