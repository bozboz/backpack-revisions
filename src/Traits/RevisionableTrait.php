<?php

namespace Bozboz\BackpackRevisions\Traits;

use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

trait RevisionableTrait
{
    /**
     * Set up fillable fields when the class is initialized
     *
     * @return void
     */
    public function initializeRevisionableTrait()
    {
        $this->fillable[] = 'uuid';
        $this->fillable[] = 'is_published';
        $this->fillable[] = 'is_current';
    }

    /**
     * Add listeners for CRUD events to set revision data
     *
     * @return void
     */
    public static function bootRevisionableTrait()
    {
        static::creating(function ($model) {
            $model->generateUuid();
            $model->is_current = true;

            if (! key_exists('is_published', $model->attributes)) {
                $model->is_published = false;
            }
        });

        static::updating(function ($model) {
            $model->newRevision();
        });

        static::updated(function ($model) {
            $model->setCurrent();
        });
    }

    protected function performUpdate(Builder $query)
    {
        dump($this->attributes, $this->original);
        parent::performUpdate($query);
        dd($this->original);
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        $saveAction = \Request::input('save_action', session('update')['saveAction']);

        if ($saveAction == 'save_as_draft') {
            $this->newDraft();
        } else {
            $this->newPublished();
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    protected function newRevision()
    {
        $this->withoutEventDispatcher(function () {
            $revision = $this->replicate();
            $revision->created_at = $this->created_at;
            $revision->updated_at = $this->getOriginal('updated_at');
            $revision->is_published = false;
            $revision->is_current = false;
            $revision->save();

            if ($this->is_published) {
                $this->setLive();
            }
        });
    }

    protected function newDraft()
    {
        $storedVersion = get_class($this)::where('id', $this->id)->first();

        $newVersion = $storedVersion->replicate();
        $newVersion->created_at = $storedVersion->created_at;
        $newVersion->updated_at = $storedVersion->updated_at;

        $newVersion->is_current = false;
        $newVersion->save();

        $this->is_published = false;
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopePublished($query)
    {
        $query->where('is_published', true);
    }

    public function revisions()
    {
        return $this->hasMany(static::class, 'uuid', 'uuid')->where('id', '<>', $this->id)->orderBy('updated_at');
    }

    public function scopeUuid($query, $uuid)
    {
        $query->where('uuid', $uuid);
    }

    public function generateUuid()
    {
        if ($this->uuid) {
            return;
        }
        $this->uuid = (string) Uuid::generate();
    }

    public function setCurrent()
    {
        $this->withoutEventDispatcher(function () {
            $this->revisions()->update(['is_current' => false]);
            $this->setAttribute('is_current', true)->save();
        });
    }

    public function setLive()
    {
        $this->withoutEventDispatcher(function () {
            $this->revisions()->published()->update(['is_published' => false]);

            $this->is_published = true;
            $this->is_current = true;
            $this->save();

            static::where('updated_at', '>', $this->updated_at)->delete();
        });
    }
}
