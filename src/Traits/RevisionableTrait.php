<?php

namespace Bozboz\BackpackRevisions\Traits;

use Webpatser\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
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

            if (Str::contains(Request::input('save_action'), 'draft')) {
                $model->setAttribute('is_published', false);
            } else {
                $model->setAttribute('is_published', true);
            }
        });

        static::updating(function ($model) {
            $model->newRevision();
        });

        static::updated(function ($model) {
            $model->setCurrent();
        });

        static::deleted(function ($model) {
            $model->revisions()->delete();
        });
    }

    protected function newRevision()
    {
        $this->withoutEvents(function () {
            $revision = $this->fresh()->replicate();
            $revision->created_at = $this->created_at;
            $revision->save();

            if (Str::contains(Request::input('save_action'), 'draft')) {
                $this->setAttribute('is_published', false);
            } else {
                $this->setLive();
            }
        });
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
        $this->withoutEvents(function () {
            $this->revisions()->update(['is_current' => false]);
            $this->setAttribute('is_current', true)->save();
        });
    }

    public function setLive()
    {
        $this->withoutEvents(function () {
            $this->revisions()->published()->update(['is_published' => false]);

            $this->is_published = true;
            $this->setCurrent();

            static::where('updated_at', '>', $this->updated_at)->delete();
        });
    }
}
