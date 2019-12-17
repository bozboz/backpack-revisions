<?php

namespace Bozboz\BackpackRevisions\Traits;

use App\User;
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
        $this->fillable[] = 'user_id';
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
            $model->user_id = backpack_user()->id;

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
            $revision->updated_at = $this->updated_at;

            $revision->save(['timestamps' => false]); // Preserve the existing updated_at

            $this->setAttribute('user_id', backpack_user()->id);

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

    public function user()
    {
        return User::where('id', $this->user_id)->first();
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
            // This has to be updated manually as with update() there's no way to prevent timestamp updates,
            // which breaks the history of the updated_at timestamp
            $oldCurrent = $this->revisions()->where('is_current', true)->first();
            if ($oldCurrent) {
                $oldCurrent->is_current = false;
                $oldCurrent->timestamps = false;
                $oldCurrent->save();
            }

            $this->setAttribute('is_current', true)->save();
        });
    }

    public function setLive()
    {
        $this->withoutEvents(function () {
            static::where('updated_at', '>', $this->updated_at)->delete();

            // This has to be updated manually as with update() there's no way to prevent timestamp updates,
            // which breaks the history of the updated_at timestamp
            $oldPublished = $this->revisions()->where('is_published', true)->first();
            if ($oldPublished) {
                $oldPublished->is_published = false;
                $oldPublished->timestamps = false;
                $oldPublished->save();
            }

            $this->is_published = true;
            $this->setCurrent();
        });
    }
}
