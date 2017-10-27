<?php

namespace PhotoContainer\PhotoContainer\Application\Resources\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class UserProfile extends EloquentModel
{
    protected $table = 'user_profiles';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
